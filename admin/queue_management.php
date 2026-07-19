<?php
// Start session
session_start();

// Check if user is logged in as doctor or admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION["role"], ['doctor', 'admin'])){
    header("location: ../login.php");
    exit;
}

// Include config and turn functions files
require_once "../includes/config.php";
require_once "../includes/turn_functions.php";

// Get doctor ID (for doctors, get their own ID; for admins, allow selection)
$doctor_id = '';
$doctor_name = '';

if($_SESSION["role"] == 'doctor') {
    // Get doctor's ID from users table
    $sql = "SELECT id, full_name FROM doctors WHERE user_id = ?";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("i", $_SESSION["user_id"]);
        $stmt->execute();
        $result = $stmt->get_result();
        if($row = $result->fetch_assoc()){
            $doctor_id = $row['id'];
            $doctor_name = $row['full_name'];
        }
        $stmt->close();
    }
} else {
    // Admin can select a doctor
    $doctor_id = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : '';
    if(!empty($doctor_id)) {
        $sql = "SELECT full_name FROM doctors WHERE id = ?";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("i", $doctor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if($row = $result->fetch_assoc()){
                $doctor_name = $row['full_name'];
            }
            $stmt->close();
        }
    }
}

// Get today's date
$today = date('Y-m-d');

// Handle queue actions
if($_SERVER["REQUEST_METHOD"] == "POST" && !empty($doctor_id)) {
    // Admin-only authorization for queue updates
    if($_SESSION["role"] !== 'admin') {
        $_SESSION['error_message'] = "Access denied: Only authorized admin staff can update queue numbers.";
        header("location: queue_management.php");
        exit;
    }
    
    if(isset($_POST['action']) && isset($_POST['appointment_id'])) {
        $appointment_id = $_POST['appointment_id'];
        $action = $_POST['action'];
        
        if($action == 'complete') {
            // Mark appointment as completed
            $sql = "UPDATE appointments SET status = 'completed', updated_at = NOW() WHERE id = ? AND doctor_id = ?";
            if($stmt = $conn->prepare($sql)){
                $stmt->bind_param("ii", $appointment_id, $doctor_id);
                $stmt->execute();
                $stmt->close();
                
                // Update queue positions
                updateQueuePositions($conn, $doctor_id, $today);
                
                // Log admin action
                $admin_name = $_SESSION['full_name'] ?? 'Admin';
                error_log("Admin $admin_name completed appointment ID: $appointment_id for doctor ID: $doctor_id");
            }
        } elseif($action == 'call_next') {
            // Get next patient and mark as being called
            $next_patient = getNextPatient($conn, $doctor_id, $today);
            if($next_patient) {
                // Update the appointment status to 'in_progress'
                $sql = "UPDATE appointments SET status = 'in_progress', updated_at = NOW() WHERE id = ? AND doctor_id = ? AND status = 'confirmed'";
                if($stmt = $conn->prepare($sql)){
                    $stmt->bind_param("ii", $next_patient['id'], $doctor_id);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Store current patient in session
                    $_SESSION['current_patient'] = $next_patient;
                    
                    // Set success message
                    $_SESSION['success_message'] = "Called next patient: " . htmlspecialchars($next_patient['full_name']) . " (Turn #" . $next_patient['turn_number'] . ")";
                    
                    // Log admin action
                    $admin_name = $_SESSION['full_name'] ?? 'Admin';
                    error_log("Admin $admin_name called next patient ID: {$next_patient['id']} for doctor ID: $doctor_id");
                }
            } else {
                // No patients in queue
                $_SESSION['error_message'] = "No patients available to call";
            }
        } elseif($action == 'skip') {
            // Skip current patient (move to end of queue or mark as no-show)
            $sql = "UPDATE appointments SET status = 'cancelled', updated_at = NOW() WHERE id = ? AND doctor_id = ?";
            if($stmt = $conn->prepare($sql)){
                $stmt->bind_param("ii", $appointment_id, $doctor_id);
                $stmt->execute();
                $stmt->close();
                
                // Update queue positions
                updateQueuePositions($conn, $doctor_id, $today);
                
                // Log admin action
                $admin_name = $_SESSION['full_name'] ?? 'Admin';
                error_log("Admin $admin_name skipped appointment ID: $appointment_id for doctor ID: $doctor_id");
            }
        }
        
        // Redirect to prevent form resubmission
        header("location: queue_management.php?doctor_id=" . urlencode($doctor_id));
        exit;
    }
}

