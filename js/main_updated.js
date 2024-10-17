let allProducts = [];
let displayedProducts = 0;
const productsPerPage = 4;
let cart = [];

window.addEventListener('error', function(event) {
    // In production, you might want to send this to a logging service
    // instead of logging to console
});

async function safeFetch(url, options) {
    try {
        const response = await fetch(url, options);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return await response.json();
    } catch (error) {
        // Handle error gracefully (e.g., show error message to user)
    }
}

function fetchProducts() {
    safeFetch('get_products.php')
        .then(data => {
            if (data.status === 'success') {
                allProducts = data.products;
                displayProducts();
            } else {
                // Handle error gracefully
            }
        })
        .catch(error => {
            // Handle error gracefully
        });
}

function displayProducts() {
    const productGrid = document.querySelector('.product-grid');
    const endIndex = Math.min(displayedProducts + productsPerPage, allProducts.length);
    
    for (let i = displayedProducts; i < endIndex; i++) {
        const product = allProducts[i];
        const productCard = createProductCard(product);
        productGrid.appendChild(productCard);
    }
    
    displayedProducts = endIndex;
    
    const moreProductsBtn = document.getElementById('moreProductsBtn');
    if (displayedProducts >= allProducts.length) {
        moreProductsBtn.style.display = 'none';
    } else {
        moreProductsBtn.style.display = 'inline-block';
    }
}

function createProductCard(product) {
    const productCard = document.createElement('div');
    productCard.className = 'product-card';
    
    let colorOptionsHtml = '<div class="color-options">';
    colorOptionsHtml += `
        <div class="color-option selected" data-color-name="Original" data-color-image="${product.image_path}">
            <img src="${product.image_path}" alt="Original" title="Original">
        </div>
    `;
    if (product.colors && product.colors.length > 0) {
        product.colors.forEach(color => {
            colorOptionsHtml += `
                <div class="color-option" data-color-name="${color.name}" data-color-image="${color.image_path}">
                    <img src="${color.image_path}" alt="${color.name}" title="${color.name}">
                </div>
            `;
        });
    }
    colorOptionsHtml += '</div>';

    productCard.innerHTML = `
        <div class="product-image">
            <img src="${product.image_path}" alt="${product.title}" class="main-product-image">
        </div>
        <h3>${product.title}</h3>
        <p class="price">₹${product.price}</p>
        ${colorOptionsHtml}
        <button class="add-to-cart" data-product-id="${product.id}">Add to Cart</button>
    `;

    const colorOptions = productCard.querySelectorAll('.color-option');
    colorOptions.forEach(option => {
        option.addEventListener('click', function() {
            const mainImage = productCard.querySelector('.main-product-image');
            mainImage.src = this.dataset.colorImage;
            colorOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
        });
    });

    const addToCartBtn = productCard.querySelector('.add-to-cart');
    addToCartBtn.addEventListener('click', () => {
        const selectedColor = productCard.querySelector('.color-option.selected');
        addToCart(product, selectedColor);
    });

    const productImage = productCard.querySelector('.main-product-image');
    productImage.addEventListener('click', () => openImageModal(product.image_path, product.title));

    return productCard;
}

function addToCart(product, selectedColor) {
    const colorName = selectedColor ? selectedColor.dataset.colorName : 'Original';
    const colorImage = selectedColor ? selectedColor.dataset.colorImage : product.image_path;

    const itemId = `${product.id}-${colorName}`;

    const existingItemIndex = cart.findIndex(item => item.itemId === itemId);

    if (existingItemIndex !== -1) {
        cart[existingItemIndex].quantity += 1;
    } else {
        cart.push({
            itemId: itemId,
            id: product.id,
            title: product.title,
            price: product.price,
            color: colorName,
            image: colorImage,
            quantity: 1
        });
    }

    updateCartUI();
    saveCartToLocalStorage();
}

function updateCartUI() {
    const cartCount = document.getElementById('cartCount');
    if (cartCount) {
        const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
        cartCount.textContent = totalItems;
    }
    updateCartMenu();
}

function updateCartMenu() {
    const cartItems = document.querySelector('.cart-items');
    const cartTotal = document.querySelector('.total-amount');
    const checkoutButton = document.getElementById('checkoutButton');
    
    if (cartItems && cartTotal) {
        cartItems.innerHTML = '';
        let total = 0;

        cart.forEach((item, index) => {
            const itemElement = document.createElement('div');
            itemElement.className = 'cart-item';
            itemElement.innerHTML = `
                <img src="${item.image}" alt="${item.title}">
                <div class="cart-item-details">
                    <h4>${item.title}</h4>
                    <p>Color: ${item.color}</p>
                    <p>Price: ₹${item.price}</p>
                    <div class="quantity-control">
                        <button class="quantity-btn minus" data-index="${index}">-</button>
                        <span class="quantity">${item.quantity}</span>
                        <button class="quantity-btn plus" data-index="${index}">+</button>
                    </div>
                </div>
                <button class="remove-item" data-index="${index}">&times;</button>
            `;
            cartItems.appendChild(itemElement);
            total += item.price * item.quantity;
        });

        cartTotal.textContent = `₹${total.toFixed(2)}`;

        if (cart.length > 0) {
            checkoutButton.classList.remove('disabled');
            checkoutButton.removeAttribute('disabled');
        } else {
            checkoutButton.classList.add('disabled');
            checkoutButton.setAttribute('disabled', 'disabled');
        }

        document.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('click', adjustQuantity);
        });
        document.querySelectorAll('.remove-item').forEach(btn => {
            btn.addEventListener('click', removeItem);
        });
    }
}

