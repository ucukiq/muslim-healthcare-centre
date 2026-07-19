<?php
// Start session and check admin access
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

// Include config file
require_once "../includes/config.php";

$message = '';
$success = false;

try {
    // Start transaction
    $conn->begin_transaction();
    
    // 1. First, update the ENUM type for the status column
    $sql = "ALTER TABLE appointments 
            MODIFY COLUMN status ENUM('Pending', 'Confirmed', 'Done', 'Call Back', 'Cancelled', 'No Show') 
            DEFAULT 'Pending'";
    $conn->query($sql);
    
    // 2. Update existing statuses to match the new format
    $statusMap = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'completed' => 'Done',
        'cancelled' => 'Cancelled'
    ];
    
    foreach ($statusMap as $old => $new) {
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE status = ?");
        $stmt->bind_param("ss", $new, $old);
        $stmt->execute();
    }
    
    // 3. Add a new column for status descriptions if it doesn't exist
    $conn->query("ALTER TABLE appointments 
                  ADD COLUMN IF NOT EXISTS status_description VARCHAR(255) DEFAULT NULL");
    
    // 4. Set status descriptions based on status
    $statusDescriptions = [
        'Pending' => 'Awaiting approval.',
        'Confirmed' => 'Appointment confirmed.',
        'Done' => 'Treatment completed.',
        'Call Back' => 'Please call back.',
        'Cancelled' => 'Appointment cancelled.',
        'No Show' => 'Patient did not attend.'
    ];
    
    foreach ($statusDescriptions as $status => $description) {
        $stmt = $conn->prepare("UPDATE appointments SET status_description = ? WHERE status = ?");
        $stmt->bind_param("ss", $description, $status);
        $stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    $message = "Appointment statuses updated successfully!";
    $success = true;
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $message = "Error updating appointment statuses: " . $e->getMessage();
}

// Set session messages
$_SESSION['message'] = $message;
$_SESSION['success'] = $success;

// Redirect back to dashboard
header("Location: dashboard.php");
exit();
?>
