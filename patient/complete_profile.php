<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'patient'){
    header("location: ../login.php");
    exit;
}

// Include config file
require_once "../includes/config.php";

// Define variables and initialize with empty values
$full_name = $email = "";
$full_name_err = $email_err = "";
$success_msg = "";

// Check if patient already exists
$patient_exists = false;
$sql = "SELECT id FROM patients WHERE user_id = ?";
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $_SESSION["user_id"]);
    if($stmt->execute()){
        $stmt->store_result();
        if($stmt->num_rows > 0){
            $patient_exists = true;
            $success_msg = "Your patient profile is already set up. <a href='appointments.php'>View your appointments</a>.";
        }
    }
    $stmt->close();
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST" && !$patient_exists){
    
    // Validate full name
    if(empty(trim($_POST["full_name"]))){
        $full_name_err = "Please enter your full name.";
    } else{
        $full_name = trim($_POST["full_name"]);
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter your email.";
    } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)){
        $email_err = "Please enter a valid email address.";
    } else{
        $email = trim($_POST["email"]);
    }
    
    // Check input errors before inserting in database
    if(empty($full_name_err) && empty($email_err)){
        // Prepare an insert statement
        $sql = "INSERT INTO patients (user_id, full_name, email) VALUES (?, ?, ?)";
         
        if($stmt = $conn->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("iss", $_SESSION["user_id"], $full_name, $email);
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                $success_msg = "Your patient profile has been created successfully. <a href='appointments.php'>View your appointments</a>.";
                $patient_exists = true;
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

<?php $page_title = "Complete Patient Profile"; ?>
<?php include('../includes/header.php'); ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Complete Your Patient Profile</h4>
                </div>
                <div class="card-body">
                    <?php if(!empty($success_msg)): ?>
                        <div class="alert alert-success"><?php echo $success_msg; ?></div>
                    <?php else: ?>
                        <p>Please fill in your details to complete your patient profile.</p>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control <?php echo (!empty($full_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $full_name; ?>">
                                <div class="invalid-feedback"><?php echo $full_name_err; ?></div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                                <div class="invalid-feedback"><?php echo $email_err; ?></div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Save Profile</button>
                                <a href="../index.php" class="btn btn-link">Skip for now</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>
