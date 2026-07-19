<?php
// Initialize the session
session_start();

// Include config file
require_once "includes/config.php";

// Check if the user is already logged in, if yes then redirect to dashboard
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && $_SESSION["role"] === 'admin'){
    header("location: admin/dashboard.php");
    exit;
}

// Define variables and initialize with empty values
$username = $password = $confirm_password = "";
$username_err = $password_err = $confirm_password_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else{
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE username = ?";
        
        if($stmt = $conn->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $param_username);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // store result
                $stmt->store_result();
                
                if($stmt->num_rows == 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 8){
        $password_err = "Password must have at least 8 characters.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm password.";     
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err)){
        
        // Prepare an insert statement
        $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')";
        
        if($stmt = $conn->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("ss", $param_username, $param_password);
            
            // Set parameters
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Redirect to login page
                header("location: admin_login.php?registered=1");
                exit();
            } else{
                echo "Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    }
    
    // Close connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration | Muslim Healthcare Centre</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #8B5CF6;
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
            max-width: 450px;
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
        
        .btn-primary {
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
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .input-group-text {
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
        }
        
        .input-group .form-control {
            border-left: none;
        }
        
        .input-group .form-control:focus {
            border-color: var(--border-color);
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
            text-decoration: underline;
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
                    <i class="fas fa-user-shield fa-2x" style="color: var(--primary-color);"></i>
                </div>
                <h1 class="mb-2">Admin Registration</h1>
                <p class="text-muted">Create a new admin account</p>
            </div>
            
            <div class="login-body">
                <?php 
                if(isset($_GET['registered']) && $_GET['registered'] == 1) {
                    echo '<div class="alert alert-success mb-4">Registration successful! You can now login with your credentials.</div>';
                }
                
                if(!empty($username_err) || !empty($password_err) || !empty($confirm_password_err)){
                    echo '<div class="alert alert-danger mb-4">Please fix the errors below.</div>';
                }        
                ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-4">
                        <label for="username" class="form-label">Admin Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user-shield"></i></span>
                            <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" 
                                   value="<?php echo htmlspecialchars($username); ?>" 
                                   placeholder="Choose a username" autofocus>
                        </div>
                        <?php if(!empty($username_err)): ?>
                            <div class="invalid-feedback d-block"><?php echo $username_err; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-container">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" id="password" name="password" 
                                       class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" 
                                       placeholder="Choose a password (min 8 characters)">
                                <button type="button" class="password-toggle" id="togglePassword">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                            <?php if(!empty($password_err)): ?>
                                <div class="invalid-feedback d-block"><?php echo $password_err; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="password-container">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" 
                                       placeholder="Confirm your password">
                                <button type="button" class="password-toggle" id="toggleConfirmPassword">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                            <?php if(!empty($confirm_password_err)): ?>
                                <div class="invalid-feedback d-block"><?php echo $confirm_password_err; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i> Register Admin
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <p class="mb-0">Already have an account? <a href="admin_login.php" class="text-primary">Login here</a></p>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="back-to-site">
            <a href="index.php">
                <i class="fas fa-arrow-left me-1"></i> Back to main website
            </a>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        function setupPasswordToggle(toggleId, inputId) {
            const toggle = document.querySelector(toggleId);
            const input = document.querySelector(inputId);
            
            if (toggle && input) {
                toggle.addEventListener('click', function () {
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }
        }
        
        // Setup password toggles
        setupPasswordToggle('#togglePassword', '#password');
        setupPasswordToggle('#toggleConfirmPassword', '#confirm_password');
    </script>
</body>
</html>
