<?php
// Quick aging script - no session check for easy testing
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

echo "<h2>🔄 Running Aging Script...</h2>";

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
                echo "<div style='padding: 10px; margin: 5px; background: #e8f5e8; border-left: 4px solid #28a745;'>";
                echo "✅ Updated Dr. " . htmlspecialchars($doctor['full_name']) . ": Age = $random_age, Address = $random_address";
                echo "</div>";
            }
        }
        
        echo "<br><div style='padding: 15px; background: #d4edda; color: #155724; border-radius: 5px;'>";
        echo "<strong>🎉 Total doctors updated: $updated</strong>";
        echo "</div>";
        
        echo "<br><div style='padding: 10px; background: #f8f9fa; border-radius: 5px;'>";
        echo "<a href='manage_doctors_complete.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📋 View Updated Doctors</a>";
        echo "</div>";
        
    } else {
        echo "<div style='padding: 15px; background: #f8d7da; color: #721c24; border-radius: 5px;'>";
        echo "❌ No doctors found in the database.";
        echo "</div>";
    }
    
} catch(Exception $e) {
    echo "<div style='padding: 15px; background: #f8d7da; color: #721c24; border-radius: 5px;'>";
    echo "❌ Error: " . $e->getMessage();
    echo "</div>";
}

echo "<br><div style='padding: 10px; background: #e9ecef; border-radius: 5px;'>";
echo "<small>✨ Script completed successfully!</small>";
echo "</div>";
?>
