document.addEventListener('DOMContentLoaded', () => {
    const productForm = document.getElementById('productForm');
    const uploadMessage = document.getElementById('uploadMessage');
    const logoutBtn = document.getElementById('logoutBtn');
    const addProductBtn = document.getElementById('addProductBtn');
    const listProductsBtn = document.getElementById('listProductsBtn');
    const addProductSection = document.getElementById('addProductSection');
    const listProductsSection = document.getElementById('listProductsSection');
    const productList = document.querySelector('.product-list');

    if (productForm) {
        productForm.addEventListener('submit', handleProductSubmit);
    }

    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }

    if (addProductBtn) {
        addProductBtn.addEventListener('click', () => {
            addProductSection.classList.add('active');
            listProductsSection.classList.remove('active');
        });
    }

    if (listProductsBtn) {
        listProductsBtn.addEventListener('click', () => {
            addProductSection.classList.remove('active');
            listProductsSection.classList.add('active');
        });
    }

    if (productList) {
        productList.addEventListener('click', handleProductDelete);
    }

    function handleProductSubmit(event) {
        event.preventDefault();
        const formData = new FormData(event.target);

        fetch('upload_product.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                displayUploadMessage(data.message, 'success');
                productForm.reset();
                addProductToList(data.product);
            } else {
                displayUploadMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            displayUploadMessage('An error occurred. Please try again.', 'error');
        });
    }

    function displayUploadMessage(message, type = 'success') {
        if (uploadMessage) {
            uploadMessage.textContent = message;
            uploadMessage.style.color = type === 'success' ? 'green' : 'red';
            uploadMessage.style.display = 'block';
            setTimeout(() => {
                uploadMessage.style.display = 'none';
            }, 5000);
        }
    }

    function addProductToList(product) {
        const productItem = document.createElement('div');
        productItem.className = 'product-item';
        productItem.dataset.id = product.id;
        productItem.innerHTML = `
            <span>${product.title} - ₹${parseFloat(product.price).toFixed(2)}</span>
            <button class='delete-btn' data-id='${product.id}'>Delete</button>
        `;
        productList.insertBefore(productItem, productList.firstChild);
    }

    function handleProductDelete(event) {
        if (event.target.classList.contains('delete-btn')) {
            const productId = event.target.dataset.id;
            if (confirm('Are you sure you want to delete this product?')) {
                deleteProduct(productId);
            }
        }
    }

    function deleteProduct(productId) {
        const formData = new FormData();
        formData.append('id', productId);

        fetch('delete_product.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                displayUploadMessage(data.message, 'success');
                removeProductFromList(productId);
            } else {
                displayUploadMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            displayUploadMessage('An error occurred while deleting the product. Please try again.', 'error');
        });
    }

    function removeProductFromList(productId) {
        const productItem = document.querySelector(`.product-item[data-id="${productId}"]`);
        if (productItem) {
            productItem.remove();
        }
    }

    function handleLogout() {
        fetch('logout.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = 'login.php';
                } else {
                    console.error('Logout failed');
                }
            })
            .catch(error => {
                console.error('Error during logout:', error);
            });
    }
});