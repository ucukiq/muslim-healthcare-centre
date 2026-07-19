<?php
// Connect to MySQL server (without database)
$conn = new mysqli('localhost', 'root', '');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS muslim_healthcare_center CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
    
    // Select the database
    $conn->select_db('muslim_healthcare_center');
    
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
    } else {
        echo "Error creating user table: " . $conn->error . "<br>";
    }
    
    // Create doctors table
    $sql = "
    CREATE TABLE IF NOT EXISTS doctors (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if ($conn->query($sql) === TRUE) {
        echo "Doctors table created successfully<br>";
    } else {
        echo "Error creating doctors table: " . $conn->error . "<br>";
    }
    
    echo "<br>Setup completed successfully! <a href='../admin_register.php'>Go to Admin Registration</a>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

$conn->close();
?>
