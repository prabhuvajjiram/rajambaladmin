<?php
header('Content-Type: application/json');
require_once 'db_config.php';

// Initialize response array
$response = ['status' => 'error', 'message' => '', 'products' => []];

try {
    $query = "SELECT p.*, GROUP_CONCAT(c.color_name, ':', c.color_image_path SEPARATOR '|') as colors 
              FROM products p 
              LEFT JOIN colors c ON p.id = c.product_id 
              GROUP BY p.id 
              ORDER BY p.id DESC";
              
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
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
        
        // Process colors if they exist
        if (!empty($row['colors'])) {
            $colorData = explode('|', $row['colors']);
            foreach ($colorData as $color) {
                if (strpos($color, ':') !== false) {
                    list($colorName, $colorImage) = explode(':', $color);
                    $product['colors'][] = [
                        'name' => $colorName,
                        'image_path' => formatImagePath($colorImage, 'colors')
                    ];
                }
            }
        }
        
        // Get additional images
        $images_query = "SELECT image_path FROM product_images WHERE product_id = ? ORDER BY id ASC";
        $stmt = mysqli_prepare($conn, $images_query);
        mysqli_stmt_bind_param($stmt, "i", $row['id']);
        
        if (mysqli_stmt_execute($stmt)) {
            $images_result = mysqli_stmt_get_result($stmt);
            while ($image_row = mysqli_fetch_assoc($images_result)) {
                $product['additional_images'][] = formatImagePath($image_row['image_path'], 'additional');
            }
        }
        mysqli_stmt_close($stmt);
        
        $products[] = $product;
    }
    
    $response['status'] = 'success';
    $response['products'] = $products;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
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
echo json_encode($response);