// Get waiting queue
$waiting_queue = [];
$current_turn = null;
$next_patient = null;

if(!empty($doctor_id)) {
    $waiting_queue = getWaitingQueue($conn, $doctor_id, $today);
    $current_turn = getCurrentTurn($conn, $doctor_id, $today);
    $next_patient = getNextPatient($conn, $doctor_id, $today);
}

// Get all doctors for admin selection
$doctors_list = [];
if($_SESSION["role"] == 'admin') {
    $sql = "SELECT id, full_name, specialization FROM doctors ORDER BY full_name";
    if($result = $conn->query($sql)){
        while($row = $result->fetch_assoc()){
            $doctors_list[] = $row;
        }
    }
}

// Set page title
$page_title = "Queue Management";

// Include header
include("../includes/header.php");
?>

<div class="container-fluid py-4">
    <?php
    // Display success messages
    if(isset($_SESSION['success_message'])):
    ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php 
            echo htmlspecialchars($_SESSION['success_message']);
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php
    // Display error messages
    if(isset($_SESSION['error_message'])):
    ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php 
            echo htmlspecialchars($_SESSION['error_message']);
            unset($_SESSION['error_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Queue Management Main Content - Now full width -->
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Queue Management</h1>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm" onclick="refreshAllQueues()">
                        <i class="fas fa-sync-alt me-1"></i> Refresh All
                    </button>
                </div>
            </div>
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-users me-2"></i>Queue Management
                        <?php if(!empty($doctor_name)): ?>
                            - <?php echo htmlspecialchars($doctor_name); ?>
                        <?php endif; ?>
                    </h4>
                </div>
                <div class="card-body">
                    <?php if($_SESSION["role"] == 'admin'): ?>
                        <!-- Admin Notice -->
                        <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Admin Access:</strong> You have full authorization to manage queue operations including calling next patients, completing appointments, and updating queue positions.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php else: ?>
                        <!-- Doctor Notice -->
                        <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>View Only:</strong> Queue management actions are restricted to authorized admin staff only. Contact an administrator for queue updates.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if($_SESSION["role"] == 'admin'): ?>
                        <!-- Doctor Selection for Admin -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <form method="GET" class="d-flex">
                                    <select name="doctor_id" class="form-select me-2" required>
                                        <option value="">Select Doctor</option>
                                        <?php foreach($doctors_list as $doc): ?>
                                            <option value="<?php echo $doc['id']; ?>" <?php echo ($doctor_id == $doc['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($doc['full_name']); ?> - <?php echo htmlspecialchars($doc['specialization']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary">View Queue</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($doctor_id)): ?>
                        <!-- Queue Status -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Current Turn</h5>
                                        <h2 class="mb-0"><?php echo $current_turn ?: '---'; ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Next Patient</h5>
                                        <h4 class="mb-0">
                                            <?php if($next_patient): ?>
                                                Turn #<?php echo $next_patient['turn_number']; ?>
                                            <?php else: ?>
                                                No one
                                            <?php endif; ?>
                                        </h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Waiting</h5>
                                        <h2 class="mb-0"><?php echo count($waiting_queue); ?></h2>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Queue Actions -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <?php if($_SESSION["role"] == 'admin'): ?>
                                    <form method="POST" class="d-inline-block me-2">
                                        <input type="hidden" name="action" value="call_next">
                                        <button type="submit" 
                                                class="btn btn-success <?php echo empty($next_patient) ? 'disabled' : ''; ?>" 
                                                title="<?php echo empty($next_patient) ? 'No patients in queue to call' : 'Admin Only: Call next patient'; ?>"
                                                <?php echo empty($next_patient) ? 'disabled' : ''; ?>>
                                            <i class="fas fa-bullhorn me-2"></i>Call Next Patient
                                            <i class="fas fa-shield-alt ms-1"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-success" disabled title="Admin Only: Only authorized staff can update queue">
                                        <i class="fas fa-bullhorn me-2"></i>Call Next Patient
                                        <i class="fas fa-lock ms-1"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <?php if(!empty($waiting_queue)): ?>
                                    <span class="text-muted ms-3">
                                        <i class="fas fa-info-circle"></i>
                                        <?php echo count($waiting_queue); ?> patient(s) in queue
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted ms-3">
                                        <i class="fas fa-info-circle"></i>
                                        No patients in queue
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if($next_patient): ?>
                            <div class="alert alert-info" role="alert">
                                <h6 class="alert-heading">
                                    <i class="fas fa-user-check me-2"></i>Next Patient Ready
                                </h6>
                                <p class="mb-0">
                                    <strong>Turn #<?php echo $next_patient['turn_number']; ?></strong> - 
                                    <?php echo htmlspecialchars($next_patient['full_name']); ?>
                                    <span class="text-muted ms-2">
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('g:i A', strtotime($next_patient['start_time'])); ?>
                                    </span>
                                </p>
                            </div>
                        <?php endif; ?>

                        <!-- Waiting Queue -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-list-ol me-2"></i>Waiting Queue - <?php echo date('F j, Y'); ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if(!empty($waiting_queue)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Queue Position</th>
                                                    <th>Turn Number</th>
                                                    <th>Patient Name</th>
                                                    <th>Phone</th>
                                                    <th>Appointment Time</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($waiting_queue as $patient): ?>
                                                    <tr class="<?php echo ($patient['status'] == 'confirmed') ? 'table-success' : ''; ?>">
                                                        <td>
                                                            <span class="badge bg-primary"><?php echo $patient['queue_position']; ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-info fs-6"><?php echo $patient['turn_number']; ?></span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($patient['full_name']); ?></td>
                                                        <td><?php echo $patient['phone'] !== null ? htmlspecialchars($patient['phone']) : ''; ?></td>
                                                        <td><?php echo date('g:i A', strtotime($patient['start_time'])); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo ($patient['status'] == 'confirmed') ? 'success' : 'secondary'; ?>">
                                                                <?php echo ucfirst($patient['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if($patient['status'] == 'confirmed'): ?>
                                                                <?php if($_SESSION["role"] == 'admin'): ?>
                                                                    <form method="POST" class="d-inline-block">
                                                                        <input type="hidden" name="action" value="complete">
                                                                        <input type="hidden" name="appointment_id" value="<?php echo $patient['id']; ?>">
                                                                        <button type="submit" class="btn btn-success btn-sm" 
                                                                                onclick="return confirm('Mark this appointment as completed?')"
                                                                                title="Admin Only: Mark appointment as completed">
                                                                            <i class="fas fa-check"></i> Complete
                                                                            <i class="fas fa-shield-alt ms-1"></i>
                                                                        </button>
                                                                    </form>
                                                                    <form method="POST" class="d-inline-block ms-1">
                                                                        <input type="hidden" name="action" value="skip">
                                                                        <input type="hidden" name="appointment_id" value="<?php echo $patient['id']; ?>">
                                                                        <button type="submit" class="btn btn-warning btn-sm" 
                                                                                onclick="return confirm('Skip this patient?')"
                                                                                title="Admin Only: Skip this patient">
                                                                            <i class="fas fa-forward"></i> Skip
                                                                            <i class="fas fa-shield-alt ms-1"></i>
                                                                        </button>
                                                                    </form>
                                                                <?php else: ?>
                                                                    <button class="btn btn-success btn-sm" disabled 
                                                                            title="Admin Only: Only authorized staff can update queue">
                                                                        <i class="fas fa-check"></i> Complete
                                                                        <i class="fas fa-lock ms-1"></i>
                                                                    </button>
                                                                    <button class="btn btn-warning btn-sm ms-1" disabled 
                                                                            title="Admin Only: Only authorized staff can update queue">
                                                                        <i class="fas fa-forward"></i> Skip
                                                                        <i class="fas fa-lock ms-1"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No patients in queue</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-user-md fa-3x text-muted mb-3"></i>
                            <p class="text-muted">
                                <?php if($_SESSION["role"] == 'admin'): ?>
                                    Please select a doctor to view their queue
                                <?php else: ?>
                                    Doctor profile not found
                                <?php endif; ?>
                            </p>
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

<script src="../includes/live_queue_widget.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize queue management live queue widget
    window.queueLiveQueue = new LiveQueueStatus('queueLiveQueueStatus', {
        refreshInterval: 5000, // 5 seconds for queue management
        maxItems: 10,
        showDoctorInfo: true,
        showEmptyMessage: true
    });
});
</script>

// Refresh all queues function
function refreshAllQueues() {
    if (window.queueLiveQueue) {
        window.queueLiveQueue.refresh();
    }
    // Also refresh the main page
    location.reload();
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (window.queueLiveQueue) {
        window.queueLiveQueue.destroy();
    }
});
</script>
