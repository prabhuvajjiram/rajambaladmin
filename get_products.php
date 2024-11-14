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

// Function to ensure proper image path format
function formatImagePath($path, $type = 'products') {
    logDebug("Formatting path: $path for type: $type");
    
    if (empty($path)) {
        logDebug("Empty path, returning placeholder");
        return 'images/placeholder.jpg';
    }
    
    // If path already has correct format, return as is
    if (strpos($path, 'images/' . $type . '/') === 0) {
        logDebug("Path already correctly formatted: $path");
        return $path;
    }
    
    // Remove any leading slashes
    $path = ltrim($path, '/');
    
    // Ensure the path starts with the correct directory
    $formattedPath = "images/$type/" . basename($path);
    logDebug("Formatted path: $formattedPath");
    
    return $formattedPath;
}

require_once 'db_config.php';

$response = ['status' => 'error', 'message' => '', 'products' => [], 'debug' => []];

try {
    $sql = "SELECT p.*, GROUP_CONCAT(c.color_name, ':', c.color_image_path SEPARATOR '|') as colors 
            FROM products p 
            LEFT JOIN colors c ON p.id = c.product_id 
            GROUP BY p.id";
            
    logDebug("Executing SQL: $sql");
    
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $products = [];
        while ($row = mysqli_fetch_assoc($result)) {
            logDebug("Processing product ID: {$row['id']}");
            logDebug("Original image path: {$row['image_path']}");
            
            $product = [
                'id' => $row['id'],
                'title' => $row['title'],
                'price' => $row['price'],
                'image_path' => formatImagePath($row['image_path'], 'products'),
                'colors' => []
            ];

            logDebug("Formatted image path: {$product['image_path']}");
            
            if ($row['colors']) {
                logDebug("Processing colors for product {$row['id']}: {$row['colors']}");
                $colors = explode('|', $row['colors']);
                foreach ($colors as $color) {
                    list($name, $image) = explode(':', $color);
                    $formattedColorPath = formatImagePath($image, 'colors');
                    logDebug("Color: $name, Original path: $image, Formatted path: $formattedColorPath");
                    $product['colors'][] = [
                        'name' => $name,
                        'image_path' => $formattedColorPath
                    ];
                }
            }
            
            // Check if image file exists
            $imagePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $product['image_path'];
            logDebug("Checking if image exists: $imagePath");
            if (!file_exists($imagePath)) {
                logDebug("Warning: Image file not found: $imagePath");
            }
            
            $products[] = $product;
        }

        $response['status'] = 'success';
        $response['products'] = $products;
    } else {
        throw new Exception("Error fetching products: " . mysqli_error($conn));
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    logDebug("Error: " . $e->getMessage());
}

$response['debug'] = $debug_messages;
mysqli_close($conn);

header('Content-Type: application/json');
echo json_encode($response);
?>
