<?php
// Disable error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Set the content type to JSON
header('Content-Type: application/json');

require_once 'db_config.php';

// Function to send JSON response
function sendJsonResponse($data) {
    echo json_encode($data);
    exit;
}

// Function to handle errors
function handleError($message) {
    error_log($message);
    sendJsonResponse([
        "status" => "error",
        "message" => "An error occurred while processing your request. Please check the server logs for more information."
    ]);
}

// Wrap the entire script in a try-catch block
try {
    if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id'])) {
        $product_id = intval($_GET['id']);
        
        // Check database connection
        if (!$conn) {
            throw new Exception("Database connection failed: " . mysqli_connect_error());
        }

        $sql = "SELECT p.*, GROUP_CONCAT(c.id, ':', c.color_name, ':', c.color_image_path SEPARATOR '|') as colors 
                FROM products p 
                LEFT JOIN colors c ON p.id = c.product_id 
                WHERE p.id = ?
                GROUP BY p.id";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                
                if ($row = mysqli_fetch_assoc($result)) {
                    $product = [
                        'id' => $row['id'],
                        'title' => $row['title'],
                        'price' => $row['price'],
                        'description' => $row['description'],
                        'image_path' => $row['image_path'],
                        'colors' => []
                    ];

                    if ($row['colors']) {
                        $colors = explode('|', $row['colors']);
                        foreach ($colors as $color) {
                            list($color_id, $color_name, $color_image_path) = explode(':', $color);
                            $product['colors'][] = [
                                'id' => $color_id,
                                'name' => $color_name,
                                'image_path' => $color_image_path
                            ];
                        }
                    }

                    sendJsonResponse([
                        "status" => "success",
                        "product" => $product
                    ]);
                } else {
                    sendJsonResponse(["status" => "error", "message" => "Product not found."]);
                }
            } else {
                throw new Exception("Error executing query: " . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception("Error preparing statement: " . mysqli_error($conn));
        }
    } else {
        sendJsonResponse(["status" => "error", "message" => "Invalid request."]);
    }
} catch (Exception $e) {
    handleError("Error in get_product.php: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>
