<?php
include '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'] ?? 0;
    
    if ($patient_id > 0) {
        // Delete the patient
        $delete_query = "DELETE FROM patients WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param('i', $patient_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Patient deleted successfully!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error deleting patient: ' . $conn->error
            ]);
        }
        $stmt->close();
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid patient ID'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

$conn->close();
?>
