<?php
header('Content-Type: application/json');
require_once 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($product_id > 0) {
        // First, get the image path
        $sql = "SELECT image_path FROM products WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $image_path);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
            
            // Now delete the product
            $sql = "DELETE FROM products WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $product_id);
                if (mysqli_stmt_execute($stmt)) {
                    // Delete the image file
                    if ($image_path && file_exists($image_path)) {
                        unlink($image_path);
                    }
                    echo json_encode(["status" => "success", "message" => "Product deleted successfully."]);
                } else {
                    echo json_encode(["status" => "error", "message" => "Error deleting product: " . mysqli_error($conn)]);
                }
                mysqli_stmt_close($stmt);
            } else {
                echo json_encode(["status" => "error", "message" => "Error preparing delete statement: " . mysqli_error($conn)]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Error preparing select statement: " . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid product ID."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}

mysqli_close($conn);
?>