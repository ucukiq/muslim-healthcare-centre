<?php
// Test database connection and create patients table
include '../includes/config.php';

echo "<h3>Testing Database Connection...</h3>";

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
} else {
    echo "✅ Database connection successful!<br>";
}

echo "<h3>Creating Patients Table...</h3>";

// Create patients table
$sql = "CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    ic_number VARCHAR(50) NOT NULL,
    age INT NOT NULL,
    gender ENUM('Male', 'Female') NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    address TEXT,
    medical_notes TEXT,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "✅ Patients table created successfully!<br>";
} else {
    echo "❌ Error creating table: " . $conn->error . "<br>";
}

// Check if table has data
$result = $conn->query("SELECT COUNT(*) as count FROM patients");
$count = $result->fetch_assoc()['count'];
echo "<h3>Current patients count: $count</h3>";

if ($count == 0) {
    echo "<h3>Adding sample data...</h3>";
    
    // Insert sample data
    $samples = [
        ['Ahmad bin Ismail', '850123-14-5678', 38, 'Male', '012-3456789', 'ahmad@email.com', 'KL', 'No allergies', 'Active'],
        ['Siti Aishah', '900234-12-3456', 33, 'Female', '013-9876543', 'siti@email.com', 'KL', 'Mild asthma', 'Active'],
        ['Mohamed Hassan', '880345-10-7890', 35, 'Male', '014-5678901', 'mohamed@email.com', 'KL', 'Diabetes', 'Active']
    ];
    
    foreach ($samples as $sample) {
        $insert = "INSERT INTO patients (full_name, ic_number, age, gender, phone, email, address, medical_notes, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert);
        $stmt->bind_param('ssissssss', $sample[0], $sample[1], $sample[2], $sample[3], $sample[4], $sample[5], $sample[6], $sample[7], $sample[8]);
        
        if ($stmt->execute()) {
            echo "✅ Added: " . $sample[0] . "<br>";
        }
    }
}

echo "<h3>🎉 Setup Complete!</h3>";
echo "<p><a href='manage_patients.php'>Go to Manage Patients</a></p>";
echo "<p><a href='dashboard.php'>Back to Dashboard</a></p>";

$conn->close();
?>
