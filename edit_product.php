<?php
header('Content-Type: application/json');
require_once 'db_config.php';

function resizeImage($sourcePath, $targetPath, $maxWidth, $maxHeight) {
    list($width, $height) = getimagesize($sourcePath);
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = $width * $ratio;
    $newHeight = $height * $ratio;
    
    $sourceImage = imagecreatefromstring(file_get_contents($sourcePath));
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    $result = imagejpeg($newImage, $targetPath, 90);
    
    imagedestroy($sourceImage);
    imagedestroy($newImage);
    
    return $result;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = intval($_POST['id']);
    $title = mysqli_real_escape_string($conn, $_POST["title"]);
    $price = floatval($_POST["price"]);
    $description = mysqli_real_escape_string($conn, $_POST["description"]);
    
    $sql = "UPDATE products SET title = ?, price = ?, description = ?";
    $params = [$title, $price, $description];
    $types = "sds";
    
    $new_image_path = null;
    if (isset($_FILES["image"]) && $_FILES["image"]["size"] > 0) {
        $upload_dir = "images/products/";
        $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $base_name = pathinfo($_FILES["image"]["name"], PATHINFO_FILENAME);
        $file_name = $base_name . "_" . uniqid() . "." . $file_extension;
        $target_file = $upload_dir . $file_name;
        
        $temp_file = $_FILES["image"]["tmp_name"];
        
        if (resizeImage($temp_file, $target_file, 800, 800)) {
            $sql .= ", image_path = ?";
            $params[] = $target_file;
            $types .= "s";
            $new_image_path = $target_file;
        } else {
            echo json_encode(["status" => "error", "message" => "Error resizing and uploading the image."]);
            exit;
        }
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $product_id;
    $types .= "i";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        
        if (mysqli_stmt_execute($stmt)) {
            // Fetch the updated product data
            $fetch_sql = "SELECT * FROM products WHERE id = ?";
            $fetch_stmt = mysqli_prepare($conn, $fetch_sql);
            mysqli_stmt_bind_param($fetch_stmt, "i", $product_id);
            mysqli_stmt_execute($fetch_stmt);
            $result = mysqli_stmt_get_result($fetch_stmt);
            $updated_product = mysqli_fetch_assoc($result);
            
            echo json_encode([
                "status" => "success",
                "message" => "Product updated successfully.",
                "product" => [
                    "id" => $updated_product['id'],
                    "title" => $updated_product['title'],
                    "price" => $updated_product['price'],
                    "description" => $updated_product['description'],
                    "image" => $new_image_path ?? $updated_product['image_path']
                ]
            ]);
            
            mysqli_stmt_close($fetch_stmt);
        } else {
            echo json_encode(["status" => "error", "message" => "Error updating product: " . mysqli_error($conn)]);
        }
        
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(["status" => "error", "message" => "Error preparing statement: " . mysqli_error($conn)]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}

mysqli_close($conn);
?>