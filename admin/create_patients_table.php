<?php
include '../includes/config.php';

// Create patients table if it doesn't exist
$create_table_query = "CREATE TABLE IF NOT EXISTS patients (
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

if ($conn->query($create_table_query)) {
    echo "✅ Patients table created successfully!<br>";
} else {
    echo "❌ Error creating patients table: " . $conn->error . "<br>";
}

// Check if table exists and show structure
$check_table = $conn->query("DESCRIBE patients");
if ($check_table) {
    echo "<h3>Patients Table Structure:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    while ($row = $check_table->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Insert sample data if table is empty
$count_query = "SELECT COUNT(*) as count FROM patients";
$count_result = $conn->query($count_query);
$count = $count_result->fetch_assoc()['count'];

if ($count == 0) {
    echo "<h3>Inserting sample patient data...</h3>";
    
    $sample_patients = [
        [
            'full_name' => 'Ahmad bin Ismail',
            'ic_number' => '850123-14-5678',
            'age' => 38,
            'gender' => 'Male',
            'phone' => '012-3456789',
            'email' => 'ahmad.ismail@email.com',
            'address' => '123 Jalan Sultan, Kuala Lumpur, 50000',
            'medical_notes' => 'No known allergies. Regular check-ups required.',
            'status' => 'Active',
            'photo' => 'default_patient.png'
        ],
        [
            'full_name' => 'Siti Aishah binti Rahman',
            'ic_number' => '900234-12-3456',
            'age' => 33,
            'gender' => 'Female',
            'phone' => '013-9876543',
            'email' => 'siti.aishah@email.com',
            'address' => '456 Jalan Ampang, Kuala Lumpur, 50450',
            'medical_notes' => 'Mild asthma, uses inhaler occasionally.',
            'status' => 'Active',
            'photo' => 'default_patient.png'
        ],
        [
            'full_name' => 'Mohamed bin Hassan',
            'ic_number' => '880345-10-7890',
            'age' => 35,
            'gender' => 'Male',
            'phone' => '014-5678901',
            'email' => 'mohamed.hassan@email.com',
            'address' => '789 Jalan Ipoh, Kuala Lumpur, 51200',
            'medical_notes' => 'Diabetes Type 2, on medication.',
            'status' => 'Active',
            'photo' => 'default_patient.png'
        ],
        [
            'full_name' => 'Fatimah binti Ali',
            'ic_number' => '920456-15-2345',
            'age' => 31,
            'gender' => 'Female',
            'phone' => '016-2345678',
            'email' => 'fatimah.ali@email.com',
            'address' => '321 Jalan Tun Razak, Kuala Lumpur, 50400',
            'medical_notes' => 'No known medical conditions.',
            'status' => 'Active',
            'photo' => 'default_patient.png'
        ],
        [
            'full_name' => 'Abdul Rahman bin Karim',
            'ic_number' => '870567-11-6789',
            'age' => 36,
            'gender' => 'Male',
            'phone' => '017-8901234',
            'email' => 'abdul.rahman@email.com',
            'address' => '567 Jalan Bukit Bintang, Kuala Lumpur, 52100',
            'medical_notes' => 'Hypertension, controlled with medication.',
            'status' => 'Inactive',
            'photo' => 'default_patient.png'
        ]
    ];
    
    foreach ($sample_patients as $patient) {
        $insert_query = "INSERT INTO patients (full_name, ic_number, age, gender, phone, email, address, medical_notes, status, photo) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param('ssisssssss', 
            $patient['full_name'], 
            $patient['ic_number'], 
            $patient['age'], 
            $patient['gender'], 
            $patient['phone'], 
            $patient['email'], 
            $patient['address'], 
            $patient['medical_notes'], 
            $patient['status'], 
            $patient['photo']
        );
        
        if ($stmt->execute()) {
            echo "✅ Added: " . $patient['full_name'] . "<br>";
        } else {
            echo "❌ Error adding " . $patient['full_name'] . ": " . $stmt->error . "<br>";
        }
    }
    
    echo "<h3>✅ Sample data inserted successfully!</h3>";
} else {
    echo "<h3>Patients table already has $count records</h3>";
}

// Create default patient image directory and placeholder
$image_dir = '../assets/images/patients';
if (!is_dir($image_dir)) {
    mkdir($image_dir, 0755, true);
    echo "✅ Created patients images directory<br>";
}

// Create a simple placeholder image
$placeholder_content = '<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg">
    <rect width="100%" height="100%" fill="#e0e0e0"/>
    <circle cx="50" cy="40" r="15" fill="#999"/>
    <ellipse cx="50" cy="75" rx="25" ry="15" fill="#999"/>
    <text x="50" y="95" text-anchor="middle" font-family="Arial" font-size="10" fill="#666">Patient</text>
</svg>';

file_put_contents($image_dir . '/default_patient.png', $placeholder_content);
echo "✅ Created default patient placeholder image<br>";

echo "<h3>🎉 Setup Complete!</h3>";
echo "<p><a href='manage_patients.php'>Go to Manage Patients</a></p>";
echo "<p><a href='../admin/dashboard.php'>Back to Dashboard</a></p>";

$conn->close();
?>
