<?php
// Include database configuration
require_once '../config/database_config.php';

// SQL to create user table
$sql = [
    "CREATE TABLE IF NOT EXISTS user (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) DEFAULT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'doctor', 'patient') NOT NULL DEFAULT 'patient',
        status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    "CREATE TABLE IF NOT EXISTS doctors (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        nric VARCHAR(20) DEFAULT NULL,
        age INT DEFAULT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        address TEXT,
        specialization VARCHAR(100) DEFAULT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        profile_photo VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

// Execute SQL queries
echo "Setting up database tables...<br>";
foreach ($sql as $query) {
    if ($conn->query($query) === TRUE) {
        echo "Query executed successfully: " . substr($query, 0, 50) . "...<br>";
    } else {
        echo "Error: " . $conn->error . "<br>";
    }
}

echo "Database setup completed. <a href='../admin_register.php'>Go to Admin Registration</a>";
?>
