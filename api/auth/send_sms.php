<?php
header('Content-Type: application/json');
include('../../database/db.php');

// ============================================
// PHILSMS CONFIGURATION
// ============================================
define('PHILSMS_API_KEY', '456|1mtvMnSeyJkCzlVpXxxgRb2hGg9uXpHUeRKSPIlod9ae86d7'); // Your PhilSMS API Token
define('PHILSMS_SENDER_NAME', 'PhilSMS'); // Default sender (Globe only) - Change to 'LITODA' after registration

/**
 * Format phone number for Philippines
 */
function formatPhoneNumber($phone) {
    $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

    if (substr($phone, 0, 1) == '0') return '63' . substr($phone, 1);
    if (substr($phone, 0, 3) == '+63') return substr($phone, 1);
    if (substr($phone, 0, 2) == '63') return $phone;
    if (strlen($phone) == 10 && substr($phone, 0, 1) == '9') return '63' . $phone;

    return $phone;
}

/**
 * Send SMS using PhilSMS API
 */
function sendPhilSMS($toNumber, $message) {
    $apiKey = PHILSMS_API_KEY;
    $sender = PHILSMS_SENDER_NAME;

    $url = "https://app.philsms.com/api/v3/sms/send";
    $data = [
        'recipient' => $toNumber,
        'sender_id' => $sender,
        'type' => 'plain',
        'message' => $message
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $responseData = json_decode($response, true);

    return [
        'success' => ($httpCode == 200 || $httpCode == 201),
        'http_code' => $httpCode,
        'response' => $responseData
    ];
}

/**
 * Log SMS to database - FIXED VERSION
 */
function logSMS($conn, $driverId, $phoneNumber, $message, $status, $response) {
    $responseJson = is_string($response) ? $response : json_encode($response);
    
    $sql = "INSERT INTO sms_logs (driver_id, phone_number, message, status, response, sent_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("issss", $driverId, $phoneNumber, $message, $status, $responseJson);
        $result = $stmt->execute();
        
        if (!$result) {
            error_log("SMS Log Error: " . $stmt->error);
        }
        
        $stmt->close();
        return $result;
    } else {
        error_log("SMS Log Prepare Error: " . $conn->error);
        return false;
    }
}

/**
 * Check if SMS was recently sent to prevent duplicates
 */
function wasRecentlySent($conn, $driverId, $minutes = 5) {
    $sql = "SELECT id FROM sms_logs 
            WHERE driver_id = ? 
            AND status = 'sent' 
            AND sent_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ORDER BY sent_at DESC 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $driverId, $minutes);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return $exists;
    }
    return false;
}

// ============================================
// MAIN SMS SENDING LOGIC
// ============================================
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $driverId = isset($_POST['driver_id']) ? intval($_POST['driver_id']) : 0;
    $driverName = isset($_POST['driver_name']) ? trim($_POST['driver_name']) : '';
    $tricycleNumber = isset($_POST['tricycle_number']) ? trim($_POST['tricycle_number']) : '';
    $contactNo = isset($_POST['contact_no']) ? trim($_POST['contact_no']) : '';

    if (empty($driverId) || empty($driverName) || empty($contactNo)) {
        throw new Exception('Missing required information');
    }

    if (wasRecentlySent($conn, $driverId, 5)) {
        echo json_encode([
            'success' => false,
            'message' => 'SMS already sent to this driver recently',
            'duplicate' => true
        ]);
        exit;
    }

    $formattedPhone = formatPhoneNumber($contactNo);

    if (strlen($formattedPhone) < 10) {
        throw new Exception('Invalid phone number format');
    }

    // Create message informing driver they are next
    $message = "Hello {$driverName}! You are the NEXT driver in the queue";
    if (!empty($tricycleNumber)) {
        $message .= " (Tricycle #{$tricycleNumber})";
    }
    $message .= ". Please prepare and proceed to the loading area now. Thank you! - LITODA Queue System";

    $result = sendPhilSMS($formattedPhone, $message);
    $status = $result['success'] ? 'sent' : 'failed';

    // Log to database
    $logResult = logSMS($conn, $driverId, $formattedPhone, $message, $status, $result['response']);

    if ($result['success']) {
        $response = [
            'success' => true,
            'message' => 'SMS sent successfully',
            'driver_name' => $driverName,
            'phone_number' => $formattedPhone,
            'sms_message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'logged' => $logResult
        ];
    } else {
        $response = [
            'success' => false,
            'message' => isset($result['response']['message']) ? $result['response']['message'] : 'Failed to send SMS',
            'http_code' => $result['http_code'],
            'error_details' => $result['response'],
            'logged' => $logResult
        ];
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => true
    ]);
}

$conn->close();
?>
