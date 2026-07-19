<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'patient'){
    header("location: ../login.php");
    exit;
}

// Include config file
require_once "../includes/config.php";

// Get patient ID first
$patient_id = '';
$sql = "SELECT id FROM patients WHERE user_id = ?";
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $_SESSION["user_id"]);
    if($stmt->execute()){
        $result = $stmt->get_result();
        if($row = $result->fetch_assoc()){
            $patient_id = $row['id'];
        }
    }
    $stmt->close();
}

// Fetch patient's appointments with doctor details
$appointments = [];
$sql = "SELECT a.*, 
        d.full_name as doctor_name, 
        d.specialization,
        d.phone as doctor_phone,
        d.email as doctor_email,
        p.full_name as patient_name
        FROM appointments a 
        JOIN doctors d ON a.doctor_id = d.id 
        JOIN patients p ON a.patient_id = p.id 
        WHERE a.patient_id = ? 
        AND a.status != 'cancelled'
        AND (a.appointment_date > CURDATE() 
             OR (a.appointment_date = CURDATE() AND a.end_time > CURTIME()))
        ORDER BY a.appointment_date ASC, a.start_time ASC";

if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $patient_id);
    if($stmt->execute()){
        $result = $stmt->get_result();
        $appointments = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
}

// Close connection
$conn->close();
?>

<?php $page_title = "My Appointments"; ?>
<?php include('../includes/header.php'); ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Appointments</h2>
        <a href="book_appointment.php" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Book New Appointment
        </a>
    </div>

    <?php if (empty($appointments)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            You don't have any appointments yet. <a href="book_appointment.php" class="alert-link">Book your first appointment</a>.
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Doctor</th>
                                <th>Specialization</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Debug output - remove after testing
                            // echo '<pre>'; print_r($appointments); echo '</pre>'; 
                            
                            foreach ($appointments as $appointment): 
                                $appointment_date = new DateTime($appointment['appointment_date']);
                                $start_time = new DateTime($appointment['start_time']);
                                $end_time = new DateTime($appointment['end_time']);
                                
                                // Debug - check doctor name
                                // echo 'Doctor name from DB: ' . htmlspecialchars($appointment['doctor_name']);
                                
                                // Status badge class
                                $status_class = '';
                                switch($appointment['status']) {
                                    case 'confirmed':
                                        $status_class = 'bg-success';
                                        break;
                                    case 'pending':
                                        $status_class = 'bg-warning';
                                        break;
                                    case 'cancelled':
                                        $status_class = 'bg-danger';
                                        break;
                                    case 'completed':
                                        $status_class = 'bg-secondary';
                                        break;
                                }
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo $appointment_date->format('D, M j, Y'); ?></div>
                                    <small class="text-muted"><?php echo $appointment_date->format('l'); ?></small>
                                </td>
                                <td>
                                    <div class="fw-bold"><?php echo $start_time->format('g:i A') . ' - ' . $end_time->format('g:i A'); ?></div>
                                    <small class="text-muted"><?php echo $start_time->format('h:i A'); ?> (30 min)</small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm me-2 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <?php echo strtoupper(substr($appointment['doctor_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php 
                                                // Remove 'Dr. ' from the name if it's already in the database
                                                $doctor_name = $appointment['doctor_name'];
                                                if (strpos($doctor_name, 'Dr. ') === 0) {
                                                    $doctor_name = substr($doctor_name, 4);
                                                }
                                                echo 'Dr. ' . htmlspecialchars(trim($doctor_name)); 
                                            ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($appointment['specialization']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($appointment['specialization']); ?></div>
                                    <small class="text-muted">
                                        <i class="fas fa-phone-alt me-1"></i> <?php echo !empty($appointment['doctor_phone']) ? htmlspecialchars($appointment['doctor_phone']) : 'N/A'; ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge rounded-pill <?php echo $status_class; ?> px-3 py-2">
                                        <i class="fas <?php 
                                            echo $appointment['status'] == 'confirmed' ? 'fa-check-circle' : 
                                                 ($appointment['status'] == 'pending' ? 'fa-clock' : 
                                                 ($appointment['status'] == 'cancelled' ? 'fa-times-circle' : 'fa-calendar-check')); 
                                        ?> me-1"></i>
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" 
                                       class="btn btn-sm btn-info" 
                                       title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed'): ?>
                                        <a href="cancel_appointment.php?id=<?php echo $appointment['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           title="Cancel Appointment"
                                           onclick="return confirm('Are you sure you want to cancel this appointment?');">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>
