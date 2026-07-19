<?php
// Quick fix for doctor IDs
require_once '../includes/config.php';

// Update all doctors without proper doctor_id
$query = "UPDATE doctors SET doctor_id = CONCAT('DOC', LPAD(id, 6, '0')), status = 'Active' WHERE doctor_id IS NULL OR doctor_id = '' OR doctor_id = 'N/A'";

if($conn->query($query)) {
    echo "Successfully updated doctor IDs!";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
