<?php
    $servername = getenv('DB_HOST') ?: "mysql.railway.internal";
    $username   = getenv('DB_USER') ?: "root";
    $password   = getenv('DB_PASSWORD') ?: "eIKUyoNeEeMStYONTCowvZAzNHJbrFkv";
    $dbname     = getenv('DB_NAME') ?: "litoda_db";
    $port       = getenv('DB_PORT') ?: 3306;

    $conn = new mysqli($servername, $username, $password, $dbname, (int)$port);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
?>
