<?php
include('../../database/db.php');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
header('Content-Type: application/json');

$response = ["success" => false, "message" => "Invalid action"];

switch ($action) {

    /* ===========================================================
       ADD TO QUEUE (Face Recognition) - WITH PERMANENT QUEUE NUMBER
    =========================================================== */
    case "add_queue":
        $driver_id = intval($_POST['driver_id'] ?? 0);

        if ($driver_id > 0) {
            $check = $conn->prepare("
                SELECT id FROM queue 
                WHERE driver_id = ? 
                AND status = 'Onqueue' 
                AND DATE(queued_at) = CURDATE()
            ");
            $check->bind_param("i", $driver_id);
            $check->execute();
            $exists = $check->get_result();

            if ($exists->num_rows > 0) {
                $response = ["success" => false, "message" => "Driver already in queue today"];
            } else {
                // Get next queue number for today
                $getMaxNum = $conn->query("
                    SELECT COALESCE(MAX(queue_number), 0) as max_num 
                    FROM queue 
                    WHERE DATE(queued_at) = CURDATE()
                ");
                $maxRow = $getMaxNum->fetch_assoc();
                $nextQueueNumber = $maxRow['max_num'] + 1;

                $stmt = $conn->prepare("
                    INSERT INTO queue (driver_id, queue_number, status, queued_at) 
                    VALUES (?, ?, 'Onqueue', NOW())
                ");
                $stmt->bind_param("ii", $driver_id, $nextQueueNumber);

                $response = $stmt->execute()
                    ? ["success" => true, "message" => "Driver added as Queue #$nextQueueNumber", "queue_number" => $nextQueueNumber]
                    : ["success" => false, "message" => "DB Error: ".$conn->error];

                $stmt->close();
            }
            $check->close();
        } else {
            $response = ["success" => false, "message" => "Invalid driver ID"];
        }
        echo json_encode($response);
        exit;

    /* ===========================================================
       DISPATCH DRIVER
    =========================================================== */
    case "dispatch":
        $driver_id = intval($_POST['driver_id'] ?? 0);

        if ($driver_id > 0) {
            $stmt = $conn->prepare("
                UPDATE queue
                SET status = 'Dispatched', dispatch_at = NOW()
                WHERE driver_id = ? AND status = 'Onqueue' AND DATE(queued_at) = CURDATE()
                ORDER BY queued_at ASC
                LIMIT 1
            ");
            $stmt->bind_param("i", $driver_id);
            $stmt->execute();

            $response = ($stmt->affected_rows > 0)
                ? ["success" => true, "message" => "Driver dispatched successfully"]
                : ["success" => false, "message" => "Driver not in queue or already dispatched"];

            $stmt->close();
        } else {
            $response = ["success" => false, "message" => "Invalid driver ID"];
        }
        echo json_encode($response);
        exit;

    /* ===========================================================
       ADD DRIVER MANUALLY (Admin) - WITH PERMANENT QUEUE NUMBER
    =========================================================== */
    case "add":
        $driver = trim($_POST['driver_name'] ?? '');
        $tricycle = trim($_POST['tricycle_no'] ?? '');
        $contact = trim($_POST['contact_no'] ?? '');

        if (!empty($driver) && !empty($tricycle)) {
            // Get next queue number for today
            $getMaxNum = $conn->query("
                SELECT COALESCE(MAX(queue_number), 0) as max_num 
                FROM queue 
                WHERE DATE(queued_at) = CURDATE()
            ");
            $maxRow = $getMaxNum->fetch_assoc();
            $nextQueueNumber = $maxRow['max_num'] + 1;

            $stmt = $conn->prepare("
                INSERT INTO queue (driver_name, tricycle_number, contact_no, queue_number, status, queued_at)
                VALUES (?, ?, ?, ?, 'Onqueue', NOW())
            ");
            $stmt->bind_param("sssi", $driver, $tricycle, $contact, $nextQueueNumber);

            $response = $stmt->execute()
                ? ["success" => true, "message" => "Driver added as Queue #$nextQueueNumber", "queue_number" => $nextQueueNumber]
                : ["success" => false, "message" => "Failed to manually add driver"];

            $stmt->close();
        } else {
            $response = ["success" => false, "message" => "Driver name and tricycle number required"];
        }
        echo json_encode($response);
        exit;

    /* ===========================================================
       SERVE DRIVER
    =========================================================== */
    case "serve":
        $id = intval($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE queue SET status='SERVING' WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $response = ($stmt->affected_rows > 0)
                ? ["success" => true, "message" => "Driver is now serving"]
                : ["success" => false, "message" => "Unable to update status"];

            $stmt->close();
        } else {
            $response = ["success" => false, "message" => "Invalid queue ID"];
        }
        echo json_encode($response);
        exit;

    /* ===========================================================
       REMOVE DRIVER FROM QUEUE
    =========================================================== */
    case "done":
        $id = intval($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM queue WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $response = ($stmt->affected_rows > 0)
                ? ["success" => true, "message" => "Driver removed from queue"]
                : ["success" => false, "message" => "Failed to remove driver"];

            $stmt->close();
        } else {
            $response = ["success" => false, "message" => "Invalid queue ID"];
        }
        echo json_encode($response);
        exit;

    /* ===========================================================
       FETCH QUEUE LIST - WITH PERMANENT QUEUE NUMBERS
    =========================================================== */
    case "fetch":
        $sql = "
            SELECT 
                q.*, 
                d.firstname, d.lastname, d.tricycle_number, d.contact_no, d.profile_pic,
                CONCAT(d.firstname, ' ', d.lastname) AS driver_name
            FROM queue q
            LEFT JOIN drivers d ON q.driver_id = d.id
            WHERE DATE(q.queued_at) = CURDATE()
            ORDER BY q.queue_number ASC
        ";

        $result = $conn->query($sql);
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $response = [
            "success" => true,
            "serving" => $rows[0] ?? null,
            "waiting" => array_slice($rows, 1),
            "data"    => $rows
        ];

        echo json_encode($response);
        exit;

    default:
        echo json_encode($response);
        exit;
}
?>
