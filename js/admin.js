document.addEventListener('DOMContentLoaded', function() {
    const listProductsBtn = document.getElementById('listProductsBtn');
    const addProductBtn = document.getElementById('addProductBtn');
    const productList = document.getElementById('productList');
    const addProductForm = document.getElementById('addProductForm');
    const newProductForm = document.getElementById('newProductForm');
    const editProductModal = document.getElementById('editProductModal');
    const closeEditModal = editProductModal.querySelector('.close');
    const addColorBtn = document.getElementById('addColorBtn');

    // Initialize event listeners
    listProductsBtn?.addEventListener('click', function(e) {
        e.preventDefault();
        showProductList();
    });

    addProductBtn?.addEventListener('click', function(e) {
        e.preventDefault();
        showAddProductForm();
    });

    if (newProductForm) {
        newProductForm.addEventListener('submit', handleProductSubmit);
    }

    if (addColorBtn) {
        addColorBtn.addEventListener('click', addColorInput);
    }

    document.getElementById('editProductForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        updateProduct(new FormData(this));
    });

    closeEditModal?.addEventListener('click', function() {
        editProductModal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == editProductModal) {
            editProductModal.style.display = 'none';
        }
    });

    // Initialize image upload functionality
    initializeImageUpload();
    
    // Initial load of products
    refreshProducts();

    // Setup mobile menu
    setupMobileMenu();
});

function showProductList() {
    document.getElementById('productList').style.display = 'block';
    document.getElementById('addProductForm').style.display = 'none';
    refreshProducts();
}

function showAddProductForm() {
    document.getElementById('productList').style.display = 'none';
    document.getElementById('addProductForm').style.display = 'block';
    
    // Reset form and reinitialize image upload
    const form = document.getElementById('newProductForm');
    if (form) {
        form.reset();
        initializeImageUpload();
    }
}

function refreshProducts() {
    const productList = document.getElementById('productTableBody');
    if (!productList) return;
    
    productList.innerHTML = '<tr><td colspan="5">Loading products...</td></tr>';

    fetch('get_products.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                displayProducts(data.products);
            } else {
                throw new Error(data.message || 'Failed to load products');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            productList.innerHTML = `<tr><td colspan="5">Error loading products: ${error.message}</td></tr>`;
        });
}

