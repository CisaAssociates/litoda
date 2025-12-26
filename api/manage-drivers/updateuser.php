<?php
// ✅ Set Philippine timezone
date_default_timezone_set('Asia/Manila');

require_once '../../database/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $driver_id = intval($_POST['driver_id']);
    $firstname = trim($_POST['firstname']);
    $middlename = trim($_POST['middlename']);
    $lastname = trim($_POST['lastname']);
    $platenumber = trim($_POST['platenumber']);
    $contact = trim($_POST['contact']);
    $existing_image = $_POST['existing_image'];

    // ✅ Get Flask API URL from environment (Railway) or use localhost
    $flask_api_url = getenv('FLASK_API_URL') ?: 'http://127.0.0.1:5000';
    $is_railway = getenv('RAILWAY_ENVIRONMENT') ? true : false;

    // ====================================================================
    // STEP 1: VALIDATE REQUIRED FIELDS
    // ====================================================================
    if (empty($firstname) || empty($lastname) || empty($platenumber)) {
        header("Location: ../../pages/manage-drivers/managedrivers.php?error=missing_fields");
        exit();
    }

    // ====================================================================
    // STEP 2: VALIDATE CONTACT NUMBER (OPTIONAL - BUT IF PROVIDED, MUST BE 11 DIGITS)
    // ====================================================================
    if (!empty($contact) && !preg_match('/^[0-9]{11}$/', $contact)) {
        header("Location: ../../pages/manage-drivers/managedrivers.php?error=invalid_contact");
        exit();
    }

    // ✅ FIX: Store original contact value for duplicate check, but prepare NULL-safe version for bind_param
    $contact_original = $contact;
    $contact = !empty($contact) ? $contact : null;

    // ====================================================================
    // STEP 3: CHECK FOR DUPLICATE FULL NAME (exclude current driver)
    // ====================================================================
    $duplicateNameCheck = $conn->prepare("
        SELECT id FROM drivers 
        WHERE LOWER(TRIM(firstname)) = LOWER(TRIM(?))
        AND LOWER(TRIM(middlename)) = LOWER(TRIM(?))
        AND LOWER(TRIM(lastname)) = LOWER(TRIM(?))
        AND id != ?
    ");
    if ($duplicateNameCheck === false) {
        error_log("Database prepare failed (duplicate name check): " . $conn->error);
        header("Location: ../../pages/manage-drivers/managedrivers.php?error=database_error");
        exit();
    }
    $duplicateNameCheck->bind_param("sssi", $firstname, $middlename, $lastname, $driver_id);
    $duplicateNameCheck->execute();
    $duplicateNameResult = $duplicateNameCheck->get_result();
    if ($duplicateNameResult && $duplicateNameResult->num_rows > 0) {
        $duplicateNameCheck->close();
        $conn->close();
        header("Location: ../../pages/manage-drivers/managedrivers.php?error=duplicate_fullname");
        exit();
    }
    $duplicateNameCheck->close();

    // ====================================================================
    // STEP 4: CHECK FOR DUPLICATE CONTACT NUMBER (exclude current driver, only if contact is provided)
    // ====================================================================
    if (!empty($contact_original)) {
        $duplicateContactCheck = $conn->prepare("
            SELECT id FROM drivers WHERE contact_no = ? AND id != ?
        ");
        if ($duplicateContactCheck === false) {
            error_log("Database prepare failed (duplicate contact check): " . $conn->error);
            header("Location: ../../pages/manage-drivers/managedrivers.php?error=database_error");
            exit();
        }
        $duplicateContactCheck->bind_param("si", $contact_original, $driver_id);
        $duplicateContactCheck->execute();
        $duplicateContactResult = $duplicateContactCheck->get_result();
        if ($duplicateContactResult && $duplicateContactResult->num_rows > 0) {
            $duplicateContactCheck->close();
            $conn->close();
            header("Location: ../../pages/manage-drivers/managedrivers.php?error=duplicate_contact");
            exit();
        }
        $duplicateContactCheck->close();
    }

    // ====================================================================
    // STEP 5: IMAGE VALIDATION + UPDATE
    // ====================================================================
    $profile_picture_path = $existing_image;

    if (!empty($_POST['profile_image'])) {
        $base64_image = $_POST['profile_image'];

        // Extract image type and validate
        if (preg_match('/^data:image\/(\w+);base64,/', $base64_image, $type)) {
            $image_type = strtolower($type[1]);
            if (!in_array($image_type, ['jpg', 'jpeg', 'png'])) {
                error_log("Invalid image type: " . $image_type);
                header("Location: ../../pages/manage-drivers/managedrivers.php?error=invalid_image_type");
                exit();
            }

            // Decode base64 image
            $base64_image = substr($base64_image, strpos($base64_image, ',') + 1);
            $base64_image_data = base64_decode($base64_image);

            if ($base64_image_data === false) {
                error_log("Base64 decode failed");
                header("Location: ../../pages/manage-drivers/managedrivers.php?error=invalid_image_data");
                exit();
            }

            // ---------------------------------------------------------------
            // 5A: VALIDATE SINGLE FACE
            // ---------------------------------------------------------------
            $flask_validate_url = $flask_api_url . "/validate_single_face";
            $validate_payload = json_encode([
                "image" => "data:image/jpeg;base64," . base64_encode($base64_image_data)
            ]);

            $ch_validate = curl_init($flask_validate_url);
            curl_setopt($ch_validate, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_validate, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            curl_setopt($ch_validate, CURLOPT_POSTFIELDS, $validate_payload);
            curl_setopt($ch_validate, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch_validate, CURLOPT_CONNECTTIMEOUT, 10);
            $validate_response = curl_exec($ch_validate);
            $validate_http_code = curl_getinfo($ch_validate, CURLINFO_HTTP_CODE);
            $validate_error = curl_error($ch_validate);
            curl_close($ch_validate);

            if ($validate_response === false || $validate_http_code != 200) {
                error_log("Flask validation failed: " . $validate_error . " (HTTP: " . $validate_http_code . ")");
                header("Location: ../../pages/manage-drivers/managedrivers.php?error=face_validation_failed");
                exit();
            }

            $validateResult = json_decode($validate_response, true);

            if (!isset($validateResult["valid"]) || $validateResult["valid"] !== true) {
                $error_message = isset($validateResult["message"]) ? $validateResult["message"] : "invalid_face";
                error_log("Face validation failed: " . $error_message);
                header("Location: ../../pages/manage-drivers/managedrivers.php?error=face_validation&message=" . urlencode($error_message));
                exit();
            }

            // ---------------------------------------------------------------
            // 5B: CHECK IF FACE MATCHES EXISTING DRIVER (MUST BE SAME PERSON)
            // ---------------------------------------------------------------
            if (!empty($existing_image) && file_exists('../../' . $existing_image)) {
                $existing_image_full_path = realpath('../../' . $existing_image);

                // Prepare payload based on environment
                if ($is_railway) {
                    // ✅ Railway: Send both images as base64
                    $existing_image_base64 = base64_encode(file_get_contents($existing_image_full_path));
                    $existing_image_mime = mime_content_type($existing_image_full_path);
                    
                    $payload = json_encode([
                        "existing_image" => "data:" . $existing_image_mime . ";base64," . $existing_image_base64,
                        "new_image" => "data:image/jpeg;base64," . base64_encode($base64_image_data)
                    ]);
                } else {
                    // ✅ Localhost: Send file path for existing image
                    $payload = json_encode([
                        "existing_image_path" => $existing_image_full_path,
                        "new_image" => "data:image/jpeg;base64," . base64_encode($base64_image_data)
                    ]);
                }

                // Check face match with existing profile
                $flask_match_url = $flask_api_url . "/check_face_match";

                $ch_match = curl_init($flask_match_url);
                curl_setopt($ch_match, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch_match, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
                curl_setopt($ch_match, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch_match, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch_match, CURLOPT_CONNECTTIMEOUT, 10);
                $match_response = curl_exec($ch_match);
                $match_http_code = curl_getinfo($ch_match, CURLINFO_HTTP_CODE);
                $match_error = curl_error($ch_match);
                curl_close($ch_match);

                if ($match_response === false || $match_http_code != 200) {
                    error_log("Flask face match failed: " . $match_error . " (HTTP: " . $match_http_code . ")");
                    header("Location: ../../pages/manage-drivers/managedrivers.php?error=face_match_check_failed");
                    exit();
                }

                $matchResult = json_decode($match_response, true);

                // ✅ CRITICAL: ONLY allow update if it's the SAME person
                if (!isset($matchResult["same_face"]) || $matchResult["same_face"] !== true) {
                    // This is NOT the same person - REJECT the update
                    $similarity = isset($matchResult["similarity"]) ? round($matchResult["similarity"] * 100, 1) : 0;
                    error_log("Face mismatch detected. Similarity: " . $similarity . "%");
                    header("Location: ../../pages/manage-drivers/managedrivers.php?error=face_mismatch");
                    exit();
                }
                
                // ✅ If we reach here, it's the same face - allow the update to proceed
                $similarity = isset($matchResult["similarity"]) ? round($matchResult["similarity"] * 100, 1) : 0;
                error_log("Face match verified. Similarity: " . $similarity . "%");
            }

            // ---------------------------------------------------------------
            // 5C: ALL VALIDATIONS PASSED - REPLACE THE IMAGE
            // ---------------------------------------------------------------
            // Delete old image
            if (!empty($existing_image) && file_exists('../../' . $existing_image)) {
                if (!unlink('../../' . $existing_image)) {
                    error_log("Failed to delete old image: " . $existing_image);
                }
            }

            // Create upload directory
            $folder_name = ucfirst(strtolower($firstname)) . '_' . ucfirst(strtolower($lastname));
            $folder_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $folder_name);
            $upload_dir = '../../uploads/' . $folder_name . '/';
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    error_log("Failed to create directory: " . $upload_dir);
                    header("Location: ../../pages/manage-drivers/managedrivers.php?error=file_upload_failed");
                    exit();
                }
            }

            // Save new image
            $filename = 'profile_' . uniqid() . '_' . time() . '.' . ($image_type === 'jpeg' ? 'jpg' : $image_type);
            $file_path = $upload_dir . $filename;

            if (file_put_contents($file_path, $base64_image_data)) {
                $profile_picture_path = 'uploads/' . $folder_name . '/' . $filename;
                error_log("Image saved successfully: " . $profile_picture_path);
            } else {
                error_log("Failed to save image to: " . $file_path);
                header("Location: ../../pages/manage-drivers/managedrivers.php?error=file_upload_failed");
                exit();
            }
        } else {
            error_log("Invalid image format - missing data:image header");
            header("Location: ../../pages/manage-drivers/managedrivers.php?error=invalid_image_data");
            exit();
        }
    }

    // ====================================================================
    // STEP 6: UPDATE DRIVER IN DATABASE
    // ====================================================================
    
    // ✅ FIX: Use separate queries based on whether contact is NULL or not
    if ($contact !== null) {
        $stmt = $conn->prepare("
            UPDATE drivers 
            SET firstname=?, middlename=?, lastname=?, tricycle_number=?, contact_no=?, profile_pic=? 
            WHERE id=?
        ");
        
        if ($stmt === false) {
            error_log("Database prepare failed: " . $conn->error);
            header("Location: ../../pages/manage-drivers/managedrivers.php?error=database_error");
            exit();
        }

        $stmt->bind_param("ssssssi", $firstname, $middlename, $lastname, $platenumber, $contact, $profile_picture_path, $driver_id);
    } else {
        // ✅ When contact is NULL, use a different query
        $stmt = $conn->prepare("
            UPDATE drivers 
            SET firstname=?, middlename=?, lastname=?, tricycle_number=?, contact_no=NULL, profile_pic=? 
            WHERE id=?
        ");
        
        if ($stmt === false) {
            error_log("Database prepare failed: " . $conn->error);
            header("Location: ../../pages/manage-drivers/managedrivers.php?error=database_error");
            exit();
        }

        $stmt->bind_param("sssssi", $firstname, $middlename, $lastname, $platenumber, $profile_picture_path, $driver_id);
    }

    if ($stmt->execute()) {
        error_log("✅ Driver updated successfully: ID=" . $driver_id);
        $stmt->close();
        $conn->close();
        header("Location: ../../pages/manage-drivers/managedrivers.php?success=user_updated");
    } else {
        error_log("❌ Database update failed: " . $stmt->error);
        error_log("❌ MySQL Error: " . $conn->error);
        error_log("❌ Driver ID: " . $driver_id);
        error_log("❌ Values: firstname=$firstname, middlename=$middlename, lastname=$lastname, plate=$platenumber, contact=$contact, pic=$profile_picture_path");
        $stmt->close();
        $conn->close();
        header("Location: ../../pages/manage-drivers/managedrivers.php?error=update_failed");
    }

    exit();
} else {
    header("Location: ../../pages/manage-drivers/managedrivers.php");
    exit();
}
?>
