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

// Check if tables exist
$tables = ['users', 'doctors'];
$missing_tables = [];

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        $missing_tables[] = $table;
    }
}

// Check columns in users table
$users_columns = [];
$result = $conn->query("SHOW COLUMNS FROM users");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users_columns[] = $row['Field'];
    }
}

// Check columns in doctors table
$doctors_columns = [];
$result = $conn->query("SHOW COLUMNS FROM doctors");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $doctors_columns[] = $row['Field'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Database Check</h1>
        
        <div class="card mt-4">
            <div class="card-header">Database Connection</div>
            <div class="card-body">
                <?php if ($conn->connect_error): ?>
                    <div class="alert alert-danger">
                        Connection failed: <?php echo $conn->connect_error; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        Successfully connected to the database.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">Required Tables</div>
            <div class="card-body">
                <?php if (empty($missing_tables)): ?>
                    <div class="alert alert-success">
                        All required tables exist.
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        Missing tables: <?php echo implode(', ', $missing_tables); ?>
                    </div>
                <?php endif; ?>

                <h5>Users Table Columns:</h5>
                <ul>
                    <?php foreach ($users_columns as $column): ?>
                        <li><?php echo $column; ?></li>
                    <?php endforeach; ?>
                </ul>

                <h5>Doctors Table Columns:</h5>
                <ul>
                    <?php foreach ($doctors_columns as $column): ?>
                        <li><?php echo $column; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">PHP Info</div>
            <div class="card-body">
                <a href="phpinfo.php" class="btn btn-primary">View PHP Info</a>
            </div>
        </div>
    </div>
    
    <!-- AI Chat Widget -->
    <?php require_once 'includes/ai_chat_widget.php'; ?>
</body>
</html>