function displayProducts(products) {
    const tableBody = document.getElementById('productTableBody');
    if (!tableBody) return;
    
    tableBody.innerHTML = '';
    
    products.forEach(product => {
        const row = document.createElement('tr');
        
        let colorHtml = '';
        if (product.colors && product.colors.length > 0) {
            colorHtml = '<div class="color-list">';
            product.colors.forEach(color => {
                colorHtml += `
                    <div class="color-item">
                        <img src="${color.image_path}" alt="${color.name}" title="${color.name}">
                    </div>
                `;
            });
            colorHtml += '</div>';
        }
        
        row.innerHTML = `
            <td><img src="${product.image_path}" alt="${product.title}" onerror="this.src='images/placeholder.jpg'"></td>
            <td>${product.title}</td>
            <td>₹${product.price}</td>
            <td>${colorHtml}</td>
            <td>
                <button onclick="editProduct(${product.id})" class="edit-btn">Edit</button>
                <button onclick="deleteProduct(${product.id})" class="delete-btn">Delete</button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

function initializeImageUpload() {
    const additionalImagesContainer = document.getElementById('additionalImagesContainer');
    if (!additionalImagesContainer) return;
    
    // Clear existing content
    additionalImagesContainer.innerHTML = '';
    
    const maxAdditionalImages = 3;

    function createImageUploadBox() {
        const box = document.createElement('div');
        box.className = 'image-upload-box empty';
        
        const input = document.createElement('input');
        input.type = 'file';
        input.name = 'additional_images[]';
        input.accept = 'image/*';
        input.className = 'hidden-file-input';
        
        input.addEventListener('change', function(e) {
            handleImageSelection(e, box);
        });
        
        box.appendChild(input);
        
        box.addEventListener('click', function() {
            if (box.classList.contains('empty')) {
                input.click();
            }
        });
        
        return box;
    }

    function handleImageSelection(e, box) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(readerEvent) {
                // Create new elements
                const img = document.createElement('img');
                img.src = readerEvent.target.result;
                img.alt = 'Selected Image';
                
                const removeBtn = document.createElement('span');
                removeBtn.className = 'remove-image';
                removeBtn.innerHTML = '×';
                removeBtn.addEventListener('click', function(evt) {
                    evt.stopPropagation();
                    removeImage(box);
                });
                
                // Create new file input
                const newInput = document.createElement('input');
                newInput.type = 'file';
                newInput.name = 'additional_images[]';
                newInput.accept = 'image/*';
                newInput.className = 'hidden-file-input';
                newInput.style.display = 'none';
                
                // Store the file in the input
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                newInput.files = dataTransfer.files;
                
                // Clear and update the box
                box.innerHTML = '';
                box.appendChild(img);
                box.appendChild(removeBtn);
                box.appendChild(newInput);
                box.classList.remove('empty');
                
                updateImageUploadBoxes();
            };
            reader.readAsDataURL(file);
        }
    }

    function removeImage(box) {
        box.innerHTML = '';
        const input = document.createElement('input');
        input.type = 'file';
        input.name = 'additional_images[]';
        input.accept = 'image/*';
        input.className = 'hidden-file-input';
        
        input.addEventListener('change', function(e) {
            handleImageSelection(e, box);
        });
        
        box.appendChild(input);
        box.classList.add('empty');
        updateImageUploadBoxes();
    }

    function updateImageUploadBoxes() {
        const container = document.getElementById('additionalImagesContainer');
        const boxes = container.querySelectorAll('.image-upload-box');
        const emptyBoxes = container.querySelectorAll('.image-upload-box.empty');
        
        if (boxes.length < 3 && emptyBoxes.length === 0) {
            container.appendChild(createImageUploadBox());
        }
    }

    // Add first upload box
    additionalImagesContainer.appendChild(createImageUploadBox());
}

function addColorInput() {
    const colorContainer = document.getElementById('colorInputs');
    const colorInputDiv = document.createElement('div');
    colorInputDiv.className = 'color-input';
    
    const nameInput = document.createElement('input');
    nameInput.type = 'text';
    nameInput.name = 'color_names[]';
    nameInput.placeholder = 'Color Name';
    nameInput.required = true;
    
    const imageInput = document.createElement('input');
    imageInput.type = 'file';
    imageInput.name = 'color_images[]';
    imageInput.accept = 'image/*';
    imageInput.required = true;
    
    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'remove-color';
    removeButton.innerHTML = '×';
    removeButton.onclick = function() {
        colorContainer.removeChild(colorInputDiv);
    };
    
    colorInputDiv.appendChild(nameInput);
    colorInputDiv.appendChild(imageInput);
    colorInputDiv.appendChild(removeButton);
    
    colorContainer.appendChild(colorInputDiv);
}

function handleProductSubmit(e) {
    e.preventDefault();
    console.log('Starting form submission...');
    
    const formData = new FormData();
    
    // Add basic product info
    formData.append('title', e.target.title.value);
    formData.append('price', e.target.price.value);
    formData.append('description', e.target.description.value);
    console.log('Added basic info');

    // Add primary image
    const primaryImage = e.target.primary_image.files[0];
    if (!primaryImage) {
        alert('Please select a primary image');
        return;
    }
    formData.append('primary_image', primaryImage);
    console.log('Added primary image:', primaryImage.name);

    // Add additional images
    const additionalImages = document.querySelectorAll('.image-upload-box:not(.empty) input[type="file"]');
    console.log('Found additional images:', additionalImages.length);
    
    additionalImages.forEach((input, index) => {
        if (input.files && input.files[0]) {
            formData.append('additional_images[]', input.files[0]);
            console.log('Added additional image:', input.files[0].name);
        }
    });

    // Add colors
    const colorInputs = document.querySelectorAll('.color-input');
    console.log('Found color inputs:', colorInputs.length);
    
    colorInputs.forEach((colorInput, index) => {
        const nameInput = colorInput.querySelector('input[type="text"]');
        const imageInput = colorInput.querySelector('input[type="file"]');
        
        if (nameInput && nameInput.value && imageInput && imageInput.files[0]) {
            formData.append('color_names[]', nameInput.value);
            formData.append('color_images[]', imageInput.files[0]);
            console.log('Added color:', {
                name: nameInput.value,
                image: imageInput.files[0].name
            });
        }
    });

    // Debug: Log form data
    console.log('=== Form Data Contents ===');
    for (let [key, value] of formData.entries()) {
        if (value instanceof File) {
            console.log(`${key}: File - ${value.name} (${value.size} bytes)`);
        } else {
            console.log(`${key}: ${value}`);
        }
    }

    // Send the data
    console.log('Sending form data to server...');
    fetch('upload_product.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Received server response');
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Server error response:', text);
                throw new Error('Server error: ' + text);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Server response:', data);
        if (data.success) {
            alert('Product added successfully');
            e.target.reset();
            showProductList();
            initializeImageUpload();
        } else {
            throw new Error(data.message || 'Failed to add product');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding product: ' + error.message);
    });
}

function deleteProduct(productId) {
    if (!confirm('Are you sure you want to delete this product?')) {
        return;
    }

    fetch('delete_product.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ product_id: productId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            refreshProducts();
        } else {
            alert('Error deleting product: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting product');
    });
}

function editProduct(productId) {
    const editProductModal = document.getElementById('editProductModal');
    const editForm = document.getElementById('editProductForm');
    
    // Show loading state
    editProductModal.style.display = 'block';
    editForm.innerHTML = '<p>Loading product details...</p>';

    // Fetch product details
    fetch(`get_product.php?id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const product = data.product;
                
                // Create form HTML
                editForm.innerHTML = `
                    <input type="hidden" name="product_id" value="${product.id}">
                    <div class="form-group">
                        <label for="edit_title">Title</label>
                        <input type="text" id="edit_title" name="title" value="${product.title}" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_price">Price</label>
                        <input type="number" id="edit_price" name="price" value="${product.price}" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea id="edit_description" name="description" required>${product.description}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Current Image</label>
                        <img src="${product.image_path}" alt="${product.title}" style="max-width: 100px;">
                        <label for="edit_image">Change Image (optional)</label>
                        <input type="file" id="edit_image" name="image" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label>Additional Images</label>
                        <div id="edit_additional_images" class="image-upload-container">
                            ${product.additional_images.map(img => `
                                <div class="existing-image">
                                    <img src="${img}" alt="Additional Image">
                                    <button type="button" class="remove-additional-image" data-path="${img}">×</button>
                                </div>
                            `).join('')}
                            <div class="image-upload-box empty">
                                <input type="file" name="new_additional_images[]" accept="image/*" multiple class="hidden-file-input">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Colors</label>
                        <div id="edit_colors" class="color-inputs">
                            ${product.colors.map(color => `
                                <div class="color-input">
                                    <input type="text" name="color_names[]" value="${color.name}" placeholder="Color name">
                                    <div class="color-image">
                                        <img src="${color.image_path}" alt="${color.name}">
                                        <button type="button" class="remove-color" data-color="${color.name}">×</button>
                                    </div>
                                </div>
                            `).join('')}
                            <button type="button" id="edit_add_color" class="btn">Add Color</button>
                        </div>
                    </div>
                    <button type="submit" class="btn">Update Product</button>
                `;

                // Initialize image upload functionality for additional images
                initializeEditImageUpload();
                
                // Add event listener for adding new colors
                document.getElementById('edit_add_color')?.addEventListener('click', addEditColorInput);
                
                // Add event listeners for removing existing images and colors
                setupRemoveHandlers();
            } else {
                editForm.innerHTML = `<p>Error loading product: ${data.message}</p>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            editForm.innerHTML = '<p>Error loading product details. Please try again.</p>';
        });
}

function initializeEditImageUpload() {
    const fileInputs = document.querySelectorAll('.image-upload-box input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const box = this.parentElement;
            if (this.files.length > 0) {
                const file = this.files[0];
                const reader = new FileReader();
                reader.onload = function(e) {
                    box.innerHTML = `
                        <img src="${e.target.result}" alt="Selected Image">
                        <button type="button" class="remove-image">×</button>
                        <input type="file" name="new_additional_images[]" accept="image/*" class="hidden-file-input">
                    `;
                    box.classList.remove('empty');
                };
                reader.readAsDataURL(file);
            }
        });
    });
}

function addEditColorInput() {
    const colorInputs = document.getElementById('edit_colors');
    const newColorInput = document.createElement('div');
    newColorInput.className = 'color-input';
    newColorInput.innerHTML = `
        <input type="text" name="color_names[]" placeholder="Color name" required>
        <input type="file" name="color_images[]" accept="image/*" required>
        <button type="button" class="remove-color">×</button>
    `;
    colorInputs.insertBefore(newColorInput, document.getElementById('edit_add_color'));
    
    // Add remove handler
    newColorInput.querySelector('.remove-color').addEventListener('click', function() {
        newColorInput.remove();
    });
}

function setupRemoveHandlers() {
    // Setup handlers for removing additional images
    document.querySelectorAll('.remove-additional-image').forEach(button => {
        button.addEventListener('click', function() {
            const imagePath = this.dataset.path;
            const imageDiv = this.parentElement;
            // Add hidden input to track removed images
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'removed_additional_images[]';
            hiddenInput.value = imagePath;
            document.getElementById('editProductForm').appendChild(hiddenInput);
            imageDiv.remove();
        });
    });
    
    // Setup handlers for removing colors
    document.querySelectorAll('.remove-color').forEach(button => {
        button.addEventListener('click', function() {
            const colorName = this.dataset.color;
            if (colorName) {
                // Add hidden input to track removed colors
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'removed_colors[]';
                hiddenInput.value = colorName;
                document.getElementById('editProductForm').appendChild(hiddenInput);
            }
            this.closest('.color-input').remove();
        });
    });
}

function updateProduct(formData) {
    fetch('update_product.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('editProductModal').style.display = 'none';
            refreshProducts();
            alert('Product updated successfully');
        } else {
            alert('Error updating product: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating product. Please try again.');
    });
}

function logout() {
    // TO DO: implement logout functionality
}

function toggleMenu() {
    const navMenu = document.querySelector('.nav-menu');
    const hamburger = document.querySelector('.hamburger-menu');
    
    navMenu.classList.toggle('active');
    
    // Close menu when a nav item is clicked
    const navItems = navMenu.querySelectorAll('button');
    navItems.forEach(item => {
        item.addEventListener('click', () => {
            navMenu.classList.remove('active');
        });
    });
}

// Close menu when clicking outside
document.addEventListener('click', (e) => {
    const navMenu = document.querySelector('.nav-menu');
    const hamburger = document.querySelector('.hamburger-menu');
    
    if (!navMenu.contains(e.target) && !hamburger.contains(e.target) && navMenu.classList.contains('active')) {
        navMenu.classList.remove('active');
    }
});

function setupMobileMenu() {
    document.addEventListener('click', function(event) {
        const nav = document.getElementById("adminNav");
        const menuIcon = document.querySelector(".menu-icon");
        if (nav && menuIcon && !nav.contains(event.target) && !menuIcon.contains(event.target)) {
            nav.classList.remove("show");
            menuIcon.textContent = "☰";
        }
    });

    const menuItems = document.querySelectorAll('#adminNav a');
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            const nav = document.getElementById("adminNav");
            const menuIcon = document.querySelector(".menu-icon");
            if (nav && menuIcon) {
                nav.classList.remove("show");
                menuIcon.textContent = "☰";
            }
        });
    });
}