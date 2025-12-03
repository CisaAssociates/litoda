<?php
    // ✅ CRITICAL: Set PHP timezone to Philippine Time
    date_default_timezone_set('Asia/Manila');

    $servername = getenv('DB_HOST') ?: "mysql.railway.internal";
    $username   = getenv('DB_USER') ?: "root";
    $password   = getenv('DB_PASSWORD') ?: "eIKUyoNeEeMStYONTCowvZAzNHJbrFkv";
    $dbname     = getenv('DB_NAME') ?: "litoda_db";
    $port       = getenv('DB_PORT') ?: 3306;
    
    // Enable error reporting for mysqli
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    try {
        $conn = new mysqli($servername, $username, $password, $dbname, (int)$port);
        
        // Set character encoding
        $conn->set_charset("utf8mb4");
        
        // ✅ CRITICAL: Set MySQL timezone to Philippine Time (+08:00)
        $conn->query("SET time_zone = '+08:00'");
        
    } catch (mysqli_sql_exception $e) {
        // Error 1049 is "Unknown database"
        if ($e->getCode() == 1049) {
            // Connect without database selected
            $conn = new mysqli($servername, $username, $password, null, (int)$port);
            
            // Create the database
            $conn->query("CREATE DATABASE IF NOT EXISTS `$dbname`");
            
            // Select the database
            $conn->select_db($dbname);
            
            // Set character encoding
            $conn->set_charset("utf8mb4");
            
            // ✅ Set timezone after database creation
            $conn->query("SET time_zone = '+08:00'");
        } else {
            throw $e;
        }
    }
?>
