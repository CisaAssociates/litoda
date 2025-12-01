<?php
// database/migrate.php

require_once 'db.php';

// Check for a secret key or only allow CLI execution to prevent unauthorized access
// if (php_sapi_name() !== 'cli' && $_GET['key'] !== 'YOUR_SECRET_KEY') {
//     die('Access Denied');
// }

$sqlFile = __DIR__ . '/dispatch_system.sql';

if (!file_exists($sqlFile)) {
    die("Error: SQL file not found at $sqlFile");
}

$sql = file_get_contents($sqlFile);

if (empty($sql)) {
    die("Error: SQL file is empty");
}

echo "Starting migration...<br>";
echo "Reading from: " . basename($sqlFile) . "<br>";

// Using multi_query to handle the entire dump file
if ($conn->multi_query($sql)) {
    do {
        /* store first result set */
        if ($result = $conn->store_result()) {
            $result->free();
        }
        // Check for errors
        if ($conn->errno) {
            echo "Error: " . $conn->error . "<br>";
            break;
        }
    } while ($conn->more_results() && $conn->next_result());
    
    if ($conn->errno) {
        echo "Migration failed with error: " . $conn->error . "<br>";
    } else {
        echo "Migration completed successfully.<br>";
    }
} else {
    echo "Migration failed to start: " . $conn->error . "<br>";
}

$conn->close();
?>
