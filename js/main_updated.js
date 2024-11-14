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
    
    // Store the original product image
    const originalImage = product.image_path;
    
    // Only create color options if the product has colors
    let colorOptionsHtml = '';
    if (product.colors && product.colors.length > 0) {
        colorOptionsHtml = `
            <div class="color-options">
                <div class="color-option original selected" 
                     data-color-name="original"
                     data-color-image="${originalImage}"
                     title="Original">
                    <img src="${originalImage}" 
                         alt="Original" 
                         onerror="this.onerror=null; this.src='images/placeholder.jpg'">
                </div>
                ${product.colors.map(color => `
                    <div class="color-option" 
                         data-color-name="${color.name}"
                         data-color-image="${color.image_path}"
                         title="${color.name}">
                        <img src="${color.image_path}" 
                             alt="${color.name}" 
                             onerror="this.onerror=null; this.src='images/placeholder.jpg'">
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    // Create the basic product card structure
    productCard.innerHTML = `
        <div class="product-image-container">
            <div class="product-image">
                <img src="${originalImage}" 
                     alt="${product.title}" 
                     class="main-product-image" 
                     data-original-image="${originalImage}"
                     onerror="this.onerror=null; this.src='images/placeholder.jpg'">
                ${product.additional_images && product.additional_images.length > 0 ? `
                    <div class="image-navigation">
                        <button class="nav-arrow prev">❮</button>
                        <button class="nav-arrow next">❯</button>
                    </div>
                ` : ''}
            </div>
        </div>
        <h3>${product.title}</h3>
        <p class="price">₹${product.price}</p>
        ${colorOptionsHtml}
        <button class="add-to-cart" data-product-id="${product.id}">
            <i class="material-icons">shopping_cart</i>
            Add to Cart
        </button>
    `;

    // Handle color option clicks if colors exist
    const colorOptions = productCard.querySelectorAll('.color-option');
    const mainImage = productCard.querySelector('.main-product-image');
    let selectedColor = null;
    
    if (colorOptions.length > 0) {
        colorOptions.forEach(option => {
            option.addEventListener('click', function() {
                const colorName = this.dataset.colorName;
                const colorImage = this.dataset.colorImage;
                
                // Update selected state
                colorOptions.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                selectedColor = colorName === 'original' ? null : colorName;
                
                // Update main image with color image
                if (mainImage && colorImage) {
                    mainImage.src = colorImage;
                    product.current_image = colorImage;
                }
            });
        });
    }

    // Add to cart button
    const addToCartBtn = productCard.querySelector('.add-to-cart');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', () => {
            const selectedColorOption = productCard.querySelector('.color-option.selected');
            const hasColorOptions = product.colors && product.colors.length > 0;
            
            // Get the current displayed image
            const currentDisplayedImage = productCard.querySelector('.main-product-image').src;
            
            // If product has colors available
            if (hasColorOptions) {
                const colorName = selectedColorOption ? selectedColorOption.dataset.colorName : null;
                const colorImage = selectedColorOption ? selectedColorOption.dataset.colorImage : null;
                
                // Use selected color image or current displayed image
                product.current_image = colorImage || currentDisplayedImage;
                
                addToCart(product, colorName === 'original' ? null : colorName);
            } else {
                // Product has no color options, use default image
                product.current_image = product.image_path;
                addToCart(product, null);
            }
        });
    }

    // Set up image preview and navigation
    if (product.additional_images && product.additional_images.length > 0) {
        const prevBtn = productCard.querySelector('.nav-arrow.prev');
        const nextBtn = productCard.querySelector('.nav-arrow.next');
        let currentIndex = 0;
        const images = [originalImage, ...product.additional_images];

        if (prevBtn && nextBtn) {
            prevBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                currentIndex = (currentIndex - 1 + images.length) % images.length;
                mainImage.src = images[currentIndex];
            });

            nextBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                currentIndex = (currentIndex + 1) % images.length;
                mainImage.src = images[currentIndex];
            });
        }
    }

    return productCard;
}

