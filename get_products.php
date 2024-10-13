<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

$debug_messages = [];
$error_messages = [];

function logDebug($message) {
    global $debug_messages;
    $debug_messages[] = date('[Y-m-d H:i:s] ') . $message;
}

function logError($message) {
    global $error_messages;
    $error_messages[] = $message;
}

// Custom error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    logDebug("PHP Error [$errno]: $errstr in $errfile on line $errline");
    logError("PHP Error [$errno]: $errstr in $errfile on line $errline");
}

set_error_handler("customErrorHandler");

function utf8ize($mixed) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize($value);
        }
    } elseif (is_string($mixed)) {
        return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
    }
    return $mixed;
}

try {
    logDebug("Script started");

    // Include database configuration
    if (!file_exists('db_config.php')) {
        throw new Exception("Database configuration file not found");
    }
    require_once 'db_config.php';
    logDebug("Database configuration loaded");

    // Check if connection variables are set
    if (!defined('DB_SERVER') || !defined('DB_USERNAME') || !defined('DB_PASSWORD') || !defined('DB_NAME')) {
        throw new Exception("Database configuration variables are not properly set");
    }
    logDebug("Database configuration variables are set");

    // Log database configuration (be careful with sensitive information in production)
    logDebug("DB_SERVER: " . DB_SERVER);
    logDebug("DB_USERNAME: " . DB_USERNAME);
    logDebug("DB_NAME: " . DB_NAME);

    // Attempt to connect to the database
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    logDebug("Database connection successful");

    // Test the connection
    if (!$conn->ping()) {
        throw new Exception("Database connection is not active: " . $conn->error);
    }
    logDebug("Database connection is active");

    // Prepare the SQL statement
    $sql = "SELECT p.*, GROUP_CONCAT(CONCAT(c.color_name, ':', c.color_image_path) SEPARATOR '|') as color_data
        FROM products p
        LEFT JOIN colors c ON p.id = c.product_id
        GROUP BY p.id
        ORDER BY p.created_at DESC";
    logDebug("SQL query: " . $sql);

    $result = $conn->query($sql);
    if (!$result) {
        logDebug("SQL error: " . $conn->error);
        throw new Exception("Database query failed: " . $conn->error);
    }
    logDebug("SQL query executed successfully");

    $products = [];
    $currentProduct = null;
    while ($row = $result->fetch_assoc()) {
        logDebug("Processing row: " . json_encode($row));
        if ($currentProduct === null || $currentProduct['id'] !== $row['id']) {
            if ($currentProduct !== null) {
                $products[] = $currentProduct;
            }
            $currentProduct = [
                'id' => $row['id'],
                'title' => utf8ize($row['title']),
                'price' => floatval($row['price']),
                'image_path' => utf8ize($row['image_path']),
                'description' => utf8ize($row['description']),
                'colors' => []
            ];
        }
        if ($row['color_data']) {
            $colors = explode('|', $row['color_data']);
            foreach ($colors as $color) {
                list($name, $path) = explode(':', $color);
                $currentProduct['colors'][] = [
                    'name' => utf8ize($name),
                    'image' => utf8ize($path)
                ];
            }
        }
    }
    if ($currentProduct !== null) {
        $products[] = $currentProduct;
    }
    foreach ($products as $product) {
        logDebug("Processed product: " . json_encode($product));
    }
    logDebug("Processed " . count($products) . " products");
    logDebug("Final products array: " . json_encode($products));

    $response_data = [
        "status" => "success",
        "products" => $products,
        "count" => count($products),
        "debug" => $debug_messages
    ];

    $response_data['errors'] = $error_messages;
    $json_response = json_encode($response_data);

    if ($json_response === false) {
        $error = "JSON encoding failed: " . json_last_error_msg();
        logError($error);
        $json_response = json_encode([
            'errors' => $error_messages,
            'debug' => $debug_messages
        ]);
    }
    logDebug("JSON encoding completed");

    echo $json_response;
    logDebug("Response sent");

} catch (Exception $e) {
    logDebug("Caught exception: " . $e->getMessage());
    $error_response = [
        "status" => "error",
        "message" => "An error occurred while fetching products. Please try again later.",
        "debug" => $debug_messages,
        "error" => $e->getMessage()
    ];
    echo json_encode($error_response);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
        logDebug("Database connection closed");
    }
    logDebug("Script finished");
}

// Restore the previous error handler
restore_error_handler();
?>
