<?php
header('Content-Type: application/json');
require_once 'db_config.php';

$response = ['status' => 'error', 'message' => '', 'data' => null];

try {
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $sql = "SELECT p.*, GROUP_CONCAT(c.color SEPARATOR ',') AS colors
        FROM products p
        LEFT JOIN colors c ON p.id = c.product_id
        GROUP BY p.id";
        $result = $conn->query($sql);

        if ($result === false) {
            throw new Exception("Database query failed: " . $conn->error);
        }

        $products = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $products[] = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'price' => floatval($row['price']),
                    'description' => $row['description'],
                    'image' => $row['image_path'],
                    'colors' => $row['colors'] ? explode(',', $row['colors']) : []
                ];
            }
        }

        $response['status'] = 'success';
        $response['data'] = $products;
    } else {
        throw new Exception("Invalid request method.");
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}

echo json_encode($response);
?>