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

// Get patient details
$patient_id = '';
$full_name = '';
$email = '';
$phone = '';
$upcoming_appointments = [];

// Fetch patient details
$sql = "SELECT p.* FROM patients p JOIN users u ON p.user_id = u.id WHERE u.id = ?";
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $_SESSION["user_id"]);
    if($stmt->execute()){
        $result = $stmt->get_result();
        if($result->num_rows == 1){
            $patient = $result->fetch_assoc();
            $patient_id = $patient['id'];
            $full_name = $patient['full_name'];
            $email = $patient['email'];
            $phone = $patient['phone'] ?? 'Not provided';
            $address = $patient['address'] ?? 'Not provided';
        }
    }
    $stmt->close();
}

// Fetch upcoming appointments
$sql = "SELECT a.*, d.full_name as doctor_name, s.name as specialization 
        FROM appointments a 
        JOIN doctors d ON a.doctor_id = d.id 
        LEFT JOIN specializations s ON d.specialization_id = s.id 
        WHERE a.patient_id = ? AND a.appointment_date >= CURDATE() 
        AND (a.status = 'pending' OR a.status = 'confirmed')
        ORDER BY a.appointment_date, a.start_time";

$upcoming_appointments = [];
$confirmed_appointments = [];

if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $patient_id);
    if($stmt->execute()){
        $result = $stmt->get_result();
        $upcoming_appointments = $result->fetch_all(MYSQLI_ASSOC);
        
        // Check for confirmed appointments
        foreach($upcoming_appointments as $apt) {
            if($apt['status'] === 'confirmed') {
                $confirmed_appointments[] = $apt;
            }
        }
    }
    $stmt->close();
}

// Close connection
$conn->close();

// Set page title
$page_title = "Patient Dashboard";

// Include header
include("../includes/header.php");

