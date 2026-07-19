<?php
// Database functions for doctor management

// Add new doctor
function addDoctor($conn, $doctorData) {
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Insert into users table first
        $hashed_password = password_hash($doctorData['password'], PASSWORD_DEFAULT);
        $user_query = "INSERT INTO users (username, password, role) VALUES (?, ?, 'doctor')";
        $stmt = $conn->prepare($user_query);
        $stmt->bind_param('ss', $doctorData['username'], $hashed_password);
        $stmt->execute();
        $user_id = $conn->insert_id;
        
        // Insert into doctors table
        $doctor_query = "INSERT INTO doctors (user_id, full_name, nric, age, email, phone, address, specialization, status, profile_photo) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($doctor_query);
        $stmt->bind_param('isisssssss', 
            $user_id, 
            $doctorData['full_name'], 
            $doctorData['nric'], 
            $doctorData['age'], 
            $doctorData['email'], 
            $doctorData['phone'], 
            $doctorData['address'], 
            $doctorData['specialization'], 
            $doctorData['status'], 
            $doctorData['profile_photo']
        );
        $stmt->execute();
        $doctor_id = $conn->insert_id;
        
        // Insert work schedule
        if(!empty($doctorData['work_days'])) {
            foreach($doctorData['work_days'] as $day) {
                $schedule_query = "INSERT INTO doctor_schedules (doctor_id, day, start_time, end_time) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($schedule_query);
                $stmt->bind_param('isss', $doctor_id, $day, $doctorData['start_time1'], $doctorData['end_time1']);
                $stmt->execute();
                
                if(!empty($doctorData['start_time2'])) {
                    $schedule_query = "INSERT INTO doctor_schedules (doctor_id, day, start_time, end_time) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($schedule_query);
                    $stmt->bind_param('isss', $doctor_id, $day, $doctorData['start_time2'], $doctorData['end_time2']);
                    $stmt->execute();
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error adding doctor: " . $e->getMessage());
        return false;
    }
}

// Get all doctors
function getAllDoctors($conn) {
    $query = "SELECT d.*, u.username FROM doctors d JOIN users u ON d.user_id = u.id ORDER BY d.full_name";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get doctor by ID
function getDoctorById($conn, $doctor_id) {
    $query = "SELECT d.*, u.username FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Update doctor
function updateDoctor($conn, $doctor_id, $doctorData) {
    try {
        $conn->begin_transaction();
        
        // Update doctors table
        $doctor_query = "UPDATE doctors SET full_name = ?, nric = ?, age = ?, email = ?, phone = ?, address = ?, specialization = ?, status = ?, profile_photo = COALESCE(?, profile_photo) WHERE id = ?";
        $stmt = $conn->prepare($doctor_query);
        $stmt->bind_param('ssissssssi', 
            $doctorData['full_name'], 
            $doctorData['nric'], 
            $doctorData['age'], 
            $doctorData['email'], 
            $doctorData['phone'], 
            $doctorData['address'], 
            $doctorData['specialization'], 
            $doctorData['status'], 
            $doctorData['profile_photo'], 
            $doctor_id
        );
        $stmt->execute();
        
        // Update users table if email changed
        if(!empty($doctorData['email'])) {
            $user_query = "UPDATE users SET email = ? WHERE id = (SELECT user_id FROM doctors WHERE id = ?)";
            $stmt = $conn->prepare($user_query);
            $stmt->bind_param('si', $doctorData['email'], $doctor_id);
            $stmt->execute();
        }
        
        // Update work schedule
        if(!empty($doctorData['work_days'])) {
            // Delete existing schedules
            $delete_schedule = "DELETE FROM doctor_schedules WHERE doctor_id = ?";
            $stmt = $conn->prepare($delete_schedule);
            $stmt->bind_param('i', $doctor_id);
            $stmt->execute();
            
            // Insert new schedules
            foreach($doctorData['work_days'] as $day) {
                $schedule_query = "INSERT INTO doctor_schedules (doctor_id, day, start_time, end_time) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($schedule_query);
                $stmt->bind_param('isss', $doctor_id, $day, $doctorData['start_time1'], $doctorData['end_time1']);
                $stmt->execute();
                
                if(!empty($doctorData['start_time2'])) {
                    $schedule_query = "INSERT INTO doctor_schedules (doctor_id, day, start_time, end_time) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($schedule_query);
                    $stmt->bind_param('isss', $doctor_id, $day, $doctorData['start_time2'], $doctorData['end_time2']);
                    $stmt->execute();
                }
            }
        }
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error updating doctor: " . $e->getMessage());
        return false;
    }
}

// Delete doctor
function deleteDoctor($conn, $doctor_id) {
    try {
        $conn->begin_transaction();
        
        // Get user_id
        $query = "SELECT user_id FROM doctors WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $doctor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $doctor = $result->fetch_assoc();
        
        if($doctor) {
            // Delete from doctor_schedules
            $schedule_query = "DELETE FROM doctor_schedules WHERE doctor_id = ?";
            $stmt = $conn->prepare($schedule_query);
            $stmt->bind_param('i', $doctor_id);
            $stmt->execute();
            
            // Delete from doctors table
            $doctor_query = "DELETE FROM doctors WHERE id = ?";
            $stmt = $conn->prepare($doctor_query);
            $stmt->bind_param('i', $doctor_id);
            $stmt->execute();
            
            // Delete from users table
            $user_query = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($user_query);
            $stmt->bind_param('i', $doctor['user_id']);
            $stmt->execute();
        }
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error deleting doctor: " . $e->getMessage());
        return false;
    }
}

// Get doctor schedule
function getDoctorSchedule($conn, $doctor_id) {
    $query = "SELECT * FROM doctor_schedules WHERE doctor_id = ? ORDER BY FIELD(day, 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get doctor availability
function getDoctorAvailability($conn, $doctor_id, $date) {
    $query = "SELECT * FROM doctor_availability WHERE doctor_id = ? AND date = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('is', $doctor_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Set doctor availability
function setDoctorAvailability($conn, $doctor_id, $date, $status, $reason = '') {
    $query = "INSERT INTO doctor_availability (doctor_id, date, status, reason) VALUES (?, ?, ?, ?) 
              ON DUPLICATE KEY UPDATE status = ?, reason = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('isssss', $doctor_id, $date, $status, $reason, $status, $reason);
    return $stmt->execute();
}

// Get doctor performance stats
function getDoctorPerformance($conn, $doctor_id) {
    $stats = [];
    
    // Total appointments
    $query = "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['total_appointments'] = $result->fetch_assoc()['total'];
    
    // Completed appointments
    $query = "SELECT COUNT(*) as completed FROM appointments WHERE doctor_id = ? AND status = 'completed'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['completed_appointments'] = $result->fetch_assoc()['completed'];
    
    // Cancelled appointments
    $query = "SELECT COUNT(*) as cancelled FROM appointments WHERE doctor_id = ? AND status = 'cancelled'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['cancelled_appointments'] = $result->fetch_assoc()['cancelled'];
    
    // Average rating (if ratings table exists)
    $query = "SELECT AVG(rating) as avg_rating FROM doctor_ratings WHERE doctor_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rating = $result->fetch_assoc()['avg_rating'];
    $stats['average_rating'] = $rating ? round($rating, 1) : 0;
    
    return $stats;
}

// Export doctors to Excel
function exportDoctorsToExcel($conn, $filters = []) {
    $query = "SELECT d.*, u.username FROM doctors d JOIN users u ON d.user_id = u.id WHERE 1=1";
    $params = [];
    $types = '';
    
    if(!empty($filters['status']) && $filters['status'] != 'all') {
        $query .= " AND d.status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    if(!empty($filters['specialization']) && $filters['specialization'] != 'all') {
        $query .= " AND d.specialization = ?";
        $params[] = $filters['specialization'];
        $types .= 's';
    }
    
    if(!empty($filters['search'])) {
        $query .= " AND (d.full_name LIKE ? OR d.specialization LIKE ? OR d.email LIKE ?)";
        $searchTerm = '%' . $filters['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'sss';
    }
    
    $query .= " ORDER BY d.full_name";
    
    if(!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($query);
    }
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Generate doctor ID
function generateDoctorId($conn) {
    $prefix = 'DOC';
    
    // Generate random 6-digit number starting from 52036
    do {
        $randomNumber = mt_rand(52036, 999999);
        $doctor_id = $prefix . $randomNumber;
        
        // Check if this ID already exists
        $query = "SELECT id FROM doctors WHERE doctor_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $doctor_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } while($result->num_rows > 0); // Keep generating until we find a unique ID
    
    return $doctor_id;
}

// Validate doctor data
function validateDoctorData($doctorData, $isEdit = false) {
    $errors = [];
    
    // Required fields
    if(empty($doctorData['full_name'])) {
        $errors[] = "Full name is required";
    }
    
    if(empty($doctorData['email'])) {
        $errors[] = "Email is required";
    } elseif(!filter_var($doctorData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if(empty($doctorData['phone'])) {
        $errors[] = "Phone number is required";
    }
    
    if(empty($doctorData['specialization'])) {
        $errors[] = "Specialization is required";
    }
    
    if(empty($doctorData['age']) || $doctorData['age'] < 25 || $doctorData['age'] > 70) {
        $errors[] = "Age must be between 25 and 70";
    }
    
    // For new doctors, check username and password
    if(!$isEdit) {
        if(empty($doctorData['username'])) {
            $errors[] = "Username is required";
        }
        
        if(empty($doctorData['password']) || strlen($doctorData['password']) < 6) {
            $errors[] = "Password must be at least 6 characters long";
        }
    }
    
    // Validate NRIC if provided
    if(!empty($doctorData['nric'])) {
        if(!preg_match('/^\d{6}-\d{2}-\d{4}$/', $doctorData['nric'])) {
            $errors[] = "Invalid NRIC format (should be XXXXXX-XX-XXXX)";
        }
    }
    
    return $errors;
}

// Check if email exists for another doctor
function isEmailExists($conn, $email, $excludeDoctorId = null) {
    $query = "SELECT id FROM doctors WHERE email = ?";
    $params = [$email];
    $types = 's';
    
    if($excludeDoctorId) {
        $query .= " AND id != ?";
        $params[] = $excludeDoctorId;
        $types .= 'i';
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

// Check if username exists
function isUsernameExists($conn, $username, $excludeUserId = null) {
    $query = "SELECT id FROM users WHERE username = ?";
    $params = [$username];
    $types = 's';
    
    if($excludeUserId) {
        $query .= " AND id != ?";
        $params[] = $excludeUserId;
        $types .= 'i';
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}
?>
