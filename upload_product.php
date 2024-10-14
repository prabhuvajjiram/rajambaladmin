<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
require_once 'db_config.php';

$response = ['success' => false, 'message' => '', 'product' => null];

function processAndResizeImage($sourcePath, $targetPath, $maxWidth, $maxHeight, $quality = 90) {
    list($width, $height, $imageType) = getimagesize($sourcePath);

    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        default:
            throw new Exception("Unsupported image type.");
    }

    // Check for EXIF orientation and rotate if necessary
    if (function_exists('exif_read_data') && $imageType == IMAGETYPE_JPEG) {
        $exif = @exif_read_data($sourcePath);
        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3:
                    $sourceImage = imagerotate($sourceImage, 180, 0);
                    break;
                case 6:
                    $sourceImage = imagerotate($sourceImage, -90, 0);
                    list($width, $height) = [$height, $width];
                    break;
                case 8:
                    $sourceImage = imagerotate($sourceImage, 90, 0);
                    list($width, $height) = [$height, $width];
                    break;
            }
        }
    }

    $aspectRatio = $width / $height;
    if ($width > $maxWidth || $height > $maxHeight) {
        if ($maxWidth / $maxHeight > $aspectRatio) {
            $newWidth = $maxHeight * $aspectRatio;
            $newHeight = $maxHeight;
        } else {
            $newWidth = $maxWidth;
            $newHeight = $maxWidth / $aspectRatio;
        }
    } else {
        $newWidth = $width;
        $newHeight = $height;
    }

    $newImage = imagecreatetruecolor($newWidth, $newHeight);

    // Preserve transparency for PNG images
    if ($imageType == IMAGETYPE_PNG) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }

    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    switch ($imageType) {
        case IMAGETYPE_JPEG:
            imagejpeg($newImage, $targetPath, $quality);
            break;
        case IMAGETYPE_PNG:
            imagepng($newImage, $targetPath, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($newImage, $targetPath);
            break;
    }

    imagedestroy($sourceImage);
    imagedestroy($newImage);

    return true;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $upload_dir = "images/products/";
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                throw new Exception("Failed to create upload directory.");
            }
        }
        
        if (!isset($_FILES["image"]) || $_FILES["image"]["error"] != UPLOAD_ERR_OK) {
            throw new Exception("Error uploading main product image.");
        }

        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception("Invalid file type. Only JPG, PNG, and GIF are allowed.");
        }

        $base_name = pathinfo($_FILES["image"]["name"], PATHINFO_FILENAME);
        $file_name = $base_name . "_" . uniqid() . "." . $file_extension;
        $target_file = $upload_dir . $file_name;
        
        $temp_file = $_FILES["image"]["tmp_name"];
        
        if (!processAndResizeImage($temp_file, $target_file, 800, 800)) {
            throw new Exception("Failed to process and resize main product image.");
        }

        $title = mysqli_real_escape_string($conn, $_POST["title"]);
        $price = floatval($_POST["price"]);
        $description = mysqli_real_escape_string($conn, $_POST["description"]);
        
        $sql = "INSERT INTO products (title, price, description, image_path) VALUES (?, ?, ?, ?)";
        
        if (!$stmt = mysqli_prepare($conn, $sql)) {
            throw new Exception("Error preparing statement: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "sdss", $title, $price, $description, $target_file);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error executing statement: " . mysqli_error($conn));
        }

        $product_id = mysqli_insert_id($conn);
        $colors_added = [];
        
        if (isset($_POST['colors']) && is_array($_POST['colors'])) {
            $color_sql = "INSERT INTO colors (product_id, color_name, color_image_path) VALUES (?, ?, ?)";
            if (!$color_stmt = mysqli_prepare($conn, $color_sql)) {
                throw new Exception("Error preparing color statement: " . mysqli_error($conn));
            }

            foreach ($_POST['colors'] as $key => $color_data) {
                if (isset($color_data['name']) && isset($_FILES['colors']['name'][$key]['image'])) {
                    $color_name = $color_data['name'];
                    $color_image = $_FILES['colors']['name'][$key]['image'];
                    
                    if ($_FILES['colors']['error'][$key]['image'] === UPLOAD_ERR_OK) {
                        $color_file_extension = strtolower(pathinfo($color_image, PATHINFO_EXTENSION));
                        if (!in_array($color_file_extension, $allowed_extensions)) {
                            continue; // Skip invalid file types
                        }

                        $color_base_name = pathinfo($color_image, PATHINFO_FILENAME);
                        $color_file_name = $color_base_name . "_" . uniqid() . "." . $color_file_extension;
                        $color_target_file = $upload_dir . $color_file_name;
                        
                        if (processAndResizeImage($_FILES['colors']['tmp_name'][$key]['image'], $color_target_file, 800, 800)) {
                            $color_image_path = $color_target_file;
                            mysqli_stmt_bind_param($color_stmt, "iss", $product_id, $color_name, $color_image_path);
                            if (!mysqli_stmt_execute($color_stmt)) {
                                throw new Exception("Error executing color statement: " . mysqli_error($conn));
                            }
                            $colors_added[] = ['name' => $color_name, 'image' => $color_image_path];
                        }
                    }
                }
            }
            
            mysqli_stmt_close($color_stmt);
        }

        $response = [
            "success" => true,
            "message" => "The product has been uploaded successfully.",
            "product" => [
                "id" => $product_id,
                "title" => $title,
                "price" => $price,
                "description" => $description,
                "image" => $target_file,
                "colors" => $colors_added
            ]
        ];

    } catch (Exception $e) {
        $response = ["success" => false, "message" => $e->getMessage(), "product" => null];
    }
} else {
    $response = ["success" => false, "message" => "Invalid request method.", "product" => null];
}

mysqli_close($conn);

echo json_encode($response);
?>
