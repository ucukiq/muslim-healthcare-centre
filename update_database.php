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
    // Check if users table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows == 0) {
        throw new Exception("The 'users' table does not exist.");
    }
    
    // Modify the users table to make email nullable and remove unique constraint
    $sql = [
        "ALTER TABLE users MODIFY COLUMN email VARCHAR(100) NULL",
        "ALTER TABLE users DROP INDEX email"
    ];
    
    foreach ($sql as $query) {
        if (!$conn->query($query)) {
            throw new Exception("Error updating database: " . $conn->error);
        }
    }
    
    $message = "Database updated successfully! The email field is now optional.";
    $success = true;
    
} catch (Exception $e) {
    $message = $e->getMessage();
    $success = false;
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Update</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .alert { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card mt-5">
                    <div class="card-header">
                        <h4>Database Update</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="admin_register.php" class="btn btn-primary">
                                    <i class="fas fa-arrow-right me-2"></i>Go to Admin Registration
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $message; ?>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="setup_database.php" class="btn btn-warning">
                                    <i class="fas fa-database me-2"></i>Run Database Setup
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>
