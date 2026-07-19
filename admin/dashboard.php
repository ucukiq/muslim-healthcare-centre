<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin'){
    header("location: ../login.php");
    exit;
}

// Include config file
require_once "../includes/config.php";

// Get counts for dashboard
$appointments_count = 0;
$doctors_count = 0;
$patients_count = 0;

// Get appointment count
$sql = "SELECT COUNT(*) as count FROM appointments";
if($result = $conn->query($sql)){
    $appointments_count = $result->fetch_assoc()['count'];
    $result->free();
}

// Get doctors count
$sql = "SELECT COUNT(*) as count FROM doctors";
if($result = $conn->query($sql)){
    $doctors_count = $result->fetch_assoc()['count'];
    $result->free();
}

// Get patients count
$sql = "SELECT COUNT(*) as count FROM patients";
if($result = $conn->query($sql)){
    $patients_count = $result->fetch_assoc()['count'];
    $result->free();
}

// Close connection
$conn->close();
?>

<?php $page_title = "Admin Dashboard"; ?>
<?php include('../includes/header.php'); ?>


<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="page-header mb-4">
                <h1 class="h3">Admin Dashboard</h1>
                <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION["name"]); ?>!</p>
            </div>

            <!-- Stats Cards -->
            <div class="row">
                <div class="col-xl-4 col-sm-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Appointments</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $appointments_count; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-sm-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total Doctors</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $doctors_count; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-md fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-sm-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Total Patients</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $patients_count; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <a href="appointments.php" class="btn btn-primary btn-block p-4">
                                        <i class="fas fa-calendar-plus fa-2x mb-2"></i>
                                        <h5>Manage Appointments</h5>
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="doctor_management_full.php" class="btn btn-success btn-block p-4">
                                        <i class="fas fa-user-md fa-2x mb-2"></i>
                                        <h5>Manage Doctors</h5>
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="manage_patients.php" class="btn btn-info btn-block p-4">
                                        <i class="fas fa-users fa-2x mb-2"></i>
                                        <h5>Manage Patients</h5>
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="queue_management.php" class="btn btn-success btn-block p-4">
                                        <i class="fas fa-ticket-alt fa-2x mb-2"></i>
                                        <h5>Queue Management</h5>
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="../admin_register_new.php" class="btn btn-warning btn-block p-4" onclick="return confirm('You are about to register a new admin. Only proceed if you have proper authorization. Continue?');">
                                        <i class="fas fa-user-shield fa-2x mb-2"></i>
                                        <h5>Register New Admin</h5>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                        </div>
                        <div class="card-body">
                            <style>
                                .activity-feed {
                                    max-height: 400px;
                                    overflow-y: auto;
                                }
                                .activity-item {
                                    padding: 10px 0;
                                    border-bottom: 1px solid #f0f0f0;
                                    transition: background-color 0.2s;
                                }
                                .activity-item:hover {
                                    background-color: #f8f9fa;
                                    border-radius: 5px;
                                    padding-left: 10px;
                                    padding-right: 10px;
                                }
                                .activity-item:last-child {
                                    border-bottom: none;
                                }
                                .activity-icon {
                                    font-size: 1.2rem;
                                    width: 30px;
                                    text-align: center;
                                }
                                .activity-details p {
                                    font-size: 0.9rem;
                                }
                            </style>
                            
                            <?php
                            // Generate random recent activities
                            $activities = [
                                ['icon' => 'fas fa-user-plus', 'text' => 'New patient registered', 'time' => '2 minutes ago', 'color' => 'text-success'],
                                ['icon' => 'fas fa-calendar-check', 'text' => 'Appointment confirmed with Dr. Ahmad', 'time' => '5 minutes ago', 'color' => 'text-primary'],
                                ['icon' => 'fas fa-file-medical', 'text' => 'Medical record updated', 'time' => '10 minutes ago', 'color' => 'text-info'],
                                ['icon' => 'fas fa-phone-alt', 'text' => 'Patient call completed', 'time' => '15 minutes ago', 'color' => 'text-warning'],
                                ['icon' => 'fas fa-pills', 'text' => 'Prescription issued', 'time' => '20 minutes ago', 'color' => 'text-danger'],
                                ['icon' => 'fas fa-user-check', 'text' => 'Patient checked out', 'time' => '25 minutes ago', 'color' => 'text-secondary'],
                                ['icon' => 'fas fa-clock', 'text' => 'Appointment rescheduled', 'time' => '30 minutes ago', 'color' => 'text-dark'],
                                ['icon' => 'fas fa-envelope', 'text' => 'Appointment reminder sent', 'time' => '35 minutes ago', 'color' => 'text-primary']
                            ];
                            
                            // Shuffle and display 6 random activities
                            shuffle($activities);
                            $display_activities = array_slice($activities, 0, 6);
                            ?>
                            
                            <div class="activity-feed">
                                <?php foreach ($display_activities as $activity): ?>
                                    <div class="activity-item d-flex align-items-center mb-3">
                                        <div class="activity-icon me-3">
                                            <i class="<?php echo $activity['icon']; ?> <?php echo $activity['color']; ?>"></i>
                                        </div>
                                        <div class="activity-details flex-grow-1">
                                            <p class="mb-0 fw-medium"><?php echo $activity['text']; ?></p>
                                            <small class="text-muted"><?php echo $activity['time']; ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="#" class="btn btn-sm btn-outline-primary">View All Activities</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize admin live queue widget
    window.adminLiveQueue = new LiveQueueStatus('adminLiveQueueStatus', {
        refreshInterval: 15000, // 15 seconds for admin dashboard
        maxItems: 8,
        showDoctorInfo: true
    });
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (window.adminLiveQueue) {
        window.adminLiveQueue.destroy();
    }
});
</script>
