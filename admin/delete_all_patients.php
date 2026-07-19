<?php
// Start session and check admin access
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Include database connection
require_once '../includes/config.php';

$message = '';
$success = false;

try {
    // Start transaction
    $conn->begin_transaction();

    // Disable foreign key checks
    $conn->query('SET FOREIGN_KEY_CHECKS = 0');

    // Delete all appointments first (due to foreign key constraint)
    $conn->query('DELETE FROM appointments');
    
    // Delete all patients
    $conn->query('DELETE FROM patients');
    
    // Re-enable foreign key checks
    $conn->query('SET FOREIGN_KEY_CHECKS = 1');
    
    // Commit transaction
    $conn->commit();
    
    $message = 'All patients and their appointments have been successfully deleted.';
    $success = true;
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $message = 'Error deleting patients: ' . $e->getMessage();
}

// Redirect back with status message
$_SESSION['message'] = $message;
$_SESSION['success'] = $success;
header('Location: manage_patients.php');
exit();
?>
