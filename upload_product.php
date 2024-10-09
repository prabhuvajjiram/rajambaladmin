<?php
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $upload_dir = "images/products/";
    $product_data_file = "products.json";

    // Generate a unique ID for the product
    $product_id = uniqid();

    // Handle file upload
    $file_name = $product_id . "_" . basename($_FILES["image"]["name"]);
    $target_file = $upload_dir . $file_name;
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    // Check if image file is an actual image or fake image
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if($check !== false) {
        $uploadOk = 1;
    } else {
        echo json_encode(["status" => "error", "message" => "File is not an image."]);
        exit;
    }

    // Check file size
    if ($_FILES["image"]["size"] > 5000000) {
        echo json_encode(["status" => "error", "message" => "Sorry, your file is too large."]);
        exit;
    }

    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
    && $imageFileType != "gif" ) {
        echo json_encode(["status" => "error", "message" => "Sorry, only JPG, JPEG, PNG & GIF files are allowed."]);
        exit;
    }

    // If everything is ok, try to upload file
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        // File uploaded successfully, now save product data
        $product = [
            "id" => $product_id,
            "title" => $_POST["title"],
            "price" => floatval($_POST["price"]),
            "description" => $_POST["description"],
            "image" => $upload_dir . $file_name
        ];

        // Read existing products
        $products = [];
        if (file_exists($product_data_file)) {
            $products = json_decode(file_get_contents($product_data_file), true);
        }

        // Add new product
        $products[] = $product;

        // Save updated products data
        file_put_contents($product_data_file, json_encode($products));

        echo json_encode(["status" => "success", "message" => "The product has been uploaded successfully.", "product" => $product]);
    } else {
        echo json_encode(["status" => "error", "message" => "Sorry, there was an error uploading your file."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
?>