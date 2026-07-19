<?php
// Create missing doctor_id column
require_once '../includes/config.php';

// Add doctor_id column if it doesn't exist
$alter_query = "ALTER TABLE doctors ADD COLUMN doctor_id VARCHAR(20) UNIQUE AFTER user_id";

if($conn->query($alter_query)) {
    echo "Successfully added doctor_id column!<br>";
    
    // Now update all doctors with proper IDs
    $update_query = "UPDATE doctors SET doctor_id = CONCAT('DOC', LPAD(id, 6, '0')) WHERE doctor_id IS NULL OR doctor_id = ''";
    
    if($conn->query($update_query)) {
        echo "Successfully updated doctor IDs!<br>";
    } else {
        echo "Error updating IDs: " . $conn->error . "<br>";
    }
    
    // Add status column if it doesn't exist
    $status_query = "ALTER TABLE doctors ADD COLUMN status ENUM('Active', 'Inactive') DEFAULT 'Active' AFTER specialization";
    
    if($conn->query($status_query)) {
        echo "Successfully added status column!<br>";
    } else {
        echo "Status column might already exist: " . $conn->error . "<br>";
    }
    
} else {
    echo "Error adding column: " . $conn->error . "<br>";
}

$conn->close();
?>
