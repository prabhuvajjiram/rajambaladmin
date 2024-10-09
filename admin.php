<?php
session_start();

// Check if the user is logged in, if not redirect to login page
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
    <title>Rajambal Cottons - Admin Panel</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        .admin-container {
            max-width: 600px;
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
            <h2>Add New Product</h2>
            <form id="productForm" enctype="multipart/form-data">
                <input type="text" name="title" placeholder="Product Title" required>
                <input type="number" name="price" placeholder="Price" required>
                <textarea name="description" placeholder="Product Description" required></textarea>
                <input type="file" name="image" accept="image/*" required>
                <button type="submit" class="btn btn-primary">Add Product</button>
            </form>
            <div id="uploadMessage"></div>
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