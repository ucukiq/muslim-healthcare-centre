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

// Set page title
$page_title = "Manage Doctors";

// Include header
include("../includes/header.php");

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if($action == 'add_doctor') {
        // Add new doctor logic here
        $_SESSION['success'] = "Doctor added successfully!";
        header('Location: manage_doctors.php');
        exit();
    } elseif($action == 'edit_doctor') {
        // Edit doctor logic here
        $_SESSION['success'] = "Doctor updated successfully!";
        header('Location: manage_doctors.php');
        exit();
    } elseif($action == 'delete_doctor') {
        // Delete doctor logic here
        $_SESSION['success'] = "Doctor deleted successfully!";
        header('Location: manage_doctors.php');
        exit();
    }
}

// Sample doctor data (replace with database query)
$doctors = [
    [
        'id' => 'DOC001',
        'name' => 'Dr. Ahmad bin Ibrahim',
        'phone' => '+6012-345-6789',
        'email' => 'ahmad@healthcare.com',
        'specialization' => 'Cardiology',
        'status' => 'Active',
        'profile_photo' => 'doctor1.jpg'
    ],
    [
        'id' => 'DOC002', 
        'name' => 'Dr. Siti Aminah',
        'phone' => '+6013-987-6543',
        'email' => 'siti@healthcare.com',
        'specialization' => 'Pediatrics',
        'status' => 'Active',
        'profile_photo' => 'doctor2.jpg'
    ],
    [
        'id' => 'DOC003',
        'name' => 'Dr. Mohd Razif',
        'phone' => '+6014-555-1234',
        'email' => 'razif@healthcare.com',
        'specialization' => 'Orthopedics',
        'status' => 'Inactive',
        'profile_photo' => 'doctor3.jpg'
    ]
];
?>

