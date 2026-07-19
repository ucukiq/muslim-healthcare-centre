<?php
// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'muslim_healthcare_centre';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check for admin users
$result = $conn->query("SELECT id, username, email FROM users WHERE role = 'admin'");

if ($result->num_rows > 0) {
    echo "<h3>Admin Users Found:</h3>";
    echo "<table class='table table-striped'><tr><th>ID</th><th>Username</th><th>Email</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><a href='admin_login.php' class='btn btn-primary'>Go to Login</a></p>";
} else {
    echo "<div class='alert alert-warning'>No admin users found in the database.</div>";
    echo "<p><a href='admin_register.php' class='btn btn-primary'>Register New Admin</a></p>";
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Check Admin Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Admin Users</h4>
                    </div>
                    <div class="card-body">
                        <?php include 'check_admin.php'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
