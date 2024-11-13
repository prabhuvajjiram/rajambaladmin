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

        .header {
            background-color: #f1f1f1;
            padding: 20px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
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

        /* Table Styles */
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .product-table th,
        .product-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .product-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .product-table img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }

        .product-table .color-list {
            display: flex;
            gap: 5px;
        }

        .product-table .color-item img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
        }

        /* Form Styles */
        #addProductForm {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }

        #addProductForm input[type="text"],
        #addProductForm input[type="number"],
        #addProductForm textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        /* Image Upload Styles */
        .image-upload-container {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .image-upload-box {
            width: 100px;
            height: 100px;
            border: 2px dashed #ccc;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .image-upload-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-upload-box.empty {
            background: #f8f9fa;
        }

        .image-upload-box.empty::after {
            content: '+';
            font-size: 24px;
            color: #666;
        }

        .image-upload-box .remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(0,0,0,0.5);
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
        }

        .hidden-file-input {
            display: none;
        }

        /* Color Input Styles */
        .color-inputs {
            margin: 20px 0;
            width: 100%;
        }

        .color-inputs h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .color-input {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            width: 100%;
        }

        .color-input input[type="text"] {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            min-width: 200px;
        }

        .color-input input[type="file"] {
            flex: 2;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
        }

        .color-input .remove-color {
            padding: 8px 12px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
            transition: background-color 0.2s;
        }

        .color-input .remove-color:hover {
            background-color: #c82333;
        }

        #addColorBtn {
            margin-bottom: 20px;
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        #addColorBtn:hover {
            background-color: #218838;
        }

        /* Mobile Responsive Styles */
        @media screen and (max-width: 768px) {
            .admin-container {
                padding: 10px;
            }

            .product-table {
                display: block;
                overflow-x: auto;
            }

            .product-table th,
            .product-table td {
                min-width: 120px;
            }

            .product-table td:first-child {
                min-width: 80px;
            }

            .header .container {
                flex-direction: column;
                align-items: flex-start;
            }

            .admin-nav {
                margin-top: 10px;
                flex-direction: column;
                width: 100%;
            }

            .admin-nav a {
                width: 100%;
                text-align: center;
            }

            .menu-icon {
                display: block;
                position: absolute;
                right: 20px;
                top: 20px;
            }
        }

        /* Menu Icon */
        .menu-icon {
            display: none;
            font-size: 24px;
            cursor: pointer;
            background: none;
            border: none;
            color: #BE3A8E;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            margin: 50px auto;
            position: relative;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
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
            <div class="menu-icon" onclick="toggleMenu()">☰</div>
            <nav class="admin-nav" id="adminNav">
                <a href="#" id="listProductsBtn">List Products</a>
                <a href="#" id="addProductBtn">Add Product</a>
                <a href="#" onclick="logout()">Logout</a>
            </nav>
        </div>
    </header>

    <main class="admin-container">
        <div id="productList">
            <table class="product-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Price</th>
                        <th>Colors</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="productTableBody">
                    <!-- Products will be loaded here -->
                </tbody>
            </table>
        </div>

        <div id="addProductForm" style="display: none;">
            <h2>Add New Product</h2>
            <form id="newProductForm" enctype="multipart/form-data">
                <input type="text" name="title" placeholder="Product Title" required>
                <input type="number" name="price" placeholder="Price" step="0.01" required>
                <textarea name="description" placeholder="Product Description" required></textarea>
                
                <div class="image-upload-section">
                    <label>Primary Image</label>
                    <input type="file" name="primary_image" accept="image/*" required>
                    
                    <label>Additional Images (Up to 3)</label>
                    <div class="image-upload-container" id="additionalImagesContainer">
                        <div class="image-upload-box empty">
                            <input type="file" name="additional_images[]" accept="image/*" class="hidden-file-input">
                        </div>
                    </div>
                </div>

                <div class="color-inputs" id="colorInputs">
                    <h3>Product Colors</h3>
                    <div class="color-input">
                        <input type="text" name="color_names[]" placeholder="Color Name">
                        <input type="file" name="color_images[]" accept="image/*">
                        <button type="button" class="remove-color">×</button>
                    </div>
                </div>
                <button type="button" id="addColorBtn" class="btn">Add Another Color</button>
                <button type="submit" class="btn">Add Product</button>
            </form>
        </div>

        <div id="editProductModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Edit Product</h2>
                <form id="editProductForm">
                    <input type="hidden" id="editId" name="id">
                    <input type="text" id="editTitle" name="title" required>
                    <input type="number" id="editPrice" name="price" step="0.01" required>
                    <textarea id="editDescription" name="description" required></textarea>
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