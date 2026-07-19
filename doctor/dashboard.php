<?php
// Start session
session_start();

// Check if user is logged in as doctor
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'doctor'){
    header("location: ../login.php");
    exit;
}

// Include config and turn functions files
require_once "../includes/config.php";
require_once "../includes/turn_functions.php";

// Get doctor details
$doctor_id = '';
$doctor_name = '';
$specialization = '';

$sql = "SELECT id, full_name, specialization FROM doctors WHERE user_id = ?";
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $_SESSION["user_id"]);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()){
        $doctor_id = $row['id'];
        $doctor_name = $row['full_name'];
        $specialization = $row['specialization'];
    }
    $stmt->close();
}

// Get today's date
$today = date('Y-m-d');

// Get today's appointments
$today_appointments = [];
$sql = "SELECT a.*, p.full_name as patient_name, p.phone 
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.id 
        WHERE a.doctor_id = ? AND a.appointment_date = ? 
        ORDER BY a.start_time ASC";
        
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("is", $doctor_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $today_appointments = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Get queue statistics
$waiting_queue = getWaitingQueue($conn, $doctor_id, $today);
$current_turn = getCurrentTurn($conn, $doctor_id, $today);
$next_patient = getNextPatient($conn, $doctor_id, $today);

// Get upcoming appointments this week
$week_appointments = [];
$week_start = date('Y-m-d');
$week_end = date('Y-m-d', strtotime('+7 days'));

$sql = "SELECT COUNT(*) as count, status 
        FROM appointments 
        WHERE doctor_id = ? AND appointment_date BETWEEN ? AND ? 
        GROUP BY status";
        
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("iss", $doctor_id, $week_start, $week_end);
    $stmt->execute();
    $result = $stmt->get_result();
    $week_stats = [];
    while($row = $result->fetch_assoc()){
        $week_stats[$row['status']] = $row['count'];
    }
    $stmt->close();
}

// Set page title
$page_title = "Doctor Dashboard";

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
                            <i class="fas fa-user-md"></i>
                        </div>
                    </div>
                    <h5 class="text-center mb-3">Dr. <?php echo htmlspecialchars($doctor_name); ?></h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-stethoscope me-2"></i>Specialization</span>
                            <span><?php echo htmlspecialchars($specialization); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-calendar me-2"></i>Today</span>
                            <span><?php echo date('F j, Y'); ?></span>
                        </li>
                    </ul>
                    <div class="d-grid gap-2 mt-3">
                        <a href="../admin/queue_management.php" class="btn btn-success">
                            <i class="fas fa-ticket-alt me-1"></i> Queue Management
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Queue Status Card -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Today's Queue</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <h4 class="text-success"><?php echo $current_turn ?: '---'; ?></h4>
                            <small class="text-muted">Current Turn</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h4 class="text-info"><?php echo count($waiting_queue); ?></h4>
                            <small class="text-muted">Waiting</small>
                        </div>
                    </div>
                    <?php if($next_patient): ?>
                        <div class="alert alert-info py-2">
                            <small><strong>Next:</strong> Turn #<?php echo $next_patient['turn_number']; ?> - <?php echo htmlspecialchars($next_patient['full_name']); ?></small>
                        </div>
                    <?php endif; ?>
                    <a href="../admin/queue_management.php" class="btn btn-sm btn-outline-success w-100">
                        <i class="fas fa-cog me-1"></i> Manage Queue
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Today's Appointments</h2>
                <span class="badge bg-primary fs-6"><?php echo count($today_appointments); ?> Appointments</span>
            </div>
            
            <?php if(count($today_appointments) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Turn #</th>
                                <th>Queue</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($today_appointments as $appointment): ?>
                                <tr class="<?php echo ($appointment['status'] == 'completed') ? 'table-secondary' : ''; ?>">
                                    <td><?php echo date('h:i A', strtotime($appointment['start_time'])); ?></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($appointment['patient_name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($appointment['phone']); ?></small>
                                        </div>
                                    </td>
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
                                        <span class="badge rounded-pill bg-<?php 
                                            echo match($appointment['status']) {
                                                'confirmed' => 'success',
                                                'pending' => 'warning',
                                                'completed' => 'secondary',
                                                'cancelled' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($appointment['status'] == 'confirmed'): ?>
                                            <form method="POST" action="../admin/queue_management.php" class="d-inline-block">
                                                <input type="hidden" name="action" value="complete">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success" title="Complete">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="#" class="btn btn-sm btn-info" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No appointments scheduled for today.
                </div>
            <?php endif; ?>
            
            <!-- Weekly Statistics -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">This Week's Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h4><?php echo $week_stats['completed'] ?? 0; ?></h4>
                                    <small>Completed</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h4><?php echo $week_stats['pending'] ?? 0; ?></h4>
                                    <small>Pending</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h4><?php echo $week_stats['confirmed'] ?? 0; ?></h4>
                                    <small>Confirmed</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <h4><?php echo $week_stats['cancelled'] ?? 0; ?></h4>
                                    <small>Cancelled</small>
                                </div>
                            </div>
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
