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
                <span class="logo-text">Rajambal Cottons - Admin Panel
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
                <button type="button" onclick="addColorInput()">Add Another Color</button>
                <button type="submit" class="btn">Add Product</button>
            </form>
        </div>
        <script>
            document.getElementById('addProductBtn').addEventListener('click', function() {
                document.getElementById('addProductForm').style.display = 'block';
            });
        </script>

        <div id="editProductModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;
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

    <script>
            document.addEventListener('DOMContentLoaded', function() {
                fetchProducts();
            });

            function fetchProducts() {
                console.log('Fetching products...');
                fetch('get_products.php')
                    .then(response => response.json())
                    .then(data => {
                        console.log('Received data:', data);
                        if (data.status === 'success') {
                            console.log('Products:', data.products);
                            data.products.forEach(product => {
                                console.log('Product colors:', product.colors);
                            });
                            displayProducts(data.products);
                        } else {
                            console.error('Error fetching products:', data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }

            function displayProducts(products) {
                console.log('Displaying products:', products);
                const productList = document.getElementById('productList');
                productList.innerHTML = '';
                products.forEach(product => {
                    console.log('Processing product:', product);
                    const productItem = document.createElement('div');
                    productItem.className = 'product-item';
                    
                    let colorHtml = '';
                    if (product.colors && product.colors.length > 0) {
                        console.log('Product colors:', product.colors);
                        colorHtml = '<div class="color-list">';
                        product.colors.forEach(color => {
                            console.log('Processing color:', color);
                            colorHtml += `
                                <div class="color-item">
                                    <img src="${color.path}" alt="${color.name}" title="${color.name}" style="width: 30px; height: 30px;" onerror="this.onerror=null; this.src='images/placeholder.png';">
                                    <span>${color.name}</span>
                                </div>
                            `;
                        });
                        colorHtml += '</div>';
                    } else {
                        console.log('No colors for this product');
                        colorHtml = '<div>No colors available</div>';
                    }
                    console.log('Final color HTML:', colorHtml);
                    
                    productItem.innerHTML = `
                        <img src="${product.image_path}" alt="${product.title}" style="width: 50px; height: 50px;">
                        <span>${product.title}</span>
                        <span>₹${product.price}</span>
                        <div class="product-colors">
                            <h4>Colors:</h4>
                            ${colorHtml}
                        </div>
                        <div class="product-actions">
                            <button onclick="editProduct(${product.id})">Edit</button>
                            <button onclick="deleteProduct(${product.id})">Delete</button>
                        </div>
                    `;
                    productList.appendChild(productItem);
                });
            }

            function addColorInput() {
                console.log('Adding new color input');
                const colorInputs = document.getElementById('colorInputs');
                const newColorInput = document.createElement('div');
                newColorInput.className = 'color-input';
                const colorIndex = colorInputs.children.length;
                newColorInput.innerHTML = `
                    <input type="text" name="colors[${colorIndex}][name]" placeholder="Color Name">
                    <input type="file" name="colors[${colorIndex}][image]" accept="image/*">
                `;
                colorInputs.appendChild(newColorInput);
                console.log('New color input added. Total color inputs:', colorInputs.children.length);
            }

            document.getElementById('newProductForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                console.log('Submitting new product form');
                const colorInputs = document.querySelectorAll('.color-input');
                colorInputs.forEach((colorInput, index) => {
                    const colorName = colorInput.querySelector('input[type="text"]').value;
                    const colorImage = colorInput.querySelector('input[type="file"]').files[0];
                    if (colorName && colorImage) {
                        formData.append(`colors[${index}][name]`, colorName);
                        formData.append(`colors[${index}][image]`, colorImage);
                    }
                });

                for (let [key, value] of formData.entries()) {
                    console.log(key, value);
                }
                
                fetch('upload_product.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Server response:', data);
                    if (data.success) {
                        alert('Product added successfully');
                        fetchProducts();
                        this.reset();
                        document.getElementById('colorInputs').innerHTML = `
                            <div class="color-input">
                                <input type="text" name="colors[0][name]" placeholder="Color Name">
                                <input type="file" name="colors[0][image]" accept="image/*">
                            </div>
                        `;
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while adding the product');
                });
            });

            // ... (rest of the admin.js code)
        </script>
</body>
</html>
