<?php
require_once 'db_connect.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('active', 'hidden') DEFAULT 'active'
    )";
    
    $conn->exec($sql);
    echo "Feedback table created successfully";
} catch(PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
