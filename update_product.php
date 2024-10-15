<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $price = floatval($_POST['price']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    // Update product details
    $query = "UPDATE products SET title = ?, price = ?, description = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sdsi", $title, $price, $description, $product_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Handle image upload if a new image is provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            // Process and save the new image
            $image_path = processAndSaveImage($_FILES['image']);
            if ($image_path) {
                $image_query = "UPDATE products SET image_path = ? WHERE id = ?";
                $image_stmt = mysqli_prepare($conn, $image_query);
                mysqli_stmt_bind_param($image_stmt, "si", $image_path, $product_id);
                mysqli_stmt_execute($image_stmt);
                mysqli_stmt_close($image_stmt);
            }
        }

        // Handle color updates
        if (isset($_POST['colorIds']) && is_array($_POST['colorIds'])) {
            foreach ($_POST['colorIds'] as $index => $colorId) {
                $colorName = mysqli_real_escape_string($conn, $_POST['colorNames'][$index]);
                if ($colorId === 'new') {
                    // Add new color
                    if (isset($_FILES['colorImages']['name'][$index]) && $_FILES['colorImages']['error'][$index] == 0) {
                        $colorImagePath = processAndSaveImage($_FILES['colorImages']['tmp_name'][$index]);
                        if ($colorImagePath) {
                            $color_query = "INSERT INTO colors (product_id, color_name, color_image_path) VALUES (?, ?, ?)";
                            $color_stmt = mysqli_prepare($conn, $color_query);
                            mysqli_stmt_bind_param($color_stmt, "iss", $product_id, $colorName, $colorImagePath);
                            mysqli_stmt_execute($color_stmt);
                            mysqli_stmt_close($color_stmt);
                        }
                    }
                } else {
                    // Update existing color
                    $colorId = intval($colorId);
                    $color_query = "UPDATE colors SET color_name = ? WHERE id = ? AND product_id = ?";
                    $color_stmt = mysqli_prepare($conn, $color_query);
                    mysqli_stmt_bind_param($color_stmt, "sii", $colorName, $colorId, $product_id);
                    mysqli_stmt_execute($color_stmt);
                    mysqli_stmt_close($color_stmt);

                    // Update color image if a new one is provided
                    if (isset($_FILES['colorImages']['name'][$index]) && $_FILES['colorImages']['error'][$index] == 0) {
                        $colorImagePath = processAndSaveImage($_FILES['colorImages']['tmp_name'][$index]);
                        if ($colorImagePath) {
                            $color_image_query = "UPDATE colors SET color_image_path = ? WHERE id = ? AND product_id = ?";
                            $color_image_stmt = mysqli_prepare($conn, $color_image_query);
                            mysqli_stmt_bind_param($color_image_stmt, "sii", $colorImagePath, $colorId, $product_id);
                            mysqli_stmt_execute($color_image_stmt);
                            mysqli_stmt_close($color_image_stmt);
                        }
                    }
                }
            }
        }

        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update product']);
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

mysqli_close($conn);

function processAndSaveImage($file) {
    // Implement image processing and saving logic here
    // Return the path of the saved image
}
?>
