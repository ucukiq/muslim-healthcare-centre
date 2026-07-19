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

// Get counts for dashboard
$counts = [
    'appointments' => 0,
    'doctors' => 0,
    'patients' => 0,
    'today_appointments' => 0,
    'pending_appointments' => 0,
    'no_shows' => 0
];

// Get appointment counts
$sql = "SELECT 
    (SELECT COUNT(*) FROM appointments) as total_appointments,
    (SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) = CURDATE()) as today_appointments,
    (SELECT COUNT(*) FROM appointments WHERE status = 'Pending') as pending_appointments,
    (SELECT COUNT(*) FROM appointments WHERE status = 'No Show') as no_shows,
    (SELECT COUNT(*) FROM doctors) as total_doctors,
    (SELECT COUNT(*) FROM patients) as total_patients";

if($result = $conn->query($sql)){
    if($row = $result->fetch_assoc()){
        $counts = [
            'appointments' => $row['total_appointments'],
            'doctors' => $row['total_doctors'],
            'patients' => $row['total_patients'],
            'today_appointments' => $row['today_appointments'],
            'pending_appointments' => $row['pending_appointments'],
            'no_shows' => $row['no_shows']
        ];
    }
    $result->free();
}

// Get recent appointments
$recent_appointments = [];
$sql = "SELECT a.*, p.full_name as patient_name, d.full_name as doctor_name 
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.id 
        JOIN doctors d ON a.doctor_id = d.id 
        ORDER BY a.appointment_date DESC, a.start_time DESC 
        LIMIT 5";

if($result = $conn->query($sql)){
    while($row = $result->fetch_assoc()){
        $recent_appointments[] = $row;
    }
    $result->free();
}

// Set page title
$page_title = "Admin Dashboard";

// Include header
include("../includes/header.php");
?>

