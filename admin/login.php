<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session
session_start();

// Check if the user is already logged in, if yes then redirect to admin dashboard
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && $_SESSION["role"] === 'admin'){
    header("location: dashboard.php");
    exit;
}

// Include config file
require_once "../includes/config.php";

// Debug: Check if config loaded
if (!isset($conn)) {
    die("Database connection failed. Check your config.php file.");
}

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
        // Prepare a select statement - using 'user' table (singular)
        $sql = "SELECT id, username, password, role, status FROM user WHERE username = ? AND role = 'admin' LIMIT 1";
        
        if($stmt = $conn->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $param_username);
            
            // Set parameters
            $param_username = $username;
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Store result
                $stmt->store_result();
                
                // Check if username exists
                if($stmt->num_rows == 1){                    
                    // Bind result variables
                    $stmt->bind_result($id, $username, $hashed_password, $role, $status);
                    if($stmt->fetch()){
                        // Debug: Log the password verification
                        error_log("Login attempt - Username: $username, Status: $status, Hashed Password: $hashed_password");
                        
                        if(password_verify($password, $hashed_password)){
                            // Password is correct, start a new session
                            session_start();
                            
                            // Verify account status
                            if($status !== 'active') {
                                $login_err = "This account is not active. Please contact the administrator.";
                                error_log("Login failed - Account not active for user: $username");
                            } else {
                                // Store data in session variables
                                $_SESSION["loggedin"] = true;
                                $_SESSION["id"] = $id;
                                $_SESSION["username"] = $username;                            
                                $_SESSION["role"] = $role;
                                $_SESSION["status"] = $status;
                                
                                error_log("Login successful - User: $username, Role: $role, Status: $status");
                                
                                // Redirect user to admin dashboard
                                header("location: dashboard.php");
                                exit();
                            }
                        } else {
                            // Password is not valid
                            $login_err = "Invalid username or password.";
                            error_log("Login failed - Invalid password for user: $username");
                            
                            // For debugging only - remove in production
                            error_log("Input password: $password");
                            error_log("Stored hash: $hashed_password");
                        }
                    }
                } else {
                    // Username doesn't exist
                    $login_err = "Invalid username or password.";
                    error_log("Login failed - Username not found: " . $username);
                }
            } else {
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
    <title>Admin Login - Muslim Healthcare Centre</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header i {
            font-size: 3rem;
            color: #0d6efd;
            margin-bottom: 1rem;
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .btn-login {
            background-color: #0d6efd;
            border: none;
            padding: 0.75rem;
            font-weight: 600;
        }
        .btn-login:hover {
            background-color: #0b5ed7;
        }
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: #6c757d;
        }
        .login-footer a {
            color: #0d6efd;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-container">
                    <div class="login-header">
                        <i class="fas fa-user-shield"></i>
                        <h2>Admin Login</h2>
                        <p class="text-muted">Access the administration panel</p>
                    </div>
                    
                    <?php 
                    if(!empty($login_err)){
                        echo '<div class="alert alert-danger">' . $login_err . '</div>';
                    }        
                    ?>

                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                                <div class="invalid-feedback"><?php echo $username_err; ?></div>
                            </div>
                        </div>    
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="far fa-eye"></i>
                                </button>
                                <div class="invalid-feedback"><?php echo $password_err; ?></div>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-login">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </div>
                    </form>
                    
                    <div class="login-footer">
                        <p class="mb-0">
                            <a href="../index.php">
                                <i class="fas fa-arrow-left me-1"></i> Back to Home
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>
