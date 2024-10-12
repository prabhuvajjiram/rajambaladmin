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

        $sql = "SELECT * FROM products WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) > 0) {
                    mysqli_stmt_bind_result($stmt, $id, $title, $price, $description, $image_path);
                    mysqli_stmt_fetch($stmt);
                    
                    sendJsonResponse([
                        "status" => "success",
                        "product" => [
                            "id" => $id,
                            "title" => $title,
                            "price" => $price,
                            "description" => $description,
                            "image" => $image_path
                        ]
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