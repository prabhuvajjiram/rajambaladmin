<?php
session_start();
require_once "db_config.php";

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
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
        }
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
        .color-list {
            display: flex;
            gap: 10px;
        }
        .color-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 12px;
        }
        .color-item img {
            border: 1px solid #ccc;
            border-radius: 50%;
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
        .header {
            background-color: #f1f1f1;
            padding: 20px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .logo {
            display: flex;
            align-items: center;
        }
        .logo-icon {
            height: 40px;
            margin-right: 10px;
        }
        .logo-text {
            font-size: 20px;
            font-weight: 600;
        }
        .menu-icon {
            display: none;
            font-size: 24px;
            cursor: pointer;
        }
        @media screen and (max-width: 768px) {
            .admin-container {
                margin: 60px auto 0;
                padding: 10px;
            }
            .header .container {
                flex-direction: column;
                align-items: center;
            }
            .logo {
                margin-bottom: 10px;
            }
            .menu-icon {
                display: block;
                position: absolute;
                right: 20px;
                top: 20px;
            }
            .admin-nav {
                display: none;
                width: 100%;
            }
            .admin-nav.show {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .admin-nav a, .btn {
                width: 100%;
                text-align: center;
            }
            #addProductBtn {
                display: block;
                margin-top: 20px;
            }
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
            <div class="menu-icon" onclick="toggleMenu()">☰</div>
            <nav class="admin-nav" id="adminNav">
                <a href="#" id="listProductsBtn">List Products</a>
                <a href="#" id="addProductBtn">Add Product</a>
                <a href="#" onclick="logout(); return false;">Logout</a>
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
                <h3>Product Colors</h3>
                <div id="colorInputs">
                    <div class="color-input">
                        <input type="text" name="colors[0][name]" placeholder="Color Name">
                        <input type="file" name="colors[0][image]" accept="image/*">
                    </div>
                </div>
                <button type="button" id="addColorBtn">Add Another Color</button>
                <button type="submit">Add Product</button>
            </form>
        </div>

        <div id="editProductModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Edit Product</h2>
                <form id="editProductForm" style="display: none;">
                    <input type="hidden" id="editId" name="id">
                    <label for="editTitle">Title:</label>
                    <input type="text" id="editTitle" name="title" required>
                    <label for="editPrice">Price:</label>
                    <input type="number" id="editPrice" name="price" step="0.01" required>
                    <label for="editDescription">Description:</label>
                    <textarea id="editDescription" name="description" required></textarea>
                    <label for="editImage">New Main Image (optional):</label>
                    <input type="file" id="editImage" name="image" accept="image/*">
                    <div id="editColorFields"></div>
                    <button type="submit" class="btn">Update Product</button>
                </form>
            </div>
        </div>
    </main>

    <script src="js/admin.js"></script>
</body>
</html>