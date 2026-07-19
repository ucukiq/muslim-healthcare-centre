<?php
// Start session and check admin access
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

// Include config file
require_once "../includes/config.php";

// Get no-show appointments
$no_shows = [];
$sql = "SELECT a.*, p.full_name as patient_name, d.full_name as doctor_name 
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.id 
        JOIN doctors d ON a.doctor_id = d.id 
        WHERE a.status = 'No Show' 
        ORDER BY a.appointment_date DESC, a.start_time DESC";

$result = $conn->query($sql);
if ($result) {
    while($row = $result->fetch_assoc()) {
        $no_shows[] = $row;
    }
}
?>

<?php $page_title = "No Show Appointments"; ?>
<?php include('../includes/header.php'); ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">No Show Appointments</h1>
                <a href="appointments.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Back to Appointments
                </a>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['success'] ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">No Show Appointments</h6>
                    <span class="badge bg-danger"><?php echo count($no_shows); ?> Records</span>
                </div>
                <div class="card-body">
                    <?php if (count($no_shows) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="noShowTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Appointment ID</th>
                                        <th>Patient Name</th>
                                        <th>Doctor</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($no_shows as $appointment): ?>
                                        <tr>
                                            <td>#<?php echo $appointment['id']; ?></td>
                                            <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                            <td>Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($appointment['start_time'])); ?></td>
                                            <td><?php echo nl2br(htmlspecialchars($appointment['notes'] ?? 'No notes')); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#rescheduleModal" 
                                                            data-id="<?php echo $appointment['id']; ?>"
                                                            data-patient="<?php echo htmlspecialchars($appointment['patient_name']); ?>">
                                                        <i class="fas fa-calendar-plus"></i> Reschedule
                                                    </button>
                                                    <a href="patient_profile.php?id=<?php echo $appointment['patient_id']; ?>" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="fas fa-user"></i> Profile
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> No no-show appointments found.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reschedule Modal -->
<div class="modal fade" id="rescheduleModal" tabindex="-1" aria-labelledby="rescheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="reschedule_appointment.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="rescheduleModalLabel">Reschedule Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="appointment_id" id="rescheduleAppointmentId">
                    <div class="mb-3">
                        <label for="patientName" class="form-label">Patient</label>
                        <input type="text" class="form-control" id="patientName" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="appointmentDate" class="form-label">New Date</label>
                        <input type="date" class="form-control" id="appointmentDate" name="appointment_date" required 
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="appointmentTime" class="form-label">New Time</label>
                        <input type="time" class="form-control" id="appointmentTime" name="appointment_time" required
                               min="08:00" max="17:00" step="900">
                    </div>
                    <div class="mb-3">
                        <label for="rescheduleNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="rescheduleNotes" name="notes" rows="3" 
                                 placeholder="Reason for rescheduling..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Reschedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

<script>
// Initialize DataTable
$(document).ready(function() {
    $('#noShowTable').DataTable({
        "order": [[3, "desc"], [4, "desc"]], // Sort by date and time
        "pageLength": 10,
        "responsive": true
    });
    
    // Handle reschedule modal
    var rescheduleModal = document.getElementById('rescheduleModal');
    rescheduleModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var appointmentId = button.getAttribute('data-id');
        var patientName = button.getAttribute('data-patient');
        
        document.getElementById('rescheduleAppointmentId').value = appointmentId;
        document.getElementById('patientName').value = patientName;
        
        // Set default time to next available slot
        var now = new Date();
        var hours = now.getHours();
        var minutes = Math.ceil(now.getMinutes() / 15) * 15;
        if (minutes === 60) {
            hours++;
            minutes = 0;
        }
        document.getElementById('appointmentTime').value = 
            (hours < 10 ? '0' + hours : hours) + ':' + 
            (minutes < 10 ? '0' + minutes : minutes);
    });
});
</script>
