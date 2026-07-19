<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize the session
session_start();
 
// Check if the user is logged in as admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin'){
    header("location: login.php");
    exit;
}

// Include config file
require_once '../includes/config.php';

$message = '';
$success = false;

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_doctors'])) {
    try {
        // Get all doctors without doctor_id
        $query = "SELECT id, full_name FROM doctors WHERE doctor_id IS NULL OR doctor_id = ''";
        $result = $conn->query($query);
        
        $updated_count = 0;
        
        if($result->num_rows > 0) {
            while($doctor = $result->fetch_assoc()) {
                // Generate doctor ID
                $doctor_id = 'DOC' . str_pad($doctor['id'], 6, '0', STR_PAD_LEFT);
                
                // Update doctor record
                $update_query = "UPDATE doctors SET doctor_id = ?, status = 'Active' WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param('si', $doctor_id, $doctor['id']);
                
                if($stmt->execute()) {
                    $updated_count++;
                }
            }
            
            $message = "Successfully updated {$updated_count} doctor records with doctor IDs and status.";
            $success = true;
        } else {
            $message = "All doctors already have doctor IDs.";
            $success = true;
        }
        
    } catch(Exception $e) {
        $message = "Error updating doctors: " . $e->getMessage();
        $success = false;
    }
}

// Get current doctor statistics
$total_doctors = 0;
$doctors_with_id = 0;
$doctors_with_status = 0;

try {
    $total_query = "SELECT COUNT(*) as count FROM doctors";
    $result = $conn->query($total_query);
    $total_doctors = $result->fetch_assoc()['count'];
    
    $with_id_query = "SELECT COUNT(*) as count FROM doctors WHERE doctor_id IS NOT NULL AND doctor_id != ''";
    $result = $conn->query($with_id_query);
    $doctors_with_id = $result->fetch_assoc()['count'];
    
    $with_status_query = "SELECT COUNT(*) as count FROM doctors WHERE status IS NOT NULL";
    $result = $conn->query($with_status_query);
    $doctors_with_status = $result->fetch_assoc()['count'];
    
} catch(Exception $e) {
    $message = "Error getting statistics: " . $e->getMessage();
}

$page_title = "Update Doctor IDs";
include('../includes/header.php');
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="page-header mb-4">
                <h1 class="h3">Update Doctor IDs</h1>
                <p class="text-muted">Fix missing doctor_id and status fields</p>
            </div>
        </div>
    </div>
    
    <?php if(!empty($message)): ?>
    <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Doctor Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h4 class="mb-0"><?php echo $total_doctors; ?></h4>
                                    <p class="mb-0">Total Doctors</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h4 class="mb-0"><?php echo $doctors_with_id; ?></h4>
                                    <p class="mb-0">With Doctor ID</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h4 class="mb-0"><?php echo $doctors_with_status; ?></h4>
                                    <p class="mb-0">With Status</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Update Actions</h5>
                </div>
                <div class="card-body">
                    <?php if($doctors_with_id < $total_doctors): ?>
                    <form method="POST">
                        <input type="hidden" name="update_doctors" value="1">
                        <button type="submit" class="btn btn-warning btn-block mb-2">
                            <i class="fas fa-sync-alt me-2"></i>
                            Generate Missing Doctor IDs
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <a href="manage_doctors_complete.php" class="btn btn-primary btn-block">
                        <i class="fas fa-users me-2"></i>
                        View Manage Doctors
                    </a>
                    
                    <hr>
                    
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>What this does:</h6>
                        <ul class="mb-0 small">
                            <li>Generates doctor IDs in format DOC000001, DOC000002, etc.</li>
                            <li>Sets default status to 'Active' for all doctors</li>
                            <li>Only updates records missing these fields</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>
