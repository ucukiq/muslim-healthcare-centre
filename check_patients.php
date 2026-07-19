<?php
// Simple diagnostic script to check patient data
include 'config/database_config.php';

echo "<h2>Patient Data Check</h2>";

// Check if patients table exists
$result = $conn->query("SHOW TABLES LIKE 'patients'");
if ($result->num_rows == 0) {
    die("Error: 'patients' table does not exist in the database.");
}

// Get all patients
$query = "SELECT * FROM patients";
$result = $conn->query($query);

if (!$result) {
    die("Error executing query: " . $conn->error);
}

// Display patient count
$count = $result->num_rows;
echo "<p>Found $count patients in the database.</p>";

// Display patient data in a table
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>IC Number</th><th>Phone</th><th>Status</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['full_name'] ?? 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($row['ic_number'] ?? 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($row['phone'] ?? 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($row['status'] ?? 'N/A') . "</td>";
    echo "</tr>";
}

echo "</table>";

// Check for any sample data
$sample_check = $conn->query("SELECT COUNT(*) as count FROM patients WHERE full_name LIKE '%Ahmad bin Ismail%'");
$sample_count = $sample_check->fetch_assoc()['count'];

if ($sample_count > 0) {
    echo "<div style='margin-top:20px; color:red;'>";
    echo "<strong>Note:</strong> Found $sample_count record(s) containing 'Ahmad bin Ismail' in the database.";
    echo "</div>";
}

$conn->close();
?>
