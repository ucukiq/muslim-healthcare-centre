<?php
// Include config file
require_once "includes/config.php";

// Define variables and initialize with empty values
$full_name = $username = $email = $password = $confirm_password = "";
$full_name_err = $username_err = $email_err = $password_err = $confirm_password_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Validate full name
    if(empty(trim($_POST["full_name"]))){
        $full_name_err = "Please enter your full name.";
    } else{
        $full_name = trim($_POST["full_name"]);
    }
 
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
    
    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter your email address.";
    } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)){
        $email_err = "Please enter a valid email address.";
    } else{
        // Prepare a select statement
        $sql = "SELECT id FROM patients WHERE email = ?";
        
        if($stmt = $conn->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $param_email);
            
            // Set parameters
            $param_email = trim($_POST["email"]);
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // store result
                $stmt->store_result();
                
                if($stmt->num_rows == 1){
                    $email_err = "This email is already registered.";
                } else{
                    $email = trim($_POST["email"]);
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
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password must have at least 6 characters.";
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
    if(empty($full_name_err) && empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)){
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Prepare an insert statement for users table
            $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, 'patient')";
             
            if($stmt = $conn->prepare($sql)){
                // Bind variables to the prepared statement as parameters
                $stmt->bind_param("ss", $param_username, $param_password);
                
                // Set parameters
                $param_username = $username;
                $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
                
                // Attempt to execute the prepared statement
                if($stmt->execute()){
                    // Get the last inserted user ID
                    $user_id = $conn->insert_id;
                    
                    // Now insert into patients table
                    $sql = "INSERT INTO patients (user_id, full_name, email) VALUES (?, ?, ?)";
                    
                    if($stmt = $conn->prepare($sql)){
                        // Bind variables to the prepared statement as parameters
                        $stmt->bind_param("iss", $user_id, $param_full_name, $param_email);
                        
                        // Set parameters
                        $param_full_name = $full_name;
                        $param_email = $email;
                        
                        // Attempt to execute the prepared statement
                        if($stmt->execute()){
                            // Commit the transaction
                            $conn->commit();
                            
                            // Set session variables
                            session_start();
                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $user_id;
                            $_SESSION["username"] = $username;    
                            $_SESSION["role"] = 'patient';
                            
                            // Redirect to patient dashboard
                            header("location: patient/dashboard.php");
                            exit();
                        } else{
                            throw new Exception("Error inserting into patients table");
                        }
                    } else{
                        throw new Exception("Error preparing patient insert statement");
                    }
                } else{
                    throw new Exception("Error inserting into users table");
                }
                
                // Close statement
                $stmt->close();
            } else{
                throw new Exception("Error preparing user insert statement");
            }
        } catch (Exception $e) {
            // Rollback the transaction in case of any error
            $conn->rollback();
            $error = "Something went wrong. Please try again later. Error: " . $e->getMessage();
        }
    }
    
    // Close connection
    $conn->close();
}
?>

<?php $page_title = "Register"; ?>
<?php include('includes/header.php'); ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm mt-5">
                <div class="card-header bg-white">
                    <h3 class="text-center mb-0">Patient Registration</h3>
                </div>
                <div class="card-body">
                    <?php 
                    if(!empty($error)){
                        echo '<div class="alert alert-danger">' . $error . '</div>';
                    }        
                    ?>
                    <p class="text-muted">Please fill in this form to create an account.</p>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control <?php echo (!empty($full_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $full_name; ?>">
                                <span class="invalid-feedback"><?php echo $full_name_err; ?></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                                <span class="invalid-feedback"><?php echo $username_err; ?></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                            <span class="invalid-feedback"><?php echo $email_err; ?></span>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>">
                                <span class="invalid-feedback"><?php echo $password_err; ?></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>">
                                <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Register</button>
                            <button type="reset" class="btn btn-secondary">Reset</button>
                        </div>
                        <p class="text-center mt-3 mb-0">
                            Already have an account? <a href="login.php">Login here</a>.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>
