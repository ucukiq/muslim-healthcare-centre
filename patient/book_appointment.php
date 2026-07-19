<?php
// Start session
session_start();

// Check if user is logged in as patient
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'patient'){
    header("location: ../login.php");
    exit;
}

// Include config and turn functions files
require_once "../includes/config.php";
require_once "../includes/turn_functions.php";

// Function to check doctor availability
function isDoctorAvailable($conn, $doctor_id, $appointment_date, $start_time, $end_time) {
    $sql = "SELECT COUNT(*) as count 
            FROM appointments 
            WHERE doctor_id = ? 
            AND appointment_date = ? 
            AND (
                (start_time < ? AND end_time > ?) OR
                (start_time < ? AND end_time > ?) OR
                (start_time >= ? AND end_time <= ?)
            )
            AND status != 'cancelled'";
    
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("isssssss", 
            $doctor_id, 
            $appointment_date,
            $end_time, $start_time,
            $start_time, $end_time,
            $start_time, $end_time
        );
        if($stmt->execute()){
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row['count'] == 0;
        }
    }
    return false;
}

// Define variables and initialize with empty values
$doctor_id = $appointment_date = $start_time = $notes = "";
$doctor_id_err = $appointment_date_err = $start_time_err = "";
$success_msg = "";

// Fetch list of specializations
$specializations = [];
$sql = "SELECT DISTINCT specialization FROM doctors WHERE specialization IS NOT NULL AND specialization != '' ORDER BY specialization";
if($result = $conn->query($sql)){
    while($row = $result->fetch_assoc()){
        $specializations[] = $row['specialization'];
    }
}

$selected_specialization = isset($_POST['specialization']) ? $_POST['specialization'] : '';
$specialization_err = '';

// Check if this is just a specialization update
$is_specialization_update = isset($_POST['update_specialization']);

