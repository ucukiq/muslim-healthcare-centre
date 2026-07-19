-- Add turn number field to appointments table
ALTER TABLE appointments 
ADD COLUMN turn_number INT NULL,
ADD COLUMN queue_position INT NULL;

-- Create index for better performance
CREATE INDEX idx_appointments_turn ON appointments(doctor_id, appointment_date, turn_number);
CREATE INDEX idx_appointments_queue ON appointments(doctor_id, appointment_date, status, queue_position);
