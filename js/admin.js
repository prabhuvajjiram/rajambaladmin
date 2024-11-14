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

const styles = `
.image-upload-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 10px 0;
}

.existing-image, .color-box {
    position: relative;
    width: 100px;
    height: 100px;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
}

.existing-image img, .color-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.remove-additional-image, .remove-color {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(255, 0, 0, 0.7);
    color: white;
    border: none;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    font-size: 16px;
    z-index: 2;
}

.image-upload-box, .color-upload-box {
    width: 100px;
    height: 100px;
    border: 2px dashed #ccc;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    position: relative;
    background: #f9f9f9;
    transition: all 0.3s ease;
}

.image-upload-box:hover, .color-upload-box:hover {
    border-color: #666;
    background: #f0f0f0;
}

.upload-icon {
    font-size: 24px;
    color: #666;
    pointer-events: none;
    user-select: none;
}

.hidden-file-input {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0,0,0,0) !important;
    border: 0 !important;
    visibility: hidden !important;
}

.color-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 10px 0;
}

.color-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
    align-items: center;
}

.color-item input[type="text"] {
    width: 100px;
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
}

.form-actions button {
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.form-actions button[type="submit"] {
    background: #4CAF50;
    color: white;
    border: none;
}

.form-actions button.close {
    background: #f44336;
    color: white;
    border: none;
}
`;

