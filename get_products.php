<?php
header('Content-Type: application/json');
require_once 'db_config.php';

$sql = "SELECT * FROM products ORDER BY created_at DESC";
$result = $conn->query($sql);

$products = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'price' => floatval($row['price']),
            'image' => $row['image_path'],
            'description' => $row['description']
        ];
    }
}

echo json_encode($products);

$conn->close();
?>