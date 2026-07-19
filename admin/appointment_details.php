<?php
// Initialize the session and check if user is logged in and is admin
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

// Include config file
require_once "../includes/config.php";

// Check if appointment ID is provided
if (!isset($_GET["id"]) || empty(trim($_GET["id"]))) {
    header("location: appointments.php");
    exit;
}

$appointment_id = trim($_GET["id"]);
$appointment = [];
$patient = [];
$doctor = [];

try {
    // Get appointment details
    $sql = "SELECT a.*, 
                   p.full_name as patient_name, p.phone as patient_phone, p.email as patient_email,
                   d.full_name as doctor_name, d.specialization
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            WHERE a.id = ?";
            
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $param_id);
        $param_id = $appointment_id;
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $appointment = $result->fetch_assoc();
            } else {
                header("location: appointments.php");
                exit();
            }
        } else {
            throw new Exception("Error fetching appointment details.");
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Handle status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["status"])) {
    $status = trim($_POST["status"]);
    $notes = !empty($_POST["notes"]) ? trim($_POST["notes"]) : null;
    
    try {
        $sql = "UPDATE appointments SET status = ?, notes = CONCAT_WS('\\n', notes, ?) WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssi", $status, $notes, $appointment_id);
            
            if ($stmt->execute()) {
                // Update successful, refresh the page
                header("location: appointment_details.php?id=" . $appointment_id);
                exit();
            } else {
                throw new Exception("Error updating appointment status.");
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Set page title
$page_title = "Appointment Details";

// Include header
include("../includes/header.php");
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Appointment #<?php echo htmlspecialchars($appointment_id); ?></h5>
                    <a href="appointments.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to Appointments
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Patient Information -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user-injured me-2"></i>Patient Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($appointment['patient_name']); ?></h5>
                                    <div class="mb-2">
                                        <i class="fas fa-phone-alt me-2 text-muted"></i>
                                        <a href="tel:<?php echo htmlspecialchars($appointment['patient_phone'] ?? ''); ?>">
                                            <?php echo !empty($appointment['patient_phone']) ? htmlspecialchars($appointment['patient_phone']) : 'N/A'; ?>
                                        </a>
                                    </div>
                                    <div class="mb-2">
                                        <i class="fas fa-envelope me-2 text-muted"></i>
                                        <a href="mailto:<?php echo htmlspecialchars($appointment['patient_email'] ?? ''); ?>">
                                            <?php echo !empty($appointment['patient_email']) ? htmlspecialchars($appointment['patient_email']) : 'N/A'; ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Doctor Information -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user-md me-2"></i>Doctor Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title">Dr. <?php echo htmlspecialchars($appointment['doctor_name'] ?? ''); ?></h5>
                                    <p class="card-text text-muted">
                                        <i class="fas fa-stethoscope me-2"></i>
                                        <?php echo htmlspecialchars($appointment['specialization'] ?? 'General Practice'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Appointment Details -->
                        <div class="col-12 mb-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="far fa-calendar-alt me-2"></i>Appointment Details
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <h6>Date</h6>
                                            <p class="text-muted">
                                                <i class="far fa-calendar me-2"></i>
                                                <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <h6>Time</h6>
                                            <p class="text-muted">
                                                <i class="far fa-clock me-2"></i>
                                                <?php echo date('h:i A', strtotime($appointment['start_time'])); ?> - 
                                                <?php echo date('h:i A', strtotime($appointment['end_time'])); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <h6>Status</h6>
                                            <span class="badge bg-<?php 
                                                echo match($appointment['status']) {
                                                    'Completed' => 'success',
                                                    'No Show' => 'danger',
                                                    'Cancelled' => 'secondary',
                                                    default => 'primary'
                                                };
                                            ?>">
                                                <?php echo htmlspecialchars($appointment['status'] ?? ''); ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($appointment['notes'])): ?>
                                        <div class="col-12">
                                            <h6>Notes</h6>
                                            <div class="bg-light p-3 rounded">
                                                <?php echo !empty($appointment['notes']) ? nl2br(htmlspecialchars($appointment['notes'])) : 'No notes available.'; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-cogs me-2"></i>Actions
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php if ($appointment['status'] !== 'Completed' && $appointment['status'] !== 'Cancelled' && $appointment['status'] !== 'No Show'): ?>
                                            <!-- Mark as Completed -->
                                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#completeModal">
                                                <i class="fas fa-check-circle me-1"></i> Mark as Completed
                                            </button>
                                            
                                            <!-- Mark as No Show -->
                                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#noShowModal">
                                                <i class="fas fa-user-times me-1"></i> Mark as No Show
                                            </button>
                                            
                                            <!-- Cancel Appointment -->
                                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                                <i class="fas fa-times-circle me-1"></i> Cancel Appointment
                                            </button>
                                        <?php endif; ?>
                                        
                                        <a href="appointments.php" class="btn btn-outline-secondary ms-auto">
                                            <i class="fas fa-arrow-left me-1"></i> Back to List
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Complete Appointment Modal -->
<div class="modal fade" id="completeModal" tabindex="-1" aria-labelledby="completeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $appointment_id; ?>" method="post">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="completeModalLabel">Mark as Completed</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to mark this appointment as completed?</p>
                    <div class="mb-3">
                        <label for="completeNotes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="completeNotes" name="notes" rows="3" placeholder="Add any notes about this appointment..."></textarea>
                    </div>
                    <input type="hidden" name="status" value="Completed">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle me-1"></i> Mark as Completed
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- No Show Modal -->
<div class="modal fade" id="noShowModal" tabindex="-1" aria-labelledby="noShowModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $appointment_id; ?>" method="post">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="noShowModalLabel">Mark as No Show</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to mark this appointment as No Show?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This will increment the patient's no-show count.
                    </div>
                    <div class="mb-3">
                        <label for="noShowNotes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="noShowNotes" name="notes" rows="3" placeholder="Add any notes about this no-show..."></textarea>
                    </div>
                    <input type="hidden" name="status" value="No Show">
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

<!-- Cancel Appointment Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $appointment_id; ?>" method="post">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="cancelModalLabel">Cancel Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel this appointment?</p>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action cannot be undone.
                    </div>
                    <div class="mb-3">
                        <label for="cancelReason" class="form-label">Reason for Cancellation (Optional)</label>
                        <textarea class="form-control" id="cancelReason" name="notes" rows="3" placeholder="Please provide a reason for cancellation..."></textarea>
                    </div>
                    <input type="hidden" name="status" value="Cancelled">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times-circle me-1"></i> Cancel Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Include footer
include("../includes/footer.php");
?>
