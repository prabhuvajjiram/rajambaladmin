document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded event fired');
    console.log('Current URL:', window.location.href);
    console.log('Current pathname:', window.location.pathname);

    // Only run checkout page specific code if we're on the checkout page
    if (window.location.pathname.includes('checkout')) {
        console.log('On checkout page, initializing checkout functionality');
        initCheckoutPage();
    } else {
        console.log('Not on checkout page, skipping checkout initialization');
    }
});

function initCheckoutPage() {
    const checkoutProductCards = document.getElementById('checkoutProductCards');
    const totalPriceElement = document.getElementById('totalPrice');
    const checkoutButton = document.getElementById('checkoutButton');

    // Check if we have the necessary elements
    if (!checkoutProductCards || !totalPriceElement) {
        console.log('Checkout page elements not found, skipping initialization');
        return;
    }

    function loadCartFromLocalStorage() {
        try {
            const savedCart = localStorage.getItem('cart');
            return savedCart ? JSON.parse(savedCart) : [];
        } catch (error) {
            console.error('Error loading cart from localStorage:', error);
            return [];
        }
    }

    function displayCheckoutItems() {
        const cart = loadCartFromLocalStorage();
        let totalPrice = 0;

        if (!checkoutProductCards) {
            console.error('Checkout product cards container not found');
            return;
        }

        checkoutProductCards.innerHTML = '';

        if (cart.length === 0) {
            checkoutProductCards.innerHTML = '<p>Your cart is empty.</p>';
            if (totalPriceElement) {
                totalPriceElement.textContent = 'Total: ₹0.00';
            }
            return;
        }

        cart.forEach(item => {
            const itemElement = document.createElement('div');
            itemElement.className = 'checkout-item';
            itemElement.innerHTML = `
                <div class="checkout-item-image">
                    <img src="${item.image}" alt="${item.title}" onerror="this.onerror=null; this.src='images/placeholder.jpg';">
                </div>
                <div class="checkout-item-details">
                    <h3>${item.title}</h3>
                    ${item.selectedColor ? `<p>Color: ${item.selectedColor}</p>` : ''}
                    <p>Quantity: ${item.quantity}</p>
                    <p>Price: ₹${(item.price * item.quantity).toFixed(2)}</p>
                </div>
            `;
            checkoutProductCards.appendChild(itemElement);

            totalPrice += item.price * item.quantity;
        });

        if (totalPriceElement) {
            totalPriceElement.textContent = `Total: ₹${totalPrice.toFixed(2)}`;
        }
    }

    // Initialize checkout page
    displayCheckoutItems();

    // Setup checkout button if it exists
    if (checkoutButton) {
        checkoutButton.addEventListener('click', function() {
            // Get cart items and total price
            const cart = loadCartFromLocalStorage();
            const totalPrice = totalPriceElement ? totalPriceElement.textContent : '₹0.00';
            
            // Prepare the message
            const message = `New order details:\n\n${cart.map(item => 
                `${item.title}${item.selectedColor ? ` - Color: ${item.selectedColor}` : ''} - Quantity: ${item.quantity} - Price: ₹${item.price}`
            ).join('\n')}\n\n${totalPrice}`;

            // Prepare the data to send
            const data = new FormData();
            data.append('name', 'Customer');
            data.append('email', 'sales@rajambalcottons.com');
            data.append('subject', 'New Order from Rajambal Cottons');
            data.append('message', message);

            // Send the email
            fetch('send_email.php', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    alert('Thank you for your purchase! Our website is currently under construction. To complete your purchase, please call our store.');
                    localStorage.removeItem('cart');
                    window.location.href = 'index.html';
                } else {
                    alert('There was an error processing your order. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('There was an error processing your order. Please try again.');
            });
        });
    }
}