<div class="container-fluid py-4">
    <!-- Welcome Section with Logo -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 bg-primary text-white">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-md-row align-items-center text-center text-md-start">
                        <div class="mb-3 mb-md-0 me-md-4">
                            <img src="../images/logo.png.png" 
                                 alt="Muslim Healthcare Center Logo" 
                                 class="img-fluid d-block mx-auto" 
                                 style="max-height: 80px; width: auto; max-width: 200px;">
                        </div>
                        <div class="flex-grow-1">
                            <h2 class="mb-1">Muslim Healthcare Center</h2>
                            <p class="text-muted mb-0">Admin Dashboard</p>
                        </div>
                        <div class="ms-auto">
                            <div class="alert alert-primary mb-0" role="alert">
                                <i class="fas fa-user-shield me-2"></i>
                                Welcome back, <?php echo htmlspecialchars($_SESSION["username"]); ?>!
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
            <span class="d-block d-sm-inline">Last login: <?php echo date('M j, Y g:i A', strtotime($_SESSION['last_login'] ?? 'now')); ?></span>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <!-- Appointments Card -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-start border-primary border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Appointments</h6>
                            <h3 class="mb-0"><?php echo $counts['appointments']; ?></h3>
                            <small class="text-muted"><?php echo $counts['today_appointments']; ?> today</small>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="fas fa-calendar-alt text-primary"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="appointments.php" class="btn btn-sm btn-outline-primary">View All</a>
                        <a href="appointments.php?status=Pending" class="btn btn-sm btn-outline-warning ms-2">
                            <?php echo $counts['pending_appointments']; ?> Pending
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Doctors Card -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-start border-success border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Doctors</h6>
                            <h3 class="mb-0"><?php echo $counts['doctors']; ?></h3>
                            <small class="text-muted">Active professionals</small>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="fas fa-user-md text-success"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="doctors.php" class="btn btn-sm btn-outline-success">Manage Doctors</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Patients Card -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-start border-info border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Patients</h6>
                            <h3 class="mb-0"><?php echo $counts['patients']; ?></h3>
                            <small class="text-muted">Registered patients</small>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded">
                            <i class="fas fa-hospital-user text-info"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="patients.php" class="btn btn-sm btn-outline-info">Manage Patients</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- No Shows Card -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-start border-danger border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">No Shows</h6>
                            <h3 class="mb-0"><?php echo $counts['no_shows']; ?></h3>
                            <small class="text-muted">Missed appointments</small>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded">
                            <i class="fas fa-user-times text-danger"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="no_show_appointments.php" class="btn btn-sm btn-outline-danger">
                            Manage No Shows
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Appointments -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Appointments</h5>
                    <a href="appointments.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recent_appointments) > 0): ?>
                                    <?php foreach ($recent_appointments as $appt): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold"><?php echo date('M j, Y', strtotime($appt['appointment_date'])); ?></span>
                                                    <small class="text-muted"><?php echo date('h:i A', strtotime($appt['start_time'])); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($appt['patient_name']); ?></td>
                                            <td><?php 
                                                // Remove any existing Dr. prefix to avoid duplication
                                                $doctorName = preg_replace('/^Dr\.?\s*/i', '', $appt['doctor_name']);
                                                echo 'Dr. ' . htmlspecialchars(trim($doctorName)); 
                                            ?></td>
                                            <td>
                                                <?php 
                                                $status_class = [
                                                    'Pending' => 'warning',
                                                    'Confirmed' => 'primary',
                                                    'Completed' => 'success',
                                                    'Cancelled' => 'secondary',
                                                    'No Show' => 'danger'
                                                ][$appt['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <?php echo $appt['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="appointment_details.php?id=<?php echo $appt['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($appt['status'] === 'Pending'): ?>
                                                        <button class="btn btn-sm btn-outline-success mark-confirmed" 
                                                                data-appointment-id="<?php echo $appt['id']; ?>"
                                                                title="Mark as Confirmed">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($appt['status'] !== 'No Show' && $appt['status'] !== 'Completed'): ?>
                                                        <button class="btn btn-sm btn-outline-warning mark-no-show" 
                                                                data-appointment-id="<?php echo $appt['id']; ?>"
                                                                data-patient-name="<?php echo htmlspecialchars($appt['patient_name']); ?>"
                                                                title="Mark as No Show">
                                                            <i class="fas fa-user-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-calendar-times fa-2x mb-2"></i>
                                                <p class="mb-0">No recent appointments found</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- No Show Confirmation Modal -->
<div class="modal fade" id="noShowModal" tabindex="-1" aria-labelledby="noShowModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="noShowForm" action="actions/mark_no_show.php" method="POST" onsubmit="return false;">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title" id="noShowModalLabel">Mark as No Show</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="appointment_id" id="noShowAppointmentId">
                    <p>You are about to mark <strong id="patientName"></strong>'s appointment as <span class="text-danger">No Show</span>.</p>
                    <div class="mb-3">
                        <label for="noShowNotes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="noShowNotes" name="notes" rows="3" 
                                placeholder="Add any additional notes about this no-show..."></textarea>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action cannot be undone. The patient's no-show count will be incremented.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-user-times me-1"></i> Mark as No Show
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include("../includes/footer.php"); ?>

<script>
// Handle No Show button click
document.querySelectorAll('.mark-no-show').forEach(button => {
    button.addEventListener('click', function() {
        const appointmentId = this.dataset.appointmentId;
        const patientName = this.dataset.patientName;
        
        // Set values in the modal
        document.getElementById('noShowAppointmentId').value = appointmentId;
        document.getElementById('patientName').textContent = patientName;
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('noShowModal'));
        modal.show();
    });
});

// Handle form submission
const noShowForm = document.getElementById('noShowForm');
if (noShowForm) {
    noShowForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
        
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Show success message
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.role = 'alert';
                alert.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>
                    ${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                
                // Insert alert at the top of the container
                const container = document.querySelector('.container-fluid');
                container.insertBefore(alert, container.firstChild);
                
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('noShowModal'));
                modal.hide();
                
                // Reload the page after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                // Show error message
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger alert-dismissible fade show';
                alert.role = 'alert';
                alert.innerHTML = `
                    <i class="fas fa-exclamation-circle me-2"></i>
                    ${data.message || 'Failed to mark as no show'}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                
                // Insert alert at the top of the container
                const container = document.querySelector('.container-fluid');
                container.insertBefore(alert, container.firstChild);
                
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    });
}
</script>
<?php
// Include footer
include("../includes/footer.php");
?>
