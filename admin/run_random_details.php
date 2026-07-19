<?php
// Initialize the session
session_start();

// Check if the user is logged in as admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin'){
    echo "Access denied. Admin login required.";
    exit;
}

// Include config file
require_once "../includes/config.php";

// Generate random age between 25 and 65
function generateRandomAge() {
    return rand(25, 65);
}

// Generate random Malaysian addresses
function generateRandomAddress() {
    $addresses = [
        "123 Jalan Sultan, Kuala Lumpur, 50000",
        "456 Jalan Ampang, Kuala Lumpur, 50450",
        "789 Jalan Ipoh, Kuala Lumpur, 51200",
        "321 Jalan Puchong, Selangor, 47100",
        "654 Jalan Klang, Selangor, 40000",
        "987 Jalan Petaling, Kuala Lumpur, 57000",
        "147 Jalan Tun Razak, Kuala Lumpur, 50400",
        "258 Jalan Bukit Bintang, Kuala Lumpur, 55100",
        "369 Jalan Masjid India, Kuala Lumpur, 50100",
        "741 Jalan Chow Kit, Kuala Lumpur, 50300",
        "852 Jalan Raja Chulan, Kuala Lumpur, 50200",
        "963 Jalan Bukit Jalil, Kuala Lumpur, 57000",
        "159 Jalan Shah Alam, Selangor, 40000",
        "753 Jalan Subang, Selangor, 47500",
        "951 Jalan Kajang, Selangor, 43000",
        "357 Jalan Seremban, Negeri Sembilan, 70000",
        "456 Jalan Melaka, Melaka, 75000",
        "654 Jalan Johor Bharu, Johor, 80000",
        "852 Jalan Penang, Pulau Pinang, 10400",
        "147 Jalan Ipoh, Perak, 30000"
    ];
    
    return $addresses[array_rand($addresses)];
}

// Update all doctors with random age and address
try {
    // Get all doctors
    $query = "SELECT id, full_name FROM doctors";
    $result = $conn->query($query);
    
    if($result->num_rows > 0) {
        $updated = 0;
        
        while($doctor = $result->fetch_assoc()) {
            $random_age = generateRandomAge();
            $random_address = generateRandomAddress();
            
            // Update doctor with random age and address
            $update_query = "UPDATE doctors SET age = ?, address = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('isi', $random_age, $random_address, $doctor['id']);
            
            if($stmt->execute()) {
                $updated++;
                echo "Updated Dr. " . htmlspecialchars($doctor['full_name']) . ": Age = $random_age, Address = $random_address<br>";
            }
        }
        
        echo "<br><strong>Total doctors updated: $updated</strong><br>";
        echo "<br><a href='manage_doctors_complete.php' class='btn btn-primary'>Back to Doctor Management</a>";
        
    } else {
        echo "No doctors found in the database.";
    }
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
