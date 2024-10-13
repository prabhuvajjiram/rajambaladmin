let products = [];
let currentProductCount = 0;
let cart = [];
let modal, modalImg, captionText;

function openModal(img) {
    modal.style.display = "block";
    modalImg.src = img.src;
    captionText.innerHTML = img.alt;
}

const lazyLoad = (selector, onIntersection) => {
    const elements = document.querySelectorAll(selector);
    if (elements.length === 0) return;

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                onIntersection(entry.target);
                observer.unobserve(entry.target);
            }
        });
    });

    elements.forEach(element => observer.observe(element));
};

document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('.header');
    const menuToggle = document.querySelector('.menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    const productGrid = document.querySelector('.product-grid');
    const contactForm = document.getElementById('contactForm');
    const moreProductsButton = document.querySelector('.more-products-button a');
    const cartToggle = document.querySelector('.cart-toggle');
    const cartMenu = document.querySelector('.cart-menu');
    const closeCart = document.querySelector('.close-cart');
    const mapContainer = document.querySelector('.map-container');
    const loadMapButton = mapContainer ? mapContainer.querySelector('.load-map-button') : null;

    modal = document.getElementById('imageModal');
    modalImg = document.getElementById('enlargedImage');
    captionText = document.getElementById('imageCaption');
    const closeBtn = document.getElementsByClassName('close')[0];

    window.addEventListener('scroll', () => {
        header.classList.toggle('scrolled', window.scrollY > 50);
    });

    if (mapContainer && loadMapButton) {
        loadMapButton.addEventListener('click', () => initializeMap(mapContainer));
    }

    if (menuToggle) {
        menuToggle.addEventListener('click', (e) => {
            e.preventDefault();
            document.body.classList.toggle('menu-open');
            mainNav.classList.toggle('active');
        });
    }

    mainNav.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            document.body.classList.remove('menu-open');
            mainNav.classList.remove('active');
        });
    });

    document.addEventListener('click', (event) => {
        if (!header.contains(event.target) && mainNav.classList.contains('active')) {
            document.body.classList.remove('menu-open');
            mainNav.classList.remove('active');
        }
    });

    if (closeBtn) {
        closeBtn.onclick = () => modal.style.display = "none";
    }

    window.onclick = (event) => {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    };

    if (cartToggle) {
        cartToggle.addEventListener('click', (e) => {
            e.preventDefault();
            cartMenu.classList.toggle('active');
        });
    }

    if (closeCart) {
        closeCart.addEventListener('click', () => {
            cartMenu.classList.remove('active');
        });
    }

    document.addEventListener('click', (event) => {
        if (cartMenu && cartToggle && !cartMenu.contains(event.target) && !cartToggle.contains(event.target)) {
            cartMenu.classList.remove('active');
        }
    });

    if (productGrid) {
        loadProducts(productGrid);
    }

    if (contactForm) {
        contactForm.addEventListener('submit', handleFormSubmit);
    }

    if (moreProductsButton) {
        moreProductsButton.addEventListener('click', loadMoreProducts);
    }

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            smoothScroll(this.getAttribute('href'), 1000);
        });
    });

    updateCartUI();

    lazyLoad('.video-container', initializeVideo);
    lazyLoad('.map-container', initializeMap);

    const savedCart = localStorage.getItem('cart');
    if (savedCart) {
        cart = JSON.parse(savedCart);
        updateCartUI();
    }

    const checkoutButton = document.getElementById('checkoutButton');
    if (checkoutButton) {
        checkoutButton.addEventListener('click', handleCheckout);
    }
});

function initializeVideo(videoContainer) {
    const playButton = videoContainer.querySelector('.play-button');
    if (!playButton) return;

    playButton.addEventListener('click', () => {
        const iframe = document.createElement('iframe');
        iframe.width = '100%';
        iframe.height = '100%';
        iframe.src = `${videoContainer.dataset.src}?autoplay=1`;
        iframe.frameBorder = '0';
        iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
        iframe.allowFullscreen = true;
        videoContainer.innerHTML = '';
        videoContainer.appendChild(iframe);
    });
}

function initializeMap(mapContainer) {
    const loadMapButton = mapContainer.querySelector('.load-map-button');
    if (!loadMapButton) return;

    const iframe = document.createElement('iframe');
    iframe.src = mapContainer.dataset.src;
    iframe.width = '100%';
    iframe.height = '300';
    iframe.style.border = '0';
    iframe.allowFullscreen = true;
    iframe.loading = 'lazy';
    mapContainer.innerHTML = '';
    mapContainer.appendChild(iframe);
    loadMapButton.style.display = 'none';
}

