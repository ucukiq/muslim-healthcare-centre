<?php
// Turn number management functions

/**
 * Generate turn number for a new appointment
 * @param mysqli $conn Database connection
 * @param int $doctor_id Doctor ID
 * @param string $appointment_date Appointment date (YYYY-MM-DD)
 * @return int Generated turn number
 */
function generateTurnNumber($conn, $doctor_id, $appointment_date) {
    // Count existing appointments for this doctor on this date
    $sql = "SELECT COUNT(*) as count 
            FROM appointments 
            WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("is", $doctor_id, $appointment_date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['count'] + 1;
    }
    
    return 1;
}

/**
 * Update queue positions for all confirmed appointments of a doctor on a specific date
 * @param mysqli $conn Database connection
 * @param int $doctor_id Doctor ID
 * @param string $appointment_date Appointment date (YYYY-MM-DD)
 */
function updateQueuePositions($conn, $doctor_id, $appointment_date) {
    // Since we don't have queue_position column, this function is now a no-op
    // Positions are calculated dynamically based on start_time and created_at
    return true;
}

/**
 * Get current turn number being served for a doctor
 * @param mysqli $conn Database connection
 * @param int $doctor_id Doctor ID
 * @param string $appointment_date Appointment date (YYYY-MM-DD)
 * @return int|null Current turn number or null if none
 */
function getCurrentTurn($conn, $doctor_id, $appointment_date) {
    // Get the most recently completed appointment
    $sql = "SELECT COUNT(*) as completed_count 
            FROM appointments 
            WHERE doctor_id = ? AND appointment_date = ? AND status = 'completed'";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("is", $doctor_id, $appointment_date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['completed_count'];
    }
    
    return 0;
}

/**
 * Get next patient in queue for a doctor
 * @param mysqli $conn Database connection
 * @param int $doctor_id Doctor ID
 * @param string $appointment_date Appointment date (YYYY-MM-DD)
 * @return array|null Next patient data or null
 */
function getNextPatient($conn, $doctor_id, $appointment_date) {
    // Get the next confirmed appointment ordered by time
    $sql = "SELECT a.id, p.full_name, p.phone, a.start_time
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            WHERE a.doctor_id = ? AND a.appointment_date = ? AND a.status = 'confirmed'
            ORDER BY a.start_time ASC, a.created_at ASC
            LIMIT 1";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("is", $doctor_id, $appointment_date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row) {
            // Calculate turn number dynamically
            $turn_sql = "SELECT COUNT(*) as position 
                         FROM appointments 
                         WHERE doctor_id = ? AND appointment_date = ? AND status = 'confirmed' 
                         AND ((start_time < ?) OR (start_time = ? AND created_at < (
                             SELECT created_at FROM appointments WHERE id = ?
                         )))";
            
            if ($turn_stmt = $conn->prepare($turn_sql)) {
                $turn_stmt->bind_param("isssi", $doctor_id, $appointment_date, 
                                      $row['start_time'], $row['start_time'], $row['id']);
                $turn_stmt->execute();
                $turn_result = $turn_stmt->get_result();
                $turn_row = $turn_result->fetch_assoc();
                $turn_stmt->close();
                
                $row['turn_number'] = $turn_row['position'] + 1;
                $row['queue_position'] = $turn_row['position'] + 1;
            } else {
                $row['turn_number'] = 1;
                $row['queue_position'] = 1;
            }
        }
        
        return $row;
    }
    
    return null;
}

/**
 * Get waiting queue for a doctor
 * @param mysqli $conn Database connection
 * @param int $doctor_id Doctor ID
 * @param string $appointment_date Appointment date (YYYY-MM-DD)
 * @return array Waiting patients
 */
function getWaitingQueue($conn, $doctor_id, $appointment_date) {
    $patients = [];
    
    // Get all confirmed/pending appointments ordered by time
    $sql = "SELECT a.id, a.start_time, p.full_name, p.phone, a.status
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            WHERE a.doctor_id = ? AND a.appointment_date = ? AND a.status IN ('pending', 'confirmed')
            ORDER BY a.start_time ASC, a.created_at ASC";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("is", $doctor_id, $appointment_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $position = 1;
        while ($row = $result->fetch_assoc()) {
            $row['turn_number'] = $position;
            $row['queue_position'] = $position;
            $patients[] = $row;
            $position++;
        }
        $stmt->close();
    }
    
    return $patients;
}
?>
