<?php
session_start();
require_once 'db_config.php';

// Check if the user is logged in, if not redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Fetch existing products
$sql = "SELECT * FROM products ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rajambal Cottons - Admin Panel</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        .admin-container {
            max-width: 800px;
            margin: 100px auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .admin-container h2 {
            margin-bottom: 20px;
        }
        .admin-container form {
            display: flex;
            flex-direction: column;
        }
        .admin-container input,
        .admin-container textarea {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .admin-container button {
            align-self: flex-start;
        }
        #uploadMessage {
            margin-top: 15px;
            font-weight: bold;
        }
        .product-list {
            margin-top: 30px;
        }
        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .product-item:last-child {
            border-bottom: none;
        }
        .delete-btn {
            background-color: #ff4d4d;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        .delete-btn:hover {
            background-color: #ff3333;
        }
        .admin-buttons {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .admin-section {
            display: none;
        }
        .admin-section.active {
            display: block;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <img src="images/shirt-icon.svg" alt="Rajambal Cottons Logo" class="logo-icon">
                <span class="logo-text">Rajambal Cottons - Admin</span>
            </div>
            <button id="logoutBtn" class="btn btn-secondary">Logout</button>
        </div>
    </header>

    <main>
        <section class="admin-container">
            <div class="admin-buttons">
                <button id="addProductBtn" class="btn btn-primary">Add Product</button>
                <button id="listProductsBtn" class="btn btn-primary">List Products</button>
            </div>

            <div id="addProductSection" class="admin-section">
                <h2>Add New Product</h2>
                <form id="productForm" enctype="multipart/form-data">
                    <input type="text" name="title" placeholder="Product Title" required>
                    <input type="number" name="price" placeholder="Price" step="0.01" required>
                    <textarea name="description" placeholder="Product Description" required></textarea>
                    <input type="file" name="image" accept="image/*" required>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                </form>
                <div id="uploadMessage"></div>
            </div>

            <div id="listProductsSection" class="admin-section">
                <h2>Product List</h2>
                <div class="product-list">
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<div class='product-item' data-id='" . $row['id'] . "'>";
                            echo "<span>" . htmlspecialchars($row['title']) . " - ₹" . number_format($row['price'], 2) . htmlspecialchars($row['image_path']) . "</span>";
                            echo "<button class='delete-btn' data-id='" . $row['id'] . "'>Delete</button>";
                            echo "</div>";
                        }
                    } else {
                        echo "<p>No products found.</p>";
                    }
                    ?>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2024 Rajambal Cottons. All rights reserved. | Designed by Prabu Vajjiram</p>
            </div>
        </div>
    </footer>

    <script src="js/admin.js"></script>
</body>
</html>
<?php
$conn->close();
?>