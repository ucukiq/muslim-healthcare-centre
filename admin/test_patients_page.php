<?php
// Test version of manage patients without session check
include '../includes/config.php';

echo "<h3>Testing Patients Page...</h3>";

// Test database connection
if ($conn->connect_error) {
    die("❌ Database connection failed: " . $conn->connect_error);
} else {
    echo "✅ Database connected successfully!<br>";
}

// Check if patients table exists
$table_check = $conn->query("SHOW TABLES LIKE 'patients'");
if ($table_check->num_rows > 0) {
    echo "✅ Patients table exists!<br>";
    
    // Get patients data
    $patients_query = "SELECT * FROM patients ORDER BY full_name LIMIT 5";
    $patients_result = $conn->query($patients_query);
    
    if ($patients_result) {
        $patients = $patients_result->fetch_all(MYSQLI_ASSOC);
        echo "✅ Found " . count($patients) . " sample patients:<br>";
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Age</th><th>Gender</th><th>Status</th></tr>";
        
        foreach ($patients as $patient) {
            echo "<tr>";
            echo "<td>" . $patient['id'] . "</td>";
            echo "<td>" . $patient['full_name'] . "</td>";
            echo "<td>" . $patient['age'] . "</td>";
            echo "<td>" . $patient['gender'] . "</td>";
            echo "<td>" . $patient['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ Error fetching patients: " . $conn->error . "<br>";
    }
} else {
    echo "❌ Patients table does not exist!<br>";
    echo "<p><a href='test_patients_setup.php'>Create patients table first</a></p>";
}

echo "<h3>Links:</h3>";
echo "<p><a href='manage_patients.php'>Try Manage Patients (with session)</a></p>";
echo "<p><a href='test_patients_setup.php'>Setup Patients Table</a></p>";
echo "<p><a href='dashboard.php'>Back to Dashboard</a></p>";

$conn->close();
?>
