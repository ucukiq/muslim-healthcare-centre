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
$appointment = [];

// Fetch appointment details
$sql = "SELECT a.*, d.full_name as doctor_name, d.specialization, d.phone as doctor_phone 
        FROM appointments a 
        JOIN doctors d ON a.doctor_id = d.id 
        WHERE a.id = ? AND a.patient_id = (SELECT id FROM patients WHERE user_id = ?) 
        LIMIT 1";

if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("ii", $appointment_id, $_SESSION['user_id']);
    if($stmt->execute()){
        $result = $stmt->get_result();
        if($result->num_rows == 1){
            $appointment = $result->fetch_assoc();
        } else {
            // No appointment found or not authorized
            header("location: dashboard.php");
            exit;
        }
    }
    $stmt->close();
}

// Set page title
$page_title = "View Appointment";

// Include header
include("../includes/header.php");
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Appointment Details</h4>
                </div>
                <div class="card-body">
                    <?php if(!empty($appointment)): ?>
                        <div class="mb-4">
                            <h5>Appointment #<?php echo $appointment['id']; ?></h5>
                            <?php if(!empty($appointment['turn_number'])): ?>
                                <div class="alert alert-primary d-inline-block py-2 px-3 mb-3">
                                    <i class="fas fa-ticket-alt me-2"></i>
                                    <strong>Turn Number:</strong> <span class="fs-5"><?php echo $appointment['turn_number']; ?></span>
                                </div>
                            <?php endif; ?>
                            <hr>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6>Doctor Information</h6>
                                    <p class="mb-1">
                                        <strong>Name:</strong> <?php 
                                            // Remove 'Dr. ' from the name if it's already in the database
                                            $doctor_name = $appointment['doctor_name'];
                                            if (strpos($doctor_name, 'Dr. ') === 0) {
                                                $doctor_name = substr($doctor_name, 4);
                                            }
                                            echo 'Dr. ' . htmlspecialchars(trim($doctor_name)); 
                                        ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Specialization:</strong> <?php echo htmlspecialchars($appointment['specialization']); ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Contact:</strong> <?php echo htmlspecialchars($appointment['doctor_phone']); ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Appointment Details</h6>
                                    <p class="mb-1">
                                        <strong>Date:</strong> <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Time:</strong> <?php echo date('h:i A', strtotime($appointment['start_time'])); ?> - 
                                        <?php echo date('h:i A', strtotime($appointment['end_time'])); ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Status:</strong> 
                                        <span class="badge bg-<?php 
                                            echo $appointment['status'] == 'pending' ? 'warning' : 
                                                ($appointment['status'] == 'confirmed' ? 'success' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <?php if(!empty($appointment['notes'])): ?>
                                <div class="mb-3">
                                    <h6>Notes:</h6>
                                    <div class="p-3 bg-light rounded">
                                        <?php echo nl2br(htmlspecialchars($appointment['notes'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="mt-4">
                                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                                <?php if($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed'): ?>
                                    <div class="float-end">
                                        <a href="delete_appointment.php?id=<?php echo $appointment['id']; ?>" 
                                           class="btn btn-danger me-2" 
                                           onclick="return confirm('⚠️ WARNING: This will PERMANENTLY delete appointment #<?php echo $appointment['id']; ?>. This action cannot be undone. Are you sure?')">
                                            <i class="fas fa-trash me-1"></i> Delete Appointment
                                        </a>
                                        <a href="cancel_appointment.php?id=<?php echo $appointment['id']; ?>" 
                                           class="btn btn-warning" 
                                           onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                            <i class="fas fa-times me-1"></i> Cancel Appointment
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger">Appointment not found.</div>
                        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
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
