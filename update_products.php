<?php
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_data_file = "products.json";
    $json_data = file_get_contents('php://input');
    $products = json_decode($json_data, true);

    if ($products !== null) {
        // Save updated products data
        if (file_put_contents($product_data_file, json_encode($products))) {
            echo json_encode(["status" => "success", "message" => "Products list updated successfully."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update products list."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid JSON data received."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
?>