<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin'){
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Include config file and functions
require_once "../includes/config.php";
require_once "doctor_functions.php";

// Get doctor ID
$doctor_id = $_GET['id'] ?? 0;

if(empty($doctor_id) || !is_numeric($doctor_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid doctor ID']);
    exit;
}

// Get doctor data
try {
    $doctor = getDoctorById($conn, $doctor_id);

    if(!$doctor) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Doctor not found']);
        exit;
    }

    // Get doctor schedule (with error handling)
    $schedule = [];
    try {
        $schedule = getDoctorSchedule($conn, $doctor_id);
    } catch(Exception $e) {
        // Schedule table might not exist, use empty array
        $schedule = [];
    }

    // Get doctor performance (with error handling)
    $performance = [];
    try {
        $performance = getDoctorPerformance($conn, $doctor_id);
    } catch(Exception $e) {
        // Performance tables might not exist, use default data
        $performance = [
            'total_appointments' => 0,
            'completed_appointments' => 0,
            'cancelled_appointments' => 0,
            'average_rating' => 0,
            'patient_satisfaction' => 0
        ];
    }

    // Format response
    $response = [
        'success' => true,
        'doctor' => [
            'id' => $doctor['id'],
            'doctor_id' => $doctor['doctor_id'] ?? 'N/A',
            'full_name' => $doctor['full_name'] ?? 'Unknown',
            'nric' => $doctor['nric'] ?? 'N/A',
            'age' => $doctor['age'] ?? 0,
            'email' => $doctor['email'] ?? 'N/A',
            'phone' => $doctor['phone'] ?? 'N/A',
            'address' => $doctor['address'] ?? 'N/A',
            'specialization' => $doctor['specialization'] ?? 'General',
            'status' => $doctor['status'] ?? 'Inactive',
            'profile_photo' => $doctor['profile_photo'] ?? '',
            'username' => $doctor['username'] ?? 'N/A',
            'created_at' => $doctor['created_at'] ?? '',
            'schedule' => $schedule,
            'performance' => $performance
        ]
    ];

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
