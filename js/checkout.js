document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded event fired');
    console.log('Current URL:', window.location.href);
    console.log('Current pathname:', window.location.pathname);

    if (window.location.pathname.endsWith('checkout.html') || window.location.pathname.endsWith('checkout')) {
        console.log('On checkout page, displaying products');
        displayCheckoutProducts();
    } else {
        console.log('On main page, setting up checkout button');
        setupCheckoutButton();
    }
});

function setupCheckoutButton() {
    const checkoutBtn = document.querySelector('#checkoutButton');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function(event) {
            event.preventDefault();
            console.log('Checkout button clicked');
            try {
                window.location.href = 'checkout.html';
            } catch (error) {
                console.error('Error navigating to checkout page:', error);
            }
        });
        console.log('Checkout button set up on main page');
    } else {
        console.error('Checkout button not found on main page');
        console.log('HTML of body:', document.body.innerHTML);
    }
}

function displayCheckoutProducts() {
    console.log('displayCheckoutProducts function called');
    
    const checkoutProductCards = document.getElementById('checkoutProductCards');
    const totalPriceElement = document.getElementById('totalPrice');

    if (!checkoutProductCards || !totalPriceElement) {
        console.error('Required elements not found in the DOM');
        return;
    }

    let cart = [];
    try {
        cart = JSON.parse(localStorage.getItem('cart')) || [];
    } catch (error) {
        console.error('Error parsing cart from localStorage:', error);
    }
    console.log('Cart contents:', cart);

    let totalPrice = 0;

    if (cart.length === 0) {
        checkoutProductCards.innerHTML = '<p>Your cart is empty.</p>';
        totalPriceElement.textContent = 'Total: ₹0';
        return;
    }

    let cardsHTML = '';
    cart.forEach((product, index) => {
        console.log(`Processing product ${index}:`, product);
        cardsHTML += `
            <div class="product-card">
                <img src="${product.image}" alt="${product.title}" 
                     onerror="this.onerror=null; this.src='images/placeholder.jpg';">
                <h3>${product.title}</h3>
                <p>Price: ₹${product.price}</p>
                <p>Quantity: ${product.quantity}</p>
            </div>
        `;
        totalPrice += parseFloat(product.price) * product.quantity;
    });

    checkoutProductCards.innerHTML = cardsHTML;
    totalPriceElement.textContent = `Total: ₹${totalPrice.toFixed(2)}`;

    // Add event listeners to all product images
    checkoutProductCards.querySelectorAll('img').forEach(img => {
        img.addEventListener('error', function() {
            this.onerror = null; // Prevent infinite loop
            this.src = 'images/placeholder.jpg';
        });
    });

    console.log('Updated checkoutProductCards innerHTML');
    console.log('Updated totalPriceElement textContent');
}

function setupCompleteCheckoutButton() {
    const checkoutButton = document.getElementById('checkoutButton');
    console.log('checkoutButton element:', checkoutButton);

    if (checkoutButton) {
        checkoutButton.addEventListener('click', function() {
            console.log('Complete purchase button clicked');
            // Implement checkout logic here
            alert('Thank you for your purchase!');
            localStorage.removeItem('cart');
            console.log('Cart cleared from localStorage');
            displayCheckoutProducts(); // Refresh the display
        });
    } else {
        console.error('Complete checkout button not found');
    }
}

// Add this line to make the functions globally accessible
window.setupCheckoutButton = setupCheckoutButton;
window.displayCheckoutProducts = displayCheckoutProducts;
window.setupCompleteCheckoutButton = setupCompleteCheckoutButton;