<?php
// Start session and check admin access
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Include config file
require_once "../../includes/config.php";

// Set content type to JSON
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'data' => []
];

try {
    // Get POST data
    $appointment_id = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
    $notes = trim(filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING) ?? '');
    
    // Validate input
    if (empty($appointment_id)) {
        throw new Exception('Invalid appointment ID');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // 1. Get appointment details
    $stmt = $conn->prepare("SELECT a.*, p.full_name as patient_name, p.id as patient_id, 
                           d.full_name as doctor_name, d.id as doctor_id 
                           FROM appointments a 
                           JOIN patients p ON a.patient_id = p.id 
                           JOIN doctors d ON a.doctor_id = d.id 
                           WHERE a.id = ?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $appointment = $stmt->get_result()->fetch_assoc();
    
    if (!$appointment) {
        throw new Exception('Appointment not found');
    }
    
    // 2. Update appointment status
    $update_sql = "UPDATE appointments SET 
                  status = 'No Show', 
                  notes = CONCAT(IFNULL(notes, ''), ?),
                  updated_at = NOW() 
                  WHERE id = ?";
                  
    $admin_note = "\n\n[No Show - " . date('Y-m-d H:i:s') . "] ";
    $admin_note .= "Marked as No Show by " . $_SESSION['username'];
    
    if (!empty($notes)) {
        $admin_note .= ": " . $notes;
    }
    
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $admin_note, $appointment_id);
    $stmt->execute();
    
    // 3. Update patient's no-show count
    $update_patient = "UPDATE patients 
                      SET no_show_count = IFNULL(no_show_count, 0) + 1,
                      last_no_show = NOW()
                      WHERE id = ?";
    $stmt = $conn->prepare($update_patient);
    $stmt->bind_param("i", $appointment['patient_id']);
    $stmt->execute();
    
    // 4. Log the action
    $log_sql = "INSERT INTO activity_log 
               (user_id, action, details, reference_id, reference_type) 
               VALUES (?, 'mark_no_show', ?, ?, 'appointment')";
    $log_details = "Marked appointment #" . $appointment_id . " as No Show";
    if (!empty($notes)) {
        $log_details .= ": " . $notes;
    }
    
    $stmt = $conn->prepare($log_sql);
    $stmt->bind_param("issi", 
        $_SESSION['id'], 
        $log_details,
        $appointment_id
    );
    $stmt->execute();
    
    // 5. Check if patient should be added to watchlist (3 or more no-shows)
    $check_watchlist = "SELECT no_show_count FROM patients WHERE id = ? AND no_show_count >= 3";
    $stmt = $conn->prepare($check_watchlist);
    $stmt->bind_param("i", $appointment['patient_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Add to watchlist if not already
        $watchlist_note = "Automatically added to watchlist due to multiple no-shows";
        $update_watchlist = "UPDATE patients SET 
                           on_watchlist = 1,
                           watchlist_reason = CONCAT(IFNULL(watchlist_reason, ''), ?)
                           WHERE id = ?";
        $stmt = $conn->prepare($update_watchlist);
        $watchlist_note = "\n\n[Auto-Watchlist " . date('Y-m-d') . "] " . $watchlist_note;
        $stmt->bind_param("si", $watchlist_note, $appointment['patient_id']);
        $stmt->execute();
        
        // Log watchlist addition
        $watchlist_log = "Patient added to watchlist due to multiple no-shows";
        $log_sql = "INSERT INTO activity_log 
                   (user_id, action, details, reference_id, reference_type) 
                   VALUES (?, 'add_to_watchlist', ?, ?, 'patient')";
        $stmt = $conn->prepare($log_sql);
        $stmt->bind_param("issi", 
            $_SESSION['id'], 
            $watchlist_log,
            $appointment['patient_id']
        );
        $stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    // Prepare success response
    $response = [
        'success' => true,
        'message' => 'Appointment marked as No Show',
        'data' => [
            'appointment_id' => $appointment_id,
            'patient_name' => $appointment['patient_name'],
            'doctor_name' => $appointment['doctor_name'],
            'appointment_date' => $appointment['appointment_date'],
            'appointment_time' => $appointment['start_time']
        ]
    ];
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?>
