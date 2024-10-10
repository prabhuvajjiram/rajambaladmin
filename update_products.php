/*<?php
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
?>*/

<?php
header('Content-Type: application/json');
require_once 'db_config.php';

$sql = "SELECT * FROM products";
$result = $conn->query($sql);

$products = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'price' => floatval($row['price']),
            'description' => $row['description'],
            'image' => $row['image_path']
        ];
    }
}

echo json_encode($products);

$conn->close();
?>