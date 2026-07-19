<?php
// Debug script to check doctor name format
require_once "../includes/config.php";

// Get a sample doctor
$sql = "SELECT id, full_name FROM doctors LIMIT 1";
$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    echo "<h2>Debug Doctor Name</h2>";
    echo "<p>Doctor ID: " . htmlspecialchars($row['id']) . "</p>";
    echo "<p>Raw Name: " . htmlspecialchars($row['full_name']) . "</p>";
    
    // Check if name starts with Dr.
    if (preg_match('/^Dr\.?\s*/i', $row['full_name'])) {
        echo "<p>Name already contains 'Dr.' prefix</p>";
    } else {
        echo "<p>Name does not contain 'Dr.' prefix</p>";
    }
    
    // Show how it will be displayed
    $display_name = 'Dr. ' . preg_replace('/^Dr\.?\s*/i', '', $row['full_name']);
    echo "<p>Will be displayed as: " . htmlspecialchars($display_name) . "</p>";
    
    // Show the actual database query
    echo "<h3>Database Query</h3>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    
    // Show all doctors for reference
    $all_doctors = $conn->query("SELECT id, full_name FROM doctors");
    if ($all_doctors) {
        echo "<h3>All Doctors in Database</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Full Name</th><th>Starts with Dr.</th></tr>";
        while ($doc = $all_doctors->fetch_assoc()) {
            $has_dr = preg_match('/^Dr\.?\s*/i', $doc['full_name']) ? 'Yes' : 'No';
            echo "<tr>";
            echo "<td>" . htmlspecialchars($doc['id']) . "</td>";
            echo "<td>" . htmlspecialchars($doc['full_name']) . "</td>";
            echo "<td>" . $has_dr . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "No doctors found in the database.";
}

// Close connection
$conn->close();
?>