<!-- Success/Error Messages -->
<?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-user-md me-2 text-primary"></i>
                    Manage Doctors
                </h1>
                <div>
                    <button class="btn btn-success me-2" onclick="exportToExcel()">
                        <i class="fas fa-file-excel me-1"></i> Export Excel
                    </button>
                    <button class="btn btn-danger me-2" onclick="exportToPDF()">
                        <i class="fas fa-file-pdf me-1"></i> Export PDF
                    </button>
                    <button class="btn btn-info me-2" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDoctorModal">
                        <i class="fas fa-plus me-1"></i> Add New Doctor
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" class="form-control" id="searchInput" placeholder="Search by name, specialization, or status...">
            </div>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="statusFilter">
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="specializationFilter">
                <option value="all">All Specializations</option>
                <option value="cardiology">Cardiology</option>
                <option value="pediatrics">Pediatrics</option>
                <option value="orthopedics">Orthopedics</option>
                <option value="general">General Practice</option>
            </select>
        </div>
    </div>

    <!-- Doctor List Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>
                Doctor List
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="doctorsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Profile Photo</th>
                            <th>Doctor ID</th>
                            <th>Full Name</th>
                            <th>Phone Number</th>
                            <th>Email</th>
                            <th>Specialization</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($doctors as $doctor): ?>
                        <tr>
                            <td>
                                <img src="../assets/images/doctors/<?php echo $doctor['profile_photo']; ?>" 
                                     alt="<?php echo htmlspecialchars($doctor['name']); ?>" 
                                     class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo $doctor['id']; ?></span>
                            </td>
                            <td>
                                <?php 
                                $display_name = $doctor['name'];
                                // Remove any existing 'Dr. ' from the name
                                $display_name = preg_replace('/^Dr\.\s*/i', '', $display_name);
                                // Add 'Dr. ' prefix
                                echo 'Dr. ' . htmlspecialchars(trim($display_name)); 
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($doctor['phone']); ?></td>
                            <td><?php echo htmlspecialchars($doctor['email']); ?></td>
                            <td><?php echo htmlspecialchars($doctor['specialization']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $doctor['status'] == 'Active' ? 'success' : 'danger'; ?>">
                                    <?php echo $doctor['status']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="doctor_profile.php?id=<?php echo $doctor['id']; ?>" 
                                       class="btn btn-outline-primary" title="View Profile">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if($_SESSION["role"] == 'admin'): ?>
                                        <a href="edit_doctor.php?id=<?php echo $doctor['id']; ?>" 
                                           class="btn btn-outline-warning" title="Edit Profile">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="doctor_performance.php?id=<?php echo $doctor['id']; ?>" 
                                           class="btn btn-outline-info" title="View Performance">
                                            <i class="fas fa-chart-line"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" onclick="deleteDoctor('<?php echo $doctor['id']; ?>')" title="Delete Doctor">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Doctor Statistics Cards -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?php echo count($doctors); ?></h4>
                            <p class="mb-0">Total Doctors</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-user-md fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?php echo count(array_filter($doctors, fn($d) => $d['status'] == 'Active')); ?></h4>
                            <p class="mb-0">Active Doctors</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?php echo count(array_filter($doctors, fn($d) => $d['status'] == 'Inactive')); ?></h4>
                            <p class="mb-0">Inactive Doctors</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">5</h4>
                            <p class="mb-0">Departments</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-hospital fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Doctor Modal -->
<div class="modal fade" id="addDoctorModal" tabindex="-1" aria-labelledby="addDoctorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addDoctorModalLabel">
                    <i class="fas fa-user-plus me-2"></i>
                    Add New Doctor
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="manage_doctors.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_doctor">
                    
                    <!-- Profile Photo Upload -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Profile Photo</label>
                            <input type="file" class="form-control" name="profile_photo" accept="image/*">
                            <small class="text-muted">Upload doctor's profile photo (JPG, PNG, max 2MB)</small>
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NRIC</label>
                            <input type="text" class="form-control" name="nric" placeholder="Optional">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Age *</label>
                            <input type="number" class="form-control" name="age" min="25" max="70" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" name="phone" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="2" placeholder="Optional"></textarea>
                        </div>
                    </div>

                    <!-- Professional Information -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Specialization *</label>
                            <select class="form-select" name="specialization" required>
                                <option value="">Select Specialization</option>
                                <option value="Cardiology">Cardiology</option>
                                <option value="Pediatrics">Pediatrics</option>
                                <option value="Orthopedics">Orthopedics</option>
                                <option value="General Practice">General Practice</option>
                                <option value="Dermatology">Dermatology</option>
                                <option value="Neurology">Neurology</option>
                                <option value="Gynecology">Gynecology</option>
                                <option value="Psychiatry">Psychiatry</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status *</label>
                            <select class="form-select" name="status" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <!-- Work Schedule -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Work Schedule</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label small">Working Days</label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="work_days[]" value="Mon" id="day1">
                                        <label class="form-check-label" for="day1">Mon</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="work_days[]" value="Tue" id="day2">
                                        <label class="form-check-label" for="day2">Tue</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="work_days[]" value="Wed" id="day3">
                                        <label class="form-check-label" for="day3">Wed</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="work_days[]" value="Thu" id="day4">
                                        <label class="form-check-label" for="day4">Thu</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="work_days[]" value="Fri" id="day5">
                                        <label class="form-check-label" for="day5">Fri</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="work_days[]" value="Sat" id="day6">
                                        <label class="form-check-label" for="day6">Sat</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="work_days[]" value="Sun" id="day7">
                                        <label class="form-check-label" for="day7">Sun</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Time Slots</label>
                                    <div class="mb-2">
                                        <input type="time" class="form-control" name="start_time1" value="09:00">
                                        <small class="text-muted">Start Time</small>
                                    </div>
                                    <div class="mb-2">
                                        <input type="time" class="form-control" name="end_time1" value="12:00">
                                        <small class="text-muted">End Time</small>
                                    </div>
                                    <div class="mb-2">
                                        <input type="time" class="form-control" name="start_time2" value="14:00">
                                        <small class="text-muted">Start Time 2</small>
                                    </div>
                                    <div>
                                        <input type="time" class="form-control" name="end_time2" value="18:00">
                                        <small class="text-muted">End Time 2</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Login Credentials -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Username *</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password *</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        Save Doctor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Doctor Modal -->
<div class="modal fade" id="editDoctorModal" tabindex="-1" aria-labelledby="editDoctorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editDoctorModalLabel">
                    <i class="fas fa-user-edit me-2"></i>
                    Edit Doctor
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="manage_doctors.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_doctor">
                    <input type="hidden" id="editDoctorId" name="doctor_id">
                    
                    <!-- Edit form fields will be populated dynamically -->
                    <div id="editFormContent">
                        <!-- Content will be loaded via JavaScript -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i>
                        Update Doctor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Doctor Availability Calendar Modal -->
<div class="modal fade" id="calendarModal" tabindex="-1" aria-labelledby="calendarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="calendarModalLabel">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Doctor Availability Calendar
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="availabilityCalendar">
                    <!-- Calendar will be rendered here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" onclick="addLeaveDate()">
                    <i class="fas fa-plus me-1"></i>
                    Add Leave/Unavailable Date
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Doctor Performance Dashboard Modal -->
<div class="modal fade" id="performanceModal" tabindex="-1" aria-labelledby="performanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="performanceModalLabel">
                    <i class="fas fa-chart-line me-2"></i>
                    Doctor Performance Dashboard
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="performanceContent">
                    <!-- Performance charts and stats will be rendered here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Permissions Settings Modal -->
<div class="modal fade" id="permissionsModal" tabindex="-1" aria-labelledby="permissionsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="permissionsModalLabel">
                    <i class="fas fa-shield-alt me-2"></i>
                    Doctor Permissions Settings
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="permissionsForm">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="perm_view_appointments" checked>
                        <label class="form-check-label" for="perm_view_appointments">
                            View Appointments
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="perm_edit_appointments" checked>
                        <label class="form-check-label" for="perm_edit_appointments">
                            Edit Appointment Status
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="perm_update_profile" checked>
                        <label class="form-check-label" for="perm_update_profile">
                            Update Own Profile
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="perm_access_patients">
                        <label class="form-check-label" for="perm_access_patients">
                            Access Patient Details
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark" onclick="savePermissions()">
                    <i class="fas fa-save me-1"></i>
                    Save Permissions
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom styles for the Manage Doctors page */
.table th {
    font-weight: 600;
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
    cursor: pointer;
}

.badge {
    font-size: 0.85em;
    padding: 0.5em 0.75em;
}

.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: box-shadow 0.15s ease-in-out;
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.modal-header {
    border-radius: 10px 10px 0 0;
}

.modal-content {
    border: none;
    border-radius: 10px;
}

.btn-group-sm > .btn, .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.input-group-text {
    background-color: #f8f9fa;
    border-color: #ced4da;
}

/* Calendar styles */
.calendar-container {
    max-height: 500px;
    overflow-y: auto;
}

.calendar-day {
    min-height: 80px;
    border: 1px solid #dee2e6;
    padding: 5px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.calendar-day:hover {
    background-color: #f8f9fa;
}

.calendar-day.unavailable {
    background-color: #f8d7da;
}

.calendar-day.leave {
    background-color: #fff3cd;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .modal-dialog {
        margin: 1rem;
    }
}

/* Loading spinner */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

/* Custom scrollbar */
.table-responsive::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Search functionality
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#doctorsTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Filter functionality
document.getElementById('statusFilter').addEventListener('change', filterTable);
document.getElementById('specializationFilter').addEventListener('change', filterTable);

function filterTable() {
    const statusFilter = document.getElementById('statusFilter').value;
    const specializationFilter = document.getElementById('specializationFilter').value;
    const rows = document.querySelectorAll('#doctorsTable tbody tr');
    
    rows.forEach(row => {
        const status = row.cells[6].textContent.trim();
        const specialization = row.cells[5].textContent.trim();
        
        const statusMatch = statusFilter === 'all' || status.toLowerCase() === statusFilter.toLowerCase();
        const specializationMatch = specializationFilter === 'all' || specialization.toLowerCase() === specializationFilter.toLowerCase();
        
        row.style.display = statusMatch && specializationMatch ? '' : 'none';
    });
}

// View Doctor
function viewDoctor(doctorId) {
    // Implement view doctor functionality
    alert('View Doctor: ' + doctorId);
}

// Edit Doctor
function editDoctor(doctorId) {
    // Load doctor data and populate edit form
    const modal = new bootstrap.Modal(document.getElementById('editDoctorModal'));
    
    // Simulate loading doctor data (replace with actual AJAX call)
    const doctorData = {
        id: doctorId,
        name: 'Dr. Ahmad bin Ibrahim',
        nric: '123456-78-9012',
        age: 45,
        email: 'ahmad@healthcare.com',
        phone: '+6012-345-6789',
        address: '123 Healthcare Street, KL',
        specialization: 'Cardiology',
        status: 'Active'
    };
    
    const editFormContent = `
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Full Name *</label>
                <input type="text" class="form-control" name="full_name" value="${doctorData.name}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">NRIC</label>
                <input type="text" class="form-control" name="nric" value="${doctorData.nric}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Age *</label>
                <input type="number" class="form-control" name="age" value="${doctorData.age}" min="25" max="70" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Email *</label>
                <input type="email" class="form-control" name="email" value="${doctorData.email}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Phone Number *</label>
                <input type="tel" class="form-control" name="phone" value="${doctorData.phone}" required>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-12">
                <label class="form-label">Address</label>
                <textarea class="form-control" name="address" rows="2">${doctorData.address}</textarea>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Specialization *</label>
                <select class="form-select" name="specialization" required>
                    <option value="Cardiology" ${doctorData.specialization === 'Cardiology' ? 'selected' : ''}>Cardiology</option>
                    <option value="Pediatrics" ${doctorData.specialization === 'Pediatrics' ? 'selected' : ''}>Pediatrics</option>
                    <option value="Orthopedics" ${doctorData.specialization === 'Orthopedics' ? 'selected' : ''}>Orthopedics</option>
                    <option value="General Practice" ${doctorData.specialization === 'General Practice' ? 'selected' : ''}>General Practice</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Status *</label>
                <select class="form-select" name="status" required>
                    <option value="Active" ${doctorData.status === 'Active' ? 'selected' : ''}>Active</option>
                    <option value="Inactive" ${doctorData.status === 'Inactive' ? 'selected' : ''}>Inactive</option>
                </select>
            </div>
        </div>
    `;
    
    document.getElementById('editFormContent').innerHTML = editFormContent;
    document.getElementById('editDoctorId').value = doctorId;
    modal.show();
}

// Delete Doctor
function deleteDoctor(doctorId) {
    if(confirm('Are you sure you want to delete this doctor? This action cannot be undone.')) {
        // Implement delete functionality
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'manage_doctors.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_doctor';
        
        const doctorIdInput = document.createElement('input');
        doctorIdInput.type = 'hidden';
        doctorIdInput.name = 'doctor_id';
        doctorIdInput.value = doctorId;
        
        form.appendChild(actionInput);
        form.appendChild(doctorIdInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Export to Excel
function exportToExcel() {
    // Implement Excel export functionality
    alert('Export to Excel functionality would be implemented here');
}

// Export to PDF
function exportToPDF() {
    // Implement PDF export functionality
    alert('Export to PDF functionality would be implemented here');
}

// Calendar functionality
function showCalendar(doctorId) {
    const modal = new bootstrap.Modal(document.getElementById('calendarModal'));
    
    // Generate calendar HTML
    const calendarHTML = generateCalendar();
    document.getElementById('availabilityCalendar').innerHTML = calendarHTML;
    
    modal.show();
}

function generateCalendar() {
    const today = new Date();
    const currentMonth = today.getMonth();
    const currentYear = today.getFullYear();
    
    let calendarHTML = '<div class="calendar-container">';
    calendarHTML += '<h6 class="mb-3">' + new Date(currentYear, currentMonth).toLocaleString('default', { month: 'long', year: 'numeric' }) + '</h6>';
    calendarHTML += '<div class="row">';
    
    // Day headers
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    days.forEach(day => {
        calendarHTML += '<div class="col text-center fw-bold">' + day + '</div>';
    });
    
    // Calendar days
    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    
    for(let i = 0; i < firstDay; i++) {
        calendarHTML += '<div class="col"></div>';
    }
    
    for(let day = 1; day <= daysInMonth; day++) {
        const isToday = day === today.getDate();
        const isUnavailable = Math.random() > 0.8; // Random unavailable days for demo
        const isLeave = Math.random() > 0.9; // Random leave days for demo
        
        let dayClass = 'calendar-day border rounded p-2 mb-2';
        if(isUnavailable) dayClass += ' unavailable';
        if(isLeave) dayClass += ' leave';
        if(isToday) dayClass += ' border-primary border-2';
        
        calendarHTML += '<div class="col ' + dayClass + '" onclick="toggleAvailability(this, ' + day + ')">';
        calendarHTML += '<div class="fw-bold">' + day + '</div>';
        if(isUnavailable) calendarHTML += '<small class="text-danger">Unavailable</small>';
        if(isLeave) calendarHTML += '<small class="text-warning">Leave</small>';
        calendarHTML += '</div>';
    }
    
    calendarHTML += '</div></div>';
    return calendarHTML;
}

function toggleAvailability(element, day) {
    element.classList.toggle('unavailable');
    element.classList.toggle('leave');
}

function addLeaveDate() {
    alert('Add leave date functionality would be implemented here');
}

// Performance Dashboard
function showPerformance(doctorId) {
    const modal = new bootstrap.Modal(document.getElementById('performanceModal'));
    
    // Generate performance charts
    const performanceHTML = generatePerformanceCharts();
    document.getElementById('performanceContent').innerHTML = performanceHTML;
    
    modal.show();
}

function generatePerformanceCharts() {
    let html = '<div class="row">';
    
    // Stats cards
    html += '<div class="col-md-3 mb-3">';
    html += '<div class="card bg-primary text-white">';
    html += '<div class="card-body text-center">';
    html += '<h3>156</h3>';
    html += '<p class="mb-0">Total Appointments</p>';
    html += '</div></div></div>';
    
    html += '<div class="col-md-3 mb-3">';
    html += '<div class="card bg-success text-white">';
    html += '<div class="card-body text-center">';
    html += '<h3>142</h3>';
    html += '<p class="mb-0">Completed</p>';
    html += '</div></div></div>';
    
    html += '<div class="col-md-3 mb-3">';
    html += '<div class="card bg-warning text-white">';
    html += '<div class="card-body text-center">';
    html += '<h3>8</h3>';
    html += '<p class="mb-0">Cancelled</p>';
    html += '</div></div></div>';
    
    html += '<div class="col-md-3 mb-3">';
    html += '<div class="card bg-info text-white">';
    html += '<div class="card-body text-center">';
    html += '<h3>4.8</h3>';
    html += '<p class="mb-0">Avg Rating</p>';
    html += '</div></div></div>';
    
    html += '</div><div class="row">';
    
    // Chart
    html += '<div class="col-md-6">';
    html += '<canvas id="appointmentChart"></canvas>';
    html += '</div>';
    
    html += '<div class="col-md-6">';
    html += '<canvas id="ratingChart"></canvas>';
    html += '</div>';
    
    html += '</div>';
    
    return html;
}

// Permissions Settings
function showPermissions(doctorId) {
    const modal = new bootstrap.Modal(document.getElementById('permissionsModal'));
    modal.show();
}

function savePermissions() {
    alert('Permissions saved successfully!');
    bootstrap.Modal.getInstance(document.getElementById('permissionsModal')).hide();
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php
// Include footer
include("../includes/footer.php");
?>
