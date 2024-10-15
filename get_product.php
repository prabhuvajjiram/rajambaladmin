<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    
    // Prepare the SQL statement to fetch the product
    $query = "SELECT p.*, GROUP_CONCAT(c.id, ':', c.color_name, ':', c.color_image_path SEPARATOR '|') as colors 
              FROM products p 
              LEFT JOIN colors c ON p.id = c.product_id 
              WHERE p.id = ?
              GROUP BY p.id";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $product = mysqli_fetch_assoc($result);
        
        if ($product) {
            // Process colors
            $colors = [];
            if ($product['colors']) {
                $colorData = explode('|', $product['colors']);
                foreach ($colorData as $color) {
                    list($colorId, $colorName, $colorImage) = explode(':', $color);
                    $colors[] = [
                        'id' => $colorId,
                        'name' => $colorName,
                        'image' => $colorImage
                    ];
                }
            }
            $product['colors'] = $colors;
            
            echo json_encode(['success' => true, 'product' => $product]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch product']);
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

mysqli_close($conn);
?>
