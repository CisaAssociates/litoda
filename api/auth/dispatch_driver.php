<?php
// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Include database connection
include('../../database/db.php');

// Check database connection
if (!$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get POST data
$queue_id = isset($_POST['queue_id']) ? intval($_POST['queue_id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Validate input
if ($queue_id <= 0 || $action !== 'dispatch') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request parameters'
    ]);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if the queue entry exists and is in 'Onqueue' status
    $checkSql = "SELECT q.id, q.driver_id, q.status, d.firstname, d.lastname, d.tricycle_number
                 FROM queue q
                 LEFT JOIN drivers d ON q.driver_id = d.id
                 WHERE q.id = ? AND q.status = 'Onqueue'";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $queue_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Queue entry not found or already dispatched'
        ]);
        exit;
    }

    $queueData = $result->fetch_assoc();
    $driver_id = $queueData['driver_id'];
    $driver_name = trim($queueData['firstname'] . ' ' . $queueData['lastname']);
    $tricycle_number = $queueData['tricycle_number'];

    // Update queue status and set dispatch_at timestamp
    $updateQueueSql = "UPDATE queue SET status = 'Dispatched', dispatch_at = NOW() WHERE id = ?";
    $updateQueueStmt = $conn->prepare($updateQueueSql);
    $updateQueueStmt->bind_param("i", $queue_id);

    if (!$updateQueueStmt->execute()) {
        throw new Exception('Failed to update queue status with dispatch time');
    }

    // Optional: Insert into history table for record keeping
    $historySql = "INSERT INTO history (driver_id, driver_name, tricycle_number, dispatch_time, queue_id)
                   VALUES (?, ?, ?, NOW(), ?)";
    $historyStmt = $conn->prepare($historySql);
    $historyStmt->bind_param("issi", $driver_id, $driver_name, $tricycle_number, $queue_id);

    if (!$historyStmt->execute()) {
        throw new Exception('Failed to insert dispatch history');
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Driver dispatched successfully',
        'queue_id' => $queue_id,
        'driver_id' => $driver_id
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error dispatching driver: ' . $e->getMessage()
    ]);
}

// Close connection
$conn->close();
?>
