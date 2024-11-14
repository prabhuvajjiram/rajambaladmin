<?php
header('Content-Type: application/json');
require_once 'db_config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Function to ensure proper image path format
function formatImagePath($path, $type = 'products') {
    if (empty($path)) return 'images/placeholder.jpg';
    
    // If path already has correct format, return as is
    if (strpos($path, 'images/' . $type . '/') === 0) {
        return $path;
    }
    
    // Remove any leading slashes
    $path = ltrim($path, '/');
    
    // Ensure the path starts with the correct directory
    return "images/$type/" . basename($path);
}

// Initialize response array
$response = ['success' => false, 'message' => '', 'product' => null];

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Product ID is required');
    }

    $product_id = intval($_GET['id']);
    
    // Get product details - explicitly list all columns
    $product_query = "SELECT id, title, description, price, image_path FROM products WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $product_query);
    if (!$stmt) {
        throw new Exception('Failed to prepare product statement: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $product_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute product statement: ' . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_bind_result($stmt, $id, $title, $description, $price, $image_path);
    
    if (mysqli_stmt_fetch($stmt)) {
        $product = [
            'id' => $id,
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'image_path' => formatImagePath($image_path),
            'colors' => [],
            'additional_images' => []
        ];
        
        mysqli_stmt_close($stmt);
        
        // Get colors with IDs
        $colors_query = "SELECT id, color_name, color_image_path FROM colors WHERE product_id = ?";
        $stmt = mysqli_prepare($conn, $colors_query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_bind_result($stmt, $color_id, $color_name, $color_image_path);
                while (mysqli_stmt_fetch($stmt)) {
                    $product['colors'][] = [
                        'id' => $color_id,
                        'name' => $color_name,
                        'image_path' => formatImagePath($color_image_path, 'colors')
                    ];
                }
            }
            mysqli_stmt_close($stmt);
        }
        
        // Get additional images with IDs
        $images_query = "SELECT id, image_path FROM product_images WHERE product_id = ?";
        $stmt = mysqli_prepare($conn, $images_query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_bind_result($stmt, $image_id, $additional_image_path);
                while (mysqli_stmt_fetch($stmt)) {
                    $product['additional_images'][] = [
                        'id' => $image_id,
                        'path' => formatImagePath($additional_image_path, 'additional')
                    ];
                }
            }
            mysqli_stmt_close($stmt);
        }
        
        $response['success'] = true;
        $response['product'] = $product;
    } else {
        throw new Exception('Product not found');
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log("Error in get_product.php: " . $e->getMessage());
}

echo json_encode($response);
mysqli_close($conn);
