<?php
// Capture any HTML output that might have occurred before this script
$initial_html = ob_get_contents();
ob_clean();

// Start output buffering
ob_start();

// Disable error output
ini_set('display_errors', 0);

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');
error_reporting(E_ALL);

$response = [
    'success' => false,
    'message' => '',
    'product' => null,
    'debug' => [
        'Script start time: ' . date('Y-m-d H:i:s'),
        'PHP Version: ' . phpversion(),
        'Server Software: ' . $_SERVER['SERVER_SOFTWARE'],
        'display_errors: ' . ini_get('display_errors'),
        'error_reporting: ' . error_reporting(),
        'log_errors: ' . ini_get('log_errors'),
        'error_log: ' . ini_get('error_log'),
        'output_buffering: ' . ini_get('output_buffering'),
        'default_mimetype: ' . ini_get('default_mimetype'),
        'default_charset: ' . ini_get('default_charset'),
        'max_execution_time: ' . ini_get('max_execution_time'),
        'memory_limit: ' . ini_get('memory_limit')
    ]
];

if (!empty($initial_html)) {
    $response['debug'][] = "Initial HTML output before script: " . $initial_html;
}

// Check for any errors logged before this point
$error_log = @file_get_contents('php_errors.log');
if ($error_log !== false && !empty($error_log)) {
    $response['debug'][] = "Errors logged before script: " . $error_log;
    // Clear the error log
    @file_put_contents('php_errors.log', '');
}

// Check if there's any output buffered
$buffered_output = ob_get_contents();
if (!empty($buffered_output)) {
    $response['debug'][] = "Buffered output at start of script: " . $buffered_output;
}
ob_clean();

// Check if headers have already been sent
if (headers_sent($filename, $linenum)) {
    $response['debug'][] = "Headers already sent in $filename on line $linenum";
}

// Function to safely encode response as JSON
if (!function_exists('safe_json_encode')) {
    function safe_json_encode($data) {
        $json = json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return json_encode([
                'success' => false,
                'message' => 'JSON encoding failed',
                'debug' => [
                    'JSON error: ' . json_last_error_msg(),
                    'Original data: ' . print_r($data, true)
                ]
            ]);
        }
        return $json;
    }
}

// Function to send JSON response and exit
if (!function_exists('send_json_response')) {
    function send_json_response() {
        global $response;
        $output = ob_get_clean();
        if (!empty($output)) {
            $response['debug'][] = "Unexpected output during script execution: " . $output;
        }
        header('Content-Type: application/json; charset=utf-8');
        echo safe_json_encode($response);
        exit;
    }
}

// Function to log debug messages
if (!function_exists('debug_log')) {
    function debug_log($message, $data = null) {
        $log = date('Y-m-d H:i:s') . " - " . $message;
        if ($data !== null) {
            $log .= "\n" . print_r($data, true);
        }
        $log .= "\n";
        error_log($log, 3, "debug.log");
    }
}

// Set custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$response) {
    $response['success'] = false;
    $response['message'] = "An error occurred: [$errno] $errstr in $errfile on line $errline";
    $response['debug'][] = "Error backtrace: " . print_r(debug_backtrace(), true);
    send_json_response();
});

// Set exception handler
set_exception_handler(function($e) use (&$response) {
    $response['success'] = false;
    $response['message'] = "An exception occurred: " . $e->getMessage();
    $response['debug'][] = "Exception trace: " . $e->getTraceAsString();
    send_json_response();
});

// Register shutdown function
register_shutdown_function(function() use (&$response) {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        $response['success'] = false;
        $response['message'] = "A fatal error occurred: [{$error['type']}] {$error['message']} in {$error['file']} on line {$error['line']}";
        send_json_response();
    }
});

