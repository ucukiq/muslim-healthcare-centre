<?php
// Initialize the session
session_start();

// Check if the user is logged in as admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin'){
    header("location: ../index.php");
    exit;
}

// Include config file
require_once "../includes/config.php";

// Set page title
$page_title = "Manage Doctors";

// Include header
include("../includes/header.php");

// Get doctors from database
$doctors = [];
$query = "SELECT d.*, u.username 
          FROM doctors d 
          LEFT JOIN users u ON d.user_id = u.id 
          ORDER BY d.full_name";
$result = $conn->query($query);
if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
}

// Get specializations for dropdown
$specializations = [
    "General Practice",
    "Cardiology", 
    "Dermatology",
    "Pediatrics",
    "Orthopedics",
    "Gynecology",
    "Neurology",
    "Psychiatry",
    "Ophthalmology",
    "ENT",
    "Internal Medicine",
    "Surgery"
];

// Get statistics
$total_doctors = count($doctors);
$active_doctors = count(array_filter($doctors, function($d) { return $d['status'] == 'Active'; }));
$inactive_doctors = $total_doctors - $active_doctors;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Management - Healthcare System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --light-bg: #ecf0f1;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease;
            border-left: 4px solid var(--secondary-color);
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .doctor-table-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
        }

        .doctor-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--secondary-color);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }

        .action-btn {
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            border: none;
            margin: 0 0.2rem;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            transform: scale(1.05);
        }

        .btn-view {
            background-color: #17a2b8;
            color: white;
        }

        .btn-edit {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .search-filter-container {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .schedule-container {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }

        .day-checkbox {
            margin-right: 0.5rem;
        }

        .calendar-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
        }

        .performance-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 1rem;
        }

        .export-buttons {
            margin-bottom: 1rem;
        }

        .permissions-container {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }

        .permission-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }

        .permission-item:last-child {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .doctor-table-container {
                padding: 1rem;
            }
            
            .stats-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="mb-0">
                        <i class="fas fa-user-md me-3"></i>Doctor Management
                    </h1>
                    <p class="mb-0 mt-2">Manage healthcare providers and their schedules</p>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-light btn-lg" onclick="showAddDoctorModal()">
                        <i class="fas fa-plus-circle me-2"></i>Add New Doctor
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h3 class="mb-0"><?php echo $total_doctors; ?></h3>
                            <p class="mb-0 text-muted">Total Doctors</p>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h3 class="mb-0"><?php echo $active_doctors; ?></h3>
                            <p class="mb-0 text-muted">Active Doctors</p>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h3 class="mb-0"><?php echo $inactive_doctors; ?></h3>
                            <p class="mb-0 text-muted">Inactive Doctors</p>
                        </div>
                        <div class="text-danger">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h3 class="mb-0">24/7</h3>
                            <p class="mb-0 text-muted">Coverage</p>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="search-filter-container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search by name, specialization, or email...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="specializationFilter">
                        <option value="">All Specializations</option>
                        <?php foreach($specializations as $spec): ?>
                        <option value="<?php echo $spec; ?>"><?php echo $spec; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Export Buttons -->
        <div class="export-buttons">
            <button class="btn btn-success" onclick="exportToPDF()">
                <i class="fas fa-file-pdf me-2"></i>Export to PDF
            </button>
            <button class="btn btn-success" onclick="exportToExcel()">
                <i class="fas fa-file-excel me-2"></i>Export to Excel
            </button>
            <button class="btn btn-primary" onclick="printTable()">
                <i class="fas fa-print me-2"></i>Print
            </button>
        </div>

        <!-- Doctors Table -->
        <div class="doctor-table-container">
            <div class="table-responsive">
                <table class="table table-hover" id="doctorsTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Profile Photo</th>
                            <th>Doctor ID</th>
                            <th>Full Name</th>
                            <th>Phone</th>
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
                                <img src="../assets/images/doctors/<?php echo $doctor['profile_photo'] ?? 'default_doctor.svg'; ?>" 
                                     alt="<?php echo htmlspecialchars($doctor['full_name']); ?>" 
                                     class="doctor-photo">
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($doctor['doctor_id'] ?? 'N/A'); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($doctor['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($doctor['phone']); ?></td>
                            <td><?php echo htmlspecialchars($doctor['email']); ?></td>
                            <td><?php echo htmlspecialchars($doctor['specialization']); ?></td>
                            <td>
                                <span class="status-badge <?php echo ($doctor['status'] ?? 'Inactive') == 'Active' ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo htmlspecialchars($doctor['status'] ?? 'Inactive'); ?>
                                </span>
                            </td>
                            <td>
                                <button class="action-btn btn-view" onclick="viewDoctor(<?php echo $doctor['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="action-btn btn-edit" onclick="editDoctor(<?php echo $doctor['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn btn-delete" onclick="deleteDoctor(<?php echo $doctor['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Doctor Availability Calendar -->
        <div class="calendar-container">
            <h3 class="mb-4">
                <i class="fas fa-calendar-alt me-2"></i>Doctor Availability Calendar
            </h3>
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Add Doctor Modal -->
    <div class="modal fade" id="addDoctorModal" tabindex="-1" aria-labelledby="addDoctorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDoctorModalLabel">
                        <i class="fas fa-user-plus me-2"></i>Add New Doctor
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addDoctorForm" enctype="multipart/form-data">
                    <div class="modal-body">
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
                                <input type="text" class="form-control" name="nric" placeholder="Optional (XXXXXX-XX-XXXX)">
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

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Specialization *</label>
                                <select class="form-select" name="specialization" required>
                                    <option value="">Select Specialization</option>
                                    <?php foreach($specializations as $spec): ?>
                                    <option value="<?php echo $spec; ?>"><?php echo $spec; ?></option>
                                    <?php endforeach; ?>
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
                        <div class="schedule-container">
                            <h6 class="mb-3">Work Schedule</h6>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label">Working Days</label>
                                    <div class="d-flex flex-wrap">
                                        <div class="form-check">
                                            <input class="form-check-input day-checkbox" type="checkbox" name="work_days[]" value="Monday" id="day1">
                                            <label class="form-check-label" for="day1">Monday</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input day-checkbox" type="checkbox" name="work_days[]" value="Tuesday" id="day2">
                                            <label class="form-check-label" for="day2">Tuesday</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input day-checkbox" type="checkbox" name="work_days[]" value="Wednesday" id="day3">
                                            <label class="form-check-label" for="day3">Wednesday</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input day-checkbox" type="checkbox" name="work_days[]" value="Thursday" id="day4">
                                            <label class="form-check-label" for="day4">Thursday</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input day-checkbox" type="checkbox" name="work_days[]" value="Friday" id="day5">
                                            <label class="form-check-label" for="day5">Friday</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input day-checkbox" type="checkbox" name="work_days[]" value="Saturday" id="day6">
                                            <label class="form-check-label" for="day6">Saturday</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input day-checkbox" type="checkbox" name="work_days[]" value="Sunday" id="day7">
                                            <label class="form-check-label" for="day7">Sunday</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Morning Shift</label>
                                    <div class="d-flex">
                                        <input type="time" class="form-control me-2" name="start_time1" value="09:00">
                                        <span class="align-self-center">to</span>
                                        <input type="time" class="form-control ms-2" name="end_time1" value="12:00">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Evening Shift</label>
                                    <div class="d-flex">
                                        <input type="time" class="form-control me-2" name="start_time2" value="14:00">
                                        <span class="align-self-center">to</span>
                                        <input type="time" class="form-control ms-2" name="end_time2" value="18:00">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Login Credentials -->
                        <div class="row mb-3 mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Username *</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password *</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                        </div>

                        <!-- Permissions Settings -->
                        <div class="permissions-container">
                            <h6 class="mb-3">Doctor Permissions</h6>
                            <div class="permission-item">
                                <span>View Appointments</span>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="view_appointments" checked>
                                </div>
                            </div>
                            <div class="permission-item">
                                <span>Edit Appointment Status</span>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="edit_appointments" checked>
                                </div>
                            </div>
                            <div class="permission-item">
                                <span>Update Own Profile</span>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="update_profile" checked>
                                </div>
                            </div>
                            <div class="permission-item">
                                <span>Access Patient Details</span>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="access_patients">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Doctor
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
                <div class="modal-header">
                    <h5 class="modal-title" id="editDoctorModalLabel">
                        <i class="fas fa-user-edit me-2"></i>Edit Doctor
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editDoctorForm" enctype="multipart/form-data">
                    <input type="hidden" id="editDoctorId" name="doctor_id">
                    <div class="modal-body">
                        <!-- Edit form content will be populated dynamically -->
                        <div id="editFormContent">
                            <!-- Content will be loaded via JavaScript -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Update Doctor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Doctor Modal -->
    <div class="modal fade" id="viewDoctorModal" tabindex="-1" aria-labelledby="viewDoctorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewDoctorModalLabel">
                        <i class="fas fa-user me-2"></i>Doctor Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <img id="viewDoctorPhoto" src="../assets/images/doctors/default_doctor.svg" alt="Doctor Photo" class="img-fluid rounded-circle mb-3" style="max-width: 150px;">
                            <h5 id="viewDoctorName" class="mb-1">Doctor Name</h5>
                            <span id="viewDoctorSpecialization" class="badge bg-info">Specialization</span>
                        </div>
                        <div class="col-md-8">
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Doctor ID:</strong></div>
                                <div class="col-sm-8"><span id="viewDoctorId" class="badge bg-secondary">N/A</span></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>NRIC:</strong></div>
                                <div class="col-sm-8" id="viewDoctorNric">N/A</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Age:</strong></div>
                                <div class="col-sm-8" id="viewDoctorAge">N/A</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Email:</strong></div>
                                <div class="col-sm-8" id="viewDoctorEmail">N/A</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Phone:</strong></div>
                                <div class="col-sm-8" id="viewDoctorPhone">N/A</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Address:</strong></div>
                                <div class="col-sm-8" id="viewDoctorAddress">N/A</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Status:</strong></div>
                                <div class="col-sm-8" id="viewDoctorStatus">N/A</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Username:</strong></div>
                                <div class="col-sm-8" id="viewDoctorUsername">N/A</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Work Schedule:</strong></div>
                                <div class="col-sm-8" id="viewDoctorSchedule">N/A</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-warning" onclick="editDoctorFromView()">
                        <i class="fas fa-edit me-2"></i>Edit Doctor
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- jsPDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <!-- html2canvas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#doctorsTable').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[2, 'asc']] // Sort by name
            });

            // Initialize Calendar
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                events: [
                    // Sample events - these would come from database
                    {
                        title: 'Ahmad Ismail - On Leave',
                        start: '2024-01-15',
                        backgroundColor: '#dc3545'
                    },
                    {
                        title: 'Siti Aishah - Unavailable',
                        start: '2024-01-20',
                        backgroundColor: '#ffc107'
                    }
                ],
                dateClick: function(info) {
                    // Handle date click for marking availability
                    showAvailabilityModal(info.dateStr);
                }
            });
            calendar.render();

            // Initialize Performance Chart
            var ctx = document.getElementById('performanceChart').getContext('2d');
            var performanceChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Completed', 'Cancelled', 'Pending', 'No-show'],
                    datasets: [{
                        label: 'Appointments',
                        data: [120, 15, 8, 5],
                        backgroundColor: [
                            '#27ae60',
                            '#e74c3c',
                            '#f39c12',
                            '#95a5a6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Load recent activities
            loadRecentActivities();
        });

        // Search and Filter Functions
        document.getElementById('searchInput').addEventListener('keyup', function() {
            filterTable();
        });

        document.getElementById('statusFilter').addEventListener('change', function() {
            filterTable();
        });

        document.getElementById('specializationFilter').addEventListener('change', function() {
            filterTable();
        });

        function filterTable() {
            var searchValue = document.getElementById('searchInput').value.toLowerCase();
            var statusValue = document.getElementById('statusFilter').value;
            var specializationValue = document.getElementById('specializationFilter').value;
            var table = document.getElementById('doctorsTable');
            var rows = table.getElementsByTagName('tr');

            for (var i = 1; i < rows.length; i++) {
                var row = rows[i];
                var cells = row.getElementsByTagName('td');
                
                var name = cells[2].textContent.toLowerCase();
                var email = cells[4].textContent.toLowerCase();
                var specialization = cells[5].textContent.toLowerCase();
                var status = cells[6].textContent.trim();
                
                var matchesSearch = name.includes(searchValue) || 
                                  email.includes(searchValue) || 
                                  specialization.includes(searchValue);
                var matchesStatus = !statusValue || status === statusValue;
                var matchesSpecialization = !specializationValue || specialization === specializationValue.toLowerCase();
                
                if (matchesSearch && matchesStatus && matchesSpecialization) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }

        // Modal Functions
        function showAddDoctorModal() {
            $('#addDoctorModal').modal('show');
        }

        function viewDoctor(doctorId) {
            // Load doctor data via AJAX
            fetch(`get_doctor_data.php?id=${doctorId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        // Populate view modal with doctor data
                        document.getElementById('viewDoctorName').textContent = data.doctor.full_name;
                        document.getElementById('viewDoctorId').textContent = data.doctor.doctor_id;
                        document.getElementById('viewDoctorSpecialization').textContent = data.doctor.specialization;
                        document.getElementById('viewDoctorEmail').textContent = data.doctor.email;
                        document.getElementById('viewDoctorPhone').textContent = data.doctor.phone;
                        document.getElementById('viewDoctorStatus').textContent = data.doctor.status;
                        document.getElementById('viewDoctorAge').textContent = data.doctor.age;
                        document.getElementById('viewDoctorAddress').textContent = data.doctor.address || 'N/A';
                        document.getElementById('viewDoctorNric').textContent = data.doctor.nric || 'N/A';
                        document.getElementById('viewDoctorUsername').textContent = data.doctor.username || 'N/A';
                        
                        // Update profile photo
                        const photoElement = document.getElementById('viewDoctorPhoto');
                        if(data.doctor.profile_photo) {
                            photoElement.src = `../assets/images/doctors/${data.doctor.profile_photo}`;
                        } else {
                            photoElement.src = '../assets/images/doctors/default_doctor.svg';
                        }
                        
                        // Show modal
                        $('#viewDoctorModal').modal('show');
                    } else {
                        alert('Error loading doctor data: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading doctor data');
                });
        }

        function editDoctor(doctorId) {
            // Load doctor data for editing
            fetch(`get_doctor_data.php?id=${doctorId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        // Populate edit form
                        const editContent = `
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" name="full_name" value="${data.full_name}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">NRIC</label>
                                    <input type="text" class="form-control" name="nric" value="${data.nric || ''}">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Age *</label>
                                    <input type="number" class="form-control" name="age" value="${data.age}" min="25" max="70" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" value="${data.email}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" name="phone" value="${data.phone}" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" name="address" rows="2">${data.address || ''}</textarea>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Specialization *</label>
                                    <select class="form-select" name="specialization" required>
                                        <?php foreach($specializations as $spec): ?>
                                        <option value="<?php echo $spec; ?>" ${data.specialization === '<?php echo $spec; ?>' ? 'selected' : ''}>
                                            <?php echo $spec; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status *</label>
                                    <select class="form-select" name="status" required>
                                        <option value="Active" ${data.status === 'Active' ? 'selected' : ''}>Active</option>
                                        <option value="Inactive" ${data.status === 'Inactive' ? 'selected' : ''}>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label">Profile Photo</label>
                                    <input type="file" class="form-control" name="profile_photo" accept="image/*">
                                    <small class="text-muted">Leave empty to keep current photo</small>
                                </div>
                            </div>
                        `;
                        
                        document.getElementById('editFormContent').innerHTML = editContent;
                        document.getElementById('editDoctorId').value = doctorId;
                        $('#editDoctorModal').modal('show');
                    } else {
                        alert('Error loading doctor data: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading doctor data');
                });
        }

        function deleteDoctor(doctorId) {
            if(confirm('Are you sure you want to delete this doctor? This action cannot be undone.')) {
                // Implement delete functionality
                fetch('delete_doctor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `doctor_id=${doctorId}`
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert('Doctor deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error deleting doctor: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting doctor');
                });
            }
        }

        // Export Functions
        function exportToPDF() {
            // Implement PDF export
            alert('PDF export functionality would be implemented here');
        }

        function exportToExcel() {
            // Implement Excel export
            alert('Excel export functionality would be implemented here');
        }

        function printTable() {
            window.print();
        }

        // Load Recent Activities
        function loadRecentActivities() {
            const activities = [
                { icon: 'fa-user-plus', text: 'Ahmad Ismail added', time: '2 hours ago', color: 'text-success' },
                { icon: 'fa-edit', text: 'Siti Aishah profile updated', time: '5 hours ago', color: 'text-warning' },
                { icon: 'fa-calendar-check', text: 'Mohd Ali schedule updated', time: '1 day ago', color: 'text-info' },
                { icon: 'fa-trash', text: 'Fatimah deleted', time: '2 days ago', color: 'text-danger' }
            ];

            const activitiesHtml = activities.map(activity => `
                <div class="d-flex align-items-center mb-3">
                    <div class="me-3">
                        <i class="fas ${activity.icon} ${activity.color}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">${activity.text}</div>
                        <small class="text-muted">${activity.time}</small>
                    </div>
                </div>
            `).join('');

            document.getElementById('recentActivities').innerHTML = activitiesHtml;
        }

        // Form Submissions
        document.getElementById('addDoctorForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // Implement form submission
            alert('Add doctor form submission would be implemented here');
        });

        document.getElementById('editDoctorForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // Implement form submission
            alert('Edit doctor form submission would be implemented here');
        });
    </script>
</body>
</html>
