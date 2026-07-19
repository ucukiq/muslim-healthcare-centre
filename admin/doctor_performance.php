<?php
// Start session
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../login.php");
    exit;
}

// Only admin can view doctor performance
if($_SESSION["role"] !== 'admin'){
    $_SESSION['error_message'] = "Access denied. Only administrators can view doctor performance.";
    header("location: dashboard.php");
    exit;
}

// Include database connection
include_once "../includes/db_connection.php";

// Get doctor ID from URL parameter
$doctor_id = isset($_GET['id']) ? $_GET['id'] : '';

// Validate doctor ID
if(empty($doctor_id)){
    $_SESSION['error_message'] = "Doctor ID not provided.";
    header("location: manage_doctors.php");
    exit;
}

// Get doctor information
$doctor = null;
$sql = "SELECT d.*, u.username 
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

// Get date range from form (default to last 30 days)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Performance statistics
$stats = [];
$sql = "SELECT 
            COUNT(*) as total_appointments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_appointments,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) / COUNT(*) * 100 as completion_rate,
            AVG(CASE WHEN status = 'completed' THEN TIMESTAMPDIFF(MINUTE, appointment_time, updated_at) END) as avg_consultation_time
        FROM appointments 
        WHERE doctor_id = ? AND appointment_date BETWEEN ? AND ?";
        
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("iss", $doctor_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    $stmt->close();
}

// Daily performance data for chart
$daily_stats = [];
$sql = "SELECT 
            DATE(appointment_date) as date,
            COUNT(*) as total_appointments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments
        FROM appointments 
        WHERE doctor_id = ? AND appointment_date BETWEEN ? AND ?
        GROUP BY DATE(appointment_date)
        ORDER BY date ASC";
        
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("iss", $doctor_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $daily_stats[] = $row;
    }
    $stmt->close();
}

// Patient satisfaction (placeholder - would need rating system)
$patient_satisfaction = 4.5; // Placeholder value

// Top performing days
$best_days = [];
$sql = "SELECT 
            DAYNAME(appointment_date) as day_name,
            COUNT(*) as appointments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) / COUNT(*) * 100 as completion_rate
        FROM appointments 
        WHERE doctor_id = ? AND appointment_date BETWEEN ? AND ?
        GROUP BY DAYNAME(appointment_date)
        ORDER BY completion_rate DESC, appointments DESC
        LIMIT 3";
        
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("iss", $doctor_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $best_days[] = $row;
    }
    $stmt->close();
}

// Recent appointments with performance
$recent_appointments = [];
$sql = "SELECT a.*, p.full_name as patient_name, p.phone as patient_phone,
            TIMESTAMPDIFF(MINUTE, a.appointment_time, a.updated_at) as consultation_time
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.id 
        WHERE a.doctor_id = ? AND a.appointment_date BETWEEN ? AND ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC 
        LIMIT 20";
        
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("iss", $doctor_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $recent_appointments[] = $row;
    }
    $stmt->close();
}

// Set page title
$page_title = "Doctor Performance - " . htmlspecialchars($doctor['full_name']);

