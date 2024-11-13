<?php
header('Content-Type: application/json');
require_once 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    $product_id = isset($data['product_id']) ? intval($data['product_id']) : 0;
    
    if ($product_id > 0) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Get all image paths before deletion
            $image_paths = array();
            
            // Get product image
            $sql = "SELECT image_path FROM products WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $image_paths[] = $row['image_path'];
            }
            mysqli_stmt_close($stmt);
            
            // Get additional images
            $sql = "SELECT image_path FROM product_images WHERE product_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $image_paths[] = $row['image_path'];
            }
            mysqli_stmt_close($stmt);
            
            // Get color images
            $sql = "SELECT color_image_path FROM colors WHERE product_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $image_paths[] = $row['color_image_path'];
            }
            mysqli_stmt_close($stmt);
            
            // Delete from colors table
            $sql = "DELETE FROM colors WHERE product_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            // Delete from product_images table
            $sql = "DELETE FROM product_images WHERE product_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            // Delete from products table
            $sql = "DELETE FROM products WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            // Delete image files
            foreach ($image_paths as $path) {
                if ($path && file_exists($path)) {
                    unlink($path);
                }
            }
            
            // Commit transaction
            mysqli_commit($conn);
            echo json_encode(["success" => true, "message" => "Product deleted successfully"]);
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Invalid product ID"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
}

mysqli_close($conn);
?>