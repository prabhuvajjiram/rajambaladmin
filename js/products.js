let products = [];

function loadProductsFromServer() {
    return fetch('get_products.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                console.log('Products loaded:', data.products);
                return data.products;
            } else {
                console.error('Error loading products:', data.message);
                return [];
            }
        })
        .catch(error => {
            console.error('Error loading products:', error);
            return [];
        });
}

function updateProducts(newProducts) {
    products = newProducts;
    localStorage.setItem('products', JSON.stringify(products));
}

function getImageUrl(imagePath) {
    if (!imagePath) return 'images/placeholder.jpg';
    return imagePath.startsWith('blob:') || imagePath.startsWith('data:') ? imagePath : imagePath;
}

async function loadProducts(container, start = 0, limit = 3) {
    if (products.length === 0) {
        products = await loadProductsFromServer();
    }

    const productsToShow = products.slice(start, start + limit);

    const productHTML = productsToShow.map(product => `
        <div class="product-card">
            <img src="${getImageUrl(product.image)}" alt="${product.title}" onerror="this.src='images/placeholder.jpg';">
            <div class="product-info">
                <h3>${product.title}</h3>
                <p class="price">₹${product.price}</p>
                <p class="description">${product.description}</p>
                ${product.colors && product.colors.length > 0 ? `
                    <p class="colors">Colors: ${product.colors.map(color => `
                        <span class="color-item">
                            <span class="color-name">${color.name}</span>
                            <img src="${getImageUrl(color.image)}" alt="${color.name}" class="color-image">
                        </span>
                    `).join('')}</p>
                ` : ''}
                <a href="#" class="btn btn-secondary">Add to Cart</a>
            </div>
        </div>
    `).join('');

    if (start === 0) {
        container.innerHTML = productHTML;
    } else {
        container.insertAdjacentHTML('beforeend', productHTML);
    }

    return products.length > (start + limit);
}

function loadMoreProducts(event) {
    event.preventDefault();
    const productGrid = document.querySelector('.product-grid');
    const currentProductCount = productGrid.children.length;
    loadProducts(productGrid, currentProductCount, 3).then(hasMoreProducts => {
        if (!hasMoreProducts) {
            event.target.style.display = 'none';
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const productGrid = document.querySelector('.product-grid');
    if (productGrid) {
        loadProducts(productGrid);
    }

    const moreProductsButton = document.querySelector('.more-products-button a');
    if (moreProductsButton) {
        moreProductsButton.addEventListener('click', loadMoreProducts);
    }
});