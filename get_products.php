<?php
header('Content-Type: application/json');

$productsFolder = 'images/products';
$products = [];

if (is_dir($productsFolder)) {
    $files = scandir($productsFolder);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && is_file($productsFolder . '/' . $file)) {
            $info = pathinfo($file);
            if (in_array(strtolower($info['extension']), ['jpg', 'jpeg', 'png', 'gif'])) {
                $products[] = [
                    'id' => uniqid(),
                    'title' => ucwords(str_replace('_', ' ', $info['filename'])),
                    'price' => rand(500, 3000), // Random price between 500 and 3000
                    'image' => $productsFolder . '/' . $file,
                    'description' => 'Beautiful ' . ucwords(str_replace('_', ' ', $info['filename'])) . ' product.'
                ];
            }
        }
    }
}

echo json_encode($products);
?>