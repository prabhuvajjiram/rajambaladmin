<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['status' => 'error', 'message' => 'Method not allowed']));
}

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['name']) || !isset($data['comment'])) {
        throw new Exception('Name and comment are required');
    }
    
    $name = trim($data['name']);
    $comment = trim($data['comment']);
    
    // Validate input
    if (strlen($name) < 2 || strlen($name) > 100) {
        throw new Exception('Name must be between 2 and 100 characters');
    }
    
    if (empty($comment)) {
        throw new Exception('Comment cannot be empty');
    }
    
    if (strlen($comment) > 500) {
        throw new Exception('Comment must not exceed 500 characters');
    }
    
    // Check if feedback table exists, create if it doesn't
    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'feedback'");
    if (mysqli_num_rows($tableCheck) == 0) {
        $createTable = "CREATE TABLE feedback (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            comment TEXT NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if (!mysqli_query($conn, $createTable)) {
            throw new Exception('Error creating feedback table: ' . mysqli_error($conn));
        }
    }
    
    // Prepare and execute insert statement
    $stmt = mysqli_prepare($conn, "INSERT INTO feedback (name, comment) VALUES (?, ?)");
    if (!$stmt) {
        throw new Exception('Error preparing statement: ' . mysqli_error($conn));
    }
    
    if (!mysqli_stmt_bind_param($stmt, "ss", $name, $comment)) {
        throw new Exception('Error binding parameters: ' . mysqli_stmt_error($stmt));
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error executing statement: ' . mysqli_stmt_error($stmt));
    }
    
    $feedbackId = mysqli_insert_id($conn);
    
    die(json_encode([
        'status' => 'success',
        'message' => 'Feedback submitted successfully',
        'id' => $feedbackId
    ]));
    
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]));
}
?>
