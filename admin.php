<?php
session_start();
require_once "db_config.php";

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Rajambal Cottons</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 80px auto 0;
            padding: 20px;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .admin-nav {
            display: flex;
            gap: 10px;
        }
        .admin-nav a, .btn {
            padding: 10px 15px;
            background-color: #BE3A8E;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .admin-nav a:hover, .btn:hover {
            background-color: #9C2D73;
        }
        .product-list {
            margin-top: 20px;
        }
        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .product-actions {
            display: flex;
            gap: 10px;
        }
        #addProductForm, #editProductForm {
            max-width: 500px;
            margin: 0 auto;
        }
        #addProductForm input,
        #addProductForm textarea,
        #editProductForm input,
        #editProductForm textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <img src="images/rc.svg" alt="Rajambal Cottons Logo" class="logo-icon">
                <span class="logo-text">Rajambal Cottons - Admin Panel</span>
            </div>
            <nav class="admin-nav">
                <a href="#" id="listProductsBtn">List Products</a>
                <a href="#" id="addProductBtn">Add Product</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <main class="admin-container">
        <h1>Welcome to the Admin Panel</h1>
        
        <div id="productList" class="product-list"></div>

        <div id="addProductForm" style="display: none;">
            <h2>Add New Product</h2>
            <form id="newProductForm" enctype="multipart/form-data">
                <input type="text" name="title" placeholder="Product Title" required>
                <input type="number" name="price" placeholder="Price" step="0.01" required>
                <textarea name="description" placeholder="Product Description" required></textarea>
                <input type="file" name="image" accept="image/*" required>
                <button type="submit" class="btn">Add Product</button>
            </form>
        </div>

        <div id="editProductModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Edit Product</h2>
                <form id="editProductForm" enctype="multipart/form-data">
                    <input type="hidden" id="editProductId" name="id">
                    <input type="text" id="editTitle" name="title" placeholder="Product Title" required>
                    <input type="number" id="editPrice" name="price" placeholder="Price" step="0.01" required>
                    <textarea id="editDescription" name="description" placeholder="Product Description" required></textarea>
                    <img id="currentImage" src="" alt="Current Product Image" style="max-width: 200px;">
                    <input type="file" id="editImage" name="image" accept="image/*">
                    <button type="submit" class="btn">Update Product</button>
                </form>
            </div>
        </div>
    </main>

    <script src="js/admin.js"></script>
</body>
</html>