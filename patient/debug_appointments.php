<?php
// Debug script to check appointments
session_start();
require_once "../includes/config.php";

echo "<h2>Debug Information</h2>";

// 1. Check if user is logged in and get user info
echo "<h3>User Information:</h3>";
echo "<pre>";
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    echo "User ID: " . $_SESSION["user_id"] . "\n";
    echo "Username: " . $_SESSION["username"] . "\n";
    echo "Role: " . $_SESSION["role"] . "\n";
} else {
    die("User not logged in");
}
echo "</pre>";

// 2. Check if patient exists and get patient ID
$patient_id = null;
$sql = "SELECT id FROM patients WHERE user_id = ?";
if($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $_SESSION["user_id"]);
    if($stmt->execute()) {
        $result = $stmt->get_result();
        if($row = $result->fetch_assoc()) {
            $patient_id = $row['id'];
            echo "<p>Patient ID found: " . $patient_id . "</p>";
        } else {
            echo "<p>No patient record found for this user.</p>";
        }
    }
    $stmt->close();
}

// 3. Check appointments table structure
$sql = "DESCRIBE appointments";
$result = $conn->query($sql);
if($result) {
    echo "<h3>Appointments Table Structure:</h3>";
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 4. Check if there are any appointments for this patient
if($patient_id) {
    $sql = "SELECT COUNT(*) as count FROM appointments WHERE patient_id = ?";
    if($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $patient_id);
        if($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            echo "<p>Number of appointments found: " . $row['count'] . "</p>";
            
            // If there are appointments, show them
            if($row['count'] > 0) {
                $sql = "SELECT a.*, d.full_name as doctor_name, d.specialization 
                        FROM appointments a 
                        JOIN doctors d ON a.doctor_id = d.id 
                        WHERE a.patient_id = ? 
                        ORDER BY a.appointment_date, a.start_time";
                
                if($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("i", $patient_id);
                    if($stmt->execute()) {
                        $result = $stmt->get_result();
                        echo "<h3>Appointments:</h3>";
                        echo "<table border='1'><tr><th>ID</th><th>Doctor</th><th>Date</th><th>Start Time</th><th>End Time</th><th>Status</th></tr>";
                        while($appt = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $appt['id'] . "</td>";
                            echo "<td>Dr. " . htmlspecialchars($appt['doctor_name']) . "</td>";
                            echo "<td>" . $appt['appointment_date'] . "</td>";
                            echo "<td>" . $appt['start_time'] . "</td>";
                            echo "<td>" . $appt['end_time'] . "</td>";
                            echo "<td>" . $appt['status'] . "</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    }
                }
            }
        }
        $stmt->close();
    }
}

// 5. Check if there are any appointments at all
$sql = "SELECT COUNT(*) as count FROM appointments";
$result = $conn->query($sql);
if($result) {
    $row = $result->fetch_assoc();
    echo "<p>Total appointments in database: " . $row['count'] . "</p>";
}

$conn->close();
?>
