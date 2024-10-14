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

require_once 'db_config.php';

$response = ['status' => 'error', 'message' => '', 'products' => []];

try {
    $sql = "SELECT p.*, GROUP_CONCAT(c.color_name, ':', c.color_image_path SEPARATOR '|') as colors 
            FROM products p 
            LEFT JOIN colors c ON p.id = c.product_id 
            GROUP BY p.id";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $products = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $product = [
                'id' => $row['id'],
                'title' => $row['title'],
                'price' => $row['price'],
                'image_path' => $row['image_path'],
                'colors' => []
            ];

            if ($row['colors']) {
                $colors = explode('|', $row['colors']);
                foreach ($colors as $color) {
                    list($name, $image) = explode(':', $color);
                    $product['colors'][] = [
                        'name' => $name,
                        'image_path' => $image
                    ];
                }
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
}

mysqli_close($conn);

header('Content-Type: application/json');
echo json_encode($response);
