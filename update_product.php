<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
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
            throw new Exception('Failed to update product details');
        }
        mysqli_stmt_close($stmt);

        // Handle main image upload if provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image_path = processAndSaveImage($_FILES['image'], 'products');
            if ($image_path) {
                // Get old image path first
                $old_image_query = "SELECT image_path FROM products WHERE id = ?";
                $old_image_stmt = mysqli_prepare($conn, $old_image_query);
                mysqli_stmt_bind_param($old_image_stmt, "i", $product_id);
                mysqli_stmt_execute($old_image_stmt);
                $old_result = mysqli_stmt_get_result($old_image_stmt);
                $old_image = mysqli_fetch_assoc($old_result);
                mysqli_stmt_close($old_image_stmt);

                // Update with new image
                $image_query = "UPDATE products SET image_path = ? WHERE id = ?";
                $image_stmt = mysqli_prepare($conn, $image_query);
                mysqli_stmt_bind_param($image_stmt, "si", $image_path, $product_id);
                if (!mysqli_stmt_execute($image_stmt)) {
                    throw new Exception('Failed to update product image');
                }
                mysqli_stmt_close($image_stmt);

                // Delete old image if it exists
                if ($old_image && $old_image['image_path']) {
                    $old_path = $old_image['image_path'];
                    if (file_exists($old_path)) {
                        unlink($old_path);
                    }
                }
            }
        }

        // Handle removed additional images
        if (isset($_POST['removed_additional_images']) && is_array($_POST['removed_additional_images'])) {
            foreach ($_POST['removed_additional_images'] as $image_path) {
                // Delete from database
                $delete_image_query = "DELETE FROM product_images WHERE product_id = ? AND image_path = ?";
                $delete_image_stmt = mysqli_prepare($conn, $delete_image_query);
                mysqli_stmt_bind_param($delete_image_stmt, "is", $product_id, $image_path);
                mysqli_stmt_execute($delete_image_stmt);
                mysqli_stmt_close($delete_image_stmt);

                // Delete file
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
        }

        // Handle new additional images
        if (isset($_FILES['new_additional_images'])) {
            $file_count = count($_FILES['new_additional_images']['name']);
            for ($i = 0; $i < $file_count; $i++) {
                if ($_FILES['new_additional_images']['error'][$i] == 0) {
                    $file = [
                        'name' => $_FILES['new_additional_images']['name'][$i],
                        'type' => $_FILES['new_additional_images']['type'][$i],
                        'tmp_name' => $_FILES['new_additional_images']['tmp_name'][$i],
                        'error' => $_FILES['new_additional_images']['error'][$i],
                        'size' => $_FILES['new_additional_images']['size'][$i]
                    ];
                    
                    $image_path = processAndSaveImage($file, 'additional');
                    if ($image_path) {
                        $additional_image_query = "INSERT INTO product_images (product_id, image_path) VALUES (?, ?)";
                        $additional_image_stmt = mysqli_prepare($conn, $additional_image_query);
                        mysqli_stmt_bind_param($additional_image_stmt, "is", $product_id, $image_path);
                        if (!mysqli_stmt_execute($additional_image_stmt)) {
                            throw new Exception('Failed to save additional image');
                        }
                        mysqli_stmt_close($additional_image_stmt);
                    }
                }
            }
        }

        // Handle removed colors
        if (isset($_POST['removed_colors']) && is_array($_POST['removed_colors'])) {
            foreach ($_POST['removed_colors'] as $color_name) {
                // Get color image path first
                $color_image_query = "SELECT color_image_path FROM colors WHERE product_id = ? AND color_name = ?";
                $color_image_stmt = mysqli_prepare($conn, $color_image_query);
                mysqli_stmt_bind_param($color_image_stmt, "is", $product_id, $color_name);
                mysqli_stmt_execute($color_image_stmt);
                $color_result = mysqli_stmt_get_result($color_image_stmt);
                $color_data = mysqli_fetch_assoc($color_result);
                mysqli_stmt_close($color_image_stmt);

                // Delete from database
                $delete_color_query = "DELETE FROM colors WHERE product_id = ? AND color_name = ?";
                $delete_color_stmt = mysqli_prepare($conn, $delete_color_query);
                mysqli_stmt_bind_param($delete_color_stmt, "is", $product_id, $color_name);
                if (!mysqli_stmt_execute($delete_color_stmt)) {
                    throw new Exception('Failed to delete color');
                }
                mysqli_stmt_close($delete_color_stmt);

                // Delete color image file
                if ($color_data && $color_data['color_image_path']) {
                    $color_image_path = $color_data['color_image_path'];
                    if (file_exists($color_image_path)) {
                        unlink($color_image_path);
                    }
                }
            }
        }

        // Handle color updates and additions
        if (isset($_POST['color_names']) && is_array($_POST['color_names'])) {
            foreach ($_POST['color_names'] as $index => $color_name) {
                if (empty($color_name)) continue;
                
                $color_name = mysqli_real_escape_string($conn, $color_name);
                
                // Check if this is an existing color
                $check_color_query = "SELECT id FROM colors WHERE product_id = ? AND color_name = ?";
                $check_color_stmt = mysqli_prepare($conn, $check_color_query);
                mysqli_stmt_bind_param($check_color_stmt, "is", $product_id, $color_name);
                mysqli_stmt_execute($check_color_stmt);
                $check_result = mysqli_stmt_get_result($check_color_stmt);
                $existing_color = mysqli_fetch_assoc($check_result);
                mysqli_stmt_close($check_color_stmt);

                if ($existing_color) {
                    // Update existing color if new image provided
                    if (isset($_FILES['color_images']['name'][$index]) && $_FILES['color_images']['error'][$index] == 0) {
                        $file = [
                            'name' => $_FILES['color_images']['name'][$index],
                            'type' => $_FILES['color_images']['type'][$index],
                            'tmp_name' => $_FILES['color_images']['tmp_name'][$index],
                            'error' => $_FILES['color_images']['error'][$index],
                            'size' => $_FILES['color_images']['size'][$index]
                        ];
                        
                        $color_image_path = processAndSaveImage($file, 'colors');
                        if ($color_image_path) {
                            // Get old image path first
                            $old_color_image_query = "SELECT color_image_path FROM colors WHERE id = ?";
                            $old_color_image_stmt = mysqli_prepare($conn, $old_color_image_query);
                            mysqli_stmt_bind_param($old_color_image_stmt, "i", $existing_color['id']);
                            mysqli_stmt_execute($old_color_image_stmt);
                            $old_color_result = mysqli_stmt_get_result($old_color_image_stmt);
                            $old_color_image = mysqli_fetch_assoc($old_color_result);
                            mysqli_stmt_close($old_color_image_stmt);

                            // Update with new image
                            $update_color_query = "UPDATE colors SET color_image_path = ? WHERE id = ?";
                            $update_color_stmt = mysqli_prepare($conn, $update_color_query);
                            mysqli_stmt_bind_param($update_color_stmt, "si", $color_image_path, $existing_color['id']);
                            if (!mysqli_stmt_execute($update_color_stmt)) {
                                throw new Exception('Failed to update color image');
                            }
                            mysqli_stmt_close($update_color_stmt);

                            // Delete old image if it exists
                            if ($old_color_image && $old_color_image['color_image_path']) {
                                $old_path = $old_color_image['color_image_path'];
                                if (file_exists($old_path)) {
                                    unlink($old_path);
                                }
                            }
                        }
                    }
                } else {
                    // Add new color
                    if (isset($_FILES['color_images']['name'][$index]) && $_FILES['color_images']['error'][$index] == 0) {
                        $file = [
                            'name' => $_FILES['color_images']['name'][$index],
                            'type' => $_FILES['color_images']['type'][$index],
                            'tmp_name' => $_FILES['color_images']['tmp_name'][$index],
                            'error' => $_FILES['color_images']['error'][$index],
                            'size' => $_FILES['color_images']['size'][$index]
                        ];
                        
                        $color_image_path = processAndSaveImage($file, 'colors');
                        if ($color_image_path) {
                            $insert_color_query = "INSERT INTO colors (product_id, color_name, color_image_path) VALUES (?, ?, ?)";
                            $insert_color_stmt = mysqli_prepare($conn, $insert_color_query);
                            mysqli_stmt_bind_param($insert_color_stmt, "iss", $product_id, $color_name, $color_image_path);
                            if (!mysqli_stmt_execute($insert_color_stmt)) {
                                throw new Exception('Failed to add new color');
                            }
                            mysqli_stmt_close($insert_color_stmt);
                        }
                    }
                }
            }
        }

        mysqli_commit($conn);
        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

mysqli_close($conn);

function processAndSaveImage($file, $type = 'products') {
    $target_dir = "images/$type/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($file_extension, $allowed_types)) {
        return false;
    }

    // Generate unique filename
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $target_file;
    }
    
    return false;
}
