<?php
// Initialize the session
session_start();
 
// Check if the user is logged in as patient, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'patient'){
    header("location: ../login.php");
    exit;
}

// Include config file
require_once "../includes/config.php";

// Check if appointment ID is provided
if(!isset($_GET['id']) || empty(trim($_GET['id']))){
    header("location: dashboard.php");
    exit;
}

$appointment_id = trim($_GET['id']);

// Process cancellation
$sql = "UPDATE appointments 
        SET status = 'cancelled' 
        WHERE id = ? 
        AND patient_id = (SELECT id FROM patients WHERE user_id = ?)
        AND status IN ('pending', 'confirmed')";

if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("ii", $appointment_id, $_SESSION['user_id']);
    
    if($stmt->execute()){
        if($stmt->affected_rows > 0){
            $_SESSION['success_msg'] = "Appointment has been cancelled successfully.";
        } else {
            $_SESSION['error_msg'] = "Unable to cancel appointment. It may have already been cancelled or doesn't exist.";
        }
    } else {
        $_SESSION['error_msg'] = "Error cancelling appointment. Please try again.";
    }
    
    $stmt->close();
}

// Redirect back to dashboard
header("location: dashboard.php");
exit;
?>