// Function to process and resize images
if (!function_exists('processAndResizeImage')) {
    function processAndResizeImage($sourcePath, $targetPath, $maxWidth = 800, $maxHeight = 800, $quality = 90) {
        $imageInfo = @getimagesize($sourcePath);
        if ($imageInfo === false) {
            throw new Exception("Failed to get image information");
        }
        list($width, $height, $imageType) = $imageInfo;

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
                throw new Exception("Unsupported image type");
        }

        if (!$sourceImage) {
            throw new Exception("Failed to create source image");
        }

        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);

        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        if (!$newImage) {
            throw new Exception("Failed to create new image");
        }

        if ($imageType == IMAGETYPE_PNG) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        if (!imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height)) {
            throw new Exception("Failed to resize image");
        }

        $success = false;
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $success = imagejpeg($newImage, $targetPath, $quality);
                break;
            case IMAGETYPE_PNG:
                $success = imagepng($newImage, $targetPath, 9);
                break;
            case IMAGETYPE_GIF:
                $success = imagegif($newImage, $targetPath);
                break;
        }

        imagedestroy($sourceImage);
        imagedestroy($newImage);

        if (!$success) {
            throw new Exception("Failed to save processed image");
        }

        return true;
    }
}

try {
    debug_log("=== Starting Product Upload ===");
    debug_log("POST Data:", $_POST);
    debug_log("FILES Data:", $_FILES);

    // Enable error reporting
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // Create upload directories if they don't exist
    $dirs = [
        'images/products/',
        'images/colors/',
        'images/additional/'
    ];

    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            debug_log("Creating directory: " . $dir);
            if (!mkdir($dir, 0755, true)) {
                throw new Exception("Failed to create directory: " . $dir);
            }
            debug_log("Directory created successfully: " . $dir);
        }
    }

    // Use the existing database connection from db_config.php
    if (!file_exists('db_config.php')) {
        throw new Exception("db_config.php file not found");
    }

    // Capture any output generated by db_config.php
    ob_start();
    require_once 'db_config.php';
    $db_config_output = ob_get_clean();
    
    if (!empty($db_config_output)) {
        $response['debug'][] = "Output from db_config.php: " . $db_config_output;
    }

    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection failed: " . (isset($conn) ? mysqli_connect_error() : "Connection variable not set"));
    }

    debug_log("Database connection successful");

    // Start transaction
    $conn->begin_transaction();
    debug_log("Started database transaction");

    try {
        // Process primary image
        debug_log("Processing primary image");
        if (!isset($_FILES['primary_image']) || $_FILES['primary_image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Primary image upload failed: " . 
                (isset($_FILES['primary_image']) ? $_FILES['primary_image']['error'] : 'No file uploaded'));
        }

        $file_extension = strtolower(pathinfo($_FILES['primary_image']['name'], PATHINFO_EXTENSION));
        debug_log("Primary image extension: " . $file_extension);
        
        if (!in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            throw new Exception("Invalid file type for primary image: " . $file_extension);
        }

        $primary_filename = uniqid() . "_primary." . $file_extension;
        $primary_filepath = $dirs[0] . $primary_filename;
        debug_log("Saving primary image to: " . $primary_filepath);

        if (!processAndResizeImage($_FILES['primary_image']['tmp_name'], $primary_filepath)) {
            throw new Exception("Failed to process primary image");
        }
        debug_log("Primary image processed successfully");

        // Insert product
        debug_log("Inserting product into database");
        $sql = "INSERT INTO products (title, price, description, image_path) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error preparing product statement: " . $conn->error);
        }

        $title = $_POST['title'];
        $price = floatval($_POST['price']);
        $description = $_POST['description'];
        
        $stmt->bind_param("sdss", $title, $price, $description, $primary_filepath);
        debug_log("Executing product insert with params:", [
            'title' => $title,
            'price' => $price,
            'description' => $description,
            'image_path' => $primary_filepath
        ]);
        
        if (!$stmt->execute()) {
            throw new Exception("Error inserting product: " . $stmt->error);
        }
        
        $product_id = $conn->insert_id;
        debug_log("Product inserted successfully with ID: " . $product_id);
        $stmt->close();

        // Process additional images
        debug_log("=== Processing Additional Images ===");
        debug_log("Additional Images Data:", isset($_FILES['additional_images']) ? $_FILES['additional_images'] : 'No additional images');
        
        if (isset($_FILES['additional_images'])) {
            foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                debug_log("Processing additional image $key");
                if (!empty($tmp_name) && $_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_extension = strtolower(pathinfo($_FILES['additional_images']['name'][$key], PATHINFO_EXTENSION));
                    debug_log("Additional image $key extension: " . $file_extension);
                    
                    if (!in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                        throw new Exception("Invalid file type for additional image: " . $file_extension);
                    }

                    $additional_filename = uniqid() . "_additional." . $file_extension;
                    $additional_filepath = $dirs[2] . $additional_filename;
                    debug_log("Saving additional image to: " . $additional_filepath);

                    if (!processAndResizeImage($tmp_name, $additional_filepath)) {
                        throw new Exception("Failed to process additional image $key");
                    }

                    $sql = "INSERT INTO product_images (product_id, image_path) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        throw new Exception("Error preparing additional image statement: " . $conn->error);
                    }
                    $stmt->bind_param("is", $product_id, $additional_filepath);
                    debug_log("Executing additional image insert for image $key");
                    if (!$stmt->execute()) {
                        throw new Exception("Error inserting additional image: " . $stmt->error);
                    }
                    $stmt->close();
                    debug_log("Additional image $key processed and saved successfully");
                }
            }
        }

        // Process colors
        debug_log("=== Processing Colors ===");
        debug_log("Color Names:", isset($_POST['color_names']) ? $_POST['color_names'] : 'No color names');
        debug_log("Color Images:", isset($_FILES['color_images']) ? $_FILES['color_images'] : 'No color images');
        
        if (isset($_POST['color_names']) && isset($_FILES['color_images'])) {
            $color_names = $_POST['color_names'];
            $color_images = $_FILES['color_images'];
            
            foreach ($color_names as $key => $color_name) {
                debug_log("Processing color $key: $color_name");
                
                if (!empty($color_name) && 
                    isset($color_images['tmp_name'][$key]) && 
                    $color_images['error'][$key] === UPLOAD_ERR_OK) {
                    
                    $tmp_name = $color_images['tmp_name'][$key];
                    $file_extension = strtolower(pathinfo($color_images['name'][$key], PATHINFO_EXTENSION));
                    debug_log("Color image $key extension: " . $file_extension);
                    
                    if (!in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                        throw new Exception("Invalid file type for color image: " . $file_extension);
                    }

                    // Create color images directory if it doesn't exist
                    if (!file_exists($dirs[1])) {
                        mkdir($dirs[1], 0777, true);
                    }

                    $color_filename = uniqid() . "_color." . $file_extension;
                    $color_filepath = $dirs[1] . $color_filename;
                    debug_log("Saving color image to: " . $color_filepath);

                    if (!processAndResizeImage($tmp_name, $color_filepath)) {
                        throw new Exception("Failed to process color image for color: " . $color_name);
                    }

                    // Insert into colors table
                    $sql = "INSERT INTO colors (product_id, color_name, color_image_path) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        throw new Exception("Error preparing color statement: " . $conn->error);
                    }
                    
                    debug_log("Executing color insert for color: " . $color_name . " with path: " . $color_filepath);
                    $stmt->bind_param("iss", $product_id, $color_name, $color_filepath);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Error inserting color: " . $stmt->error . " SQL: " . $sql);
                    }
                    
                    $stmt->close();
                    debug_log("Color $key ($color_name) processed and saved successfully");
                } else {
                    debug_log("Skipping color $key due to missing data");
                }
            }
        }

        // If we got here, everything succeeded
        $conn->commit();
        debug_log("=== Product Upload Completed Successfully ===");
        
        $response['success'] = true;
        $response['message'] = 'Product added successfully';
        $response['product'] = [
            'id' => $product_id,
            'title' => $title,
            'price' => $price,
            'image_path' => $primary_filepath
        ];
        debug_log("Final response:", $response);

    } catch (Exception $e) {
        $conn->rollback();
        debug_log("ERROR: Transaction rolled back - " . $e->getMessage());
        throw $e;
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    $response['debug'][] = "Error trace: " . $e->getTraceAsString();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
    // Ensure we have a clean output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    // Send JSON response
    header('Content-Type: application/json');
    echo safe_json_encode($response);
    exit;
}