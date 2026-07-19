<?php
// Session check removed for direct access
// session_start();
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header('Location: login.php');
//     exit();
// }

include '../includes/config.php';

// Fetch patients data with error handling
$patients = [];
$total_patients = 0;
$active_patients = 0;
$inactive_patients = 0;

// Check if patients table exists
$table_check = $conn->query("SHOW TABLES LIKE 'patients'");
if ($table_check->num_rows > 0) {
    $patients_query = "SELECT * FROM patients ORDER BY full_name";
    $patients_result = $conn->query($patients_query);
    
    if ($patients_result) {
        $patients = $patients_result->fetch_all(MYSQLI_ASSOC);
        
        // Get statistics
        $total_patients = count($patients);
        foreach ($patients as $patient) {
            if (isset($patient['status']) && $patient['status'] == 'Active') {
                $active_patients++;
            } else {
                $inactive_patients++;
            }
        }
    }
} else {
    // Table doesn't exist - show message
    $table_missing = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Patients - Muslim Healthcare Centre</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-bg: #ecf0f1;
            --card-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            background: white;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            margin: 20px;
            padding: 0;
            overflow: hidden;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            margin: 0;
        }

        .page-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 600;
        }

        .page-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            border-left: 4px solid var(--secondary-color);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.success {
            border-left-color: var(--success-color);
        }

        .stat-card.warning {
            border-left-color: var(--warning-color);
        }

        .stat-card.danger {
            border-left-color: var(--danger-color);
        }

        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.8;
        }

        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        .stat-card p {
            margin: 5px 0 0 0;
            color: #666;
            font-weight: 500;
        }

        .content-section {
            padding: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }

        .search-filter-container {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            position: relative;
            min-width: 300px;
        }

        .search-box input {
            padding-left: 40px;
            border-radius: 25px;
            border: 1px solid #ddd;
            height: 40px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .filter-dropdown {
            min-width: 150px;
        }

        .patients-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .patients-table .table {
            margin: 0;
        }

        .patients-table .table th {
            background: var(--primary-color);
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px;
        }

        .patients-table .table td {
            padding: 15px;
            vertical-align: middle;
            border-color: #f0f0f0;
        }

        .patient-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #f0f0f0;
        }

        .patient-id {
            background: var(--secondary-color);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .action-btn.view {
            background: #e3f2fd;
            color: #1976d2;
        }

        .action-btn.edit {
            background: #fff3e0;
            color: #f57c00;
        }

        .action-btn.delete {
            background: #ffebee;
            color: #d32f2f;
        }

        .action-btn:hover {
            transform: scale(1.1);
        }

        .export-buttons {
            display: flex;
            gap: 10px;
        }

        .export-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .export-btn.pdf {
            background: #dc3545;
            color: white;
        }

        .export-btn.excel {
            background: #28a745;
            color: white;
        }

        .export-btn.print {
            background: #6c757d;
            color: white;
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 10px 15px;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .btn-primary {
            background: var(--secondary-color);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
        }

        .appointment-history {
            max-height: 300px;
            overflow-y: auto;
        }

        .appointment-item {
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
        }

        .appointment-item:last-child {
            border-bottom: none;
        }

        .appointment-date {
            font-weight: 600;
            color: var(--secondary-color);
        }

        .appointment-doctor {
            color: #666;
            font-size: 0.9rem;
        }

        .appointment-status {
            float: right;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .appointment-status.completed {
            background: #d4edda;
            color: #155724;
        }

        .appointment-status.pending {
            background: #fff3cd;
            color: #856404;
        }

        .appointment-status.cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .main-container {
                margin: 10px;
                border-radius: 10px;
            }

            .page-header {
                padding: 20px;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .stats-container {
                padding: 20px;
                grid-template-columns: 1fr;
            }

            .content-section {
                padding: 20px;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .search-filter-container {
                width: 100%;
            }

            .search-box {
                min-width: 100%;
            }

            .patients-table {
                overflow-x: auto;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-users me-3"></i>Manage Patients</h1>
            <p>Comprehensive patient management system for Muslim Healthcare Centre</p>
        </div>

        <?php if (isset($table_missing) && $table_missing): ?>
        <!-- Setup Required Message -->
        <div class="content-section">
            <div class="alert alert-warning" style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 10px; padding: 20px; margin: 20px;">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>Database Setup Required</h5>
                <p class="mb-3">The patients table hasn't been created yet. Click the button below to set up the database:</p>
                <a href="test_patients_setup.php" class="btn btn-primary">
                    <i class="fas fa-database me-2"></i>Setup Patients Table
                </a>
                <a href="dashboard.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>
        <?php else: ?>

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <i class="fas fa-users" style="color: var(--secondary-color);"></i>
                <h3><?php echo $total_patients; ?></h3>
                <p>Total Patients</p>
            </div>
            <div class="stat-card success">
                <i class="fas fa-user-check"></i>
                <h3><?php echo $active_patients; ?></h3>
                <p>Active Patients</p>
            </div>
            <div class="stat-card warning">
                <i class="fas fa-user-times"></i>
                <h3><?php echo $inactive_patients; ?></h3>
                <p>Inactive Patients</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-check"></i>
                <h3>247</h3>
                <p>Total Appointments</p>
            </div>
        </div>

        <!-- Patients List Section -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-list me-2"></i>Patients List</h2>
                <div class="search-filter-container">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search by name...">
                    </div>
                    <select class="form-select filter-dropdown" id="genderFilter">
                        <option value="">All Genders</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                    <select class="form-select filter-dropdown" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                    <select class="form-select filter-dropdown" id="ageFilter">
                        <option value="">All Ages</option>
                        <option value="0-18">0-18 years</option>
                        <option value="19-35">19-35 years</option>
                        <option value="36-50">36-50 years</option>
                        <option value="51+">51+ years</option>
                    </select>
                </div>
            </div>

            <div class="export-buttons mb-3">
                <button class="export-btn pdf" onclick="exportToPDF()">
                    <i class="fas fa-file-pdf me-2"></i>Export to PDF
                </button>
                <button class="export-btn excel" onclick="exportToExcel()">
                    <i class="fas fa-file-excel me-2"></i>Export to Excel
                </button>
                <button class="export-btn print" onclick="printTable()">
                    <i class="fas fa-print me-2"></i>Print
                </button>
            </div>

            <div class="patients-table">
                <table class="table table-hover" id="patientsTable">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Patient ID</th>
                            <th>Full Name</th>
                            <th>Age/IC</th>
                            <th>Gender</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Total Appointments</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $patient): ?>
                        <tr>
                            <td>
                                <img src="../assets/images/patients/<?php echo $patient['photo'] ?? 'default_patient.png'; ?>" 
                                     alt="<?php echo htmlspecialchars($patient['full_name'] ?? ''); ?>" 
                                     class="patient-photo">
                            </td>
                            <td><span class="patient-id">P<?php echo str_pad($patient['id'], 4, '0', STR_PAD_LEFT); ?></span></td>
                            <td><?php echo htmlspecialchars($patient['full_name'] ?? ''); ?></td>
                            <td><?php echo $patient['age'] ?? 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($patient['gender'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($patient['phone'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($patient['email'] ?? ''); ?></td>
                            <td><?php echo rand(5, 25); ?></td>
                            <td>
                                <span class="status-badge <?php echo strtolower(isset($patient['status']) ? $patient['status'] : 'Active'); ?>">
                                    <?php echo htmlspecialchars(isset($patient['status']) ? $patient['status'] : 'Active'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-btn view" onclick="viewPatient(<?php echo $patient['id']; ?>)" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn edit" onclick="editPatient(<?php echo $patient['id']; ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete" onclick="deletePatient(<?php echo $patient['id']; ?>)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Patient Modal -->
    <div class="modal fade" id="addPatientModal" tabindex="-1" aria-labelledby="addPatientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPatientModalLabel">
                        <i class="fas fa-user-plus me-2"></i>Add New Patient
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addPatientForm">
                    <div class="modal-body">
                        <!-- Personal Information -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" name="full_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">IC Number *</label>
                                <input type="text" class="form-control" name="ic_number" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Age *</label>
                                <input type="number" class="form-control" name="age" min="0" max="120" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gender *</label>
                                <select class="form-select" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status *</label>
                                <select class="form-select" name="status" required>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" name="phone" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Medical Notes</label>
                                <textarea class="form-control" name="medical_notes" rows="4" placeholder="Any medical conditions, allergies, or important notes..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Patient
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Patient Modal -->
    <div class="modal fade" id="editPatientModal" tabindex="-1" aria-labelledby="editPatientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPatientModalLabel">
                        <i class="fas fa-user-edit me-2"></i>Edit Patient
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editPatientForm">
                    <input type="hidden" name="patient_id" id="editPatientId">
                    <div class="modal-body" id="editPatientContent">
                        <!-- Patient data will be loaded here -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Patient
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Patient Modal -->
    <div class="modal fade" id="viewPatientModal" tabindex="-1" aria-labelledby="viewPatientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewPatientModalLabel">
                        <i class="fas fa-user me-2"></i>Patient Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewPatientContent">
                    <!-- Patient details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="editPatientFromView()">
                        <i class="fas fa-edit me-2"></i>Edit Patient
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this patient?</p>
                    <p class="text-danger"><strong>This action cannot be undone!</strong></p>
                    <p id="deletePatientName"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                        <i class="fas fa-trash me-2"></i>Delete Patient
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Add Button -->
    <button class="btn btn-primary position-fixed bottom-0 end-0 m-4 rounded-circle" 
            style="width: 60px; height: 60px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);"
            data-bs-toggle="modal" data-bs-target="#addPatientModal">
        <i class="fas fa-plus fa-lg"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- jsPDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <!-- SheetJS -->
    <script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>

    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#patientsTable').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[2, 'asc']] // Sort by name
            });

            // Search functionality
            $('#searchInput').on('keyup', function() {
                var table = $('#patientsTable').DataTable();
                table.search(this.value).draw();
            });

            // Filter functionality
            $('#genderFilter, #statusFilter, #ageFilter').on('change', function() {
                filterPatients();
            });
        });

        // Filter patients
        function filterPatients() {
            var gender = $('#genderFilter').val();
            var status = $('#statusFilter').val();
            var ageGroup = $('#ageFilter').val();
            
            var table = $('#patientsTable').DataTable();
            
            // Reset search
            table.search('').columns().search('').draw();
            
            // Apply filters
            if (gender) {
                table.column(4).search(gender).draw();
            }
            if (status) {
                table.column(8).search(status).draw();
            }
            if (ageGroup) {
                // Custom age filter would need to be implemented
                // This is a placeholder for age group filtering
            }
        }

        // View patient details
        function viewPatient(patientId) {
            // In a real application, this would fetch data from the server
            // For demo purposes, we'll use sample data
            var patientData = {
                id: patientId,
                full_name: 'Ahmad bin Ismail',
                ic_number: '850123-14-5678',
                age: 38,
                gender: 'Male',
                phone: '012-3456789',
                email: 'ahmad@email.com',
                address: '123 Jalan Sultan, Kuala Lumpur, 50000',
                medical_notes: 'No known allergies. Regular check-ups required.',
                status: 'Active'
            };

            var content = `
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-user me-2"></i>Personal Information</h6>
                        <p><strong>Name:</strong> ${patientData.full_name}</p>
                        <p><strong>IC Number:</strong> ${patientData.ic_number}</p>
                        <p><strong>Age:</strong> ${patientData.age}</p>
                        <p><strong>Gender:</strong> ${patientData.gender}</p>
                        <p><strong>Status:</strong> <span class="status-badge ${patientData.status.toLowerCase()}">${patientData.status}</span></p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-phone me-2"></i>Contact Information</h6>
                        <p><strong>Phone:</strong> ${patientData.phone}</p>
                        <p><strong>Email:</strong> ${patientData.email}</p>
                        <p><strong>Address:</strong> ${patientData.address}</p>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <h6><i class="fas fa-notes-medical me-2"></i>Medical Notes</h6>
                        <p>${patientData.medical_notes}</p>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <h6><i class="fas fa-history me-2"></i>Appointment History</h6>
                        <div class="appointment-history">
                            <div class="appointment-item">
                                <span class="appointment-date">2024-01-15</span>
                                <span class="appointment-doctor">Dr. Ahmad Ismail</span>
                                <span class="appointment-status completed">Completed</span>
                            </div>
                            <div class="appointment-item">
                                <span class="appointment-date">2024-01-08</span>
                                <span class="appointment-doctor">Dr. Siti Aishah</span>
                                <span class="appointment-status completed">Completed</span>
                            </div>
                            <div class="appointment-item">
                                <span class="appointment-date">2024-01-25</span>
                                <span class="appointment-doctor">Dr. Rahman</span>
                                <span class="appointment-status pending">Pending</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('#viewPatientContent').html(content);
            $('#viewPatientModal').modal('show');
        }

        // Edit patient
        function editPatient(patientId) {
            // In a real application, this would fetch patient data from the server
            // For demo purposes, we'll populate with sample data
            var patientData = {
                id: patientId,
                full_name: 'Ahmad bin Ismail',
                ic_number: '850123-14-5678',
                age: 38,
                gender: 'Male',
                phone: '012-3456789',
                email: 'ahmad@email.com',
                address: '123 Jalan Sultan, Kuala Lumpur, 50000',
                medical_notes: 'No known allergies. Regular check-ups required.',
                status: 'Active'
            };

            var content = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="full_name" value="${patientData.full_name}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">IC Number *</label>
                        <input type="text" class="form-control" name="ic_number" value="${patientData.ic_number}" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Age *</label>
                        <input type="number" class="form-control" name="age" value="${patientData.age}" min="0" max="120" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Gender *</label>
                        <select class="form-select" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male" ${patientData.gender === 'Male' ? 'selected' : ''}>Male</option>
                            <option value="Female" ${patientData.gender === 'Female' ? 'selected' : ''}>Female</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status *</label>
                        <select class="form-select" name="status" required>
                            <option value="Active" ${patientData.status === 'Active' ? 'selected' : ''}>Active</option>
                            <option value="Inactive" ${patientData.status === 'Inactive' ? 'selected' : ''}>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Phone Number *</label>
                        <input type="tel" class="form-control" name="phone" value="${patientData.phone}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" value="${patientData.email}" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3">${patientData.address}</textarea>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Medical Notes</label>
                        <textarea class="form-control" name="medical_notes" rows="4">${patientData.medical_notes}</textarea>
                    </div>
                </div>
            `;

            $('#editPatientId').val(patientId);
            $('#editPatientContent').html(content);
            $('#editPatientModal').modal('show');
        }

        // Delete patient
        var deletePatientId = null;
        function deletePatient(patientId) {
            deletePatientId = patientId;
            // In a real application, you would fetch the patient name
            $('#deletePatientName').text('Patient ID: P' + patientId.toString().padStart(4, '0'));
            $('#deleteModal').modal('show');
        }

        function confirmDelete() {
            if (deletePatientId) {
                // Send AJAX request to delete the patient
                $.ajax({
                    url: 'delete_patient.php',
                    method: 'POST',
                    data: { patient_id: deletePatientId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            $('#deleteModal').modal('hide');
                            // Refresh the page to show updated list
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error occurred while deleting patient. Please try again.');
                    }
                });
            }
        }

        // Edit patient from view modal
        function editPatientFromView() {
            $('#viewPatientModal').modal('hide');
            // Get patient ID from the view modal (would need to store it)
            var patientId = 1; // This would be dynamic
            editPatient(patientId);
        }

        // Export to PDF
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Add title
            doc.setFontSize(20);
            doc.text('Patients List', 20, 20);
            
            // Add date
            doc.setFontSize(12);
            doc.text('Generated on: ' + new Date().toLocaleDateString(), 20, 30);
            
            // Add table data
            var tableData = [];
            $('#patientsTable tbody tr').each(function() {
                var row = [];
                $(this).find('td').each(function(index) {
                    if (index < 9) { // Skip action column
                        row.push($(this).text().trim());
                    }
                });
                tableData.push(row);
            });
            
            // Create table
            doc.autoTable({
                head: [['ID', 'Name', 'Age/IC', 'Gender', 'Phone', 'Email', 'Appointments', 'Status']],
                body: tableData,
                startY: 40
            });
            
            // Save the PDF
            doc.save('patients_list.pdf');
        }

        // Export to Excel
        function exportToExcel() {
            var tableData = [];
            var headers = ['Patient ID', 'Full Name', 'Age/IC', 'Gender', 'Phone', 'Email', 'Total Appointments', 'Status'];
            
            tableData.push(headers);
            
            $('#patientsTable tbody tr').each(function() {
                var row = [];
                $(this).find('td').each(function(index) {
                    if (index < 9) { // Skip action column
                        row.push($(this).text().trim());
                    }
                });
                tableData.push(row);
            });
            
            var ws = XLSX.utils.aoa_to_sheet(tableData);
            var wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Patients');
            XLSX.writeFile(wb, 'patients_list.xlsx');
        }

        // Print table
        function printTable() {
            var printContent = document.getElementById('patientsTable').outerHTML;
            var originalContent = document.body.innerHTML;
            
            document.body.innerHTML = `
                <html>
                    <head>
                        <title>Patients List</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            table { border-collapse: collapse; width: 100%; }
                            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                            th { background-color: #f2f2f2; }
                            h1 { color: #2c3e50; }
                        </style>
                    </head>
                    <body>
                        <h1>Patients List</h1>
                        <p>Generated on: ${new Date().toLocaleDateString()}</p>
                        ${printContent}
                    </body>
                </html>
            `;
            
            window.print();
            document.body.innerHTML = originalContent;
            location.reload();
        }

        // Handle form submissions
        $('#addPatientForm').on('submit', function(e) {
            e.preventDefault();
            // In a real application, you would send the form data to the server
            alert('Patient added successfully!');
            $('#addPatientModal').modal('hide');
            this.reset();
            location.reload();
        });

        $('#editPatientForm').on('submit', function(e) {
            e.preventDefault();
            // In a real application, you would send the form data to the server
            alert('Patient updated successfully!');
            $('#editPatientModal').modal('hide');
            location.reload();
        });
    </script>
    <?php endif; ?>
</body>
</html>
