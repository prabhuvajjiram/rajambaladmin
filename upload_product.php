<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
require_once 'db_config.php';

$debug_messages = [];
$error_messages = [];
$response = ['success' => false, 'message' => '', 'debug' => [], 'errors' => []];

function logDebug($message) {
    global $debug_messages;
    $debug_messages[] = date('[Y-m-d H:i:s] ') . $message;
}

function logError($message) {
    global $error_messages;
    $error_messages[] = $message;
}

function customErrorHandler($errno, $errstr, $errfile, $errline) {
    logDebug("PHP Error [$errno]: $errstr in $errfile on line $errline");
    logError("PHP Error [$errno]: $errstr in $errfile on line $errline");
}

set_error_handler("customErrorHandler");

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
    try {
        $upload_dir = "images/products/";
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                throw new Exception("Failed to create upload directory.");
            }
        }
        
        if (!isset($_FILES["image"]) || $_FILES["image"]["error"] != UPLOAD_ERR_OK) {
            throw new Exception("Error uploading main product image.");
        }

        $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $base_name = pathinfo($_FILES["image"]["name"], PATHINFO_FILENAME);
        $file_name = $base_name . "_" . uniqid() . "." . $file_extension;
        $target_file = $upload_dir . $file_name;
        
        $temp_file = $_FILES["image"]["tmp_name"];
        
        if (!resizeImage($temp_file, $target_file, 800, 800)) {
            throw new Exception("Failed to resize main product image.");
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
        
        // Handle color uploads
        if (isset($_POST['colors']) && is_array($_POST['colors'])) {
            $color_sql = "INSERT INTO colors (product_id, color_name, color_image_path) VALUES (?, ?, ?)";
            if (!$color_stmt = mysqli_prepare($conn, $color_sql)) {
                throw new Exception("Error preparing color statement: " . mysqli_error($conn));
            }

            logDebug("Processing " . count($_POST['colors']) . " colors");

            foreach ($_POST['colors'] as $key => $color_data) {
                logDebug("Processing color: " . $key);
                if (isset($color_data['name']) && isset($_FILES['colors']['name'][$key])) {
                    $color_name = $color_data['name'];
                    $color_image = [
                        'name' => $_FILES['colors']['name'][$key],
                        'type' => $_FILES['colors']['type'][$key],
                        'tmp_name' => $_FILES['colors']['tmp_name'][$key],
                        'error' => $_FILES['colors']['error'][$key],
                        'size' => $_FILES['colors']['size'][$key]
                    ];
                    
                    logDebug("Color data: " . json_encode($color_image));

                    if ($color_image['error'] === UPLOAD_ERR_OK) {
                        $color_file_extension = pathinfo($color_image["name"], PATHINFO_EXTENSION);
                        $color_base_name = pathinfo($color_image["name"], PATHINFO_FILENAME);
                        $color_file_name = $color_base_name . "_" . uniqid() . "." . $color_file_extension;
                        $color_target_file = $upload_dir . $color_file_name;
                        
                        if (resizeImage($color_image["tmp_name"], $color_target_file, 800, 800)) {
                            mysqli_stmt_bind_param($color_stmt, "iss", $product_id, $color_name, $color_target_file);
                            if (!mysqli_stmt_execute($color_stmt)) {
                                throw new Exception("Error executing color statement: " . mysqli_error($conn));
                            }
                            $colors_added[] = ['name' => $color_name, 'image' => $color_target_file];
                            logDebug("Color added: " . $color_name . " - " . $color_target_file);
                        } else {
                            logError("Failed to resize color image: " . $color_image["name"]);
                        }
                    } else {
                        logError("Error uploading color image: " . $color_image['error']);
                    }
                } else {
                    logError("Missing color name or file for key: " . $key);
                }
            }
            
            mysqli_stmt_close($color_stmt);
            logDebug("Number of colors added: " . count($colors_added));
        } else {
            logDebug("No colors to process");
        }

        $response = [
            "status" => "success",
            "message" => "The product has been uploaded and resized successfully.",
            "product" => [
                "id" => $product_id,
                "title" => $title,
                "price" => $price,
                "description" => $description,
                "colors" => $colors_added,
                "image" => $target_file,
                "colors" => $colors_added
            ]
        ];

    } catch (Exception $e) {
        logError($e->getMessage());
        $response = ["status" => "error", "message" => $e->getMessage()];
    }
} else {
    $response = ["status" => "error", "message" => "Invalid request method."];
}

mysqli_close($conn);

echo json_encode($response);
?>