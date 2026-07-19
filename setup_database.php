<?php
// Database connection without selecting a database
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

try {
    // Create database if not exists
    $sql = "CREATE DATABASE IF NOT EXISTS muslim_healthcare_centre CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if ($conn->query($sql) === TRUE) {
        echo "Database created successfully or already exists.<br>";
        
        // Select the database
        $conn->select_db('muslim_healthcare_centre');
        
        // SQL to create users table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'doctor', 'patient') NOT NULL DEFAULT 'patient',
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        // Execute the query
        if ($conn->query($sql) === TRUE) {
            echo "Users table created successfully or already exists.<br>";
            
            // Check if any admin exists
            $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
            $row = $result->fetch_assoc();
            
            $admin_message = ($row['count'] == 0) 
                ? "No admin users found. You can register an admin below."
                : "Admin users exist in the database.";
                
            $show_register_button = ($row['count'] == 0);
            
        } else {
            throw new Exception("Error creating table: " . $conn->error);
        }
    } else {
        throw new Exception("Error creating database: " . $conn->error);
    }
} catch (Exception $e) {
    die($e->getMessage());
} finally {
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4>Database Setup</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <?php echo $admin_message; ?>
                        </div>
                        <?php if ($show_register_button): ?>
                            <a href="admin_register.php" class="btn btn-primary">
                                <i class="fas fa-user-shield me-2"></i>Register Admin
                            </a>
                        <?php else: ?>
                            <a href="admin_login.php" class="btn btn-success">
                                <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>
