<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'config/database_config.php';

// Function to execute SQL queries
function executeQuery($conn, $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "<div class='alert alert-success'>✓ " . $sql . "</div>";
        return true;
    } else {
        echo "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
        return false;
    }
}

// Start HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Admin - Muslim Healthcare Centre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .alert { margin-bottom: 10px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="my-4">Admin Setup</h1>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Database Setup</h5>
                <?php
                // Create users table if not exists
                $sql = "CREATE TABLE IF NOT EXISTS user (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    email VARCHAR(100) NULL,
                    role VARCHAR(20) NOT NULL DEFAULT 'user',
                    status VARCHAR(20) NOT NULL DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                
                if (executeQuery($conn, $sql)) {
                    echo "<div class='alert alert-success'>✓ Users table created successfully</div>";
                    
                    // Add default admin user if not exists
                    $check_admin = $conn->query("SELECT id FROM user WHERE username = 'admin'");
                    
                    if ($check_admin->num_rows == 0) {
                        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
                        $sql = "INSERT INTO user (username, password, role, status) 
                                VALUES ('admin', '$hashed_password', 'admin', 'active')";
                        
                        if (executeQuery($conn, $sql)) {
                            echo "<div class='alert alert-success'>✓ Default admin user created</div>";
                            echo "<div class='alert alert-info'>Username: admin<br>Password: admin123</div>";
                            echo "<div class='alert alert-warning'>IMPORTANT: Change this password after first login!</div>";
                        }
                    } else {
                        echo "<div class='alert alert-info'>Admin user already exists</div>";
                    }
                }
                
                // Close connection
                $conn->close();
                ?>
                
                <hr>
                <h5>Next Steps:</h5>
                <ol>
                    <li>Try logging in with the admin credentials above</li>
                    <li>Delete this setup file for security</li>
                    <li>Change the default admin password</li>
                </ol>
                
                <div class="mt-4">
                    <a href="admin/login.php" class="btn btn-primary">Go to Login Page</a>
                    <a href="admin_register.php" class="btn btn-secondary">Register New Admin</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
