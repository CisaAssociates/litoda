<?php
require_once '../../database/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $driver_id = intval($_POST['driver_id']);
    $firstname = trim($_POST['firstname']);
    $middlename = trim($_POST['middlename']);
    $lastname = trim($_POST['lastname']);
    $platenumber = trim($_POST['platenumber']);
    $contact = trim($_POST['contact']);
    $existing_image = $_POST['existing_image'];

    // Validate required fields (contact is now optional)
    if (empty($firstname) || empty($lastname) || empty($platenumber)) {
        header("Location: ../../pages/manage-drivers/managedrivers.php?error=missing_fields");
        exit();
    }

    // Validate contact number only if provided (must be exactly 11 digits)
    if (!empty($contact) && !preg_match('/^[0-9]{11}$/', $contact)) {
        header("Location: ../../pages/manage-drivers/managedrivers.php?error=invalid_contact");
        exit();
    }

    // ✅ CHECK FOR DUPLICATE FULL NAME (exclude current driver)
    $duplicateNameCheck = $conn->prepare("
        SELECT id FROM drivers 
        WHERE LOWER(TRIM(firstname)) = LOWER(TRIM(?))
        AND LOWER(TRIM(middlename)) = LOWER(TRIM(?))
        AND LOWER(TRIM(lastname)) = LOWER(TRIM(?))
        AND id != ?
    ");
    if ($duplicateNameCheck === false) {
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

    // ✅ CHECK FOR DUPLICATE CONTACT NUMBER (exclude current driver, only if contact is provided)
    if (!empty($contact)) {
        $duplicateContactCheck = $conn->prepare("
            SELECT id FROM drivers WHERE contact_no = ? AND id != ?
        ");
        if ($duplicateContactCheck === false) {
            header("Location: ../../pages/manage-drivers/managedrivers.php?error=database_error");
            exit();
        }
        $duplicateContactCheck->bind_param("si", $contact, $driver_id);
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

    // Handle image update
    $profile_picture_path = $existing_image;
    $base64_image_data = null;

    if (!empty($_POST['profile_image'])) {
        $base64_image = $_POST['profile_image'];

        if (preg_match('/^data:image\/(\w+);base64,/', $base64_image, $type)) {
            $image_type = strtolower($type[1]);
            if (!in_array($image_type, ['jpg', 'jpeg', 'png'])) {
                header("Location: ../../pages/manage-drivers/managedrivers.php?error=invalid_image_type");
                exit();
            }

            $base64_image = substr($base64_image, strpos($base64_image, ',') + 1);
            $base64_image_data = base64_decode($base64_image);

            if ($base64_image_data === false) {
                header("Location: ../../pages/manage-drivers/managedrivers.php?error=invalid_image_data");
                exit();
            }

            // 🔹 STEP 1: Validate that exactly ONE face is present (no hands, multiple faces, etc.)
            // Internal communication via localhost since they run in the same container
            $flask_api_url = 'http://127.0.0.1:5000'; 
            $flask_validate_url = $flask_api_url . "/validate_single_face";
            $validate_payload = json_encode([
                "image" => "data:image/jpeg;base64," . base64_encode($base64_image_data)
            ]);

            $ch_validate = curl_init($flask_validate_url);
            curl_setopt($ch_validate, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_validate, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            curl_setopt($ch_validate, CURLOPT_POSTFIELDS, $validate_payload);
            curl_setopt($ch_validate, CURLOPT_TIMEOUT, 10);
            $validate_response = curl_exec($ch_validate);
            $validate_http_code = curl_getinfo($ch_validate, CURLINFO_HTTP_CODE);
            curl_close($ch_validate);

            // Check if validation request failed
            if ($validate_response === false || $validate_http_code != 200) {
                header("Location: ../../pages/manage-drivers/managedrivers.php?error=face_validation_failed");
                exit();
            }

            $validateResult = json_decode($validate_response, true);

            // ❌ REJECT: If face validation fails (no face, multiple faces, hands, etc.)
            if (!isset($validateResult["valid"]) || $validateResult["valid"] !== true) {
                $error_message = isset($validateResult["message"]) ? urlencode($validateResult["message"]) : "invalid_face";
                header("Location: ../../pages/manage-drivers/managedrivers.php?error=face_validation&message=" . $error_message);
                exit();
            }

            // ✅ Face is valid (exactly one face detected)
            // 🔹 STEP 2: Now check if this face matches the existing driver's face
            if (!empty($existing_image) && file_exists('../../' . $existing_image)) {
                $existing_image_full_path = realpath('../../' . $existing_image);

                $flask_url = "http://127.0.0.1:5000/check_face_match";
                $payload = json_encode([
                    "existing_image_path" => $existing_image_full_path,
                    "new_image" => "data:image/jpeg;base64," . base64_encode($base64_image_data)
                ]);

                $ch = curl_init($flask_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                // Check if face match request failed
                if ($response === false || $http_code != 200) {
                    header("Location: ../../pages/manage-drivers/managedrivers.php?error=face_match_check_failed");
                    exit();
                }

                $flaskResult = json_decode($response, true);

                // ❌ REJECT: If face does NOT match the existing driver
                if (isset($flaskResult["same_face"]) && $flaskResult["same_face"] === false) {
                    header("Location: ../../pages/manage-drivers/managedrivers.php?error=face_mismatch");
                    exit();
                }
            }

            // ✅ ALL VALIDATIONS PASSED:
            // 1. Exactly one face detected
            // 2. Face matches the existing driver
            // Now proceed to replace the old image

            // 🔹 STEP 3: Replace the old image
            if (!empty($existing_image) && file_exists('../../' . $existing_image)) {
                unlink('../../' . $existing_image);
            }

            $folder_name = ucfirst(strtolower($firstname)) . '_' . ucfirst(strtolower($lastname));
            $folder_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $folder_name);
            $upload_dir = '../../uploads/' . $folder_name . '/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $filename = 'profile_' . uniqid() . '_' . time() . '.' . ($image_type === 'jpeg' ? 'jpg' : $image_type);
            $file_path = $upload_dir . $filename;

            if (file_put_contents($file_path, $base64_image_data)) {
                $profile_picture_path = 'uploads/' . $folder_name . '/' . $filename;
            } else {
                header("Location: ../../pages/manage-drivers/managedrivers.php?error=file_upload_failed");
                exit();
            }
        }
    }

    // 🔹 STEP 4: Update driver info in database
    $stmt = $conn->prepare("
        UPDATE drivers 
        SET firstname=?, middlename=?, lastname=?, tricycle_number=?, contact_no=?, profile_pic=? 
        WHERE id=?
    ");
    $stmt->bind_param("ssssssi", $firstname, $middlename, $lastname, $platenumber, $contact, $profile_picture_path, $driver_id);

    if ($stmt->execute()) {
        header("Location: ../../pages/manage-drivers/managedrivers.php?success=user_updated");
    } else {
        header("Location: ../../pages/manage-drivers/managedrivers.php?error=update_failed");
    }

    $stmt->close();
    $conn->close();
    exit();
} else {
    header("Location: ../../pages/manage-drivers/managedrivers.php");
    exit();
}
?>