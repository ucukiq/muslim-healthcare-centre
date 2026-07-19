<?php
echo "<h2>Database Connection Test</h2>";

// Test database connection with different credentials
$configs = [
    [
        'name' => 'Default XAMPP',
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'db'   => 'muslim_healthcare_centre'
    ],
    [
        'name' => 'Alternative DB Name',
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'db'   => 'muslim_helthcare_center'
    ]
];

foreach ($configs as $config) {
    echo "<h3>Trying: {$config['name']}</h3>";
    echo "<pre>Host: {$config['host']}\nUser: {$config['user']}\nPass: {$config['pass']}\nDB: {$config['db']}\n</pre>";
    
    $conn = @new mysqli($config['host'], $config['user'], $config['pass'], $config['db']);
    
    if ($conn->connect_error) {
        echo "<div style='color:red'>Connection failed: " . $conn->connect_error . "</div>";
    } else {
        echo "<div style='color:green'>✓ Connection successful!</div>";
        
        // List tables if connection is successful
        $tables = $conn->query("SHOW TABLES");
        if ($tables) {
            echo "<h4>Tables in database:</h4><ul>";
            while ($table = $tables->fetch_array()) {
                echo "<li>" . $table[0] . "</li>";
            }
            echo "</ul>";
        }
        
        $conn->close();
    }
    echo "<hr>";
}

// Show current database_config.php contents
echo "<h3>Current database_config.php:</h3>";
$configFile = __DIR__ . '/config/database_config.php';
if (file_exists($configFile)) {
    highlight_file($configFile);
} else {
    echo "<div style='color:red'>database_config.php not found!</div>";
}
?>
