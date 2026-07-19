<?php
// Start session
session_start();

// Include config and turn functions files
require_once "../includes/config.php";
require_once "../includes/turn_functions.php";

// Set content type to JSON
header('Content-Type: application/json');

// Enable CORS for local development
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Get today's date
$today = date('Y-m-d');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => [
        'stats' => [
            'total_patients' => 0,
            'current_turn' => '---',
            'waiting' => 0
        ],
        'queues' => [],
        'doctors' => []
    ]
];

try {
    // Get all doctors with active appointments today
    $doctors_sql = "SELECT DISTINCT d.id, d.full_name, d.specialization 
                   FROM doctors d 
                   INNER JOIN appointments a ON d.id = a.doctor_id 
                   WHERE a.appointment_date = ? 
                   AND a.status IN ('pending', 'confirmed', 'in_progress')
                   ORDER BY d.full_name";
    
    $doctors = [];
    if ($stmt = $conn->prepare($doctors_sql)) {
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $doctors[] = $row;
        }
        $stmt->close();
    }
    
    $response['data']['doctors'] = $doctors;
    
    // Collect all queue data across all doctors
    $all_queues = [];
    $total_patients = 0;
    $current_turns = [];
    $waiting_counts = [];
    
    foreach ($doctors as $doctor) {
        // Get waiting queue for this doctor
        $waiting_queue = getWaitingQueue($conn, $doctor['id'], $today);
        
        // Get current turn for this doctor
        $current_turn = getCurrentTurn($conn, $doctor['id'], $today);
        
        // Add doctor info to each patient
        foreach ($waiting_queue as $patient) {
            $patient['doctor_name'] = $doctor['full_name'];
            $patient['doctor_specialization'] = $doctor['specialization'];
            $patient['appointment_time'] = date('g:i A', strtotime($patient['start_time']));
            $all_queues[] = $patient;
        }
        
        // Update stats
        $total_patients += count($waiting_queue);
        if ($current_turn) {
            $current_turns[] = $current_turn;
        }
        $waiting_counts[$doctor['id']] = count($waiting_queue);
    }
    
    // Sort queues by turn number and then by appointment time
    usort($all_queues, function($a, $b) {
        if ($a['turn_number'] != $b['turn_number']) {
            return $a['turn_number'] - $b['turn_number'];
        }
        return strcmp($a['start_time'], $b['start_time']);
    });
    
    // Update response stats
    $response['data']['stats']['total_patients'] = $total_patients;
    $response['data']['stats']['current_turn'] = !empty($current_turns) ? implode(', ', $current_turns) : '---';
    $response['data']['stats']['waiting'] = $total_patients;
    
    // Update queues
    $response['data']['queues'] = $all_queues;
    
    $response['success'] = true;
    $response['message'] = 'Queue data retrieved successfully';
    
} catch (Exception $e) {
    $response['message'] = 'Error retrieving queue data: ' . $e->getMessage();
}

// Close connection
$conn->close();

// Return JSON response
echo json_encode($response);
?>
