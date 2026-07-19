<?php
// Initialize the session
session_start();

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
$username = $password = $user_type = "";
$username_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter your username or email.";
    } else {
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Get user type (patient or admin)
    $user_type = isset($_POST["user_type"]) ? $_POST["user_type"] : '';
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        // Prepare a select statement based on user type
        if($user_type === 'admin') {
            // Admin login - check in doctors table
            $sql = "SELECT u.id, u.username, u.password, u.role, d.full_name 
                    FROM users u 
                    LEFT JOIN doctors d ON u.id = d.user_id 
                    WHERE u.username = ? AND u.role = 'admin'";
        } else {
            // Patient login - check in patients table
            $sql = "SELECT u.id, u.username, u.password, u.role, p.first_name, p.last_name 
                    FROM users u 
                    LEFT JOIN patients p ON u.id = p.user_id 
                    WHERE u.username = ? AND u.role = 'patient'";
        }
        
        if($stmt = $conn->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $username);
            
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
                        // Password is correct, so start a new session
                        session_start();
                        
                        // Store data in session variables
                        $_SESSION["loggedin"] = true;
                        $_SESSION["user_id"] = $user['id'];
                        $_SESSION["username"] = $user['username'];
                        
                        // Set name based on user type
                        if($user_type === 'admin') {
                            $_SESSION["name"] = $user['full_name'];
                        } else {
                            $_SESSION["name"] = $user['first_name'] . ' ' . $user['last_name'];
                        }
                        
                        $_SESSION["role"] = $user['role'];
                        
                        // Redirect user to dashboard based on role
                        header("location: " . strtolower($user['role']) . "/dashboard.php");
                        exit();
                    } else {
                        // Password is not valid
                        $login_err = "Invalid username or password.";
                    }
                } else {
                    // Username doesn't exist
                    $login_err = "Invalid username or password.";
                }
            } else {
                $login_err = "Oops! Something went wrong. Please try again later.";
            }
            // Close statement
            $stmt->close();
        }
    }
    // Close connection
    $conn->close();
}
?>

<?php $page_title = "Login"; ?>
<?php include('includes/header.php'); ?>

<div class="container-fluid login-container">
    <div class="row min-vh-100">
        <!-- Left Side - Hospital Image -->
        <div class="col-md-6 d-none d-md-block p-0">
            <div class="login-image" style="background-image: url('assets/images/hospital.jpg');">
                <div class="login-overlay">
                    <h2>Welcome Back!</h2>
                    <p>Access your healthcare portal to manage your health records and appointments.</p>
                </div>
            </div>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="col-md-6 d-flex align-items-center">
            <div class="container py-5">
                <div class="text-center mb-4">
                    <img src="assets/images/logo.png" alt="Healthcare Center Logo" class="mb-3" style="height: 60px;">
                    <h2 class="text-primary">Healthcare Portal</h2>
                    <p class="text-muted">Sign in to access your account</p>
                </div>
                
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <?php 
                        if(isset($_GET['registered'])) {
                            echo '<div class="alert alert-success mb-4">Registration successful! You can now login.</div>';
                        }
                        if(isset($_GET['logged_out'])) {
                            echo '<div class="alert alert-info mb-4">You have been successfully logged out.</div>';
                        }
                        if(!empty($login_err)){
                            echo '<div class="alert alert-danger mb-4">' . $login_err . '</div>';
                        }        
                        ?>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="mb-4">
                                <label class="form-label">I am a:</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="user_type" id="patientRadio" value="patient" checked>
                                        <label class="form-check-label" for="patientRadio">
                                            <i class="fas fa-user me-1"></i> Patient
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="user_type" id="adminRadio" value="admin">
                                        <label class="form-check-label" for="adminRadio">
                                            <i class="fas fa-user-shield me-1"></i> Admin
                                        </label>
                                    </div>
                                </div>
                            </div>
                            

                            <div class="mb-3">
                                <label for="username" class="form-label">Username or Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" id="username" name="username" 
                                           class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" 
                                           value="<?php echo htmlspecialchars($username); ?>" 
                                           placeholder="Enter your username or email" 
                                           autofocus>
                                </div>
                                <div class="invalid-feedback"><?php echo $username_err; ?></div>
                            </div>
                            

                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" id="password" name="password" 
                                           class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" 
                                           placeholder="Enter your password">
                                    <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                        <i class="far fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback"><?php echo $password_err; ?></div>
                            </div>
                            

                            <div class="d-grid gap-2 mb-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i> Sign In
                                </button>
                            </div>
                            

                            <div class="text-center mb-3">
                                <a href="forgot-password.php" class="text-decoration-none small">
                                    <i class="fas fa-key me-1"></i> Forgot Password?
                                </a>
                            </div>
                            

                            <div class="divider">OR</div>
                            

                            <div class="text-center">
                                <a href="register.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-user-plus me-2"></i> Create New Patient Account
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.login-container {
    background-color: #f8f9fa;
}

.login-image {
    height: 100%;
    background-size: cover;
    background-position: center;
    position: relative;
}

.login-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0,0,0,0.7));
    color: white;
    padding: 2rem;
}

.login-overlay h2 {
    font-weight: 700;
    margin-bottom: 1rem;
}

.card {
    border-radius: 10px;
    overflow: hidden;
}

.btn-primary {
    background-color: #1a73e8;
    border-color: #1a73e8;
    padding: 10px 20px;
    font-weight: 500;
}

.form-control:focus {
    border-color: #1a73e8;
    box-shadow: 0 0 0 0.2rem rgba(26, 115, 232, 0.25);
}

.divider {
    display: flex;
    align-items: center;
    text-align: center;
    margin: 1.5rem 0;
    color: #6c757d;
}

.divider::before,
.divider::after {
    content: "";
    flex: 1;
    border-bottom: 1px solid #dee2e6;
}

.divider:not(:empty)::before {
    margin-right: 1rem;
}

.divider:not(:empty)::after {
    margin-left: 1rem;
}

.form-check-input:checked {
    background-color: #1a73e8;
    border-color: #1a73e8;
}
</style>

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

// Update form labels based on user type selection
const patientRadio = document.getElementById('patientRadio');
const adminRadio = document.getElementById('adminRadio');
const usernameLabel = document.querySelector('label[for="username"]');

function updateFormLabels() {
    if (adminRadio.checked) {
        usernameLabel.textContent = 'Admin Username';
    } else {
        usernameLabel.textContent = 'Username or Email';
    }
}

patientRadio.addEventListener('change', updateFormLabels);
adminRadio.addEventListener('change', updateFormLabels);
</script>

<?php include('includes/footer.php'); ?>
