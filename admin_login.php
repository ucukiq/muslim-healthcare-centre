<?php
// Initialize the session
session_start();

// Define base URL - simple and reliable for XAMPP
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/muslim_healthcare_centre';

// For debugging - uncomment next line if needed
// echo "<!-- Base URL: $base_url -->";

// Check if the user is already logged in, if yes then redirect to dashboard
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && $_SESSION["role"] === 'admin'){
    header("location: admin/dashboard.php");
    exit;
}

// Include config file
require_once "includes/config.php";

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        $sql = "SELECT u.id, u.username, u.password, u.role, 
                       CASE WHEN d.full_name IS NOT NULL THEN d.full_name ELSE u.username END as full_name 
                FROM users u 
                LEFT JOIN doctors d ON u.id = d.user_id 
                WHERE u.username = ? AND u.role = 'admin'";
        
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("s", $username);
            
            if($stmt->execute()){
                $stmt->store_result();
                
                if($stmt->num_rows == 1){                    
                    $stmt->bind_result($id, $username, $hashed_password, $role, $full_name);
                    if($stmt->fetch()){
                        if(password_verify($password, $hashed_password)){
                            // Password is correct, start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $id;
                            $_SESSION["username"] = $username;    
                            $_SESSION["name"] = $full_name;
                            $_SESSION["role"] = $role;
                            
                            // Redirect user to admin dashboard
                            header("location: /muslim_healthcare_centre/admin/dashboard.php");
                            exit();
                        } else {
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else {
                    $login_err = "Invalid username or password.";
                }
            } else {
                $login_err = "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Muslim Healthcare Centre</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #8B5CF6; /* Purple accent color */
            --primary-dark: #7C3AED;
            --dark: #111827;
            --darker: #0A0F1A;
            --light: #F9FAFB;
            --danger: #EF4444;
            --text-primary: #E5E7EB;
            --text-secondary: #9CA3AF;
            --card-bg: rgba(17, 24, 39, 0.8);
            --input-bg: rgba(31, 41, 55, 0.6);
            --border-color: #374151;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 2rem;
        }
        
        .login-card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            border: 1px solid var(--border-color);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .login-header {
            background: linear-gradient(135deg, #1F2937 0%, #111827 100%);
            color: var(--text-primary);
            padding: 2.5rem 2rem;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }
        
        .login-header h1 {
            font-weight: 700;
            font-size: 1.75rem;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        
        .login-header p {
            opacity: 0.9;
            margin: 0.5rem 0 0;
            font-size: 0.95rem;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background-color: var(--input-bg);
            color: var(--text-primary);
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2);
            background-color: rgba(31, 41, 55, 0.8);
        }
        
        .btn-login {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .divider:not(:empty)::before {
            margin-right: 1rem;
        }
        
        .divider:not(:empty)::after {
            margin-left: 1rem;
        }
        
        .back-to-site {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .back-to-site a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .back-to-site a:hover {
            color: var(--primary-color);
            text-shadow: 0 0 8px rgba(139, 92, 246, 0.6);
        }
        
        .back-to-site a:hover {
            text-decoration: underline;
        }
        
        .input-group-text {
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            border-right: none;
            color: var(--text-secondary);
        }
        
        .input-group .form-control {
            border-left: none;
        }
        
        .input-group .form-control:focus {
            border-color: #D1D5DB;
        }
        
        .input-group .form-control:focus + .input-group-text {
            border-color: var(--primary-color);
        }
        
        .alert {
            border-radius: 8px;
            font-size: 0.9rem;
            padding: 0.75rem 1rem;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            z-index: 10;
            transition: color 0.2s;
        }
        
        .password-toggle:hover {
            color: var(--primary-color);
        }
        
        .password-container {
            position: relative;
        }
    </style>
</head>
<body>
    <div class="login-container" style="position: relative; z-index: 1;">
    <!-- Animated background elements -->
    <div style="position: absolute; top: -100px; right: -100px; width: 300px; height: 300px; border-radius: 50%; background: radial-gradient(circle, rgba(79, 70, 229, 0.1) 0%, rgba(79, 70, 229, 0) 70%); z-index: -1;"></div>
    <div style="position: absolute; bottom: -150px; left: -100px; width: 400px; height: 400px; border-radius: 50%; background: radial-gradient(circle, rgba(124, 58, 237, 0.1) 0%, rgba(124, 58, 237, 0) 70%); z-index: -1;"></div>
        <div class="login-card">
            <div class="login-header">
                <div class="mb-3">
                    <i class="fas fa-shield-alt fa-2x" style="color: var(--primary-color);"></i>
                </div>
                <h1 class="mb-2">Admin Portal</h1>
                <p class="text-muted">Secure access to the healthcare management system</p>
            </div>
            
            <div class="login-body">
                <?php 
                if(!empty($login_err)){
                    echo '<div class="alert alert-danger mb-4">' . $login_err . '</div>';
                }        
                ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-4">
                        <label for="username" class="form-label">Admin Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user-shield"></i></span>
                            <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" 
                                   value="<?php echo htmlspecialchars($username); ?>" 
                                   placeholder="Enter admin username" autofocus>
                        </div>
                        <div class="invalid-feedback"><?php echo $username_err; ?></div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-container">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" id="password" name="password" 
                                       class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" 
                                       placeholder="Enter your password">
                                <button type="button" class="password-toggle" id="togglePassword">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback"><?php echo $password_err; ?></div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i> Sign In
                        </button>
                    </div>
                    
                </form>
            </div>
        </div>
        
        <div class="back-to-site">
            <a href="<?php echo $base_url; ?>/index.php">
                <i class="fas fa-arrow-left me-1"></i> Back to main website
            </a>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        
        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
        
        // Add focus styles to input group when input is focused
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.closest('.input-group').style.border = '1px solid var(--primary-color)';
                this.closest('.input-group').style.borderRadius = '8px';
                this.closest('.input-group').style.boxShadow = '0 0 0 3px rgba(79, 70, 229, 0.1)';
            });
            
            input.addEventListener('blur', function() {
                this.closest('.input-group').style.border = '1px solid #D1D5DB';
                this.closest('.input-group').style.borderRadius = '8px';
                this.closest('.input-group').style.boxShadow = 'none';
            });
        });
    </script>
    
    <!-- AI Chat Widget -->
    <?php require_once 'includes/ai_chat_widget.php'; ?>
</body>
</html>
