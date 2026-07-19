<?php
// Test script for turn number system
require_once "includes/config.php";
require_once "includes/turn_functions.php";

echo "<h2>Turn Number System Test</h2>";

// Test 1: Generate turn numbers for existing appointments
echo "<h3>Testing Turn Number Generation</h3>";

// Get appointments without turn numbers
$sql = "SELECT id, doctor_id, appointment_date FROM appointments WHERE turn_number IS NULL ORDER BY doctor_id, appointment_date, start_time";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<p>Found " . $result->num_rows . " appointments without turn numbers. Assigning...</p>";
    
    while ($row = $result->fetch_assoc()) {
        $turn_number = generateTurnNumber($conn, $row['doctor_id'], $row['appointment_date']);
        
        // Update the appointment
        $update_sql = "UPDATE appointments SET turn_number = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ii", $turn_number, $row['id']);
        $stmt->execute();
        $stmt->close();
        
        echo "<p class='text-success'>✓ Appointment #" . $row['id'] . " assigned turn number: " . $turn_number . "</p>";
    }
    
    // Update queue positions
    echo "<h3>Updating Queue Positions</h3>";
    
    // Get all unique doctor-date combinations
    $sql_dates = "SELECT DISTINCT doctor_id, appointment_date FROM appointments WHERE appointment_date >= CURDATE() ORDER BY doctor_id, appointment_date";
    $result_dates = $conn->query($sql_dates);
    
    if ($result_dates && $result_dates->num_rows > 0) {
        while ($row = $result_dates->fetch_assoc()) {
            updateQueuePositions($conn, $row['doctor_id'], $row['appointment_date']);
            echo "<p class='text-success'>✓ Updated queue positions for Doctor #" . $row['doctor_id'] . " on " . $row['appointment_date'] . "</p>";
        }
    }
} else {
    echo "<p class='text-info'>All appointments already have turn numbers.</p>";
}

// Test 2: Display current queue for a doctor
echo "<h3>Sample Queue Display</h3>";

// Get a doctor with appointments today
$today = date('Y-m-d');
$sql = "SELECT DISTINCT doctor_id FROM appointments WHERE appointment_date = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $doctor_id = $row['doctor_id'];
    
    // Get waiting queue
    $queue = getWaitingQueue($conn, $doctor_id, $today);
    $current_turn = getCurrentTurn($conn, $doctor_id, $today);
    $next_patient = getNextPatient($conn, $doctor_id, $today);
    
    echo "<div class='card'>";
    echo "<div class='card-header bg-info text-white'>";
    echo "<h5>Queue for Doctor #" . $doctor_id . " - " . date('F j, Y') . "</h5>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    echo "<p><strong>Current Turn:</strong> " . ($current_turn ?: 'None') . "</p>";
    echo "<p><strong>Next Patient:</strong> " . ($next_patient ? "Turn #" . $next_patient['turn_number'] . " - " . $next_patient['full_name'] : 'None') . "</p>";
    echo "<p><strong>Waiting:</strong> " . count($queue) . " patients</p>";
    
    if (!empty($queue)) {
        echo "<table class='table table-sm'>";
        echo "<thead><tr><th>Position</th><th>Turn #</th><th>Patient</th><th>Time</th><th>Status</th></tr></thead>";
        echo "<tbody>";
        
        foreach ($queue as $patient) {
            echo "<tr>";
            echo "<td>" . $patient['queue_position'] . "</td>";
            echo "<td><span class='badge bg-info'>" . $patient['turn_number'] . "</span></td>";
            echo "<td>" . htmlspecialchars($patient['full_name']) . "</td>";
            echo "<td>" . date('h:i A', strtotime($patient['start_time'])) . "</td>";
            echo "<td><span class='badge bg-" . ($patient['status'] == 'confirmed' ? 'success' : 'secondary') . "'>" . ucfirst($patient['status']) . "</span></td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
    }
    
    echo "</div></div>";
} else {
    echo "<p class='text-warning'>No appointments found for today to test queue display.</p>";
}

echo "<div class='mt-4'>";
echo "<a href='index.php' class='btn btn-primary me-2'>Return to Home</a>";
echo "<a href='admin/queue_management.php' class='btn btn-success'>View Queue Management</a>";
echo "</div>";

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Turn Number System Test - Muslim Healthcare Centre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Turn Number System Test</h4>
                    </div>
                    <div class="card-body">
                        <!-- PHP output will appear here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