// Process form data when form is submitted (but not for just specialization updates)
if($_SERVER["REQUEST_METHOD"] == "POST" && !$is_specialization_update && isset($_POST['submit_booking'])){
    
    // Validate specialization
    if(empty(trim($_POST["specialization"]))){
        $specialization_err = "Please select a medical field.";
    } else {
        $selected_specialization = trim($_POST["specialization"]);
    }
    
    // Only validate other fields if form was submitted via the Book Appointment button
    if(empty($specialization_err)) {
        // Find an available doctor with the selected specialization
        $doctor_id = '';
        $doctor_name = '';
        
        // First, get all doctors with the selected specialization
        $sql = "SELECT d.id, d.full_name, 
                (SELECT COUNT(*) FROM appointments a 
                 WHERE a.doctor_id = d.id 
                 AND a.appointment_date = ? 
                 AND a.start_time = ? 
                 AND a.status != 'cancelled') as appointment_count
                FROM doctors d 
                WHERE d.specialization = ? 
                ORDER BY appointment_count, RAND()";
                
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("sss", $appointment_date, $start_time, $selected_specialization);
            if($stmt->execute()){
                $result = $stmt->get_result();
                if($result->num_rows == 0){
                    $specialization_err = "No doctors available for the selected medical field. Please try another field or different time slot.";
                } else {
                    // Get the first available doctor with the least appointments
                    $row = $result->fetch_assoc();
                    $doctor_id = $row['id'];
                    $doctor_name = $row['full_name'];
                    
                    // Check if the selected time slot is actually available
                    $end_time = date('H:i:s', strtotime($start_time . ' + 30 minutes'));
                    if(!isDoctorAvailable($conn, $doctor_id, $appointment_date, $start_time, $end_time)){
                        $start_time_err = "The selected time slot is not available. Please choose a different time.";
                    }
                }
            } else {
                $specialization_err = "Error finding available doctors. Please try again.";
            }
            $stmt->close();
        }
        
        // Validate date
        if(empty(trim($_POST["appointment_date"]))){
            $appointment_date_err = "Please select a date.";
        } else{
            $appointment_date = trim($_POST["appointment_date"]);
            // Check if date is in the future
            if(strtotime($appointment_date) < strtotime('today')){
                $appointment_date_err = "Please select a future date.";
            }
            // Check if it's a weekend (0 = Sunday, 6 = Saturday)
            $day_of_week = date('w', strtotime($appointment_date));
            if($day_of_week == 0 || $day_of_week == 6){
                $appointment_date_err = "Appointments are not available on weekends.";
            }
        }
        
        // Validate time
        if(empty(trim($_POST["start_time"]))){
            $start_time_err = "Please select a time slot.";
        } else{
            $start_time = trim($_POST["start_time"]);
            $time = strtotime($start_time);
            $hour = date('H', $time);
            $minute = date('i', $time);
            
            // Check if time is within working hours (8 AM to 5 PM)
            if($hour < 8 || $hour >= 17){
                $start_time_err = "Appointments are only available between 8:00 AM and 5:00 PM.";
            }
            // Check if time is on the hour or half hour
            if($minute != 0 && $minute != 30){
                $start_time_err = "Appointments can only be booked on the hour or half hour.";
            }
        }
    }
    
    // Validate date
    if(empty(trim($_POST["appointment_date"]))){
        $appointment_date_err = "Please select a date.";
    } else{
        $appointment_date = trim($_POST["appointment_date"]);
        // Check if date is in the future
        if(strtotime($appointment_date) < strtotime('today')){
            $appointment_date_err = "Please select a future date.";
        }
        // Check if it's a weekend (0 = Sunday, 6 = Saturday)
        $day_of_week = date('w', strtotime($appointment_date));
        if($day_of_week == 0 || $day_of_week == 6){
            $appointment_date_err = "Appointments are not available on weekends.";
        }
    }
    
    // Validate time
    if(empty(trim($_POST["start_time"]))){
        $start_time_err = "Please select a time slot.";
    } else{
        $start_time = trim($_POST["start_time"]);
        $time = strtotime($start_time);
        $hour = date('H', $time);
        $minute = date('i', $time);
        
        // Check if time is within working hours (8 AM to 5 PM)
        if($hour < 8 || $hour >= 17){
            $start_time_err = "Appointments are only available between 8:00 AM and 5:00 PM.";
        }
        // Check if time is on the hour or half hour
        if($minute != 0 && $minute != 30){
            $start_time_err = "Appointments can only be booked on the hour or half hour.";
        }
    }
    
    // Get patient ID
    $patient_id = '';
    $sql = "SELECT id FROM patients WHERE user_id = ?";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("i", $_SESSION["user_id"]);
        if($stmt->execute()){
            $stmt->store_result();
            if($stmt->num_rows == 1){
                $stmt->bind_result($patient_id);
                $stmt->fetch();
            }
        }
        $stmt->close();
    }
    
    // Check input errors before inserting in database
    if(empty($specialization_err) && empty($appointment_date_err) && empty($start_time_err) && !empty($patient_id) && !empty($doctor_id)){
        
        // Calculate end time (30 minutes after start time)
        $end_time = date('H:i:s', strtotime($start_time . ' + 30 minutes'));
        $notes = trim($_POST["notes"]);
        
        // Check for existing appointment at the same time
        $sql = "SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND start_time = ? AND status != 'cancelled'";
        
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("iss", $doctor_id, $appointment_date, $start_time);
            
            if($stmt->execute()){
                $stmt->store_result();
                
                if($stmt->num_rows > 0){
                    $start_time_err = "This time slot is already booked. Please choose another time.";
                } else{
                    // Insert new appointment
                    $sql = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, start_time, end_time, notes, status) 
                            VALUES (?, ?, ?, ?, ?, ?, 'pending')";
                    
                    if($stmt = $conn->prepare($sql)){
                        $insert_stmt = $conn->prepare($sql);
                        if($insert_stmt){
                            $insert_stmt->bind_param("iissss", $patient_id, $doctor_id, $appointment_date, $start_time, $end_time, $notes);
                            
                            if($insert_stmt->execute()){
                                // Get the patient's name for the success message
                                $patient_name = '';
                                $sql_patient = "SELECT full_name FROM patients WHERE user_id = ?";
                                if($stmt_patient = $conn->prepare($sql_patient)){
                                    $stmt_patient->bind_param("i", $_SESSION["user_id"]);
                                    if($stmt_patient->execute()){
                                        $result_patient = $stmt_patient->get_result();
                                        if($result_patient->num_rows == 1){
                                            $patient_row = $result_patient->fetch_assoc();
                                            $patient_name = $patient_row['full_name'];
                                        }
                                    }
                                    $stmt_patient->close();
                                }
                                
                                // Generate turn number for display only (calculated dynamically)
                                $turn_number = generateTurnNumber($conn, $doctor_id, $appointment_date);
                                
                                // Success message with appointment details
                                $success_msg = "<strong>Appointment booked successfully!</strong><br><br>";
                                $success_msg .= "<strong>Patient:</strong> " . htmlspecialchars($patient_name) . "<br>";
                                $success_msg .= "<strong>Date:</strong> " . date('F j, Y', strtotime($appointment_date)) . "<br>";
                                $success_msg .= "<strong>Time:</strong> " . date('g:i A', strtotime($start_time)) . " - " . date('g:i A', strtotime($end_time)) . "<br>";
                                $success_msg .= "<strong>Turn Number:</strong> <span class='badge bg-primary fs-6'>" . $turn_number . "</span><br>";
                                $success_msg .= "<strong>Doctor:</strong> Dr. " . htmlspecialchars($doctor_name) . "<br>";
                                $success_msg .= "<strong>Specialization:</strong> " . htmlspecialchars($selected_specialization) . "<br><br>";
                                $success_msg .= "<div class='alert alert-info mt-2'><i class='fas fa-ticket-alt me-2'></i>Please arrive 15 minutes early and bring your turn number: <strong>" . $turn_number . "</strong></div>";
                                $success_msg .= "<div class='alert alert-success mt-2'><i class='fas fa-check-circle me-2'></i>You will receive a confirmation once your appointment is approved.</div>";
                                
                                // Update queue positions for all appointments of this doctor on this date
                                updateQueuePositions($conn, $doctor_id, $appointment_date);
                                
                                // Clear form
                                $appointment_date = $start_time = $notes = "";
                                $selected_specialization = "";
                            } else {
                                $start_time_err = "Error booking appointment. Please try again.";
                            }
                            $insert_stmt->close();
                        } else {
                            $start_time_err = "Error preparing the booking. Please try again.";
                        }
                    }
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
}

// Set page title
$page_title = "Book Appointment";

// Include header
include("../includes/header.php");
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Book New Appointment</h4>
                </div>
                <div class="card-body">
                    <?php 
                    if(!empty($success_msg)){
                        echo '<div class="alert alert-success">' . $success_msg . '</div>';
                    }
                    ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="mb-3">
                            <label for="specialization" class="form-label">Medical Field</label>
                            <select class="form-select <?php echo (!empty($specialization_err)) ? 'is-invalid' : ''; ?>" 
                                    id="specialization" name="specialization" required>
                                <option value="">-- Select Medical Field --</option>
                                <?php foreach($specializations as $spec): ?>
                                    <option value="<?php echo htmlspecialchars($spec); ?>" 
                                        <?php echo ($selected_specialization === $spec) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($spec); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"><?php echo $specialization_err; ?></div>
                        </div>
                        
                        <?php if(!empty($selected_specialization)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> A doctor from the <?php echo htmlspecialchars($selected_specialization); ?> department will be assigned to you.
                            </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="appointment_date" class="form-label">Appointment Date</label>
                                <input type="date" class="form-control <?php echo (!empty($appointment_date_err)) ? 'is-invalid' : ''; ?>" 
                                       id="appointment_date" name="appointment_date" 
                                       min="<?php echo date('Y-m-d'); ?>" 
                                       value="<?php echo $appointment_date; ?>" required>
                                <div class="invalid-feedback"><?php echo $appointment_date_err; ?></div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="start_time" class="form-label">Time Slot</label>
                                <select class="form-select <?php echo (!empty($start_time_err)) ? 'is-invalid' : ''; ?>" 
                                        id="start_time" name="start_time" required>
                                    <option value="">-- Select Time --</option>
                                    <?php
                                    // Generate time slots from 8:00 AM to 4:30 PM in 30-minute increments
                                    for($hour = 8; $hour < 17; $hour++){
                                        foreach(['00', '30'] as $minute){
                                            // Skip the last half hour slot (4:30 PM is the last valid start time)
                                            if($hour == 16 && $minute == '30') continue;
                                            
                                            $time = sprintf('%02d:%s:00', $hour, $minute);
                                            $display_time = date('h:i A', strtotime($time));
                                            $selected = ($start_time == $time) ? 'selected' : '';
                                            echo "<option value='$time' $selected>$display_time</option>";
                                        }
                                    }
                                    ?>
                                </select>
                                <div class="invalid-feedback"><?php echo $start_time_err; ?></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Any specific concerns or details you'd like to share?"><?php echo $notes; ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="dashboard.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" name="submit_booking" class="btn btn-primary">Book Appointment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Disable weekends in the date picker
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('appointment_date');
    
    // Set minimum date to today
    const today = new Date();
    const dd = String(today.getDate()).padStart(2, '0');
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const yyyy = today.getFullYear();
    dateInput.min = yyyy + '-' + mm + '-' + dd;
    
    dateInput.addEventListener('input', function() {
        const selectedDate = new Date(this.value);
        const day = selectedDate.getDay();
        
        // If it's Saturday (6) or Sunday (0), show error
        if(day === 0 || day === 6) {
            this.setCustomValidity('Appointments are not available on weekends.');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // No need for auto-submit since we don't need to load doctors anymore
});
</script>

<?php
// Include footer
include("../includes/footer.php");
?>