async function loadProductsFromServer(page = 1) {
    try {
        const response = await fetch(`get_products.php?page=${page}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const rawText = await response.text();
        
        let data;
        try {
            data = JSON.parse(rawText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.log('Problematic JSON string:', rawText);
            throw parseError;
        }
        
        if (data.debug_output) {
            console.log('PHP Debug Output:', data.debug_output);
        }
        
        if (data.status === 'error') {
            throw new Error(data.message);
        }
        
        if (!data.products || !Array.isArray(data.products)) {
            console.error('Invalid products data:', data);
            throw new Error('Invalid products data received from server');
        }
        
        return data;
    } catch (error) {
        console.error('Error loading products:', error);
        return { products: [], pagination: {} };
    }
}

async function loadProducts(container, start = 0, limit = 3) {
    if (products.length === 0) {
        const data = await loadProductsFromServer();
        if (data.status === 'error') {
            console.error('Error loading products:', data.message);
            container.innerHTML = `<p>Error loading products: ${data.message}</p>`;
            return false;
        }
        products = data.products || [];
    }

    if (!Array.isArray(products) || products.length === 0) {
        console.error('No products available or products is not an array');
        container.innerHTML = '<p>No products available at the moment. Please check back later.</p>';
        return false;
    }

    const productsToShow = products.slice(start, start + limit);

    if (productsToShow.length === 0) {
        if (start === 0) {
            container.innerHTML = '<p>No products available at the moment. Please check back later.</p>';
        }
        return false;
    }

    const productHTML = productsToShow.map(product => `
        <div class="product-card">
            <img src="${product.image}" alt="${product.title}" class="product-image" onerror="this.src='images/placeholder.jpg';">
            <div class="product-info">
                <h3>${product.title}</h3>
                <p class="price">₹${product.price}</p>
                <p class="description">${product.description}</p>
                <button class="btn btn-secondary add-to-cart" data-id="${product.id}">Add to Cart</button>
            </div>
        </div>
    `).join('');

    if (start === 0) {
        container.innerHTML = productHTML;
    } else {
        container.insertAdjacentHTML('beforeend', productHTML);
    }

    container.querySelectorAll('.product-image').forEach(img => {
        img.addEventListener('click', () => openModal(img));
    });

    container.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', (e) => {
            const productId = e.target.getAttribute('data-id');
            const product = products.find(p => p.id === productId);
            addToCart(product);
        });
    });

    currentProductCount = start + productsToShow.length;

    return products.length > currentProductCount;
}

function addToCart(product) {
    const existingItem = cart.find(item => item.id === product.id);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: product.id,
            title: product.title,
            price: parseFloat(product.price),
            quantity: 1
        });
    }
    
    updateCartUI();
    localStorage.setItem('cart', JSON.stringify(cart));
}

function updateCartUI() {
    const cartItems = document.querySelector('.cart-items');
    const cartCount = document.querySelector('.cart-count');
    const totalAmount = document.querySelector('.total-amount');
    const checkoutButton = document.getElementById('checkoutButton');
    
    if (!cartItems || !cartCount || !totalAmount || !checkoutButton) return;

    cartItems.innerHTML = '';
    let total = 0;
    
    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        
        cartItems.innerHTML += `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div>${item.title}</div>
                    <div>₹${item.price.toFixed(2)} x ${item.quantity}</div>
                </div>
                <div class="cart-item-actions">
                    <button class="cart-item-decrease" data-id="${item.id}">-</button>
                    <span class="cart-item-quantity">${item.quantity}</span>
                    <button class="cart-item-increase" data-id="${item.id}">+</button>
                    <button class="cart-item-remove" data-id="${item.id}">Remove</button>
                </div>
            </div>
        `;
    });
    
    cartCount.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
    totalAmount.textContent = `₹${total.toFixed(2)}`;
    
    // Update checkout button state
    if (cart.length === 0) {
        checkoutButton.disabled = true;
        checkoutButton.textContent = 'Cart is Empty';
    } else {
        checkoutButton.disabled = false;
        checkoutButton.textContent = 'Proceed to Checkout';
    }
    
    document.querySelectorAll('.cart-item-decrease').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            updateCartItemQuantity(button.dataset.id, -1);
        });
    });
    
    document.querySelectorAll('.cart-item-increase').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            updateCartItemQuantity(button.dataset.id, 1);
        });
    });
    
    document.querySelectorAll('.cart-item-remove').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            removeFromCart(button.dataset.id);
        });
    });
}

function updateCartItemQuantity(productId, change) {
    const item = cart.find(item => item.id === productId);
    if (item) {
        item.quantity += change;
        if (item.quantity < 1) {
            removeFromCart(productId);
        } else {
            updateCartUI();
            localStorage.setItem('cart', JSON.stringify(cart));
        }
    }
}

function removeFromCart(productId) {
    const index = cart.findIndex(item => item.id === productId);
    if (index !== -1) {
        cart.splice(index, 1);
        updateCartUI();
        localStorage.setItem('cart', JSON.stringify(cart));
    }
}

async function loadMoreProducts(event) {
    event.preventDefault();
    const productGrid = document.querySelector('.product-grid');
    const hasMoreProducts = await loadProducts(productGrid, currentProductCount, 3);

    if (!hasMoreProducts) {
        event.target.style.display = 'none';
    }
}

function handleFormSubmit(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const formMessage = document.getElementById('formMessage');
    
    fetch('send_email.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        formMessage.textContent = data.message;
        formMessage.style.display = 'block';
        formMessage.style.color = data.status === 'success' ? 'green' : 'red';
        
        if (data.status === 'success') {
            event.target.reset();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        formMessage.textContent = 'An error occurred. Please try again later.';
        formMessage.style.display = 'block';
        formMessage.style.color = 'red';
    });
}

function smoothScroll(target, duration) {
    const targetElement = document.querySelector(target);
    const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset;
    const startPosition = window.pageYOffset;
    const distance = targetPosition - startPosition;
    let startTime = null;

    function animation(currentTime) {
        if (startTime === null) startTime = currentTime;
        const timeElapsed = currentTime - startTime;
        const run = ease(timeElapsed, startPosition, distance, duration);
        window.scrollTo(0, run);
        if (timeElapsed < duration) requestAnimationFrame(animation);
    }

    function ease(t, b, c, d) {
        t /= d / 2;
        if (t < 1) return c / 2 * t * t + b;
        t--;
        return -c / 2 * (t * (t - 2) - 1) + b;
    }

    requestAnimationFrame(animation);
}

function handleCheckout() {
    if (cart.length === 0) {
        alert("Your cart is empty. Add some items before proceeding to checkout.");
    } else {
        // Proceed to checkout page
        window.location.href = 'checkout.php';
    }
}