document.addEventListener('DOMContentLoaded', function() {
    const listProductsBtn = document.getElementById('listProductsBtn');
    const addProductBtn = document.getElementById('addProductBtn');
    const productList = document.getElementById('productList');
    const addProductForm = document.getElementById('addProductForm');
    const newProductForm = document.getElementById('newProductForm');
    const editProductModal = document.getElementById('editProductModal');
    const editProductForm = document.getElementById('editProductForm');
    const closeEditModal = editProductModal.querySelector('.close');

    listProductsBtn.addEventListener('click', function(e) {
        e.preventDefault();
        loadProducts();
        productList.style.display = 'block';
        addProductForm.style.display = 'none';
    });

    addProductBtn.addEventListener('click', function(e) {
        e.preventDefault();
        productList.style.display = 'none';
        addProductForm.style.display = 'block';
    });

    newProductForm.addEventListener('submit', function(e) {
        e.preventDefault();
        addProduct(new FormData(this));
    });

    editProductForm.addEventListener('submit', function(e) {
        e.preventDefault();
        updateProduct(new FormData(this));
    });

    closeEditModal.addEventListener('click', function() {
        editProductModal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == editProductModal) {
            editProductModal.style.display = 'none';
        }
    });

    // Load products when the page loads
    loadProducts();
});

function loadProducts() {
    fetch('get_products.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                displayProducts(data.products);
            } else {
                console.error('Error loading products:', data.message);
                alert('Error loading products. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
}

function displayProducts(products) {
    const productList = document.getElementById('productList');
    productList.innerHTML = '';
    products.forEach(product => {
        const productItem = document.createElement('div');
        productItem.className = 'product-item';
        productItem.innerHTML = `
            <div>
                <h3>${product.title}</h3>
                <p>Price: ₹${product.price}</p>
            </div>
            <div class="product-actions">
                <button onclick="editProduct(${product.id})" class="btn">Edit</button>
                <button onclick="deleteProduct(${product.id})" class="btn">Delete</button>
            </div>
        `;
        productList.appendChild(productItem);
    });
}

function addProduct(formData) {
    fetch('upload_product.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Product added successfully!');
            document.getElementById('newProductForm').reset();
            loadProducts();
        } else {
            alert('Error adding product: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

function editProduct(id) {
    fetch(`get_product.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('editProductId').value = data.product.id;
                document.getElementById('editTitle').value = data.product.title;
                document.getElementById('editPrice').value = data.product.price;
                document.getElementById('editDescription').value = data.product.description;
                document.getElementById('currentImage').src = data.product.image;
                
                document.getElementById('editProductModal').style.display = 'block';
            } else {
                console.error('Error fetching product:', data.message);
                alert('Error fetching product details. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
}

function updateProduct(formData) {
    fetch('edit_product.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Product updated successfully!');
            document.getElementById('editProductModal').style.display = 'none';
            loadProducts();
        } else {
            alert('Error updating product: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

function logout() {
    // Send a request to logout.php
    fetch('logout.php', {
        method: 'POST',
        credentials: 'same-origin'
    })
    .then(response => {
        if (response.ok) {
            // Redirect to the home page after successful logout
            window.location.href = 'https://www.rajambalcottons.com/';
        } else {
            console.error('Logout failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function toggleMenu() {
    var nav = document.getElementById("adminNav");
    var menuIcon = document.querySelector(".menu-icon");
    nav.classList.toggle("show");
    
    // Change the menu icon to an 'X' when opened, and back to '☰' when closed
    if (nav.classList.contains("show")) {
        menuIcon.innerHTML = "✕";
    } else {
        menuIcon.innerHTML = "☰";
    }
}
// Close the menu when clicking outside of it
function toggleMenu() {
    var nav = document.getElementById("adminNav");
    var menuIcon = document.querySelector(".menu-icon");
    nav.classList.toggle("show");
    
    if (nav.classList.contains("show")) {
        menuIcon.innerHTML = "✕";
    } else {
        menuIcon.innerHTML = "☰";
    }
}

// Close the menu when a menu item is clicked
document.querySelectorAll('#adminNav a').forEach(item => {
    item.addEventListener('click', function() {
        var nav = document.getElementById("adminNav");
        var menuIcon = document.querySelector(".menu-icon");
        nav.classList.remove("show");
        menuIcon.innerHTML = "☰";
    });
});
function deleteProduct(productId) {
    if (confirm('Are you sure you want to delete this product?')) {
        fetch('delete_product.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Product deleted successfully!');
                loadProducts();
            } else {
                alert('Error deleting product: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
}
