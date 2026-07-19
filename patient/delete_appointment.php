<?php
// Start session
session_start();

// Check if user is logged in as patient
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

// First verify the appointment belongs to the current patient
$verify_sql = "SELECT id FROM appointments 
               WHERE id = ? 
               AND patient_id = (SELECT id FROM patients WHERE user_id = ?)
               LIMIT 1";

if($stmt = $conn->prepare($verify_sql)){
    $stmt->bind_param("ii", $appointment_id, $_SESSION['user_id']);
    
    if($stmt->execute()){
        $result = $stmt->get_result();
        
        if($result->num_rows == 1){
            // Appointment belongs to user, proceed with deletion
            $delete_sql = "DELETE FROM appointments WHERE id = ?";
            
            if($delete_stmt = $conn->prepare($delete_sql)){
                $delete_stmt->bind_param("i", $appointment_id);
                
                if($delete_stmt->execute()){
                    if($delete_stmt->affected_rows > 0){
                        $_SESSION['success_msg'] = "Appointment #{$appointment_id} has been permanently deleted.";
                    } else {
                        $_SESSION['error_msg'] = "Unable to delete appointment. Please try again.";
                    }
                } else {
                    $_SESSION['error_msg'] = "Error deleting appointment: " . $delete_stmt->error;
                }
                
                $delete_stmt->close();
            } else {
                $_SESSION['error_msg'] = "Error preparing delete statement. Please try again.";
            }
        } else {
            $_SESSION['error_msg'] = "Appointment not found or you don't have permission to delete it.";
        }
    } else {
        $_SESSION['error_msg'] = "Error verifying appointment. Please try again.";
    }
    
    $stmt->close();
} else {
    $_SESSION['error_msg'] = "Error preparing verification statement. Please try again.";
}

// Close connection
$conn->close();

// Redirect back to dashboard
header("location: dashboard.php");
exit;
?>
