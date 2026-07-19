<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'patient'){
    header("location: ../login.php");
    exit;
}

// Include config file
require_once "../includes/config.php";

// Get patient ID
$patient_id = '';
$sql = "SELECT id FROM patients WHERE user_id = ?";
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $_SESSION["user_id"]);
    if($stmt->execute()){
        $result = $stmt->get_result();
        if($row = $result->fetch_assoc()){
            $patient_id = $row['id'];
        }
    }
    $stmt->close();
}

// Fetch patient's prescriptions
$prescriptions = [];
$sql = "SELECT p.*, 
        d.full_name as doctor_name, 
        d.specialization,
        pt.full_name as patient_name,
        a.appointment_date
        FROM prescriptions p
        JOIN doctors d ON p.doctor_id = d.id 
        JOIN patients pt ON p.patient_id = pt.id
        LEFT JOIN appointments a ON p.appointment_id = a.id
        WHERE p.patient_id = ?
        ORDER BY p.prescribed_date DESC, p.created_at DESC";

if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $patient_id);
    if($stmt->execute()){
        $result = $stmt->get_result();
        $prescriptions = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
}

// Close connection
$conn->close();
?>

<?php $page_title = "My Prescriptions"; ?>
<?php include('../includes/header.php'); ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Prescriptions</h2>
        <a href="dashboard.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <?php if (empty($prescriptions)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No prescriptions found.
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Doctor</th>
                                <th>Diagnosis</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prescriptions as $prescription): 
                                $prescribed_date = new DateTime($prescription['prescribed_date']);
                                
                                // Status badge class
                                $status_class = '';
                                switch($prescription['status']) {
                                    case 'active':
                                        $status_class = 'bg-success';
                                        break;
                                    case 'completed':
                                        $status_class = 'bg-primary';
                                        break;
                                    case 'cancelled':
                                        $status_class = 'bg-danger';
                                        break;
                                }
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo $prescribed_date->format('M j, Y'); ?></div>
                                    <small class="text-muted"><?php echo $prescribed_date->format('D'); ?></small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm me-2 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <?php echo strtoupper(substr($prescription['doctor_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php 
                                                $doctor_name = $prescription['doctor_name'];
                                                if (strpos($doctor_name, 'Dr. ') === 0) {
                                                    $doctor_name = substr($doctor_name, 4);
                                                }
                                                echo 'Dr. ' . htmlspecialchars(trim($doctor_name)); 
                                            ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($prescription['specialization']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($prescription['diagnosis'] ?? 'No diagnosis provided'); ?>">
                                        <?php 
                                        $diagnosis = $prescription['diagnosis'] ?? 'No diagnosis provided';
                                        echo strlen($diagnosis) > 50 ? htmlspecialchars(substr($diagnosis, 0, 50)) . '...' : htmlspecialchars($diagnosis);
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill <?php echo $status_class; ?> px-3 py-2">
                                        <i class="fas <?php 
                                            echo $prescription['status'] == 'active' ? 'fa-check-circle' : 
                                                 ($prescription['status'] == 'completed' ? 'fa-check-double' : 'fa-times-circle'); 
                                        ?> me-1"></i>
                                        <?php echo ucfirst($prescription['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info view-prescription" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#prescriptionModal"
                                            data-diagnosis="<?php echo htmlspecialchars($prescription['diagnosis'] ?? 'No diagnosis provided'); ?>"
                                            data-prescription="<?php echo htmlspecialchars($prescription['prescription_text']); ?>"
                                            data-doctor="<?php echo htmlspecialchars($prescription['doctor_name']); ?>"
                                            data-date="<?php echo $prescribed_date->format('F j, Y'); ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <a href="javascript:window.print()" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-print"></i> Print
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Prescription Details Modal -->
<div class="modal fade" id="prescriptionModal" tabindex="-1" aria-labelledby="prescriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="prescriptionModalLabel">Prescription Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="prescription-header mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Healthcare Centre</h4>
                            <p class="mb-1">123 Medical Drive</p>
                            <p class="mb-1">City, State 12345</p>
                            <p class="mb-0">Phone: (123) 456-7890</p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="mb-1"><strong>Date:</strong> <span id="prescriptionDate"></span></p>
                            <p class="mb-1"><strong>Doctor:</strong> <span id="prescriptionDoctor"></span></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Patient Information</h5>
                            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION["username"]); ?></p>
                            <p class="mb-0"><strong>Patient ID:</strong> <?php echo $patient_id; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="diagnosis-section mb-4">
                    <h5>Diagnosis</h5>
                    <div id="prescriptionDiagnosis" class="p-3 bg-light rounded"></div>
                </div>
                
                <div class="prescription-section">
                    <h5>Prescription</h5>
                    <div id="prescriptionText" class="p-3 bg-light rounded"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle view prescription button click
    document.querySelectorAll('.view-prescription').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('prescriptionDate').textContent = this.getAttribute('data-date');
            document.getElementById('prescriptionDoctor').textContent = this.getAttribute('data-doctor');
            document.getElementById('prescriptionDiagnosis').innerHTML = this.getAttribute('data-diagnosis') || 'No diagnosis provided';
            document.getElementById('prescriptionText').innerHTML = this.getAttribute('data-prescription');
        });
    });
});
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .modal,
    .modal * {
        visibility: visible;
    }
    .modal {
        position: absolute;
        left: 0;
        top: 0;
        margin: 0;
        padding: 0;
        min-height: 100%;
        width: 100%;
    }
    .modal-dialog {
        max-width: 100%;
        width: 100%;
        margin: 0;
    }
    .prescription-header {
        border-bottom: 2px solid #dee2e6;
        padding-bottom: 1rem;
        margin-bottom: 1.5rem;
    }
    .diagnosis-section, .prescription-section {
        margin-bottom: 2rem;
    }
    .modal-footer {
        display: none;
    }
}
</style>
