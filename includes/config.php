<?php
// Prevent multiple session starts
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'healthcare_centre');
define('DB_USER', 'root');      // Default XAMPP username
define('DB_PASS', '');         // Default XAMPP password is empty

// Twilio / SMS gateway settings (leave empty if not using)
define('TWILIO_SID', '');
define('TWILIO_TOKEN', '');
define('TWILIO_FROM', ''); // E.164 number e.g. +1234567890

// MessageBird settings
define('MESSAGEBIRD_API_KEY', '');
define('MESSAGEBIRD_ORIGINATOR', '');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

// Define base URL
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/muslim_healthcare_centre/');

// Create database connection
// Disable mysqli exception reporting so we can handle missing DB gracefully
mysqli_report(MYSQLI_REPORT_OFF);

// Try to connect to the configured database, catching exceptions for missing DB
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1049) { // Unknown database
        $tmp = new mysqli(DB_HOST, DB_USER, DB_PASS);
        if ($tmp->connect_error) {
            die("Connection failed: " . $tmp->connect_error);
        }

        $sqlFile = realpath(__DIR__ . '/../database.sql');
        if ($sqlFile && is_readable($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            if ($sql !== false) {
                if ($tmp->multi_query($sql)) {
                    // flush multi_query results
                    do {
                        if ($res = $tmp->store_result()) {
                            $res->free();
                        }
                    } while ($tmp->more_results() && $tmp->next_result());
                }
            }
        }
        $tmp->close();

        // Try reconnecting to the newly created database
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        } catch (mysqli_sql_exception $e2) {
            die("Connection failed: " . $e2->getMessage());
        }
    } else {
        die("Connection failed: " . $e->getMessage());
    }
}

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");
?>