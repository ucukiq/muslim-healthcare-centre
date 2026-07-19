<?php
// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'muslim_healthcare_centre';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get table structure
$result = $conn->query("SHOW CREATE TABLE users");
$row = $result->fetch_assoc();
$create_table = $row['Create Table'];

// Show current table structure
echo "<h3>Current Users Table Structure:</h3>";
echo "<pre>" . htmlspecialchars($create_table) . "</pre>";

// Check if email column exists and its properties
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'email'");
if ($result->num_rows > 0) {
    $email_col = $result->fetch_assoc();
    echo "<h3>Email Column Properties:</h3>";
    echo "<pre>";
    print_r($email_col);
    echo "</pre>";
} else {
    echo "<p>Email column does not exist in the users table.</p>";
}

$conn->close();
?>

<h3>Actions:</h3>
<a href="fix_email_column.php" class="btn btn-primary">Fix Email Column</a>

<style>
    body { padding: 20px; font-family: Arial, sans-serif; }
    pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }
    .btn { margin: 5px; }
</style>
