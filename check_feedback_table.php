<?php
require_once 'db_config.php';

if($conn === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Check if feedback table exists
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'feedback'");

if(mysqli_num_rows($tableCheck) == 0) {
    // Table doesn't exist, create it
    $sql = "CREATE TABLE IF NOT EXISTS feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        comment TEXT NOT NULL,
        status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if(mysqli_query($conn, $sql)){
        echo "Feedback table created successfully";
    } else {
        echo "ERROR: Could not create table. " . mysqli_error($conn);
    }
} else {
    echo "Feedback table already exists";
}

mysqli_close($conn);
?>
