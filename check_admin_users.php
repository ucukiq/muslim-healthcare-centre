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

// Get all admin users
$sql = "SHOW TABLES LIKE 'users'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("The 'users' table does not exist in the database.");
}

// Get all admin users
$sql = "SELECT * FROM users WHERE role = 'admin' OR 1=1";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Users Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .table-responsive { margin-top: 20px; }
        .alert { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Admin Users Check</h2>
        
        <div class="alert alert-info">
            <h5>Database Connection:</h5>
            <p>Database: <?php echo DB_NAME; ?></p>
            <p>Host: <?php echo DB_SERVER; ?></p>
            <p>Connection: <?php echo $conn->host_info; ?></p>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <h4>Admin Users in Database</h4>
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo isset($row['email']) ? htmlspecialchars($row['email']) : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($row['role']); ?></td>
                            <td><?php echo isset($row['status']) ? htmlspecialchars($row['status']) : 'N/A'; ?></td>
                            <td><?php echo isset($row['created_at']) ? $row['created_at'] : 'N/A'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                No admin users found in the database.
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <h4>Database Structure</h4>
            <?php
            $tables = $conn->query("SHOW TABLES");
            if ($tables->num_rows > 0):
                echo "<ul class='list-group'>";
                while($table = $tables->fetch_array()) {
                    echo "<li class='list-group-item'>" . $table[0] . "</li>";
                }
                echo "</ul>";
            else:
                echo "<p>No tables found in the database.</p>";
            endif;
            ?>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
