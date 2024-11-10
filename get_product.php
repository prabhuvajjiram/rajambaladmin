<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    
    $query = "SELECT p.id, p.title, p.price, p.description, p.image_path, 
              GROUP_CONCAT(c.id, ':', c.color_name, ':', c.color_image_path SEPARATOR '|') as colors 
              FROM products p 
              LEFT JOIN colors c ON p.id = c.product_id 
              WHERE p.id = ? 
              GROUP BY p.id";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Bind the result variables
        mysqli_stmt_bind_result($stmt, $id, $title, $price, $description, $image_path, $colors);
        
        // Fetch the result
        if (mysqli_stmt_fetch($stmt)) {
            $product = [
                'id' => $id,
                'title' => $title,
                'price' => $price,
                'description' => $description,
                'image_path' => $image_path,
                'colors' => []
            ];
            
            if ($colors) {
                $colorData = explode('|', $colors);
                foreach ($colorData as $color) {
                    list($colorId, $colorName, $colorImage) = explode(':', $color);
                    $product['colors'][] = [
                        'id' => $colorId,
                        'name' => $colorName,
                        'image' => $colorImage
                    ];
                }
            }
            
            echo json_encode(['success' => true, 'product' => $product]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
        }
    } else {
        error_log("SQL Error: " . mysqli_error($conn)); // Log any SQL errors
        echo json_encode(['success' => false, 'message' => 'Failed to fetch product']);
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

mysqli_close($conn);
?>
