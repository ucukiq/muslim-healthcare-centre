<?php
// Start session
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../login.php");
    exit;
}

// Only admin can edit doctor profiles
if($_SESSION["role"] !== 'admin'){
    $_SESSION['error_message'] = "Access denied. Only administrators can edit doctor profiles.";
    header("location: dashboard.php");
    exit;
}

// Include database connection
include_once "../includes/db_connection.php";

// Get doctor ID from URL parameter
$doctor_id = isset($_GET['id']) ? $_GET['id'] : '';

// Validate doctor ID
if(empty($doctor_id)){
    $_SESSION['error_message'] = "Doctor ID not provided.";
    header("location: manage_doctors.php");
    exit;
}

// Get doctor information
$doctor = null;
$sql = "SELECT d.*, u.username, u.email as user_email 
        FROM doctors d 
        JOIN users u ON d.user_id = u.id 
        WHERE d.id = ?";
        
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($row = $result->fetch_assoc()){
        $doctor = $row;
    } else {
        $_SESSION['error_message'] = "Doctor not found.";
        header("location: manage_doctors.php");
        exit;
    }
    $stmt->close();
}

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Validate required fields and clean up doctor's name
    $full_name = trim($_POST["full_name"]);
    // Remove any existing 'Dr.' prefix to prevent duplicates
    $full_name = preg_replace('/^Dr\.\s*/i', '', $full_name);
    $specialization = trim($_POST["specialization"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $age = trim($_POST["age"]);
    $nric = trim($_POST["nric"]);
    $address = trim($_POST["address"]);
    $status = $_POST["status"];
    $username = trim($_POST["username"]);
    
    // Basic validation
    if(empty($full_name) || empty($specialization) || empty($email) || empty($phone) || empty($age) || empty($username)){
        $error = "All required fields must be filled.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = "Invalid email format.";
    } elseif(!is_numeric($age) || $age < 1 || $age > 120){
        $error = "Please enter a valid age between 1 and 120.";
    } else {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Update users table
            $sql = "UPDATE users SET username = ?, email = ? WHERE id = ?";
            if($stmt = $conn->prepare($sql)){
                $stmt->bind_param("ssi", $username, $email, $doctor['user_id']);
                $stmt->execute();
                $stmt->close();
            }
            
            // Update doctors table
            $sql = "UPDATE doctors SET full_name = ?, specialization = ?, email = ?, phone = ?, 
                    age = ?, nric = ?, address = ?, status = ?, updated_at = NOW() WHERE id = ?";
            if($stmt = $conn->prepare($sql)){
                $stmt->bind_param("ssssisssi", $full_name, $specialization, $email, $phone, $age, $nric, $address, $status, $doctor_id);
                $stmt->execute();
                $stmt->close();
            }
            
            // Handle profile photo upload
            if(isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0){
                $target_dir = "../assets/images/doctors/";
                $file_extension = strtolower(pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION));
                $allowed_extensions = array("jpg", "jpeg", "png", "gif");
                
                if(in_array($file_extension, $allowed_extensions)){
                    // Generate unique filename
                    $new_filename = "doctor_" . $doctor_id . "_" . time() . "." . $file_extension;
                    $target_file = $target_dir . $new_filename;
                    
                    // Create directory if it doesn't exist
                    if(!file_exists($target_dir)){
                        mkdir($target_dir, 0777, true);
                    }
                    
                    // Upload file
                    if(move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)){
                        // Update database with new photo
                        $sql = "UPDATE doctors SET profile_photo = ? WHERE id = ?";
                        if($stmt = $conn->prepare($sql)){
                            $stmt->bind_param("si", $new_filename, $doctor_id);
                            $stmt->execute();
                            $stmt->close();
                        }
                        
                        // Delete old photo if exists
                        if(!empty($doctor['profile_photo']) && file_exists($target_dir . $doctor['profile_photo'])){
                            unlink($target_dir . $doctor['profile_photo']);
                        }
                    }
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            $_SESSION['success_message'] = "Doctor profile updated successfully!";
            header("location: doctor_profile.php?id=" . $doctor_id);
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            $error = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Set page title
$page_title = "Edit Doctor Profile";

// Include header
include "../includes/header.php";
?>

<div class="container-fluid py-4">
    <?php
    // Display error messages
    if(isset($error)):
    ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Edit Doctor Profile</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="manage_doctors.php">Doctors</a></li>
                            <li class="breadcrumb-item"><a href="doctor_profile.php?id=<?php echo $doctor['id']; ?>">
                                <?php echo htmlspecialchars($doctor['full_name']); ?>
                            </a></li>
                            <li class="breadcrumb-item active">Edit Profile</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="doctor_profile.php?id=<?php echo $doctor['id']; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user-edit me-2"></i>Edit Doctor Information
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="editDoctorForm">
                        <div class="row">
                            <!-- Left Column -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($doctor['full_name']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username *</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($doctor['username']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($doctor['email']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($doctor['phone']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="age" class="form-label">Age *</label>
                                    <input type="number" class="form-control" id="age" name="age" 
                                           value="<?php echo htmlspecialchars($doctor['age']); ?>" min="1" max="120" required>
                                </div>
                            </div>
                            
                            <!-- Right Column -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="specialization" class="form-label">Specialization *</label>
                                    <input type="text" class="form-control" id="specialization" name="specialization" 
                                           value="<?php echo htmlspecialchars($doctor['specialization']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="nric" class="form-label">NRIC</label>
                                    <input type="text" class="form-control" id="nric" name="nric" 
                                           value="<?php echo htmlspecialchars($doctor['nric'] ?? ''); ?>" 
                                           placeholder="e.g., 123456-78-9012">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status *</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="Active" <?php echo ($doctor['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="Inactive" <?php echo ($doctor['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="profile_photo" class="form-label">Profile Photo</label>
                                    <input type="file" class="form-control" id="profile_photo" name="profile_photo" 
                                           accept="image/*">
                                    <div class="form-text">Allowed formats: JPG, JPEG, PNG, GIF (Max 5MB)</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($doctor['address'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Current Photo Preview -->
                        <?php if(!empty($doctor['profile_photo'])): ?>
                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label">Current Profile Photo</label>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="../assets/images/doctors/<?php echo htmlspecialchars($doctor['profile_photo']); ?>" 
                                         alt="Current Profile" class="rounded-circle" width="80" height="80" style="object-fit: cover;">
                                    <div>
                                        <small class="text-muted">Current photo</small><br>
                                        <small class="text-muted">Upload new photo to replace</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Form Actions -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="doctor_profile.php?id=<?php echo $doctor['id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Quick Info -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Quick Info
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Doctor ID</label>
                        <div class="fw-bold"><?php echo htmlspecialchars($doctor['doctor_id']); ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Current Status</label>
                        <div>
                            <span class="badge bg-<?php echo ($doctor['status'] == 'Active') ? 'success' : 'secondary'; ?>">
                                <?php echo htmlspecialchars($doctor['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Member Since</label>
                        <div class="fw-bold"><?php echo date('F j, Y', strtotime($doctor['created_at'])); ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Last Updated</label>
                        <div class="fw-bold"><?php echo date('F j, Y', strtotime($doctor['updated_at'])); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Guidelines -->
            <div class="card shadow-sm mt-3">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>Editing Guidelines
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled small">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            All fields marked with * are required
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Email must be a valid format
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Age must be between 1-120 years
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Profile photo should be professional
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Changes are saved immediately
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.getElementById('editDoctorForm');
    
    form.addEventListener('submit', function(e) {
        // Email validation
        const email = document.getElementById('email').value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if(!emailRegex.test(email)) {
            e.preventDefault();
            alert('Please enter a valid email address.');
            return false;
        }
        
        // Age validation
        const age = document.getElementById('age').value;
        if(age < 1 || age > 120) {
            e.preventDefault();
            alert('Age must be between 1 and 120.');
            return false;
        }
        
        // Phone validation (basic)
        const phone = document.getElementById('phone').value;
        if(phone.length < 10) {
            e.preventDefault();
            alert('Please enter a valid phone number.');
            return false;
        }
        
        return true;
    });
    
    // File upload validation
    const fileInput = document.getElementById('profile_photo');
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if(file) {
            // Check file size (5MB limit)
            if(file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB.');
                e.target.value = '';
                return;
            }
            
            // Check file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if(!allowedTypes.includes(file.type)) {
                alert('Only JPG, JPEG, PNG, and GIF files are allowed.');
                e.target.value = '';
                return;
            }
        }
    });
});
</script>
