<?php
// Database setup for doctors management
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin'){
    header("location: login.php");
    exit;
}

// Include config file
require_once "../includes/config.php";

$message = "";
$error = "";

// Check if tables exist
$tables_to_check = ['doctors', 'doctor_schedules', 'doctor_availability', 'doctor_ratings', 'doctor_permissions'];
$missing_tables = [];

foreach($tables_to_check as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if($result->num_rows == 0) {
        $missing_tables[] = $table;
    }
}

// If setup is requested
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['setup'])) {
    try {
        // Read and execute the SQL file
        $sql_file = '../database_doctors.sql';
        if(file_exists($sql_file)) {
            $sql_content = file_get_contents($sql_file);
            
            // Split SQL statements
            $statements = array_filter(array_map('trim', explode(';', $sql_content)));
            
            foreach($statements as $statement) {
                if(!empty($statement) && !preg_match('/^--/', $statement)) {
                    if(!$conn->query($statement)) {
                        throw new Exception("Error executing: " . $statement . " - " . $conn->error);
                    }
                }
            }
            
            $message = "Database setup completed successfully!";
            
            // Refresh to check tables again
            header("Refresh: 2");
        } else {
            $error = "Database schema file not found!";
        }
    } catch(Exception $e) {
        $error = "Setup failed: " . $e->getMessage();
    }
}

// Include header
include("../includes/header.php");
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-database me-2"></i>
                        Doctor Management Database Setup
                    </h4>
                </div>
                <div class="card-body">
                    <?php if($message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="mb-4">
                        <h5>Database Status</h5>
                        <?php if(empty($missing_tables)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                All required tables are present. The system is ready to use!
                            </div>
                            <a href="manage_doctors_complete.php" class="btn btn-primary">
                                <i class="fas fa-arrow-right me-1"></i>
                                Go to Manage Doctors
                            </a>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Missing tables: <?php echo implode(', ', $missing_tables); ?>
                            </div>
                            <p class="text-muted">
                                Click the button below to create the required database tables and insert sample data.
                            </p>
                            <form method="POST">
                                <button type="submit" name="setup" class="btn btn-success">
                                    <i class="fas fa-play me-1"></i>
                                    Run Database Setup
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div class="border-top pt-3">
                        <h6>What will be created:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-table me-2 text-primary"></i>doctors table - Doctor information</li>
                            <li><i class="fas fa-table me-2 text-primary"></i>doctor_schedules table - Work schedules</li>
                            <li><i class="fas fa-table me-2 text-primary"></i>doctor_availability table - Leave/unavailable dates</li>
                            <li><i class="fas fa-table me-2 text-primary"></i>doctor_ratings table - Patient ratings</li>
                            <li><i class="fas fa-table me-2 text-primary"></i>doctor_permissions table - Access permissions</li>
                        </ul>
                        <p class="text-muted small">
                            Sample data will be inserted for testing purposes.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include("../includes/footer.php");
?>
