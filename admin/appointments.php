<?php
// Initialize the session
session_start();
 
// Check if the user is logged in as admin, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin'){
    header("location: ../login.php");
    exit;
}

// Include config file
require_once "../includes/config.php";

// Process cancel request
if(isset($_GET['action']) && isset($_GET['id'])) {
    $appointment_id = $_GET['id'];
    
    if($_GET['action'] == 'cancel') {
        $sql = "UPDATE appointments SET status = 'cancelled' WHERE id = ?";
        $success_msg = "Appointment has been cancelled successfully.";
        $error_msg = "Error cancelling appointment. Please try again.";
    } 
    elseif($_GET['action'] == 'approve') {
        $sql = "UPDATE appointments SET status = 'confirmed' WHERE id = ? AND status = 'pending'";
        $success_msg = "Appointment has been approved successfully.";
        $error_msg = "Error approving appointment. It may have already been processed.";
    }
    
    if(isset($sql)) {
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("i", $appointment_id);
            if($stmt->execute()){
                $_SESSION['success_msg'] = $success_msg;
            } else {
                $_SESSION['error_msg'] = $error_msg;
            }
            $stmt->close();
            
            // Redirect to prevent form resubmission
            header("location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

// Fetch all appointments
$appointments = [];
$sql = "SELECT a.*, 
               p.full_name as patient_name, 
               p.phone as patient_phone,
               d.full_name as doctor_name,
               d.specialization
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.status != 'cancelled'
        ORDER BY a.appointment_date DESC, a.start_time DESC";

if($result = $conn->query($sql)){
    while($row = $result->fetch_assoc()){
        $appointments[] = $row;
    }
}

// Set page title
$page_title = "Manage Appointments";

// Include header
include("../includes/header.php");
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Manage Appointments</h4>
                    <a href="index.php" class="btn btn-light">Back to Dashboard</a>
                </div>
                <div class="card-body">
                    <?php if(isset($success_msg)): ?>
                        <div class="alert alert-success"><?php echo $success_msg; ?></div>
                    <?php endif; ?>
                    
                    <?php if(isset($error_msg)): ?>
                        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                    <?php endif; ?>
                    
                    <?php if(empty($appointments)): ?>
                        <div class="alert alert-info">No appointments found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Patient</th>
                                        <th>Doctor</th>
                                        <th>Specialization</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($appointments as $index => $appointment): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($appointment['patient_name'] ?? ''); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($appointment['patient_phone'] ?? ''); ?></small>
                                            </td>
                                            <td>Dr. <?php echo htmlspecialchars($appointment['doctor_name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['specialization'] ?? ''); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($appointment['start_time'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $appointment['status'] == 'pending' ? 'warning' : 
                                                         ($appointment['status'] == 'confirmed' ? 'success' : 'danger'); 
                                                ?>">
                                                    <?php echo ucfirst($appointment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <?php if($appointment['status'] == 'pending'): ?>
                                                        <a href="appointments.php?action=approve&id=<?php echo $appointment['id']; ?>" 
                                                           class="btn btn-sm btn-success me-1" 
                                                           title="Approve Appointment"
                                                           onclick="return confirm('Are you sure you want to approve this appointment?')">
                                                            <i class="fas fa-check"></i> Approve
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if($appointment['status'] != 'cancelled'): ?>
                                                        <a href="appointments.php?action=cancel&id=<?php echo $appointment['id']; ?>" 
                                                           class="btn btn-sm btn-danger" 
                                                           title="Cancel Appointment"
                                                           onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                                            <i class="fas fa-times"></i> Cancel
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include("../includes/footer.php");
?>
