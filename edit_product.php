<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
header('Content-Type: application/json; charset=utf-8');
require_once 'db_config.php';

$response = ['status' => 'error', 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    mysqli_begin_transaction($conn);
    
    try {
        $id = intval($_POST['id']);
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $price = floatval($_POST['price']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);

        $sql = "UPDATE products SET title = ?, price = ?, description = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sdsi", $title, $price, $description, $id);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error updating product: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);

        // Handle image update if a new image is uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = "images/products/";
            $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $file_name = uniqid() . "_primary." . $file_extension;
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                // Get old image path to delete later
                $old_image_sql = "SELECT image_path FROM products WHERE id = ?";
                $stmt = mysqli_prepare($conn, $old_image_sql);
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $old_image);
                if (mysqli_stmt_fetch($stmt)) {
                    if (!empty($old_image) && file_exists($old_image)) {
                        unlink($old_image);
                    }
                }
                mysqli_stmt_close($stmt);

                // Update new image path
                $image_sql = "UPDATE products SET image_path = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $image_sql);
                mysqli_stmt_bind_param($stmt, "si", $target_file, $id);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error updating product image: " . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);
            }
        }

        // Handle color updates
        if (isset($_POST['colors'])) {
            foreach ($_POST['colors'] as $colorId => $colorData) {
                $colorName = mysqli_real_escape_string($conn, $colorData['name']);
                
                if (isset($_FILES['colors'][$colorId]['image']) && $_FILES['colors'][$colorId]['image']['error'] == UPLOAD_ERR_OK) {
                    // Handle color image upload
                    $colorImage = $_FILES['colors'][$colorId]['image'];
                    $color_upload_dir = "images/colors/";
                    $color_file_name = uniqid() . "_color." . pathinfo($colorImage['name'], PATHINFO_EXTENSION);
                    $color_target_file = $color_upload_dir . $color_file_name;
                    
                    if (move_uploaded_file($colorImage['tmp_name'], $color_target_file)) {
                        // Get old color image to delete
                        $old_color_sql = "SELECT color_image_path FROM colors WHERE id = ?";
                        $stmt = mysqli_prepare($conn, $old_color_sql);
                        mysqli_stmt_bind_param($stmt, "i", $colorId);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_bind_result($stmt, $old_color_image);
                        if (mysqli_stmt_fetch($stmt) && !empty($old_color_image) && file_exists($old_color_image)) {
                            unlink($old_color_image);
                        }
                        mysqli_stmt_close($stmt);
                        
                        // Update color with new image
                        $color_sql = "UPDATE colors SET color_name = ?, color_image_path = ? WHERE id = ?";
                        $stmt = mysqli_prepare($conn, $color_sql);
                        mysqli_stmt_bind_param($stmt, "ssi", $colorName, $color_target_file, $colorId);
                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception("Error updating color with image: " . mysqli_error($conn));
                        }
                        mysqli_stmt_close($stmt);
                    }
                } else {
                    // Update color name only
                    $color_sql = "UPDATE colors SET color_name = ? WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $color_sql);
                    mysqli_stmt_bind_param($stmt, "si", $colorName, $colorId);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Error updating color name: " . mysqli_error($conn));
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }

        // Handle additional images
        if (isset($_FILES['additional_images'])) {
            foreach ($_FILES['additional_images']['error'] as $key => $error) {
                if ($error == UPLOAD_ERR_OK) {
                    $upload_dir = "images/additional/";
                    $file_name = uniqid() . "_additional." . pathinfo($_FILES['additional_images']['name'][$key], PATHINFO_EXTENSION);
                    $target_file = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$key], $target_file)) {
                        $sql = "INSERT INTO product_images (product_id, image_path) VALUES (?, ?)";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "is", $id, $target_file);
                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception("Error adding additional image: " . mysqli_error($conn));
                        }
                        mysqli_stmt_close($stmt);
                    }
                }
            }
        }

        mysqli_commit($conn);
        $response['status'] = 'success';
        $response['message'] = 'Product updated successfully';
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $response['message'] = $e->getMessage();
        error_log("Error in edit_product.php: " . $e->getMessage());
    }
} else {
    $response['message'] = "Invalid request method.";
}

echo json_encode($response);
mysqli_close($conn);