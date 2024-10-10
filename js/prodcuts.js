document.addEventListener('DOMContentLoaded', () => {
    const allProductsGrid = document.getElementById('allProductsGrid');
    
    function loadAllProducts() {
        if (allProductsGrid) {
            allProductsGrid.innerHTML = '';
            products.forEach(product => {
                const productCard = createProductCard(product);
                allProductsGrid.appendChild(productCard);
            });
        } else {
            console.warn('All products grid element not found');
        }
    }

    function createProductCard(product) {
        const card = document.createElement('div');
        card.className = 'product-card';
        card.innerHTML = `
            <img src="${product.image}" alt="${product.title}">
            <div class="product-info">
                <h3>${product.title}</h3>
                <p class="price">₹${product.price}</p>
                <a href="#" class="btn btn-secondary">Add to Cart</a>
            </div>
        `;
        return card;
    }

    loadAllProducts();
});