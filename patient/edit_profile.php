<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'patient'){
    header("location: ../login.php");
    exit;
}

// Database connection
require_once "../includes/config.php";

// Get current user data
$user_id = $_SESSION["user_id"];
$query = "SELECT p.* FROM patients p JOIN users u ON p.user_id = u.id WHERE u.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if($action == 'update_profile') {
        // Update profile information
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        // Validate inputs
        $errors = [];
        
        if(empty($name)) {
            $errors[] = "Name is required";
        }
        
        if(empty($email)) {
            $errors[] = "Email is required";
        } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        // Check if email is already taken by another user
        if(empty($errors)) {
            $email_check = "SELECT p.id FROM patients p JOIN users u ON p.user_id = u.id WHERE p.email = ? AND u.id != ?";
            $stmt = $conn->prepare($email_check);
            $stmt->bind_param('si', $email, $_SESSION["user_id"]);
            $stmt->execute();
            $email_result = $stmt->get_result();
            
            if($email_result->num_rows > 0) {
                $errors[] = "Email is already taken";
            }
        }
        
        if(empty($errors)) {
            $update_query = "UPDATE patients p JOIN users u ON p.user_id = u.id SET p.full_name = ?, p.email = ?, p.address = ? WHERE u.id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('sssi', $name, $email, $address, $_SESSION["user_id"]);
            
            if($stmt->execute()) {
                $_SESSION['success'] = "Profile updated successfully!";
                header('Location: dashboard.php');
                exit();
            } else {
                $errors[] = "Error updating profile";
            }
        }
        
        if(!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
        }
    }
    elseif($action == 'change_password') {
        // Change password
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        if(empty($current_password)) {
            $errors[] = "Current password is required";
        }
        
        if(empty($new_password)) {
            $errors[] = "New password is required";
        } elseif(strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters";
        }
        
        if($new_password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }
        
        // Verify current password
        if(empty($errors)) {
            $password_query = "SELECT u.password FROM users u WHERE u.id = ?";
            $stmt = $conn->prepare($password_query);
            $stmt->bind_param('i', $_SESSION["user_id"]);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
            
            if(!password_verify($current_password, $user_data['password'])) {
                $errors[] = "Current password is incorrect";
            }
        }
        
        if(empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('si', $hashed_password, $_SESSION["user_id"]);
            
            if($stmt->execute()) {
                $_SESSION['success'] = "Password changed successfully!";
                header('Location: dashboard.php');
                exit();
            } else {
                $errors[] = "Error changing password";
            }
        }
        
        if(!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
        }
    }
    elseif($action == 'upload_picture' && isset($_FILES['profile_picture'])) {
        // Handle profile picture upload
        $file = $_FILES['profile_picture'];
        
        $errors = [];
        
        // Check file size (max 5MB)
        if($file['size'] > 5 * 1024 * 1024) {
            $errors[] = "File size must be less than 5MB";
        }
        
        // Check file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if(!in_array($file['type'], $allowed_types)) {
            $errors[] = "Only JPG, PNG, and GIF files are allowed";
        }
        
        if(empty($errors)) {
            // Create upload directory if it doesn't exist
            $upload_dir = '../assets/profile_pictures/';
            if(!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $filename = 'patient_' . $_SESSION["user_id"] . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $filepath = $upload_dir . $filename;
            
            // Move uploaded file
            if(move_uploaded_file($file['tmp_name'], $filepath)) {
                // Update database with new profile picture
                $update_query = "UPDATE patients p JOIN users u ON p.user_id = u.id SET p.profile_picture = ? WHERE u.id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param('si', $filename, $_SESSION["user_id"]);
                
                if($stmt->execute()) {
                    $_SESSION['success'] = "Profile picture updated successfully!";
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $errors[] = "Error updating profile picture in database";
                }
            } else {
                $errors[] = "Error uploading file";
            }
        }
        
        if(!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
        }
    }
}

$page_title = 'Edit Profile';
require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-user-edit me-2"></i>
                    Edit Profile
                </h4>
            </div>
            <div class="card-body">
                <!-- Success/Error Messages -->
                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                            echo $_SESSION['success']; 
                            unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                            echo $_SESSION['error']; 
                            unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-content" type="button" role="tab">
                            <i class="fas fa-user me-2"></i>Profile Information
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password-content" type="button" role="tab">
                            <i class="fas fa-lock me-2"></i>Change Password
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="picture-tab" data-bs-toggle="tab" data-bs-target="#picture-content" type="button" role="tab">
                            <i class="fas fa-camera me-2"></i>Profile Picture
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="profileTabsContent">
                    <!-- Profile Information Tab -->
                    <div class="tab-pane fade show active" id="profile-content" role="tabpanel">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Change Password Tab -->
                    <div class="tab-pane fade" id="password-content" role="tabpanel">
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                       minlength="6" required>
                                <div class="form-text">Password must be at least 6 characters long</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       minlength="6" required>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-lock me-2"></i>Change Password
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Profile Picture Tab -->
                    <div class="tab-pane fade" id="picture-content" role="tabpanel">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_picture">
                            
                            <div class="text-center mb-4">
                                <?php if(!empty($user['profile_picture'])): ?>
                                    <img src="../assets/profile_pictures/<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                         alt="Profile Picture" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                                         style="width: 150px; height: 150px; margin: 0 auto;">
                                        <i class="fas fa-user fa-3x text-white"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="profile_picture" class="form-label">Choose New Profile Picture</label>
                                <input type="file" class="form-control" id="profile_picture" name="profile_picture" 
                                       accept="image/jpeg,image/png,image/gif">
                                <div class="form-text">Allowed formats: JPG, PNG, GIF. Maximum size: 5MB</div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-camera me-2"></i>Upload Picture
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
