<?php
// Session is already started in header.php
// Check if the user is already logged in, if yes then redirect to dashboard
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: " . strtolower($_SESSION["role"]) . "/dashboard.php");
    exit;
}

// Include config file
require_once "includes/config.php";

// Check if user was logged out
if (isset($_GET['logged_out']) && $_GET['logged_out'] == 1) {
    $login_success = "You have been successfully logged out.";
}

// Define variables and initialize with empty values
$name = $password = "";
$name_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // No need to set user type as we'll check against all roles

    // Check if name is empty
    if(empty(trim($_POST["name"]))){
        $name_err = "Please enter your name.";
    } else {
        $name = trim($_POST["name"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($name_err) && empty($password_err)){
        // Find the user by username
        $sql = "SELECT u.id, u.username, u.password, u.role 
                FROM users u 
                WHERE u.username = ?";
        
        if($stmt = $conn->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $name);
            
            // Execute the query
            if($stmt->execute()){
                // Get the result
                $result = $stmt->get_result();
                
                // Check if user exists
                if($result->num_rows == 1){                    
                    // Fetch the result
                    $user = $result->fetch_assoc();
                    $hashed_password = $user['password'];
                    
                    // Verify password
                    if(password_verify($password, $hashed_password)){
                        // Store data in session variables
                        $_SESSION["loggedin"] = true;
                        $_SESSION["user_id"] = $user['id'];
                        $_SESSION["username"] = $user['username'];
                        $_SESSION["role"] = $user['role'];
                        
                        // Set name based on role
                        if ($user['role'] === 'patient') {
                            $sql = "SELECT full_name FROM patients WHERE user_id = ?";
                        } else if ($user['role'] === 'doctor') {
                            $sql = "SELECT full_name FROM doctors WHERE user_id = ?";
                        } else {
                            // For admin, just use the username
                            $_SESSION["name"] = $user['username'];
                            $sql = false;
                        }
                        
                        if ($sql) {
                            if ($stmt_name = $conn->prepare($sql)) {
                                $stmt_name->bind_param("i", $user['id']);
                                if ($stmt_name->execute()) {
                                    $result_name = $stmt_name->get_result();
                                    if ($row = $result_name->fetch_assoc()) {
                                        $_SESSION["name"] = $row['full_name'];
                                    }
                                }
                                $stmt_name->close();
                            }
                        }
                        
                        // Redirect user to dashboard based on role
                        header("location: " . strtolower($user['role']) . "/dashboard.php");
                        exit();
                    } else {
                        // Password is not valid, display a generic error message
                        $login_err = "Invalid username or password.";
                    }
                } else {
                    // Username doesn't exist, display a generic error message
                    $login_err = "Invalid username or password.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
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
    <title>Login - Muslim Healthcare Centre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1e88e5;
            --primary-hover: #1976d2;
            --text-color: #333;
            --border-color: #e0e0e0;
            --error-color: #d32f2f;
        }
        
        body {
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), 
                        url('images/bg_login_page.png.jpeg') center/cover no-repeat fixed;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: var(--text-color);
            line-height: 1.6;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            margin: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .login-header {
            background: var(--primary-color);
            color: white;
            padding: 2rem 2rem 1.5rem;
            text-align: center;
        }

        .logo {
            width: 100%;
            max-width: 200px;
            margin: 0 auto 1.5rem;
            display: block;
            transition: all 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.03);
            opacity: 0.95;
        }

        .login-body {
            padding: 2rem 2.5rem;
        }

        .form-control {
            height: 48px;
            padding: 0.75rem 1rem;
            font-size: 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(30, 136, 229, 0.2);
        }

        .form-floating>label {
            padding: 0.75rem 1rem;
            color: #666;
        }

        .form-floating>.form-control:focus~label,
        .form-floating>.form-control:not(:placeholder-shown)~label {
            transform: scale(0.85) translateY(-0.8rem) translateX(0.15rem);
            color: #666;
        }

        .btn-login {
            background-color: var(--primary-color);
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 100%;
            margin-top: 1rem;
        }

        .btn-login:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(30, 136, 229, 0.3);
        }

        .form-floating {
            margin-bottom: 1.25rem;
        }

        .error-message {
            color: var(--error-color);
            font-size: 0.85rem;
            margin-top: 0.5rem;
            display: block;
        }

        .forgot-password {
            text-align: right;
            margin-top: -0.75rem;
            margin-bottom: 1rem;
        }

        .forgot-password a {
            color: #666;
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.2s ease;
        }

        .forgot-password a:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }

        .login-footer {
            text-align: center;
            padding: 1.5rem 2rem;
            border-top: 1px solid #eee;
            font-size: 0.9rem;
            color: #666;
        }

        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 10px;
                border-radius: 8px;
            }
            
            .login-body {
                padding: 1.5rem;
            }
            
            .login-header {
                padding: 1.5rem 1.5rem 1rem;
            }
        }
            border-color: var(--primary-color);
        }
        .forgot-password {
            text-align: right;
            margin: 0.5rem 0 1.5rem;
        }
        .forgot-password a {
            color: #6c757d;
            text-decoration: none;
            font-size: 0.85rem;
        }
        .forgot-password a:hover {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="images/logo.png.png" alt="Muslim Healthcare Centre" class="logo">
            <h2 class="mt-2">Welcome to Muslim Healthcare Centre</h2>
            <p class="mb-0 text-white-50">Sign in to access your account</p>
        </div>
        
        <div class="login-body">
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" novalidate>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="name" placeholder="Enter your username" required>
                    <label for="username">Username</label>
                    <div class="invalid-feedback">
                        Please enter your username
                    </div>
                </div>
                
                <div class="form-floating mb-2">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                    <label for="password">Password</label>
                    <div class="invalid-feedback">
                        Please enter your password
                    </div>
                </div>
                
                <div class="forgot-password">
                    <a href="#">Forgot password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login w-100 mb-3">
                    <i class="fas fa-sign-in-alt me-2"></i> Sign In
                </button>
                
                <a href="index.php" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-arrow-left me-2"></i> Return to Main Menu
                </a>
            </form>
        </div>
        
        <div class="login-footer">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            
            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.querySelectorAll('form.needs-validation')
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
        
        // Toggle password visibility
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.createElement('span');
            togglePassword.className = 'position-absolute end-0 top-50 translate-middle-y me-3';
            togglePassword.style.cursor = 'pointer';
            togglePassword.innerHTML = '<i class="far fa-eye text-muted"></i>';
            
            const passwordInput = document.getElementById('password');
            const formFloating = passwordInput.closest('.form-floating');
            
            if (formFloating) {
                formFloating.style.position = 'relative';
                formFloating.appendChild(togglePassword);
                
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    const icon = this.querySelector('i');
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                });
            }
        });
    </script>
    
    <!-- AI Chat Widget -->
    <?php require_once 'includes/ai_chat_widget.php'; ?>
</body>
</html>