const styleSheet = document.createElement("style");
styleSheet.textContent = styles;
document.head.appendChild(styleSheet);

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

    // Add cache-busting timestamp
    fetch('get_products.php?t=' + new Date().getTime(), {
        headers: {
            'Cache-Control': 'no-cache',
            'Pragma': 'no-cache'
        }
    })
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
            'Cache-Control': 'no-cache',
            'Pragma': 'no-cache'
        },
        body: JSON.stringify({ product_id: productId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            refreshProducts();
            alert('Product deleted successfully');
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
                        <input type="number" id="edit_price" name="price" value="${product.price}" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea id="edit_description" name="description" required>${product.description}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Current Image</label>
                        <div class="existing-image">
                            <img src="${product.image_path}" alt="${product.title}">
                        </div>
                        <label for="edit_image">Change Image (optional)</label>
                        <input type="file" id="edit_image" name="image" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label>Additional Images</label>
                        <div id="edit_additional_images" class="image-upload-container">
                            ${product.additional_images.map(img => `
                                <div class="existing-image">
                                    <img src="${img.path}" alt="Additional Image">
                                    <button type="button" class="remove-additional-image" data-image-id="${img.id}">×</button>
                                </div>
                            `).join('')}
                            <div class="image-upload-box">
                                <input type="file" name="additional_images[]" accept="image/*" class="hidden-file-input">
                                <div class="upload-icon">+</div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Colors</label>
                        <div id="edit_colors" class="color-container">
                            ${product.colors.map(color => `
                                <div class="color-item">
                                    <input type="hidden" name="colors[${color.id}][id]" value="${color.id}">
                                    <div class="color-box">
                                        <img src="${color.image_path}" alt="${color.name}">
                                        <button type="button" class="remove-color" data-color-id="${color.id}">×</button>
                                    </div>
                                    <input type="text" name="colors[${color.id}][name]" value="${color.name}" placeholder="Color name" required>
                                </div>
                            `).join('')}
                            <div class="color-item">
                                <div class="color-upload-box">
                                    <input type="file" accept="image/*" class="new-color-input hidden-file-input">
                                    <div class="upload-icon">+</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit">Update Product</button>
                        <button type="button" class="close">Cancel</button>
                    </div>
                `;

                // Setup event handlers
                setupRemoveHandlers();
                initializeEditImageUpload();
                setupNewColorUpload();
                
                // Add click handler for cancel button
                editForm.querySelector('.close').addEventListener('click', () => {
                    editProductModal.style.display = 'none';
                });
            } else {
                editForm.innerHTML = '<p>Error loading product details. Please try again.</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            editForm.innerHTML = '<p>Error loading product details. Please try again.</p>';
        });
}

function addEditColorInput() {
    const colorInputs = document.getElementById('edit_colors');
    const newColorId = 'new_' + Date.now();
    
    const colorInput = document.createElement('div');
    colorInput.className = 'color-input';
    colorInput.innerHTML = `
        <input type="text" name="colors[${newColorId}][name]" placeholder="Color name" required>
        <div class="color-image-preview"></div>
        <input type="file" name="colors[${newColorId}][image]" accept="image/*" required>
        <button type="button" class="remove-color">×</button>
    `;

    // Add preview for the new color image
    const fileInput = colorInput.querySelector('input[type="file"]');
    const preview = colorInput.querySelector('.color-image-preview');
    fileInput.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(event) {
                preview.innerHTML = `<img src="${event.target.result}" alt="Color Preview">`;
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    colorInput.querySelector('.remove-color').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        colorInput.remove();
    });

    colorInputs.insertBefore(colorInput, document.getElementById('edit_add_color'));
}

function setupRemoveHandlers() {
    // Handle removal of existing additional images
    document.querySelectorAll('.remove-additional-image').forEach(button => {
        button.addEventListener('click', function() {
            const imageDiv = this.closest('.existing-image');
            if (imageDiv) {
                const imageId = this.dataset.imageId;
                if (imageId) {
                    // Add hidden input to track deleted images
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'deleted_additional_images[]';
                    hiddenInput.value = imageId;
                    document.getElementById('editProductForm').appendChild(hiddenInput);
                }
                imageDiv.remove();
            }
        });
    });

    // Handle removal of existing colors
    document.querySelectorAll('.remove-color').forEach(button => {
        button.addEventListener('click', function() {
            const colorDiv = this.closest('.color-item');
            if (colorDiv) {
                const colorId = this.dataset.colorId;
                if (colorId) {
                    // Add hidden input to track removed colors
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'removed_colors[]';
                    hiddenInput.value = colorId;
                    document.getElementById('editProductForm').appendChild(hiddenInput);
                }
                colorDiv.remove();
            }
        });
    });
}

function createNewUploadBox() {
    const box = document.createElement('div');
    box.className = 'image-upload-box';
    box.innerHTML = `
        <input type="file" name="additional_images[]" accept="image/*" class="hidden-file-input">
        <div class="upload-icon">+</div>
    `;
    return box;
}

function setupNewColorUpload() {
    const colorContainer = document.getElementById('edit_colors');
    if (!colorContainer) return;

    // Add click handler to all color upload boxes
    colorContainer.querySelectorAll('.color-upload-box').forEach(box => {
        box.addEventListener('click', function() {
            const fileInput = this.querySelector('.new-color-input');
            if (fileInput) {
                fileInput.click();
            }
        });
    });

    // Handle color file input changes
    colorContainer.addEventListener('change', function(e) {
        const target = e.target;
        if (target && target.classList.contains('new-color-input') && target.files && target.files[0]) {
            const file = target.files[0];
            const reader = new FileReader();
            const newColorId = 'new_' + Date.now();
            
            reader.onload = function(event) {
                const newColorItem = document.createElement('div');
                newColorItem.className = 'color-item';
                newColorItem.innerHTML = `
                    <div class="color-box">
                        <img src="${event.target.result}" alt="New Color">
                        <button type="button" class="remove-color">×</button>
                    </div>
                    <input type="text" name="colors[${newColorId}][name]" placeholder="Color name" required>
                    <input type="file" name="colors[${newColorId}][image]" accept="image/*" class="hidden-file-input">
                `;

                // Copy the file to the hidden input
                const hiddenInput = newColorItem.querySelector('input[type="file"]');
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                hiddenInput.files = dataTransfer.files;

                // Add remove handler
                const removeButton = newColorItem.querySelector('.remove-color');
                removeButton.addEventListener('click', function() {
                    newColorItem.remove();
                });

                // Insert before the upload box container
                const uploadBox = target.closest('.color-item');
                colorContainer.insertBefore(newColorItem, uploadBox);

                // Reset the file input
                target.value = '';
            };

            reader.readAsDataURL(file);
        }
    });
}

function initializeEditImageUpload() {
    const container = document.getElementById('edit_additional_images');
    if (!container) return;

    // Add click handler to all image upload boxes
    function addUploadBoxClickHandler(uploadBox) {
        uploadBox.addEventListener('click', function() {
            const fileInput = this.querySelector('.hidden-file-input');
            if (fileInput) {
                fileInput.click();
            }
        });
    }

    // Add initial click handlers
    container.querySelectorAll('.image-upload-box').forEach(addUploadBoxClickHandler);

    // Create and add the upload box if it doesn't exist
    function ensureUploadBox() {
        if (!container.querySelector('.image-upload-box')) {
            const uploadBox = document.createElement('div');
            uploadBox.className = 'image-upload-box';
            uploadBox.innerHTML = `
                <input type="file" name="additional_images[]" accept="image/*" class="hidden-file-input">
                <div class="upload-icon">+</div>
            `;
            
            addUploadBoxClickHandler(uploadBox);
            container.appendChild(uploadBox);
        }
    }

    // Initial setup
    ensureUploadBox();

    // Add click handlers for remove buttons
    container.querySelectorAll('.remove-additional-image').forEach(button => {
        button.addEventListener('click', function() {
            const imageDiv = this.closest('.existing-image');
            if (imageDiv) {
                const imageId = this.dataset.imageId;
                if (imageId) {
                    // Add hidden input to track removed images
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'removed_additional_images[]';
                    hiddenInput.value = imageId;
                    document.getElementById('editProductForm').appendChild(hiddenInput);
                }
                imageDiv.remove();
                ensureUploadBox();
            }
        });
    });

    // Use event delegation for file inputs
    container.addEventListener('change', function(e) {
        const target = e.target;
        if (target && target.classList.contains('hidden-file-input') && target.files && target.files[0]) {
            const file = target.files[0];
            const reader = new FileReader();
            
            reader.onload = function(event) {
                // Create new image preview
                const imagePreview = document.createElement('div');
                imagePreview.className = 'existing-image';
                imagePreview.innerHTML = `
                    <img src="${event.target.result}" alt="Additional Image">
                    <button type="button" class="remove-additional-image">×</button>
                    <input type="file" name="additional_images[]" accept="image/*" class="hidden-file-input">
                `;

                // Copy the file to the hidden input
                const hiddenInput = imagePreview.querySelector('input[type="file"]');
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                hiddenInput.files = dataTransfer.files;

                // Add remove handler
                const removeButton = imagePreview.querySelector('.remove-additional-image');
                removeButton.addEventListener('click', function() {
                    imagePreview.remove();
                    ensureUploadBox();
                });

                // Add the new image preview before the upload box
                const uploadBox = container.querySelector('.image-upload-box');
                container.insertBefore(imagePreview, uploadBox);

                // Reset the upload box input
                target.value = '';
            };

            reader.readAsDataURL(file);
        }
    });
}

function updateProduct(formData) {
    // Log form data for debugging
    console.log('Form data being sent:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    fetch('update_product.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Server response:', data); // Debug log
        if (data.success) {
            document.getElementById('editProductModal').style.display = 'none';
            refreshProducts();
            alert('Product updated successfully');
        } else {
            console.error('Server error:', data);
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