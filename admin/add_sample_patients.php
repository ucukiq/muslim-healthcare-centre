<?php
session_start();
// Ensure only admins can access this
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/config.php';

// Sample patient data
$patients = [
    [
        'full_name' => 'Ahmad bin Abdullah',
        'email' => 'ahmad@example.com',
        'phone' => '0112345678',
        'address' => '123 Jalan Melur, Kuala Lumpur',
        'date_of_birth' => '1990-05-15',
        'gender' => 'Male'
    ],
    [
        'full_name' => 'Siti binti Ali',
        'email' => 'siti@example.com',
        'phone' => '0123456789',
        'address' => '456 Jalan Mawar, Petaling Jaya',
        'date_of_birth' => '1985-08-20',
        'gender' => 'Female'
    ],
    [
        'full_name' => 'Muthu a/l Samy',
        'email' => 'muthu@example.com',
        'phone' => '0134567890',
        'address' => '789 Jalan Kenanga, Subang Jaya',
        'date_of_birth' => '1992-11-30',
        'gender' => 'Male'
    ],
    [
        'full_name' => 'Aminah binti Hassan',
        'email' => 'aminah@example.com',
        'phone' => '0145678901',
        'address' => '321 Jalan Anggerik, Shah Alam',
        'date_of_birth' => '1988-03-25',
        'gender' => 'Female'
    ],
    [
        'full_name' => 'Wong Chen Wei',
        'email' => 'wong@example.com',
        'phone' => '0156789012',
        'address' => '654 Jalan Cempaka, Puchong',
        'date_of_birth' => '1995-07-10',
        'gender' => 'Male'
    ]
];

$successCount = 0;
$errorMessages = [];

foreach ($patients as $patient) {
    try {
        // Generate username from first name and last name initial
        $nameParts = explode(' ', $patient['full_name']);
        $firstName = strtolower($nameParts[0]);
        $lastInitial = strtolower(substr(end($nameParts), 0, 1));
        $username = $firstName . $lastInitial . rand(10, 99);
        
        // Set default password
        $password = 'P@word';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Start transaction
        $conn->begin_transaction();
        
        // Insert into users table
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'patient')");
        $stmt->bind_param("ss", $username, $hashedPassword);
        $stmt->execute();
        $user_id = $conn->insert_id;
        
        // Insert into patients table
        $stmt = $conn->prepare("INSERT INTO patients (user_id, full_name, email, phone, address, date_of_birth, gender) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", 
            $user_id, 
            $patient['full_name'], 
            $patient['email'], 
            $patient['phone'], 
            $patient['address'], 
            $patient['date_of_birth'], 
            $patient['gender']
        );
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        $successCount++;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $errorMessages[] = "Error adding patient {$patient['full_name']}: " . $e->getMessage();
    }
}

// Prepare response
$message = "Successfully added $successCount patients.";
if (!empty($errorMessages)) {
    $message .= " " . count($errorMessages) . " patients could not be added.";
}

$_SESSION['message'] = $message;
$_SESSION['success'] = $successCount > 0;

// If there were errors, add them to the session
if (!empty($errorMessages)) {
    $_SESSION['error_messages'] = $errorMessages;
}

header('Location: manage_patients.php');
exit();
?>
