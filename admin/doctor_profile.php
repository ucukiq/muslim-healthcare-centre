<?php
// Start session
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../login.php");
    exit;
}

// Check if user has permission (admin or doctor viewing own profile)
if($_SESSION["role"] !== 'admin' && $_SESSION["role"] !== 'doctor'){
    header("location: ../unauthorized.php");
    exit;
}

// Include database connection
include_once "../includes/db_connection.php";

// Get doctor ID from URL parameter
$doctor_id = isset($_GET['id']) ? $_GET['id'] : '';

// If no doctor ID provided and user is a doctor, show their own profile
if(empty($doctor_id) && $_SESSION["role"] == 'doctor'){
    // Get doctor ID from user session
    $sql = "SELECT id FROM doctors WHERE user_id = ?";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("i", $_SESSION["id"]);
        $stmt->execute();
        $result = $stmt->get_result();
        if($row = $result->fetch_assoc()){
            $doctor_id = $row['id'];
        }
        $stmt->close();
    }
}

// Validate doctor ID
if(empty($doctor_id)){
    $_SESSION['error_message'] = "Doctor ID not provided or not found.";
    header("location: manage_doctors.php");
    exit;
}

// Get doctor information
$doctor = null;
$sql = "SELECT d.*, u.username, u.email as user_email, u.created_at as user_created_at 
        FROM doctors d 
        JOIN users u ON d.user_id = u.id 
        WHERE d.id = ?";
        
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($row = $result->fetch_assoc()){
        $doctor = $row;
    } else {
        $_SESSION['error_message'] = "Doctor not found.";
        header("location: manage_doctors.php");
        exit;
    }
    $stmt->close();
}

// Check permissions
if($_SESSION["role"] !== 'admin' && ($_SESSION["role"] == 'doctor' && $doctor['user_id'] != $_SESSION["id"])){
    $_SESSION['error_message'] = "You can only view your own profile.";
    header("location: dashboard.php");
    exit;
}

// Get doctor's appointments statistics
$stats = [];
$sql = "SELECT 
            COUNT(*) as total_appointments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_appointments,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) / COUNT(*) * 100 as completion_rate
        FROM appointments 
        WHERE doctor_id = ? AND appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    $stmt->close();
}

// Get recent appointments
$recent_appointments = [];
$sql = "SELECT a.*, p.full_name as patient_name, p.phone as patient_phone 
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.id 
        WHERE a.doctor_id = ? 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC 
        LIMIT 10";
        
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $recent_appointments[] = $row;
    }
    $stmt->close();
}

// Get today's appointments
$today_appointments = [];
$today = date('Y-m-d');
$sql = "SELECT a.*, p.full_name as patient_name, p.phone as patient_phone 
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.id 
        WHERE a.doctor_id = ? AND a.appointment_date = ? 
        ORDER BY a.appointment_time ASC";
        
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("is", $doctor_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $today_appointments[] = $row;
    }
    $stmt->close();
}

// Set page title
$page_title = "Doctor Profile - " . htmlspecialchars($doctor['full_name']);

// Include header
include "../includes/header.php";
?>

