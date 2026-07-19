<?php
// Start session and check admin access
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

// Include config file
require_once "../includes/config.php";

// Set headers for file download
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="appointments_backup_'.date('Y-m-d_His').'.sql"');

// Start output buffering
ob_start();

try {
    // Get all appointments
    $result = $conn->query("SELECT * FROM appointments");
    
    echo "-- MySQL Backup of appointments table\n";
    echo "-- Backup Date: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Create table if not exists statement
    echo "-- Create table if not exists\n";
    echo "CREATE TABLE IF NOT EXISTS `appointments_backup_" . date('Ymd_His') . "` (\n";
    echo "  `id` int(11) NOT NULL AUTO_INCREMENT,\n  `patient_id` int(11) NOT NULL,\n  `doctor_id` int(11) NOT NULL,\n  `appointment_date` date NOT NULL,\n  `start_time` time NOT NULL,\n  `end_time` time NOT NULL,\n  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',\n  `notes` text DEFAULT NULL,\n  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),\n  PRIMARY KEY (`id`),\n  KEY `patient_id` (`patient_id`),\n  KEY `doctor_id` (`doctor_id`),\n  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,\n  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";

    // Insert statements
    if ($result->num_rows > 0) {
        echo "-- Insert data\n";
        while($row = $result->fetch_assoc()) {
            $values = array_map([$conn, 'real_escape_string'], $row);
            $values = array_map(function($v) { 
                return "'" . $v . "'"; 
            }, $values);
            
            echo "INSERT INTO `appointments_backup_" . date('Ymd_His') . "` (`" . 
                 implode("`, `", array_keys($row)) . 
                 "`) VALUES (" . implode(", ", $values) . ");\n";
        }
    }
    
    // Add restore instructions
    echo "\n-- To restore, you can use the following command in phpMyAdmin or MySQL CLI:\n";
    echo "-- 1. Create a new database or use an existing one\n";
    echo "-- 2. Run the SQL statements above\n";
    
    // Get the output buffer and clean it
    $output = ob_get_clean();
    
    // Output the file
    echo $output;
    
    // Log the backup action
    $logMessage = "Appointments backup created by admin user: " . $_SESSION['username'] . "\n";
    file_put_contents('../logs/backup.log', date('Y-m-d H:i:s') . ' - ' . $logMessage, FILE_APPEND);
    
    exit();
    
} catch (Exception $e) {
    // Clear the output buffer
    ob_clean();
    
    // Set error message
    $_SESSION['error'] = "Backup failed: " . $e->getMessage();
    header("Location: dashboard.php");
    exit();
}
?>
