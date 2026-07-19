<?php
// Include database configuration
require_once 'config/database_config.php';

// Create database if not exists
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists<br>";
    
    // Select the database
    $conn->select_db(DB_NAME);
    
    // Create user table
    $sql = "
    CREATE TABLE IF NOT EXISTS user (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) DEFAULT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'doctor', 'patient') NOT NULL DEFAULT 'patient',
        status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if ($conn->query($sql) === TRUE) {
        echo "User table created successfully<br>";
        
        // Create admin account
        $username = 'admin';
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $email = 'admin@mhc.com';
        $role = 'admin';
        $status = 'active';
        
        // Check if admin already exists
        $check = $conn->query("SELECT id FROM user WHERE username = 'admin'");
        
        if ($check->num_rows == 0) {
            // Insert admin user
            $stmt = $conn->prepare("INSERT INTO user (username, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $email, $password, $role, $status);
            
            if ($stmt->execute()) {
                echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 4px;'>
                        <h3>✅ Admin Account Created Successfully!</h3>
                        <p><strong>Username:</strong> admin</p>
                        <p><strong>Password:</strong> admin123</p>
                        <div style='background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-top: 10px;'>
                            <strong>Important:</strong> Please change this password immediately after logging in!
                        </div>
                        <p style='margin-top: 15px;'>
                            <a href='admin_login.php' class='btn btn-primary'>Go to Login Page</a>
                        </p>
                      </div>";
            } else {
                echo "Error creating admin account: " . $conn->error . "<br>";
            }
        } else {
            echo "Admin account already exists. <a href='admin_login.php'>Go to Login Page</a><br>";
        }
    } else {
        echo "Error creating user table: " . $conn->error . "<br>";
    }
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Setup Admin Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .success-box {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mb-4">Muslim Healthcare Center - Setup</h1>
                <?php if (!isset($username)): ?>
                    <div class="alert alert-info">
                        <h4>Setup Instructions:</h4>
                        <ol>
                            <li>This script will create the database and an admin account.</li>
                            <li>Click the button below to proceed.</li>
                            <li>After setup, you can login with the provided credentials.</li>
                            <li>For security, please change the default password after logging in.</li>
                        </ol>
                        <form method="post">
                            <button type="submit" class="btn btn-primary">Setup Database & Create Admin Account</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
