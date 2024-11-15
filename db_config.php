<?php
error_reporting(0);
ini_set('display_errors', 0);

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'rajambal_admin');
//define('DB_USERNAME', 'theangel_admin'); PROD
define('DB_PASSWORD', 's3DqGWV22-A%');
define('DB_NAME', 'theangel_rajambal'); //- This is for prod
//define('DB_NAME', 'rajambal_rajambal');

// Create connection
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($conn === false){
    header('Content-Type: application/json');
    http_response_code(500);
    die(json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . mysqli_connect_error()
    ]));
}

// Set charset to ensure proper encoding
//mysqli_set_charset($conn, "utf8mb4");
?>
