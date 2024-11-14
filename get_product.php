<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display
header('Content-Type: application/json');

require_once 'db_config.php';

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
    
    // Get product details
    $product_query = "SELECT p.*, GROUP_CONCAT(c.color_name, ':', c.color_image_path SEPARATOR '|') as colors 
                     FROM products p 
                     LEFT JOIN colors c ON p.id = c.product_id 
                     WHERE p.id = ? 
                     GROUP BY p.id";
    
    $stmt = mysqli_prepare($conn, $product_query);
    if (!$stmt) {
        throw new Exception('Failed to prepare product statement: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $product_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute product statement: ' . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        throw new Exception('Failed to get product result: ' . mysqli_stmt_error($stmt));
    }

    if ($row = mysqli_fetch_assoc($result)) {
        $product = [
            'id' => $row['id'],
            'title' => $row['title'],
            'price' => $row['price'],
            'description' => $row['description'],
            'image_path' => formatImagePath($row['image_path'], 'products'),
            'colors' => [],
            'additional_images' => []
        ];

        // Process colors
        if (!empty($row['colors'])) {
            $colorData = explode('|', $row['colors']);
            foreach ($colorData as $color) {
                list($colorName, $colorImage) = explode(':', $color);
                $product['colors'][] = [
                    'name' => $colorName,
                    'image_path' => formatImagePath($colorImage, 'colors')
                ];
            }
        }

        mysqli_stmt_close($stmt);

        // Get additional images in a separate query
        $images_query = "SELECT image_path FROM product_images WHERE product_id = ? ORDER BY id ASC";
        $images_stmt = mysqli_prepare($conn, $images_query);
        
        if ($images_stmt) {
            mysqli_stmt_bind_param($images_stmt, "i", $product_id);
            
            if (mysqli_stmt_execute($images_stmt)) {
                $images_result = mysqli_stmt_get_result($images_stmt);
                while ($image_row = mysqli_fetch_assoc($images_result)) {
                    $product['additional_images'][] = formatImagePath($image_row['image_path'], 'additional');
                }
            }
            mysqli_stmt_close($images_stmt);
        }

        $response['success'] = true;
        $response['product'] = $product;
    } else {
        throw new Exception('Product not found');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Error in get_product.php: " . $e->getMessage());
}

mysqli_close($conn);
// Ensure clean output
ob_clean();
echo json_encode($response);
exit;
?>
