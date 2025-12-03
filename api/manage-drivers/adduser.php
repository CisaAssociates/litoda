<?php
// ✅ Set Philippine timezone
date_default_timezone_set('Asia/Manila');

require_once '../../database/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = trim($_POST['firstname']);
    $middlename = trim($_POST['middlename']);
    $lastname = trim($_POST['lastname']);
    $platenumber = trim($_POST['platenumber']);
    $contact = trim($_POST['contact']);

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

    // ✅ CHECK FOR DUPLICATE FULL NAME (firstname + middlename + lastname)
    $duplicateNameCheck = $conn->prepare("
        SELECT id FROM drivers 
        WHERE LOWER(TRIM(firstname)) = LOWER(TRIM(?))
        AND LOWER(TRIM(middlename)) = LOWER(TRIM(?))
        AND LOWER(TRIM(lastname)) = LOWER(TRIM(?))
    ");
    if ($duplicateNameCheck === false) {
        header("Location: ../../pages/manage-drivers/managedrivers.php?error=database_error");
        exit();
    }
    $duplicateNameCheck->bind_param("sss", $firstname, $middlename, $lastname);
    $duplicateNameCheck->execute();
    $duplicateNameResult = $duplicateNameCheck->get_result();
    if ($duplicateNameResult && $duplicateNameResult->num_rows > 0) {
        $duplicateNameCheck->close();
        $conn->close();
        header("Location: ../../pages/manage-drivers/managedrivers.php?error=duplicate_fullname");
        exit();
    }
    $duplicateNameCheck->close();

    // ✅ CHECK FOR DUPLICATE CONTACT NUMBER (only if contact is provided)
    if (!empty($contact)) {
        $duplicateContactCheck = $conn->prepare("
            SELECT id FROM drivers WHERE contact_no = ?
        ");
        if ($duplicateContactCheck === false) {
            header("Location: ../../pages/manage-drivers/managedrivers.php?error=database_error");
            exit();
        }
        $duplicateContactCheck->bind_param("s", $contact);
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

    // Check duplicate by name + plate (original check)
    $duplicateCheck = $conn->prepare("
        SELECT id FROM drivers 
        WHERE LOWER(TRIM(firstname)) = LOWER(TRIM(?))
        AND LOWER(TRIM(lastname)) = LOWER(TRIM(?))
        AND LOWER(TRIM(tricycle_number)) = LOWER(TRIM(?))
    ");
    if ($duplicateCheck === false) {
        header("Location: ../../pages/manage-drivers/managedrivers.php?error=database_error");
        exit();
    }
    $duplicateCheck->bind_param("sss", $firstname, $lastname, $platenumber);
    $duplicateCheck->execute();
    $duplicateResult = $duplicateCheck->get_result();
    if ($duplicateResult && $duplicateResult->num_rows > 0) {
        $duplicateCheck->close();
        $conn->close();
        header("Location: ../../pages/manage-drivers/managedrivers.php?error=duplicate_driver");
        exit();
    }
    $duplicateCheck->close();

    // Handle profile image upload
    $profile_picture_path = null;
    
    if (!empty($_POST['profile_image'])) {
        $base64_image = $_POST['profile_image'];

        if (preg_match('/^data:image\/(\w+);base64,/', $base64_image, $type)) {
            $image_type = strtolower($type[1]);
            if (!in_array($image_type, ['jpg', 'jpeg', 'png', 'gif'])) {
                header("Location: ../../pages/manage-drivers/managedrivers.php?error=invalid_image_type");
                exit();
            }

            $base64_image = substr($base64_image, strpos($base64_image, ',') + 1);
            $decoded_image_data = base64_decode($base64_image);
            if ($decoded_image_data === false) {
                header("Location: ../../pages/manage-drivers/managedrivers.php?error=invalid_image_data");
                exit();
            }

            $folder_name = ucfirst(strtolower($firstname)) . '_' . ucfirst(strtolower($lastname));
            $folder_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $folder_name);

            $upload_dir = '../../uploads/' . $folder_name . '/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $filename = 'profile_' . uniqid() . '_' . time() . '.' . ($image_type === 'jpeg' ? 'jpg' : $image_type);
            $file_path = $upload_dir . $filename;

            if (file_put_contents($file_path, $decoded_image_data)) {
                $profile_picture_path = 'uploads/' . $folder_name . '/' . $filename;
            } else {
                header("Location: ../../pages/manage-drivers/managedrivers.php?error=file_upload_failed");
                exit();
            }
        } else {
            header("Location: ../../pages/manage-drivers/managedrivers.php?error=invalid_image_data");
            exit();
        }
    } else {
        header("Location: ../../pages/manage-drivers/managedrivers.php?error=no_image_provided");
        exit();
    }

    // Insert driver into database with optional contact
    $stmt = $conn->prepare("
        INSERT INTO drivers (firstname, middlename, lastname, tricycle_number, contact_no, profile_pic)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if ($stmt === false) {
        if ($profile_picture_path && file_exists('../../' . $profile_picture_path)) {
            unlink('../../' . $profile_picture_path);
        }
        header("Location: ../../pages/manage-drivers/managedrivers.php?error=database_error");
        exit();
    }

    $stmt->bind_param("ssssss", $firstname, $middlename, $lastname, $platenumber, $contact, $profile_picture_path);
    if (!$stmt->execute()) {
        if ($profile_picture_path && file_exists('../../' . $profile_picture_path)) {
            unlink('../../' . $profile_picture_path);
        }
        $stmt->close();
        $conn->close();
        header("Location: ../../pages/manage-drivers/managedrivers.php?error=database_insert_failed");
        exit();
    }
    
    $stmt->close();
    $conn->close();
    header("Location: ../../pages/manage-drivers/managedrivers.php?success=user_added");
    exit();
} else {
    header("Location: ../../pages/manage-drivers/managedrivers.php");
    exit();
}
?>
