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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['appointment_id'])) {
    $appointment_id = trim($_POST['appointment_id']);
    $notes = trim($_POST['notes'] ?? '');
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Update appointment status to 'No Show'
        $sql = "UPDATE appointments SET status = 'No Show', notes = CONCAT(IFNULL(notes, ''), ?), updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $additional_notes = "\n\nMarked as No Show by " . $_SESSION['username'] . " on " . date('Y-m-d H:i:s');
        if (!empty($notes)) {
            $additional_notes .= ": " . $notes;
        }
        $stmt->bind_param("si", $additional_notes, $appointment_id);
        $stmt->execute();
        
        // Log the action
        $log_sql = "INSERT INTO activity_log (user_id, action, details) VALUES (?, 'mark_no_show', ?)";
        $log_stmt = $conn->prepare($log_sql);
        $log_details = "Marked appointment #" . $appointment_id . " as No Show";
        $log_stmt->bind_param("is", $_SESSION['id'], $log_details);
        $log_stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        $message = "Appointment marked as 'No Show' successfully.";
        $success = true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $message = "Error updating appointment: " . $e->getMessage();
    }
    
    // Set session message
    $_SESSION['message'] = $message;
    $_SESSION['success'] = $success;
    
    // Redirect back to previous page or dashboard
    $redirect = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
    header("Location: " . $redirect);
    exit();
}

// If not a POST request, redirect to dashboard
header("Location: dashboard.php");
exit();
?>