// Include header
include "../includes/header.php";
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Doctor Performance</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="manage_doctors.php">Doctors</a></li>
                            <li class="breadcrumb-item"><a href="doctor_profile.php?id=<?php echo $doctor['id']; ?>">
                                <?php echo htmlspecialchars($doctor['full_name']); ?>
                            </a></li>
                            <li class="breadcrumb-item active">Performance</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="doctor_profile.php?id=<?php echo $doctor['id']; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="id" value="<?php echo $doctor_id; ?>">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo htmlspecialchars($start_date); ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?php echo htmlspecialchars($end_date); ?>" required>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                </div>
                <div class="col-md-auto">
                    <a href="?id=<?php echo $doctor_id; ?>&start_date=<?php echo date('Y-m-d', strtotime('-30 days')); ?>&end_date=<?php echo date('Y-m-d'); ?>" 
                       class="btn btn-outline-secondary">
                        Last 30 Days
                    </a>
                </div>
                <div class="col-md-auto">
                    <a href="?id=<?php echo $doctor_id; ?>&start_date=<?php echo date('Y-m-01'); ?>&end_date=<?php echo date('Y-m-t'); ?>" 
                       class="btn btn-outline-secondary">
                        This Month
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Performance Overview -->
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
        <div class="col-md-3">
            <div class="card bg-info text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-0">Avg Consultation</h6>
                            <h3 class="mb-0"><?php echo round($stats['avg_consultation_time'] ?? 0); ?>m</h3>
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
                            <h6 class="mb-0">Patient Satisfaction</h6>
                            <h3 class="mb-0"><?php echo $patient_satisfaction; ?>/5</h3>
                        </div>
                        <div class="ms-3">
                            <i class="fas fa-star fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>Performance Trend
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="performanceChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Best Performing Days -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-trophy me-2"></i>Best Performing Days
                    </h5>
                </div>
                <div class="card-body">
                    <?php if(!empty($best_days)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach($best_days as $day): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($day['day_name']); ?></div>
                                        <small class="text-muted"><?php echo $day['appointments']; ?> appointments</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success"><?php echo round($day['completion_rate'], 1); ?>%</span>
                                        <div class="small text-muted"><?php echo $day['completed']; ?> completed</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No performance data available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Performance Insights -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>Performance Insights
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-success">Strengths</h6>
                        <ul class="list-unstyled small">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <?php if(($stats['completion_rate'] ?? 0) >= 80): ?>
                                    Excellent completion rate
                                <?php else: ?>
                                    Room for improvement in completion rate
                                <?php endif; ?>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <?php if(($stats['avg_consultation_time'] ?? 0) <= 30): ?>
                                    Efficient consultation time
                                <?php else: ?>
                                    Consider optimizing consultation duration
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="text-warning">Recommendations</h6>
                        <ul class="list-unstyled small">
                            <li class="mb-2">
                                <i class="fas fa-lightbulb text-warning me-2"></i>
                                Schedule more appointments on <?php echo !empty($best_days) ? htmlspecialchars($best_days[0]['day_name']) : 'peak days'; ?>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-lightbulb text-warning me-2"></i>
                                Maintain consistent appointment timing
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-lightbulb text-warning me-2"></i>
                                Consider patient feedback for improvement
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Appointments -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>Recent Appointments Performance
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
                                        <th>Duration</th>
                                        <th>Performance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_appointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></td>
                                            <td><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo ($appointment['status'] == 'completed') ? 'success' : (($appointment['status'] == 'confirmed') ? 'info' : 'secondary'); ?>">
                                                    <?php echo ucfirst($appointment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if($appointment['status'] == 'completed' && $appointment['consultation_time']): ?>
                                                    <?php echo round($appointment['consultation_time']); ?> min
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($appointment['status'] == 'completed'): ?>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-check-circle text-success me-1"></i>
                                                        <small class="text-success">Completed</small>
                                                    </div>
                                                <?php elseif($appointment['status'] == 'cancelled'): ?>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-times-circle text-danger me-1"></i>
                                                        <small class="text-danger">Cancelled</small>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-clock text-info me-1"></i>
                                                        <small class="text-info">Pending</small>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No appointments found in selected period</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prepare data for chart
    const dailyStats = <?php echo json_encode($daily_stats); ?>;
    const labels = dailyStats.map(item => new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
    const totalAppointments = dailyStats.map(item => item.total_appointments);
    const completedAppointments = dailyStats.map(item => item.completed_appointments);
    
    // Create performance chart
    const ctx = document.getElementById('performanceChart').getContext('2d');
    const performanceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Appointments',
                data: totalAppointments,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.1
            }, {
                label: 'Completed Appointments',
                data: completedAppointments,
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    
    // Date validation
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    startDateInput.addEventListener('change', function() {
        if(endDateInput.value && this.value > endDateInput.value) {
            endDateInput.value = this.value;
        }
    });
    
    endDateInput.addEventListener('change', function() {
        if(startDateInput.value && this.value < startDateInput.value) {
            startDateInput.value = this.value;
        }
    });
});
</script>
