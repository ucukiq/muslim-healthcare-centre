<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include config file
require_once "includes/config.php";

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form was submitted
$test_username = isset($_POST['test_username']) ? $_POST['test_username'] : '';
$test_password = isset($_POST['test_password']) ? $_POST['test_password'] : '';
$result_message = '';
$user_data = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($test_username) && !empty($test_password)) {
        // Query to find patient by username
        $sql = "SELECT u.id, u.username, u.password, u.role, p.first_name, p.last_name 
                FROM users u 
                LEFT JOIN patients p ON u.id = p.user_id 
                WHERE u.username = ? AND u.role = 'patient'";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $test_username);
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                
                if ($result->num_rows == 1) {
                    $user = $result->fetch_assoc();
                    $user_data = $user;
                    
                    if (password_verify($test_password, $user['password'])) {
                        $result_message = '<div class="alert alert-success">✅ Login successful! User found and password matches.</div>';
                    } else {
                        $result_message = '<div class="alert alert-warning">⚠️ Username found but password does not match.</div>';
                    }
                } else {
                    $result_message = '<div class="alert alert-danger">❌ No patient found with that username.</div>';
                }
            } else {
                $result_message = '<div class="alert alert-danger">Error executing query: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        } else {
            $result_message = '<div class="alert alert-danger">Error preparing statement: ' . $conn->error . '</div>';
        }
    } else {
        $result_message = '<div class="alert alert-info">Please enter both username and password to test.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Login Tester</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .debug-info { margin-top: 30px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="my-4">Patient Login Tester</h1>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Test Patient Login</h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="test_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="test_username" name="test_username" value="<?php echo htmlspecialchars($test_username); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="test_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="test_password" name="test_password">
                    </div>
                    <button type="submit" class="btn btn-primary">Test Login</button>
                </form>
                
                <?php if ($result_message): ?>
                    <div class="mt-3">
                        <?php echo $result_message; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card debug-info">
            <div class="card-header">
                <h5 class="mb-0">Debug Information</h5>
            </div>
            <div class="card-body">
                <h6>User Data:</h6>
                <pre><?php print_r($user_data); ?></pre>
                
                <h6 class="mt-4">SQL Query:</h6>
                <pre>SELECT u.id, u.username, u.password, u.role, p.first_name, p.last_name 
FROM users u 
LEFT JOIN patients p ON u.id = p.user_id 
WHERE u.username = ? AND u.role = 'patient'</pre>
                
                <h6 class="mt-4">Database Connection:</h6>
                <pre>Host: <?php echo DB_SERVER; ?>
Database: <?php echo DB_NAME; ?>
User: <?php echo DB_USERNAME; ?>
Connection: <?php echo $conn ? '✅ Successful' : '❌ Failed'; ?></pre>
                
                <h6 class="mt-4">PHP Version:</h6>
                <pre><?php echo phpversion(); ?></pre>
            </div>
        </div>
        
        <div class="mt-3 text-center">
            <a href="patient_login.php" class="btn btn-outline-secondary">← Back to Patient Login</a>
        </div>
    </div>
    
    <!-- AI Chat Widget -->
    <?php require_once 'includes/ai_chat_widget.php'; ?>
</body>
</html>
