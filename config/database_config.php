<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');      // Default XAMPP username
define('DB_PASSWORD', '');          // Default XAMPP password (empty)
define('DB_NAME', 'healthcare_centre'); // Database name (padankan dengan database.sql)

// Attempt to connect to MySQL database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");
?>
