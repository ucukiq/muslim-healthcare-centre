-- Database schema for doctor management system

-- Create doctors table
CREATE TABLE IF NOT EXISTS doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    doctor_id VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    nric VARCHAR(14) UNIQUE,
    age INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    specialization VARCHAR(50) NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    profile_photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create doctor_schedules table
CREATE TABLE IF NOT EXISTS doctor_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    day ENUM('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- Create doctor_availability table
CREATE TABLE IF NOT EXISTS doctor_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('Available', 'Unavailable', 'Leave') DEFAULT 'Available',
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_doctor_date (doctor_id, date)
);

-- Create doctor_ratings table
CREATE TABLE IF NOT EXISTS doctor_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    patient_id INT NOT NULL,
    appointment_id INT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
);

-- Create doctor_permissions table
CREATE TABLE IF NOT EXISTS doctor_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    can_view_appointments BOOLEAN DEFAULT TRUE,
    can_edit_appointments BOOLEAN DEFAULT TRUE,
    can_update_profile BOOLEAN DEFAULT TRUE,
    can_access_patients BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- Insert sample doctors
INSERT INTO doctors (user_id, doctor_id, full_name, nric, age, email, phone, address, specialization, status, profile_photo) VALUES
(1, 'DOC001', 'Dr. Ahmad bin Ibrahim', '123456-78-9012', 45, 'ahmad@healthcare.com', '+6012-345-6789', '123 Healthcare Street, Kuala Lumpur', 'Cardiology', 'Active', 'doctor1.jpg'),
(2, 'DOC002', 'Dr. Siti Aminah', '234567-89-0123', 38, 'siti@healthcare.com', '+6013-987-6543', '456 Medical Center, Selangor', 'Pediatrics', 'Active', 'doctor2.jpg'),
(3, 'DOC003', 'Dr. Mohd Razif', '345678-90-1234', 52, 'razif@healthcare.com', '+6014-555-1234', '789 Hospital Road, Penang', 'Orthopedics', 'Inactive', 'doctor3.jpg');

-- Insert sample doctor schedules
INSERT INTO doctor_schedules (doctor_id, day, start_time, end_time) VALUES
(1, 'Mon', '09:00:00', '12:00:00'),
(1, 'Mon', '14:00:00', '18:00:00'),
(1, 'Tue', '09:00:00', '12:00:00'),
(1, 'Tue', '14:00:00', '18:00:00'),
(1, 'Wed', '09:00:00', '12:00:00'),
(1, 'Wed', '14:00:00', '18:00:00'),
(1, 'Thu', '09:00:00', '12:00:00'),
(1, 'Thu', '14:00:00', '18:00:00'),
(1, 'Fri', '09:00:00', '12:00:00'),
(1, 'Fri', '14:00:00', '18:00:00'),

(2, 'Mon', '08:00:00', '13:00:00'),
(2, 'Tue', '08:00:00', '13:00:00'),
(2, 'Wed', '08:00:00', '13:00:00'),
(2, 'Thu', '08:00:00', '13:00:00'),
(2, 'Fri', '08:00:00', '13:00:00'),

(3, 'Mon', '10:00:00', '16:00:00'),
(3, 'Tue', '10:00:00', '16:00:00'),
(3, 'Wed', '10:00:00', '16:00:00');

-- Insert sample doctor permissions
INSERT INTO doctor_permissions (doctor_id, can_view_appointments, can_edit_appointments, can_update_profile, can_access_patients) VALUES
(1, TRUE, TRUE, TRUE, TRUE),
(2, TRUE, TRUE, TRUE, FALSE),
(3, TRUE, FALSE, TRUE, FALSE);

-- Insert sample availability data
INSERT INTO doctor_availability (doctor_id, date, status, reason) VALUES
(1, CURDATE() + INTERVAL 7 DAY, 'Leave', 'Annual Leave'),
(1, CURDATE() + INTERVAL 14 DAY, 'Unavailable', 'Medical Conference'),
(2, CURDATE() + INTERVAL 10 DAY, 'Leave', 'Personal Leave');

-- Insert sample ratings
INSERT INTO doctor_ratings (doctor_id, patient_id, rating, review) VALUES
(1, 1, 5, 'Very professional and caring doctor'),
(1, 2, 4, 'Good consultation, explained everything well'),
(2, 1, 5, 'Excellent with children, very patient'),
(2, 3, 5, 'Best pediatrician I have ever met'),
(3, 2, 4, 'Knowledgeable and thorough examination');

-- Create indexes for better performance
CREATE INDEX idx_doctors_specialization ON doctors(specialization);
CREATE INDEX idx_doctors_status ON doctors(status);
CREATE INDEX idx_doctor_schedules_doctor_day ON doctor_schedules(doctor_id, day);
CREATE INDEX idx_doctor_availability_date ON doctor_availability(doctor_id, date);
CREATE INDEX idx_doctor_ratings_doctor ON doctor_ratings(doctor_id);
