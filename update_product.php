<?php
require_once 'db_config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
error_log("Starting update_product.php");

header('Content-Type: application/json');

// Function to ensure proper image path format and process uploaded image
function processAndSaveImage($file, $type = 'products', $suffix = 'primary') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error uploading file: ' . $file['error']);
    }

    $upload_dir = "images/{$type}/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($file_extension, $allowed_types)) {
        throw new Exception('Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.');
    }

    $new_filename = uniqid() . "_{$suffix}." . $file_extension;
    $target_path = $upload_dir . $new_filename;

    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        throw new Exception('Failed to move uploaded file.');
    }

    return $target_path;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        mysqli_begin_transaction($conn);
        
        $product_id = intval($_POST['product_id']);
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $price = floatval($_POST['price']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);

        // Update product details
        $query = "UPDATE products SET title = ?, price = ?, description = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sdsi", $title, $price, $description, $product_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to update product details: ' . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);

        // Handle main image upload if provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            // Get old image path first
            $old_image_query = "SELECT image_path FROM products WHERE id = ?";
            $stmt = mysqli_prepare($conn, $old_image_query);
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $old_image_path);
            if (mysqli_stmt_fetch($stmt)) {
                if (!empty($old_image_path) && file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
            }
            mysqli_stmt_close($stmt);

            // Process and save new image
            $image_path = processAndSaveImage($_FILES['image'], 'products', 'primary');

            // Update with new image path
            $image_query = "UPDATE products SET image_path = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $image_query);
            mysqli_stmt_bind_param($stmt, "si", $image_path, $product_id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to update product image: ' . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt);
        }

        // Handle removed colors
        if (isset($_POST['removed_colors']) && is_array($_POST['removed_colors'])) {
            foreach ($_POST['removed_colors'] as $color_id) {
                // Get color image path first
                $color_image_query = "SELECT color_image_path FROM colors WHERE id = ? AND product_id = ?";
                $stmt = mysqli_prepare($conn, $color_image_query);
                mysqli_stmt_bind_param($stmt, "ii", $color_id, $product_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $color_image_path);
                if (mysqli_stmt_fetch($stmt)) {
                    if (!empty($color_image_path) && file_exists($color_image_path)) {
                        unlink($color_image_path);
                    }
                }
                mysqli_stmt_close($stmt);

                // Delete the color record
                $delete_color_query = "DELETE FROM colors WHERE id = ? AND product_id = ?";
                $stmt = mysqli_prepare($conn, $delete_color_query);
                mysqli_stmt_bind_param($stmt, "ii", $color_id, $product_id);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Failed to delete color: ' . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);
            }
        }

        // Handle color updates and additions
        if (isset($_POST['colors']) && is_array($_POST['colors'])) {
            foreach ($_POST['colors'] as $color_id => $color_data) {
                $color_name = mysqli_real_escape_string($conn, $color_data['name']);
                
                if ($color_id > 0) {
                    // Update existing color
                    if (isset($_FILES['colors']['name'][$color_id]) && $_FILES['colors']['error'][$color_id] == 0) {
                        // Get old color image to delete
                        $old_color_query = "SELECT color_image_path FROM colors WHERE id = ? AND product_id = ?";
                        $stmt = mysqli_prepare($conn, $old_color_query);
                        mysqli_stmt_bind_param($stmt, "ii", $color_id, $product_id);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_bind_result($stmt, $old_color_path);
                        if (mysqli_stmt_fetch($stmt) && !empty($old_color_path) && file_exists($old_color_path)) {
                            unlink($old_color_path);
                        }
                        mysqli_stmt_close($stmt);

                        // Process and save new color image
                        $color_file = [
                            'name' => $_FILES['colors']['name'][$color_id],
                            'type' => $_FILES['colors']['type'][$color_id],
                            'tmp_name' => $_FILES['colors']['tmp_name'][$color_id],
                            'error' => $_FILES['colors']['error'][$color_id],
                            'size' => $_FILES['colors']['size'][$color_id]
                        ];
                        $color_image_path = processAndSaveImage($color_file, 'colors', 'color');

                        // Update color with new image
                        $update_color_query = "UPDATE colors SET color_name = ?, color_image_path = ? WHERE id = ? AND product_id = ?";
                        $stmt = mysqli_prepare($conn, $update_color_query);
                        mysqli_stmt_bind_param($stmt, "ssii", $color_name, $color_image_path, $color_id, $product_id);
                    } else {
                        // Update color name only
                        $update_color_query = "UPDATE colors SET color_name = ? WHERE id = ? AND product_id = ?";
                        $stmt = mysqli_prepare($conn, $update_color_query);
                        mysqli_stmt_bind_param($stmt, "sii", $color_name, $color_id, $product_id);
                    }
                } else {
                    // Add new color
                    if (isset($_FILES['colors']['name'][$color_id]) && $_FILES['colors']['error'][$color_id] == 0) {
                        $color_file = [
                            'name' => $_FILES['colors']['name'][$color_id],
                            'type' => $_FILES['colors']['type'][$color_id],
                            'tmp_name' => $_FILES['colors']['tmp_name'][$color_id],
                            'error' => $_FILES['colors']['error'][$color_id],
                            'size' => $_FILES['colors']['size'][$color_id]
                        ];
                        $color_image_path = processAndSaveImage($color_file, 'colors', 'color');

                        $insert_color_query = "INSERT INTO colors (product_id, color_name, color_image_path) VALUES (?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $insert_color_query);
                        mysqli_stmt_bind_param($stmt, "iss", $product_id, $color_name, $color_image_path);
                    }
                }

                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Failed to update/add color: ' . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);
            }
        }

        // Handle additional images
        if (isset($_FILES['additional_images'])) {
            foreach ($_FILES['additional_images']['error'] as $key => $error) {
                if ($error == UPLOAD_ERR_OK) {
                    $additional_file = [
                        'name' => $_FILES['additional_images']['name'][$key],
                        'type' => $_FILES['additional_images']['type'][$key],
                        'tmp_name' => $_FILES['additional_images']['tmp_name'][$key],
                        'error' => $_FILES['additional_images']['error'][$key],
                        'size' => $_FILES['additional_images']['size'][$key]
                    ];
                    $additional_image_path = processAndSaveImage($additional_file, 'additional', 'additional');

                    $insert_image_query = "INSERT INTO product_images (product_id, image_path) VALUES (?, ?)";
                    $stmt = mysqli_prepare($conn, $insert_image_query);
                    mysqli_stmt_bind_param($stmt, "is", $product_id, $additional_image_path);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception('Failed to add additional image: ' . mysqli_error($conn));
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }

        // Handle removed additional images
        if (isset($_POST['removed_additional_images']) && is_array($_POST['removed_additional_images'])) {
            foreach ($_POST['removed_additional_images'] as $image_id) {
                // Get image path first
                $image_query = "SELECT image_path FROM product_images WHERE id = ? AND product_id = ?";
                $stmt = mysqli_prepare($conn, $image_query);
                mysqli_stmt_bind_param($stmt, "ii", $image_id, $product_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $image_path);
                if (mysqli_stmt_fetch($stmt)) {
                    if (!empty($image_path) && file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
                mysqli_stmt_close($stmt);

                // Delete the image record
                $delete_image_query = "DELETE FROM product_images WHERE id = ? AND product_id = ?";
                $stmt = mysqli_prepare($conn, $delete_image_query);
                mysqli_stmt_bind_param($stmt, "ii", $image_id, $product_id);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Failed to delete additional image: ' . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);
            }
        }

        mysqli_commit($conn);
        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Error in update_product.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

mysqli_close($conn);
