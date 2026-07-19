<?php
// Start session
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../login.php");
    exit;
}

// Check if user has permission (admin or doctor)
if($_SESSION["role"] !== 'admin' && $_SESSION["role"] !== 'doctor'){
    header("location: ../unauthorized.php");
    exit;
}

// Include database connection
include_once "../includes/db_connection.php";

// Get doctors from database
$doctors = [];
$sql = "SELECT d.*, u.username, u.email as user_email 
        FROM doctors d 
        JOIN users u ON d.user_id = u.id 
        ORDER BY d.full_name ASC";

if($stmt = $conn->prepare($sql)){
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $doctors[] = $row;
    }
    $stmt->close();
}

// Handle delete action
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_doctor']) && $_SESSION["role"] == 'admin'){
    $doctor_id = $_POST['doctor_id'];
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Get user_id and profile photo before deletion
        $sql = "SELECT user_id, profile_photo FROM doctors WHERE id = ?";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("i", $doctor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $doctor_data = $result->fetch_assoc();
            $stmt->close();
        }
        
        // Delete from doctors table
        $sql = "DELETE FROM doctors WHERE id = ?";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("i", $doctor_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Delete from users table
        if($doctor_data){
            $sql = "DELETE FROM users WHERE id = ?";
            if($stmt = $conn->prepare($sql)){
                $stmt->bind_param("i", $doctor_data['user_id']);
                $stmt->execute();
                $stmt->close();
            }
            
            // Delete profile photo if exists
            if(!empty($doctor_data['profile_photo'])){
                $photo_path = "../assets/images/doctors/" . $doctor_data['profile_photo'];
                if(file_exists($photo_path)){
                    unlink($photo_path);
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['success_message'] = "Doctor deleted successfully!";
        header("location: manage_doctors.php");
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        $_SESSION['error_message'] = "Error deleting doctor: " . $e->getMessage();
        header("location: manage_doctors.php");
        exit;
    }
}

// Set page title
$page_title = "Manage Doctors";

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

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Manage Doctors</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Doctors</li>
                        </ol>
                    </nav>
                </div>
                <?php if($_SESSION["role"] == 'admin'): ?>
                <div>
                    <a href="add_doctor.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add New Doctor
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-0">Total Doctors</h6>
                            <h3 class="mb-0"><?php echo count($doctors); ?></h3>
                        </div>
                        <div class="ms-3">
                            <i class="fas fa-user-md fa-2x opacity-75"></i>
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
                            <h6 class="mb-0">Active Doctors</h6>
                            <h3 class="mb-0"><?php echo count(array_filter($doctors, function($d) { return $d['status'] == 'Active'; })); ?></h3>
                        </div>
                        <div class="ms-3">
                            <i class="fas fa-check-circle fa-2x opacity-75"></i>
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
                            <h6 class="mb-0">Inactive Doctors</h6>
                            <h3 class="mb-0"><?php echo count(array_filter($doctors, function($d) { return $d['status'] == 'Inactive'; })); ?></h3>
                        </div>
                        <div class="ms-3">
                            <i class="fas fa-pause-circle fa-2x opacity-75"></i>
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
                            <h6 class="mb-0">Specializations</h6>
                            <h3 class="mb-0"><?php echo count(array_unique(array_column($doctors, 'specialization'))); ?></h3>
                        </div>
                        <div class="ms-3">
                            <i class="fas fa-stethoscope fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Doctor List Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Doctor List
                <span class="badge bg-light text-primary ms-2"><?php echo count($doctors); ?></span>
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
                            <th>Specialization</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($doctors as $doctor): ?>
                        <tr>
                            <td>
                                <?php if(!empty($doctor['profile_photo'])): ?>
                                    <img src="../assets/images/doctors/<?php echo htmlspecialchars($doctor['profile_photo']); ?>" 
                                         alt="<?php echo htmlspecialchars($doctor['full_name']); ?>" 
                                         class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px;">
                                        <i class="fas fa-user-md text-white"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($doctor['doctor_id']); ?></span>
                            </td>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($doctor['full_name']); ?></div>
                                <small class="text-muted">@<?php echo htmlspecialchars($doctor['username']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($doctor['specialization']); ?></td>
                            <td><?php echo htmlspecialchars($doctor['email']); ?></td>
                            <td><?php echo htmlspecialchars($doctor['phone']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo ($doctor['status'] == 'Active') ? 'success' : 'secondary'; ?>">
                                    <?php echo htmlspecialchars($doctor['status']); ?>
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
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="confirmDelete(<?php echo $doctor['id']; ?>, '<?php echo htmlspecialchars($doctor['full_name']); ?>')" 
                                                title="Delete Doctor">
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
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="doctor_id" id="deleteDoctorId">
                    <input type="hidden" name="delete_doctor" value="1">
                    
                    <p>Are you sure you want to delete <strong id="deleteDoctorName"></strong>?</p>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone and will:
                        <ul class="mb-0 mt-2">
                            <li>Delete the doctor's profile and all associated data</li>
                            <li>Remove the doctor's user account</li>
                            <li>Delete their profile photo if it exists</li>
                            <li>Cancel all future appointments</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Doctor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    $('#doctorsTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[2, 'asc']], // Sort by name
        language: {
            search: "Search doctors:",
            lengthMenu: "Show _MENU_ doctors per page",
            info: "Showing _START_ to _END_ of _TOTAL_ doctors",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        }
    });
    
    // Delete confirmation function
    window.confirmDelete = function(doctorId, doctorName) {
        document.getElementById('deleteDoctorId').value = doctorId;
        document.getElementById('deleteDoctorName').textContent = doctorName;
        
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    };
});
</script>
