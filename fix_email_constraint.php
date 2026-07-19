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

try {
    // First, check if the email column exists and has a unique constraint
    $result = $conn->query("SHOW INDEX FROM users WHERE Column_name = 'email' AND Non_unique = 0");
    
    if ($result->num_rows > 0) {
        // Remove the unique constraint
        $conn->query("ALTER TABLE users DROP INDEX email");
        echo "<div class='alert alert-success'>✅ Successfully removed unique constraint from email column.</div>";
    } else {
        echo "<div class='alert alert-info'>ℹ️ No unique constraint found on email column.</div>";
    }
    
    // Make sure email can be NULL
    $conn->query("ALTER TABLE users MODIFY COLUMN email VARCHAR(100) NULL");
    echo "<div class='alert alert-success'>✅ Email column is now nullable.</div>";
    
    echo "<div class='alert alert-success mt-3'>✅ Database updated successfully! You can now <a href='admin_register.php'>register an admin</a>.</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Error: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fix Email Constraint</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .alert { margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card mt-5">
                    <div class="card-header">
                        <h4>Database Fix Tool</h4>
                    </div>
                    <div class="card-body">
                        <p>This tool will fix the email constraint issue in your database.</p>
                        <a href="admin_register.php" class="btn btn-primary">Try Registration Again</a>
                        <a href="setup_database.php" class="btn btn-secondary">Re-run Database Setup</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
