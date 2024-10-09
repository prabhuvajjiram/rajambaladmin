document.addEventListener('DOMContentLoaded', () => {
    const productForm = document.getElementById('productForm');
    const uploadMessage = document.getElementById('uploadMessage');
    const logoutBtn = document.getElementById('logoutBtn');

    if (productForm) {
        productForm.addEventListener('submit', handleProductSubmit);
    }

    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
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
                updateProductsList(data.product);
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

    function updateProductsList(newProduct) {
        fetch('products.json')
            .then(response => response.json())
            .then(products => {
                products.push(newProduct);
                return fetch('update_products.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(products)
                });
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    console.log('Products list updated successfully');
                } else {
                    console.error('Failed to update products list');
                }
            })
            .catch(error => {
                console.error('Error updating products list:', error);
            });
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