function addToCart(product, colorName = null, currentImage = null) {
    console.log('Adding to cart:', { 
        productId: product.id, 
        colorName, 
        current_image: currentImage,
        original_image: product.image_path
    });
    
    // Get existing cart or initialize new one
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Find if this product with the same color already exists in cart
    const existingItemIndex = cart.findIndex(item => 
        item.id === product.id && 
        (!colorName || item.selectedColor === colorName)
    );
    
    if (existingItemIndex !== -1) {
        // Update quantity if item exists
        cart[existingItemIndex].quantity += 1;
        console.log('Updated existing cart item quantity');
    } else {
        // Add new item if it doesn't exist
        const cartItem = {
            id: product.id,
            title: product.title,
            price: product.price,
            image: currentImage || product.image_path,
            selectedColor: colorName,
            quantity: 1
        };
        cart.push(cartItem);
        console.log('Added new item to cart:', cartItem);
    }
    
    // Save updated cart
    localStorage.setItem('cart', JSON.stringify(cart));
    console.log('Updated cart in localStorage:', cart);
    
    // Ensure cart button is visible and update UI
    ensureCartButtonVisibility();
    updateCartUI();
    
    // Show success message
    showNotification('Product added to cart!');
}

function setupImagePreview(productCard, product) {
    // Fetch additional images
    fetch(`get_product.php?id=${product.id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.product) {
                const allImages = [product.image_path];
                if (data.product.additional_images && data.product.additional_images.length > 0) {
                    allImages.push(...data.product.additional_images);
                }

                const mainImage = productCard.querySelector('.main-product-image');
                const navigation = productCard.querySelector('.image-navigation');
                const prevBtn = productCard.querySelector('.nav-arrow.prev');
                const nextBtn = productCard.querySelector('.nav-arrow.next');

                // Only show navigation if we have multiple images
                if (allImages.length <= 1) {
                    navigation.style.display = 'none';
                }

                let currentImageIndex = 0;

                function updateImage() {
                    mainImage.src = allImages[currentImageIndex];
                    console.log('Updated main image to:', mainImage.src);
                }

                if (prevBtn && nextBtn && allImages.length > 1) {
                    prevBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        currentImageIndex = (currentImageIndex - 1 + allImages.length) % allImages.length;
                        updateImage();
                    });

                    nextBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        currentImageIndex = (currentImageIndex + 1) % allImages.length;
                        updateImage();
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error loading additional images:', error);
            const navigation = productCard.querySelector('.image-navigation');
            navigation.style.display = 'none';
        });
}

function displayProductDetails(product) {
    const productDetails = document.querySelector('.product-details');
    productDetails.innerHTML = `
        <div id="productImageSlider" class="product-image-container"></div>
        <div class="product-info">
            <h2 class="product-title">${product.title}</h2>
            <p class="product-description">${product.description}</p>
            <p class="product-price">₹${product.price}</p>
            <div class="color-options"></div>
        </div>
    `;

    // Initialize image slider with main image and additional images
    const sliderImages = {
        main: product.image_path,
        additional: [...(product.additional_images || []), ...(product.colors?.map(color => color.image) || [])]
    };

    // Initialize new slider
    new ProductImageSlider('productImageSlider', sliderImages, {
        autoplay: true,
        interval: 10000,
        showArrows: true,
        showDots: true
    });

    // Display color options
    const colorOptions = productDetails.querySelector('.color-options');
    if (product.colors && product.colors.length > 0) {
        colorOptions.innerHTML = `
            <h3>Available Colors</h3>
            <div class="color-list">
                ${product.colors.map(color => `
                    <div class="color-option" data-color-image="${color.image}">
                        <img src="${color.image}" alt="${color.name}" title="${color.name}">
                        <span>${color.name}</span>
                    </div>
                `).join('')}
            </div>
        `;

        // Add click handlers for color options
        const colorOptionElements = colorOptions.querySelectorAll('.color-option');
        colorOptionElements.forEach(option => {
            option.onclick = () => {
                const colorImage = option.dataset.colorImage;
                const slider = document.querySelector('.product-slider__image img');
                if (slider) {
                    slider.src = colorImage;
                }
            };
        });
    }

    productDetails.style.display = 'flex';
    productDetails.scrollIntoView({ behavior: 'smooth' });
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function updateCartCount() {
    const cartCount = document.getElementById('cartCount');
    if (cartCount) {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
        cartCount.textContent = totalItems;
    }
}

function updateCartUI() {
    const cartCount = document.getElementById('cartCount');
    const cartItems = document.querySelector('.cart-items');
    const cartTotal = document.querySelector('.cart-total .total-amount');
    
    // Update cart count
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    if (cartCount) {
        const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
        cartCount.textContent = totalItems;
    }
    
    // Update cart items
    if (cartItems && cartTotal) {
        cartItems.innerHTML = '';
        let total = 0;
        
        cart.forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            const itemElement = document.createElement('div');
            itemElement.className = 'cart-item';
            itemElement.innerHTML = `
                <div class="cart-item-image">
                    <img src="${item.image}" alt="${item.title}" onerror="this.onerror=null; this.src='images/placeholder.jpg'">
                </div>
                <div class="cart-item-details">
                    <h4>${item.title}</h4>
                    ${item.selectedColor ? `<p class="color-name">Color: ${item.selectedColor}</p>` : ''}
                    <p class="price">Price: ₹${item.price}</p>
                    <p class="item-total">Total: ₹${itemTotal.toFixed(2)}</p>
                    <div class="quantity-controls">
                        <button class="quantity-btn minus" data-index="${index}">-</button>
                        <span class="quantity">${item.quantity}</span>
                        <button class="quantity-btn plus" data-index="${index}">+</button>
                    </div>
                </div>
                <button class="remove-item" data-index="${index}">&times;</button>
            `;
            cartItems.appendChild(itemElement);
            total += itemTotal;
        });
        
        // Update total
        cartTotal.textContent = `₹${total.toFixed(2)}`;
        
        // Add event listeners for quantity controls and remove buttons
        cartItems.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('click', handleQuantityChange);
        });
        
        cartItems.querySelectorAll('.remove-item').forEach(btn => {
            btn.addEventListener('click', handleRemoveItem);
        });
    }
}

function handleQuantityChange(event) {
    const index = parseInt(event.target.dataset.index);
    const isIncrease = event.target.classList.contains('plus');
    
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const item = cart[index];
    
    if (isIncrease) {
        item.quantity += 1;
    } else {
        item.quantity = Math.max(1, item.quantity - 1);
    }
    
    // Update item total in UI immediately
    const itemDetails = event.target.closest('.cart-item-details');
    if (itemDetails) {
        const itemTotal = itemDetails.querySelector('.item-total');
        const total = item.price * item.quantity;
        if (itemTotal) {
            itemTotal.textContent = `Total: ₹${total.toFixed(2)}`;
        }
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartUI();
}

function handleRemoveItem(event) {
    const index = parseInt(event.target.dataset.index);
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    cart.splice(index, 1);
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartUI();
}

function toggleCartMenu() {
    console.log('Toggling cart menu');
    const cartMenu = document.querySelector('.cart-menu');
    if (cartMenu) {
        cartMenu.classList.toggle('active');
        updateCartUI();
    }
}

function setupCheckoutButton() {
    const checkoutBtn = document.getElementById('checkoutButton');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function(event) {
            event.preventDefault();
            console.log('Checkout button clicked');
            window.location.href = 'checkout.html';
        });
        console.log('Checkout button handler added');
    } else {
        console.error('Checkout button not found');
    }
}

function ensureCartButtonVisibility() {
    const cartToggle = document.querySelector('.cart-toggle');
    if (cartToggle) {
        cartToggle.style.display = 'flex';
        updateCartCount();
    }
}

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
                            ensureCartButtonVisibility();
                        }, 300);
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

    // Initial cart setup
    ensureCartButtonVisibility();
    
    // Add cart toggle functionality
    const cartToggle = document.querySelector('.cart-toggle');
    if (cartToggle) {
        cartToggle.addEventListener('click', toggleCartMenu);
        console.log('Cart toggle listener added');
    }
    
    // Add close cart button functionality
    const closeCartBtn = document.querySelector('.close-cart');
    if (closeCartBtn) {
        closeCartBtn.addEventListener('click', toggleCartMenu);
        console.log('Close cart button listener added');
    }

    fetchProducts();
    
    const moreProductsBtn = document.getElementById('moreProductsBtn');
    if (moreProductsBtn) {
        moreProductsBtn.addEventListener('click', displayProducts);
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
    
    // Setup checkout button
    setupCheckoutButton();
    
    // Initial cart UI update
    updateCartUI();
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

function loadProducts(page = 1) {
    console.log('Loading products page:', page);
    fetch('get_products.php')
        .then(response => response.json())
        .then(data => {
            console.log('Products data:', data);
            if (data.status === 'success') {
                const productsContainer = document.getElementById('products-container');
                if (productsContainer) {
                    data.products.forEach(product => {
                        console.log('Creating card for product:', product);
                        const productCard = createProductCard(product);
                        productsContainer.appendChild(productCard);
                    });
                } else {
                    console.error('Products container not found');
                }
            } else {
                console.error('Error loading products:', data.message);
            }
        })
        .catch(error => console.error('Error:', error));
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
