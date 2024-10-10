let products = [];

// Function to load products from the server
async function loadProductsFromServer() {
    try {
        const response = await fetch('get_products.php');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error loading products:', error);
        return [];
    }
}

// Function to get image URL
function getImageUrl(imagePath) {
    if (!imagePath) return 'images/placeholder.jpg';
    return imagePath.startsWith('blob:') || imagePath.startsWith('data:') ? imagePath : imagePath;
}

// Function to load products
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
                <a href="#" class="btn btn-secondary">Add to Cart</a>
            </div>
        </div>
    `).join('');

    container.insertAdjacentHTML('beforeend', productHTML);

    return products.length > (start + limit);
}

// Function to load more products
async function loadMoreProducts(event) {
    event.preventDefault();
    const productGrid = document.querySelector('.product-grid');
    const currentProductCount = productGrid.children.length;
    const hasMoreProducts = await loadProducts(productGrid, currentProductCount, 6);

    if (!hasMoreProducts) {
        event.target.style.display = 'none';
    }
}

// Initialize product display when the DOM is loaded
document.addEventListener('DOMContentLoaded', async () => {
    const productGrid = document.querySelector('.product-grid');
    if (productGrid) {
        await loadProducts(productGrid, 0, 3); // Initially load only 3 products
    }

    const moreProductsButton = document.querySelector('.more-products-button a');
    if (moreProductsButton) {
        moreProductsButton.addEventListener('click', loadMoreProducts);
    }
});