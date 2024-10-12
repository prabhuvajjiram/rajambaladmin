<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

require_once 'db_config.php';

function utf8ize($mixed) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize($value);
        }
    } elseif (is_string($mixed)) {
        return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
    }
    return $mixed;
}

try {
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    $sql = "SELECT * FROM products ORDER BY created_at DESC";
    $result = $conn->query($sql);

    if ($result === false) {
        throw new Exception("Database query failed: " . $conn->error);
    }

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'id' => $row['id'],
            'title' => utf8ize($row['title']),
            'price' => floatval($row['price']),
            'image' => utf8ize($row['image_path']),
            'description' => utf8ize($row['description'])
        ];
    }

$output = ob_get_clean();
$json_response = json_encode([
    "status" => "success",
    "products" => $products,
    "debug_output" => $output
]);

if ($json_response === false) {
    $json_response = json_encode([
        "status" => "error",
        "message" => "JSON encoding failed: " . json_last_error_msg(),
        "debug_output" => $output
    ]);
}

echo $json_response;


} catch (Exception $e) {
    error_log("Error in get_products.php: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => "An error occurred while fetching products. Please try again later.",
        "debug" => $e->getMessage()
    ]);
}

$conn->close();
?>