<div class="container-fluid py-4">
    <?php
    // Display success/error messages
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

    <!-- Profile Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Doctor Profile</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="manage_doctors.php">Doctors</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($doctor['full_name']); ?></li>
                        </ol>
                    </nav>
                </div>
                <?php if($_SESSION["role"] == 'admin'): ?>
                <div>
                    <a href="edit_doctor.php?id=<?php echo $doctor['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Edit Profile
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Profile Content -->
    <div class="row">
        <!-- Left Column - Profile Info -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <!-- Profile Photo -->
                    <div class="mb-3">
                        <?php if(!empty($doctor['profile_photo'])): ?>
                            <img src="../assets/images/doctors/<?php echo htmlspecialchars($doctor['profile_photo']); ?>" 
                                 alt="<?php echo htmlspecialchars($doctor['full_name']); ?>" 
                                 class="rounded-circle" width="150" height="150" style="object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center" 
                                 style="width: 150px; height: 150px;">
                                <i class="fas fa-user-md fa-3x text-white"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h4 class="mb-1"><?php 
                        $display_name = $doctor['full_name'];
                        // Remove any existing 'Dr. ' from the name
                        $display_name = preg_replace('/^Dr\.\s*/i', '', $display_name);
                        // Add 'Dr. ' prefix if not already present
                        echo 'Dr. ' . htmlspecialchars(trim($display_name)); 
                    ?></h4>
                    <p class="text-muted mb-3"><?php echo htmlspecialchars($doctor['specialization']); ?></p>
                    
                    <div class="d-flex justify-content-center gap-2 mb-3">
                        <span class="badge bg-<?php echo ($doctor['status'] == 'Active') ? 'success' : 'secondary'; ?>">
                            <?php echo htmlspecialchars($doctor['status']); ?>
                        </span>
                        <span class="badge bg-info">
                            <?php echo htmlspecialchars($doctor['doctor_id']); ?>
                        </span>
                    </div>
                    
                    <?php if($_SESSION["role"] == 'admin'): ?>
                    <div class="d-grid gap-2">
                        <a href="edit_doctor.php?id=<?php echo $doctor['id']; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-edit me-1"></i>Edit Profile
                        </a>
                        <a href="doctor_performance.php?id=<?php echo $doctor['id']; ?>" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-chart-line me-1"></i>View Performance
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-address-card me-2"></i>Contact Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Email</label>
                        <div class="fw-bold"><?php echo htmlspecialchars($doctor['email']); ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Phone</label>
                        <div class="fw-bold"><?php echo htmlspecialchars($doctor['phone']); ?></div>
                    </div>
                    <?php if(!empty($doctor['address'])): ?>
                    <div class="mb-3">
                        <label class="text-muted small">Address</label>
                        <div class="fw-bold"><?php echo nl2br(htmlspecialchars($doctor['address'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="text-muted small">Username</label>
                        <div class="fw-bold"><?php echo htmlspecialchars($doctor['username']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Professional Information -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i>Professional Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Specialization</label>
                        <div class="fw-bold"><?php echo htmlspecialchars($doctor['specialization']); ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Age</label>
                        <div class="fw-bold"><?php echo htmlspecialchars($doctor['age']); ?> years</div>
                    </div>
                    <?php if(!empty($doctor['nric'])): ?>
                    <div class="mb-3">
                        <label class="text-muted small">NRIC</label>
                        <div class="fw-bold"><?php echo htmlspecialchars($doctor['nric']); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="text-muted small">Member Since</label>
                        <div class="fw-bold"><?php echo date('F j, Y', strtotime($doctor['created_at'])); ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Last Updated</label>
                        <div class="fw-bold"><?php echo date('F j, Y', strtotime($doctor['updated_at'])); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Statistics and Appointments -->
        <div class="col-lg-8">
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Total Appointments</h6>
                                    <h3 class="mb-0"><?php echo $stats['total_appointments'] ?? 0; ?></h3>
                                </div>
                                <div class="ms-3">
                                    <i class="fas fa-calendar-check fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Completed</h6>
                                    <h3 class="mb-0"><?php echo $stats['completed_appointments'] ?? 0; ?></h3>
                                </div>
                                <div class="ms-3">
                                    <i class="fas fa-check-circle fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Confirmed</h6>
                                    <h3 class="mb-0"><?php echo $stats['confirmed_appointments'] ?? 0; ?></h3>
                                </div>
                                <div class="ms-3">
                                    <i class="fas fa-clock fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Completion Rate</h6>
                                    <h3 class="mb-0"><?php echo round($stats['completion_rate'] ?? 0, 1); ?>%</h3>
                                </div>
                                <div class="ms-3">
                                    <i class="fas fa-percentage fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Appointments -->
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-day me-2"></i>Today's Appointments
                        <span class="badge bg-primary ms-2"><?php echo count($today_appointments); ?></span>
                    </h5>
                    <span class="text-muted small"><?php echo date('F j, Y'); ?></span>
                </div>
                <div class="card-body">
                    <?php if(!empty($today_appointments)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Patient</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($today_appointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['patient_phone']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo ($appointment['status'] == 'confirmed') ? 'success' : (($appointment['status'] == 'completed') ? 'primary' : 'secondary'); ?>">
                                                    <?php echo ucfirst($appointment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No appointments scheduled for today</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Appointments -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>Recent Appointments
                        <span class="badge bg-secondary ms-2"><?php echo count($recent_appointments); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if(!empty($recent_appointments)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Patient</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_appointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></td>
                                            <td><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo ($appointment['status'] == 'confirmed') ? 'success' : (($appointment['status'] == 'completed') ? 'primary' : 'secondary'); ?>">
                                                    <?php echo ucfirst($appointment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No recent appointments</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh today's appointments every 5 minutes
    setInterval(function() {
        if(document.querySelector('.card-header:has("Today\'s Appointments")')) {
            location.reload();
        }
    }, 300000); // 5 minutes
});
</script>