function adjustQuantity(event) {
    const index = event.target.dataset.index;
    const isPlus = event.target.classList.contains('plus');
    
    if (isPlus) {
        cart[index].quantity += 1;
    } else {
        cart[index].quantity = Math.max(0, cart[index].quantity - 1);
        if (cart[index].quantity === 0) {
            cart.splice(index, 1);
        }
    }
    
    updateCartUI();
    saveCartToLocalStorage();
}

function removeItem(event) {
    const index = event.target.dataset.index;
    cart.splice(index, 1);
    updateCartUI();
    saveCartToLocalStorage();
}

function saveCartToLocalStorage() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

function loadCartFromLocalStorage() {
    const savedCart = localStorage.getItem('cart');
    if (savedCart) {
        cart = JSON.parse(savedCart);
        updateCartUI();
    }
}

function openImageModal(imageSrc, imageAlt) {
    const modal = document.createElement('div');
    modal.className = 'image-modal';
    modal.innerHTML = `
        <div class="modal-content">
            <img src="${imageSrc}" alt="${imageAlt}">
            <button class="close-modal">&times;</button>
        </div>
    `;

    document.body.appendChild(modal);

    const closeButton = modal.querySelector('.close-modal');
    closeButton.addEventListener('click', () => {
        document.body.removeChild(modal);
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            document.body.removeChild(modal);
        }
    });
}

function lazyLoadProducts() {
    const options = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const productCard = entry.target;
                const img = productCard.querySelector('img');
                img.src = img.dataset.src;
                img.classList.add('loaded');
                observer.unobserve(productCard);
            }
        });
    }, options);

    document.querySelectorAll('.product-card').forEach(card => {
        observer.observe(card);
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

const searchProducts = debounce(() => {
    // Implement search functionality here
}, 300);

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM fully loaded and parsed');

    // Hamburger menu functionality
    const menuToggle = document.querySelector('.menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    

    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            toggleMenu();
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!mainNav.contains(event.target) && !menuToggle.contains(event.target)) {
                closeMenu();
            }
        });

        // Handle menu item clicks
        mainNav.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                
                if (href.startsWith('#')) {
                    // For hash links (internal page links)
                    e.preventDefault();
                    closeMenu();
                    const targetElement = document.querySelector(href);
                    if (targetElement) {
                        setTimeout(() => {
                            targetElement.scrollIntoView({ behavior: 'smooth' });
                        }, 300); // Delay to allow menu to close
                    }
                } else {
                    // For external links, just close the menu
                    closeMenu();
                }
            });
        });

        // Prevent clicks inside the menu from closing it
        mainNav.addEventListener('click', function(event) {
            event.stopPropagation();
        });
    } else {
        console.error('Menu toggle or main nav not found');
    }

    fetchProducts();
    loadCartFromLocalStorage();
    
    const moreProductsBtn = document.getElementById('moreProductsBtn');
    if (moreProductsBtn) {
        moreProductsBtn.addEventListener('click', displayProducts);
    }

    const cartToggle = document.querySelector('.cart-toggle');
    if (cartToggle) {
        cartToggle.addEventListener('click', toggleCartMenu);
    }

    const closeCartBtn = document.querySelector('.close-cart');
    if (closeCartBtn) {
        closeCartBtn.addEventListener('click', toggleCartMenu);
    }
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', submitContactForm);
    } else {
        console.log('Contact form not found on this page');
    }

    const searchInput = document.querySelector('#searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', searchProducts);
    } else {
        console.log('Search input not found on this page');
    }

    const productForm = document.getElementById('productForm');
    if (productForm) {
        productForm.addEventListener('submit', submitProductForm);
    } else {
        console.log('Product form not found on this page');
    }
});

function toggleMenu() {
    const mainNav = document.querySelector('.main-nav');
    document.body.classList.toggle('menu-open');
    mainNav.classList.toggle('active');
    console.log('Menu toggled');
}

function closeMenu() {
    const mainNav = document.querySelector('.main-nav');
    document.body.classList.remove('menu-open');
    mainNav.classList.remove('active');
    console.log('Menu closed');
}

function toggleCartMenu() {
    const cartMenu = document.querySelector('.cart-menu');
    if (cartMenu) {
        cartMenu.classList.toggle('active');
        updateCartMenu();
    }
}

function submitProductForm(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);

    fetch('upload_product.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Product uploaded successfully
            alert(data.message);
            // Optionally, reset the form or redirect to a product list page
            form.reset();
        } else {
            // There was an error
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An unexpected error occurred. Please try again.');
    });
}

function submitContactForm(event) {
    event.preventDefault();
    console.log('Contact form submitted');

    const formData = new FormData(event.target);
    const formMessage = document.getElementById('formMessage');
    
    fetch('send_email.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response received:', response);
        return response.json();
    })
    .then(data => {
        console.log('Data received:', data);
        formMessage.textContent = data.message;
        formMessage.style.display = 'block';
        formMessage.style.color = data.status === 'success' ? 'green' : 'red';
        
        if (data.status === 'success') {
            event.target.reset();
        } else {
            console.error('Error details:', data.error);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        formMessage.textContent = 'An error occurred. Please try again later.';
        formMessage.style.display = 'block';
        formMessage.style.color = 'red';
    });
}


