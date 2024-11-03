document.addEventListener('DOMContentLoaded', function() {
    const listProductsBtn = document.getElementById('listProductsBtn');
    const addProductBtn = document.getElementById('addProductBtn');
    const productList = document.getElementById('productList');
    const addProductForm = document.getElementById('addProductForm');
    const newProductForm = document.getElementById('newProductForm');
    const editProductModal = document.getElementById('editProductModal');
    const editProductForm = document.getElementById('editProductForm');
    const closeEditModal = editProductModal.querySelector('.close');
    const addColorBtn = document.getElementById('addColorBtn');

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

    if (newProductForm) {
        newProductForm.addEventListener('submit', handleProductSubmit);
    }

    if (addColorBtn) {
        addColorBtn.addEventListener('click', addColorInput);
        console.log('Add Color button event listener added');
    } else {
        console.error('Add Color button not found');
    }

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
    const productList = document.getElementById('productList');
    productList.innerHTML = '';
    fetch('get_products.php')
        .then(response => response.json())
        .then(data => {
            console.log('Products data:', data);
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
        
        let colorHtml = '';
        if (product.colors && product.colors.length > 0) {
            colorHtml = '<div class="color-list">';
            product.colors.forEach(color => {
                colorHtml += `
                    <div class="color-item">
                        <img src="${color.image_path}" alt="${color.name}" title="${color.name}" style="width: 30px; height: 30px;" onerror="this.onerror=null; this.src='images/placeholder.png';">
                        <span>${color.name}</span>
                    </div>
                `;
            });
            colorHtml += '</div>';
        } else {
            colorHtml = '<div>No colors available</div>';
        }
        
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

function handleProductSubmit(e) {
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
        if (data.status === 'success') {
            alert('Product added successfully');
            loadProducts();
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

function editProduct(id) {
    fetch(`get_product.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Edit product data:', data);
            if (data.status === 'success') {
                document.getElementById('editProductId').value = data.product.id;
                document.getElementById('editTitle').value = data.product.title;
                document.getElementById('editPrice').value = data.product.price;
                document.getElementById('editDescription').value = data.product.description;
                document.getElementById('currentImage').src = data.product.image_path;
                document.getElementById('editProductModal').style.display = 'block';
            } else {
                alert('Error fetching product details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while fetching product details. Please try again.');
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

function deleteProduct(id) {
    if (confirm('Are you sure you want to delete this product?')) {
        fetch('delete_product.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
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

function logout() {
    fetch('logout.php', {
        method: 'POST',
        credentials: 'same-origin'
    })
    .then(response => {
        if (response.ok) {
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
    
    if (nav.classList.contains("show")) {
        menuIcon.innerHTML = "✕";
    } else {
        menuIcon.innerHTML = "☰";
    }
}

// Close the menu when clicking outside of it
document.addEventListener('click', function(event) {
    var nav = document.getElementById("adminNav");
    var menuIcon = document.querySelector(".menu-icon");
    if (!nav.contains(event.target) && !menuIcon.contains(event.target)) {
        nav.classList.remove("show");
        menuIcon.innerHTML = "☰";
    }
});

// Close the menu when a menu item is clicked
document.querySelectorAll('#adminNav a').forEach(item => {
    item.addEventListener('click', function() {
        var nav = document.getElementById("adminNav");
        var menuIcon = document.querySelector(".menu-icon");
        nav.classList.remove("show");
        menuIcon.innerHTML = "☰";
    });
});
