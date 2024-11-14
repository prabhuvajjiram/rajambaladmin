<?php
header('Content-Type: application/json');
require_once 'db_config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Function to ensure proper image path format
function formatImagePath($path, $type = 'products') {
    if (empty($path)) return null;
    
    // If path already has correct format, return as is
    if (strpos($path, 'images/' . $type . '/') === 0) {
        return $path;
    }
    
    // Remove any leading slashes
    $path = ltrim($path, '/');
    
    // Ensure the path starts with the correct directory
    return "images/$type/" . basename($path);
}

// Function to safely delete a file
function safeDeleteFile($filepath) {
    if (empty($filepath)) return;
    
    $fullPath = __DIR__ . '/' . $filepath;
    if (file_exists($fullPath) && is_file($fullPath)) {
        unlink($fullPath);
    }
}

// Initialize response
$response = ['success' => false, 'message' => ''];

try {
    mysqli_begin_transaction($conn);

    // Basic validation
    if (!isset($_POST['product_id'])) {
        throw new Exception('Product ID is required');
    }

    $product_id = intval($_POST['product_id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = floatval($_POST['price']);

    // Handle primary image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'images/products/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '_primary.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            // Get and delete old image
            $old_image_query = "SELECT image_path FROM products WHERE id = ?";
            $stmt = mysqli_prepare($conn, $old_image_query);
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $old_image);
            if (mysqli_stmt_fetch($stmt)) {
                safeDeleteFile($old_image);
            }
            mysqli_stmt_close($stmt);

            $image_path = $upload_path;
        }
    }

    // Update product basic info
    $update_query = "UPDATE products SET title = ?, description = ?, price = ?";
    $params = [$title, $description, $price];
    $types = "ssd";

    if ($image_path) {
        $update_query .= ", image_path = ?";
        $params[] = $image_path;
        $types .= "s";
    }

    $update_query .= " WHERE id = ?";
    $params[] = $product_id;
    $types .= "i";

    $stmt = mysqli_prepare($conn, $update_query);
    if (!$stmt) {
        throw new Exception('Failed to prepare product update statement: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, $types, ...$params);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to update product: ' . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);

    // Handle removed additional images
    if (isset($_POST['removed_additional_images']) && is_array($_POST['removed_additional_images'])) {
        foreach ($_POST['removed_additional_images'] as $image_id) {
            // Get the image path before deleting
            $get_path_query = "SELECT image_path FROM product_images WHERE id = ? AND product_id = ?";
            $stmt = mysqli_prepare($conn, $get_path_query);
            mysqli_stmt_bind_param($stmt, "ii", $image_id, $product_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $image_path);
            if (mysqli_stmt_fetch($stmt)) {
                safeDeleteFile($image_path);
            }
            mysqli_stmt_close($stmt);

            // Delete from database
            $delete_query = "DELETE FROM product_images WHERE id = ? AND product_id = ?";
            $stmt = mysqli_prepare($conn, $delete_query);
            mysqli_stmt_bind_param($stmt, "ii", $image_id, $product_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    // Handle new additional images
    if (isset($_FILES['additional_images'])) {
        $upload_dir = 'images/additional/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK) {
                $file_extension = pathinfo($_FILES['additional_images']['name'][$key], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '_additional.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($tmp_name, $upload_path)) {
                    $insert_query = "INSERT INTO product_images (product_id, image_path) VALUES (?, ?)";
                    $stmt = mysqli_prepare($conn, $insert_query);
                    mysqli_stmt_bind_param($stmt, "is", $product_id, $upload_path);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }

    // Handle removed colors
    if (isset($_POST['removed_colors']) && is_array($_POST['removed_colors'])) {
        foreach ($_POST['removed_colors'] as $color_id) {
            // Get the image path before deleting
            $get_path_query = "SELECT color_image_path FROM colors WHERE id = ? AND product_id = ?";
            $stmt = mysqli_prepare($conn, $get_path_query);
            mysqli_stmt_bind_param($stmt, "ii", $color_id, $product_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $color_image_path);
            if (mysqli_stmt_fetch($stmt)) {
                safeDeleteFile($color_image_path);
            }
            mysqli_stmt_close($stmt);

            // Delete from database
            $delete_query = "DELETE FROM colors WHERE id = ? AND product_id = ?";
            $stmt = mysqli_prepare($conn, $delete_query);
            mysqli_stmt_bind_param($stmt, "ii", $color_id, $product_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    // Handle existing and new colors
    if (isset($_POST['colors']) && is_array($_POST['colors'])) {
        $upload_dir = 'images/colors/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($_POST['colors'] as $color_key => $color_data) {
            $color_name = mysqli_real_escape_string($conn, $color_data['name']);
            $is_new_color = strpos($color_key, 'new_') === 0;

            if ($is_new_color) {
                // Handle new color
                if (isset($_FILES['colors']['name'][$color_key]['image']) && 
                    $_FILES['colors']['error'][$color_key]['image'] === UPLOAD_ERR_OK) {
                    
                    $file_extension = pathinfo($_FILES['colors']['name'][$color_key]['image'], PATHINFO_EXTENSION);
                    $new_filename = uniqid() . '_color.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;

                    if (move_uploaded_file($_FILES['colors']['tmp_name'][$color_key]['image'], $upload_path)) {
                        $insert_query = "INSERT INTO colors (product_id, color_name, color_image_path) VALUES (?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $insert_query);
                        mysqli_stmt_bind_param($stmt, "iss", $product_id, $color_name, $upload_path);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }
                }
            } else {
                // Handle existing color update
                $color_id = intval($color_data['id']);
                $update_query = "UPDATE colors SET color_name = ?";
                $params = [$color_name];
                $types = "s";

                // Check if new image is uploaded for existing color
                if (isset($_FILES['colors']['name'][$color_key]['image']) && 
                    $_FILES['colors']['error'][$color_key]['image'] === UPLOAD_ERR_OK) {
                    
                    // Get and delete old image
                    $old_image_query = "SELECT color_image_path FROM colors WHERE id = ? AND product_id = ?";
                    $stmt = mysqli_prepare($conn, $old_image_query);
                    mysqli_stmt_bind_param($stmt, "ii", $color_id, $product_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_bind_result($stmt, $old_image);
                    if (mysqli_stmt_fetch($stmt)) {
                        safeDeleteFile($old_image);
                    }
                    mysqli_stmt_close($stmt);

                    $file_extension = pathinfo($_FILES['colors']['name'][$color_key]['image'], PATHINFO_EXTENSION);
                    $new_filename = uniqid() . '_color.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;

                    if (move_uploaded_file($_FILES['colors']['tmp_name'][$color_key]['image'], $upload_path)) {
                        $update_query .= ", color_image_path = ?";
                        $params[] = $upload_path;
                        $types .= "s";
                    }
                }

                $update_query .= " WHERE id = ? AND product_id = ?";
                $params[] = $color_id;
                $params[] = $product_id;
                $types .= "ii";

                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, $types, ...$params);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
    }

    mysqli_commit($conn);
    $response['success'] = true;
    $response['message'] = 'Product updated successfully';

} catch (Exception $e) {
    mysqli_rollback($conn);
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('Error in update_product.php: ' . $e->getMessage());
} finally {
    echo json_encode($response);
    mysqli_close($conn);
}
