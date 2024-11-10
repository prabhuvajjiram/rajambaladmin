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
        refreshProducts();
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
    }

    editProductForm.addEventListener('submit', function(e) {
        e.preventDefault();
        updateProduct(new FormData(this));
    });

    closeEditModal.addEventListener('click', function() {
        document.getElementById('editProductModal').style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == editProductModal) {
            editProductModal.style.display = 'none';
        }
    });

    // Initial load of products
    refreshProducts();
});

function refreshProducts(showLoading = true) {
    const productList = document.getElementById('productList');
    
    if (showLoading) {
        productList.innerHTML = '<div class="loading">Loading products...</div>';
    }

    return fetch('get_products.php', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Cache-Control': 'no-cache',
            'Pragma': 'no-cache'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            displayProducts(data.products);
            return data.products;
        } else {
            throw new Error(data.message || 'Failed to load products');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        productList.innerHTML = `<div class="error">Error loading products: ${error.message}</div>`;
        if (error.message.includes('Unauthorized')) {
            window.location.href = 'login.php';
        }
        return [];
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
                        <img src="${color.image_path}" alt="${color.name}" title="${color.name}" 
                             style="width: 30px; height: 30px;" 
                             onerror="this.onerror=null; this.src='images/placeholder.png';">
                        <span>${color.name}</span>
                    </div>
                `;
            });
            colorHtml += '</div>';
        } else {
            colorHtml = '<div>No colors available</div>';
        }
        
        productItem.innerHTML = `
            <img src="${product.image_path}" alt="${product.title}" style="width: 50px; height: 50px;"
                 onerror="this.onerror=null; this.src='images/placeholder.png';">
            <span>${product.title}</span>
            <span>₹${product.price}</span>
            <div class="product-colors">
                <h4>Colors:</h4>
                ${colorHtml}
            </div>
            <div class="product-actions">
                <button onclick="editProduct(${product.id})" class="edit-btn">Edit</button>
                <button onclick="deleteProduct(${product.id})" class="delete-btn">Delete</button>
            </div>
        `;
        productList.appendChild(productItem);
    });
}

function handleProductSubmit(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    const colorInputs = document.querySelectorAll('.color-input');
    colorInputs.forEach((colorInput, index) => {
        const colorName = colorInput.querySelector('input[type="text"]').value;
        const colorImage = colorInput.querySelector('input[type="file"]').files[0];
        if (colorName && colorImage) {
            formData.append(`colors[${index}][name]`, colorName);
            formData.append(`colors[${index}][image]`, colorImage);
        }
    });

    fetch('upload_product.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Product added successfully');
            this.reset();
            refreshProducts();
            document.getElementById('colorInputs').innerHTML = `
                <div class="color-input">
                    <input type="text" name="colors[0][name]" placeholder="Color Name">
                    <input type="file" name="colors[0][image]" accept="image/*">
                </div>
            `;
        } else {
            throw new Error(data.message || 'Failed to add product');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding product: ' + error.message);
    });
}

function addColorInput() {
    const colorInputs = document.getElementById('colorInputs');
    const newColorInput = document.createElement('div');
    newColorInput.className = 'color-input';
    const colorIndex = colorInputs.children.length;
    newColorInput.innerHTML = `
        <input type="text" name="colors[${colorIndex}][name]" placeholder="Color Name">
        <input type="file" name="colors[${colorIndex}][image]" accept="image/*">
    `;
    colorInputs.appendChild(newColorInput);
}

function editProduct(id) {
    fetch(`get_product.php?id=${id}`)
        .then(response => {
            console.log('Response:', response);
            return response.json();
        })
        .then(data => {
            console.log('Data:', data);
            if (data.success) {
                const product = data.product;
                document.getElementById('editId').value = product.id;
                document.getElementById('editTitle').value = product.title;
                document.getElementById('editPrice').value = product.price;
                document.getElementById('editDescription').value = product.description;

                // Populate colors if available
                const editColorFields = document.getElementById('editColorFields');
                editColorFields.innerHTML = ''; // Clear existing colors
                if (product.colors && product.colors.length > 0) {
                    product.colors.forEach(color => {
                        editColorFields.innerHTML += `
                            <div class="color-input">
                                <input type="text" name="colors[${color.id}][name]" value="${color.name}" placeholder="Color Name">
                                <input type="file" name="colors[${color.id}][image]" accept="image/*">
                            </div>
                        `;
                    });
                }

                // Show the modal
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
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            alert('Product updated successfully!');
            document.getElementById('editProductModal').style.display = 'none';
            refreshProducts();
        } else {
            throw new Error(data.message || 'Failed to update product');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating product: ' + error.message);
    });
}

function deleteProduct(id) {
    if (!confirm('Are you sure you want to delete this product?')) {
        return;
    }

    fetch('delete_product.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${id}`,
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            alert('Product deleted successfully!');
            refreshProducts();
        } else {
            throw new Error(data.message || 'Failed to delete product');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting product: ' + error.message);
    });
}

function logout() {
    fetch('logout.php', {
        method: 'POST',
        credentials: 'same-origin'
    })
    .then(response => {
        if (response.ok) {
            window.location.href = 'login.php';
        } else {
            throw new Error('Logout failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error during logout: ' + error.message);
    });
}

function toggleMenu() {
    const nav = document.getElementById("adminNav");
    const menuIcon = document.querySelector(".menu-icon");
    nav.classList.toggle("show");
    menuIcon.innerHTML = nav.classList.contains("show") ? "✕" : "☰";
}

// Close menu when clicking outside
document.addEventListener('click', function(event) {
    const nav = document.getElementById("adminNav");
    const menuIcon = document.querySelector(".menu-icon");
    if (!nav.contains(event.target) && !menuIcon.contains(event.target)) {
        nav.classList.remove("show");
        menuIcon.innerHTML = "☰";
    }
});

// Close menu when menu item is clicked
document.querySelectorAll('#adminNav a').forEach(item => {
    item.addEventListener('click', function() {
        const nav = document.getElementById("adminNav");
        const menuIcon = document.querySelector(".menu-icon");
        nav.classList.remove("show");
        menuIcon.innerHTML = "☰";
    });
});