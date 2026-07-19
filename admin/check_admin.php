<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include config file
require_once "../includes/config.php";

// Function to check if a user exists
function checkAdminUser($username, $conn) {
    $sql = "SELECT id, username, password, role, status FROM users WHERE username = ? LIMIT 1";
    
    if($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        $stmt->close();
    }
    return false;
}

// Function to verify password
function verifyPassword($password, $hashed_password) {
    return password_verify($password, $hashed_password);
}

// Get all admin users
$sql = "SELECT id, username, password, role, status FROM users WHERE role = 'admin'";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin User Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Admin User Check</h2>
        
        <?php if(isset($_POST['check_user'])): 
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            $user = checkAdminUser($username, $conn);
        ?>
            <div class="card mt-4">
                <div class="card-header">Test Results</div>
                <div class="card-body">
                    <?php if($user): ?>
                        <div class="alert alert-success">
                            <h5>User Found:</h5>
                            <pre><?php print_r($user); ?></pre>
                            <p>Password verification: 
                                <?php 
                                if(verifyPassword($password, $user['password'])) {
                                    echo '<span class="text-success">SUCCESS</span>';
                                } else {
                                    echo '<span class="text-danger">FAILED</span>';
                                    echo '<div class="mt-2"><strong>Debug Info:</strong>';
                                    echo '<div>Input password: ' . htmlspecialchars($password) . '</div>';
                                    echo '<div>Stored hash: ' . htmlspecialchars($user['password']) . '</div>';
                                    echo '</div>';
                                }
                                ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            User not found in the database.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card mt-4">
            <div class="card-header">Test Login</div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" name="check_user" class="btn btn-primary">Check User</button>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">All Admin Users</div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Status</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo $row['status']; ?></td>
                            <td><?php echo $row['role']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
