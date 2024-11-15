<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once 'db_config.php';

try {
    // Connection is already established in db_config.php
    if (!isset($conn) || $conn === false) {
        throw new Exception("Database connection failed");
    }
    
    // Check if feedback table exists
    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'feedback'");
    if (!$tableCheck) {
        throw new Exception(mysqli_error($conn));
    }
    
    if (mysqli_num_rows($tableCheck) == 0) {
        throw new Exception("Feedback table does not exist");
    }
    
    // Get all active feedback entries
    $query = "SELECT id, name, comment, created_at 
              FROM feedback 
              WHERE status = 'active' 
              ORDER BY created_at DESC";
    
    $result = mysqli_query($conn, $query);
    if (!$result) {
        throw new Exception(mysqli_error($conn));
    }
    
    $feedback = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $feedback[] = array(
            'id' => (int)$row['id'],
            'name' => htmlspecialchars($row['name']),
            'comment' => htmlspecialchars($row['comment']),
            'created_at' => $row['created_at']
        );
    }
    
    die(json_encode([
        'status' => 'success',
        'data' => $feedback
    ]));
    
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]));
}
?>
