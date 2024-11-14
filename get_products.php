<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
error_log("Starting get_products.php");

header('Content-Type: application/json');
require_once 'db_config.php';

// Initialize response array
$response = ['status' => 'error', 'message' => '', 'products' => []];

try {
    error_log("Database connection status: " . ($conn ? "Connected" : "Failed"));
    
    // Start with the simplest query possible
    $query = "SELECT * FROM products ORDER BY id DESC";
    error_log("Executing query: " . $query);
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        error_log("MySQL Error: " . mysqli_error($conn));
        throw new Exception('Failed to fetch products: ' . mysqli_error($conn));
    }
    
    $products = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $product = [
            'id' => $row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'price' => $row['price'],
            'image_path' => formatImagePath($row['image_path'], 'products'),
            'colors' => [],
            'additional_images' => []
        ];
        
        // Try to get colors if they exist
        try {
            $colors_query = "SELECT color_name, color_image_path FROM colors WHERE product_id = ?";
            $stmt = mysqli_prepare($conn, $colors_query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $row['id']);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_bind_result($stmt, $color_name, $color_image_path);
                    while (mysqli_stmt_fetch($stmt)) {
                        $product['colors'][] = [
                            'name' => $color_name,
                            'image_path' => formatImagePath($color_image_path, 'colors')
                        ];
                    }
                }
                mysqli_stmt_close($stmt);
            }
        } catch (Exception $e) {
            error_log("Error fetching colors: " . $e->getMessage());
            // Continue execution even if colors fetch fails
        }

        // Try to get additional images if they exist
        try {
            $images_query = "SELECT image_path FROM product_images WHERE product_id = ?";
            $stmt = mysqli_prepare($conn, $images_query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $row['id']);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_bind_result($stmt, $additional_image_path);
                    while (mysqli_stmt_fetch($stmt)) {
                        $product['additional_images'][] = formatImagePath($additional_image_path, 'additional');
                    }
                }
                mysqli_stmt_close($stmt);
            }
        } catch (Exception $e) {
            error_log("Error fetching additional images: " . $e->getMessage());
            // Continue execution even if additional images fetch fails
        }

        $products[] = $product;
    }
    
    $response['status'] = 'success';
    $response['products'] = $products;
    error_log("Successfully fetched " . count($products) . " products");
    
} catch (Exception $e) {
    error_log("Critical error in get_products.php: " . $e->getMessage());
    $response['message'] = "An error occurred while fetching products. Please try again later.";
}

// Function to ensure proper image path format
function formatImagePath($path, $type = 'products') {
    if (empty($path)) {
        return 'images/placeholder.jpg';
    }
    
    // If path already has correct format, return as is
    if (strpos($path, 'images/' . $type . '/') === 0) {
        return $path;
    }
    
    // Remove any leading slashes
    $path = ltrim($path, '/');
    
    // Ensure the path starts with the correct directory
    return "images/$type/" . basename($path);
}

mysqli_close($conn);
error_log("Sending response: " . json_encode($response));
echo json_encode($response);
