<?php
// Database update script for turn numbers
require_once "includes/config.php";

echo "<h2>Updating Database for Turn Number System</h2>";

try {
    // Add turn_number column
    $sql1 = "ALTER TABLE appointments ADD COLUMN turn_number INT NULL";
    if ($conn->query($sql1)) {
        echo "<p class='text-success'>✓ Added turn_number column to appointments table</p>";
    } else {
        echo "<p class='text-warning'>- turn_number column might already exist</p>";
    }
    
    // Add queue_position column
    $sql2 = "ALTER TABLE appointments ADD COLUMN queue_position INT NULL";
    if ($conn->query($sql2)) {
        echo "<p class='text-success'>✓ Added queue_position column to appointments table</p>";
    } else {
        echo "<p class='text-warning'>- queue_position column might already exist</p>";
    }
    
    // Create index for better performance
    $sql3 = "CREATE INDEX idx_appointments_turn ON appointments(doctor_id, appointment_date, turn_number)";
    if ($conn->query($sql3)) {
        echo "<p class='text-success'>✓ Created turn number index</p>";
    } else {
        echo "<p class='text-warning'>- Turn index might already exist</p>";
    }
    
    $sql4 = "CREATE INDEX idx_appointments_queue ON appointments(doctor_id, appointment_date, status, queue_position)";
    if ($conn->query($sql4)) {
        echo "<p class='text-success'>✓ Created queue index</p>";
    } else {
        echo "<p class='text-warning'>- Queue index might already exist</p>";
    }
    
    echo "<h3 class='text-success mt-4'>Database update completed successfully!</h3>";
    echo "<p><a href='index.php' class='btn btn-primary'>Return to Home</a></p>";
    
} catch (Exception $e) {
    echo "<p class='text-danger'>Error: " . $e->getMessage() . "</p>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Update - Muslim Healthcare Centre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Database Update</h4>
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
