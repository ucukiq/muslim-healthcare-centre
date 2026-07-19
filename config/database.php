<?php
// Database configuration
$db_host = 'localhost';     // Your database host (usually 'localhost' for XAMPP)
$db_name = 'muslim_healthcare_centre'; // Your database name
$db_user = 'root';          // Your database username
$db_pass = '';              // Your database password (default is empty for XAMPP)

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for proper character encoding
$conn->set_charset("utf8mb4");

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