?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">My Profile</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px; font-size: 40px;">
                            <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                        </div>
                    </div>
                    <h5 class="text-center mb-3"><?php echo htmlspecialchars($full_name); ?></h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-envelope me-2"></i>Email</span>
                            <span><?php echo htmlspecialchars($email); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-phone me-2"></i>Phone</span>
                            <span><?php echo htmlspecialchars($phone); ?></span>
                        </li>
                        <li class="list-group-item">
                            <span><i class="fas fa-map-marker-alt me-2"></i>Address</span>
                            <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($address)); ?></p>
                        </li>
                    </ul>
                    <div class="d-grid gap-2 mt-3">
                        <a href="edit_profile.php" class="btn btn-outline-primary">
                            <i class="fas fa-edit me-1"></i> Edit Profile
                        </a>
                    </div>
                </div>
            </div>
            <?php if(!empty($confirmed_appointments)): ?>
                <!-- Live Queue Status - Only show if patient has confirmed appointments -->
                <div id="liveQueueStatus" class="live-queue-widget">
                    <div class="queue-header">
                        <h6><i class="fas fa-ticket-alt me-2"></i>Live Queue Status</h6>
                        <div class="live-indicator">
                            <span class="live-dot"></span>
                            <span class="live-text">LIVE</span>
                        </div>
                    </div>
                    
                    <div class="queue-content">
                        <div id="liveQueueStatusLoading" class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-light" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mb-0 mt-2 small">Loading queue status...</p>
                        </div>
                        
                        <div id="liveQueueStatusContent" style="display: none;">
                            <!-- Queue content will be dynamically loaded here -->
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- No confirmed appointments - Show booking prompt -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No Confirmed Appointments</h6>
                        <p class="text-muted small mb-3">You don't have any confirmed appointments. Book an appointment to see live queue status.</p>
                        <a href="book_appointment.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> Book Appointment
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Upcoming Appointments</h2>
                <a href="book_appointment.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Book Appointment
                </a>
            </div>
            
            <?php if(count($upcoming_appointments) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Doctor</th>
                                <th>Specialization</th>
                                <th>Turn Number</th>
                                <th>Queue Position</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($upcoming_appointments as $appointment): 
                                $appointment_date = new DateTime($appointment['appointment_date']);
                                $start_time = new DateTime($appointment['start_time']);
                                $end_time = new DateTime($appointment['end_time']);
                                
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
                                <td><?php echo $appointment_date->format('M j, Y'); ?></td>
                                <td><?php echo $start_time->format('h:i A') . ' - ' . $end_time->format('h:i A'); ?></td>
                                <td><?php 
                                    // Remove 'Dr. ' from the name if it's already in the database
                                    $doctor_name = $appointment['doctor_name'];
                                    if (strpos($doctor_name, 'Dr. ') === 0) {
                                        $doctor_name = substr($doctor_name, 4);
                                    }
                                    echo 'Dr. ' . htmlspecialchars(trim($doctor_name)); 
                                ?></td>
                                <td><?php echo htmlspecialchars($appointment['specialization'] ?? 'General'); ?></td>
                                <td>
                                    <?php if(!empty($appointment['turn_number'])): ?>
                                        <span class="badge bg-info fs-6"><?php echo $appointment['turn_number']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">---</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if(!empty($appointment['queue_position'])): ?>
                                        <span class="badge bg-primary"><?php echo $appointment['queue_position']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">---</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge rounded-pill <?php echo $status_class; ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed'): ?>
                                        <a href="cancel_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-danger" title="Cancel" onclick="return confirm('Are you sure you want to cancel this appointment?');">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    You don't have any upcoming appointments. <a href="book_appointment.php" class="alert-link">Book an appointment now</a>.
                </div>
            <?php endif; ?>
            
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="appointment_history.php" class="text-decoration-none">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                                            <i class="fas fa-history text-primary fa-2x"></i>
                                        </div>
                                        <h5 class="card-title mb-1">Appointment History</h5>
                                        <p class="text-muted small mb-0">View your past appointments</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="prescriptions.php" class="text-decoration-none">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <div class="bg-success bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                                            <i class="fas fa-prescription text-success fa-2x"></i>
                                        </div>
                                        <h5 class="card-title mb-1">Prescriptions</h5>
                                        <p class="text-muted small mb-0">View your prescriptions</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="medical_records.php" class="text-decoration-none">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <div class="bg-info bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                                            <i class="fas fa-file-medical text-info fa-2x"></i>
                                        </div>
                                        <h5 class="card-title mb-1">Medical Records</h5>
                                        <p class="text-muted small mb-0">Access your medical history</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include("../includes/footer.php");
?>

<style>
.live-queue-widget {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 15px;
    color: white;
    margin-bottom: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.live-queue-widget .queue-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.live-queue-widget .queue-header h6 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.live-queue-widget .live-indicator {
    display: flex;
    align-items: center;
    font-size: 12px;
    font-weight: 600;
}

.live-queue-widget .live-dot {
    width: 8px;
    height: 8px;
    background-color: #4ade80;
    border-radius: 50%;
    margin-right: 5px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.live-queue-widget .queue-content {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 10px;
}

.live-queue-widget .queue-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.live-queue-widget .queue-item:last-child {
    border-bottom: none;
}

.live-queue-widget .queue-number {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}

.live-queue-widget .queue-patient {
    flex: 1;
    margin-left: 10px;
    font-size: 14px;
}

.live-queue-widget .queue-time {
    font-size: 12px;
    opacity: 0.8;
}
</style>

<script src="../includes/live_queue_widget.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize live queue widget only if container exists (i.e., patient has confirmed appointments)
    const queueContainer = document.getElementById('liveQueueStatus');
    if (queueContainer) {
        window.liveQueue = new LiveQueueStatus('liveQueueStatus', {
            refreshInterval: 10000, // 10 seconds
            maxItems: 5
        });
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (window.liveQueue) {
        window.liveQueue.destroy();
    }
});
</script>
</body>
</html>
