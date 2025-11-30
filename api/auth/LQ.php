<?php
include('../../database/db.php');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$response = ["success" => false, "message" => "Invalid action"];

switch ($action) {

    /* ===========================================================
       ADD TO QUEUE (FACE RECOGNITION)
    =========================================================== */
    case "add_queue":
        $driver_id = intval($_POST['driver_id'] ?? 0);

        if ($driver_id > 0) {

            // Check if driver already in queue today
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
                // Insert new queue entry
                $stmt = $conn->prepare("
                    INSERT INTO queue (driver_id, status, queued_at) 
                    VALUES (?, 'Onqueue', NOW())
                ");
                $stmt->bind_param("i", $driver_id);

                if ($stmt->execute()) {
                    $response = ["success" => true, "message" => "Driver added to queue"];
                } else {
                    $response = ["success" => false, "message" => "DB Error: ".$conn->error];
                }
                $stmt->close();
            }
            $check->close();

        } else {
            $response = ["success" => false, "message" => "Invalid or missing driver ID"];
        }

        echo json_encode($response);
        exit;




    /* ===========================================================
       DISPATCH DRIVER
       – Updates status to Dispatched
       – Adds dispatch_at timestamp
    =========================================================== */
    case "dispatch":
        $driver_id = intval($_POST['driver_id'] ?? 0);

        if ($driver_id > 0) {

            $stmt = $conn->prepare("
                UPDATE queue
                SET status = 'Dispatched',
                    dispatch_at = NOW()
                WHERE driver_id = ?
                AND status = 'Onqueue'
                AND DATE(queued_at) = CURDATE()
                ORDER BY queued_at ASC
                LIMIT 1
            ");
            $stmt->bind_param("i", $driver_id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $response = ["success" => true, "message" => "Driver dispatched successfully"];
            } else {
                $response = ["success" => false, "message" => "Driver not in queue or already dispatched"];
            }

            $stmt->close();

        } else {
            $response = ["success" => false, "message" => "Invalid driver ID"];
        }

        echo json_encode($response);
        exit;




    /* ===========================================================
       ADD DRIVER MANUALLY (ADMIN)
    =========================================================== */
    case "add":
        $driver = trim($_POST['driver_name'] ?? '');
        $tricycle = trim($_POST['tricycle_no'] ?? '');
        $contact = trim($_POST['contact_no'] ?? '');

        if (!empty($driver) && !empty($tricycle)) {

            $stmt = $conn->prepare("
                INSERT INTO queue (driver_name, tricycle_number, contact_no, status, queued_at) 
                VALUES (?, ?, ?, 'Onqueue', NOW())
            ");
            $stmt->bind_param("sss", $driver, $tricycle, $contact);

            if ($stmt->execute()) {
                $response = ["success" => true, "message" => "Driver added to queue"];
            } else {
                $response = ["success" => false, "message" => "Failed to manually add driver"];
            }

            $stmt->close();

        } else {
            $response = ["success" => false, "message" => "Driver name and tricycle number required"];
        }

        echo json_encode($response);
        exit;




    /* ===========================================================
       SERVE (First driver in queue becomes SERVING)
    =========================================================== */
    case "serve":
        $id = intval($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE queue SET status='SERVING' WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $response = ["success" => true, "message" => "Driver is now serving"];
            } else {
                $response = ["success" => false, "message" => "Unable to update status"];
            }

            $stmt->close();
        } else {
            $response = ["success" => false, "message" => "Invalid queue ID"];
        }

        echo json_encode($response);
        exit;




    /* ===========================================================
       MARK DONE (REMOVE FROM QUEUE)
    =========================================================== */
    case "done":
        $id = intval($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM queue WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $response = ["success" => true, "message" => "Driver removed from queue"];
            } else {
                $response = ["success" => false, "message" => "Failed to remove driver"];
            }

            $stmt->close();
        } else {
            $response = ["success" => false, "message" => "Invalid queue ID"];
        }

        echo json_encode($response);
        exit;




    /* ===========================================================
       FETCH QUEUE LIST
    =========================================================== */
    case "fetch":
        $sql = "
            SELECT 
                q.*, 
                d.firstname, 
                d.lastname, 
                d.tricycle_number, 
                d.contact_no,
                d.profile_pic,
                CONCAT(d.firstname, ' ', d.lastname) AS driver_name
            FROM queue q
            LEFT JOIN drivers d ON q.driver_id = d.id
            WHERE DATE(q.queued_at) = CURDATE()
            ORDER BY q.queued_at ASC
        ";

        $result = $conn->query($sql);

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        // First person is serving, rest are waiting
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
