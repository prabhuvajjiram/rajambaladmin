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
    $upload_dir = "images/products/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
    $base_name = pathinfo($_FILES["image"]["name"], PATHINFO_FILENAME);
    $file_name = $base_name . "_" . uniqid() . "." . $file_extension;
    $target_file = $upload_dir . $file_name;
    
    $temp_file = $_FILES["image"]["tmp_name"];
    
    if (resizeImage($temp_file, $target_file, 800, 800)) {
        $title = mysqli_real_escape_string($conn, $_POST["title"]);
        $price = floatval($_POST["price"]);
        $description = mysqli_real_escape_string($conn, $_POST["description"]);
        
        $sql = "INSERT INTO products (title, price, description, image_path) VALUES (?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "sdss", $title, $price, $description, $target_file);
            
            if(mysqli_stmt_execute($stmt)){
                $product_id = mysqli_insert_id($conn);
                echo json_encode([
                    "status" => "success", 
                    "message" => "The product has been uploaded and resized successfully.",
                    "product" => [
                        "id" => $product_id,
                        "title" => $title,
                        "price" => $price,
                        "description" => $description,
                        "image" => $target_file
                    ]
                ]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error executing statement: " . mysqli_error($conn)]);
            }
            
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode(["status" => "error", "message" => "Error preparing statement: " . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Sorry, there was an error uploading and resizing your file."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}

mysqli_close($conn);
?>