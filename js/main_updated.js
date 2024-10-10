let products = [];
let currentProductCount = 0;
let cart = [];

// Modal elements
let modal, modalImg, captionText;

function openModal(img) {
    modal.style.display = "block";
    modalImg.src = img.src;
    captionText.innerHTML = img.alt;
}

// Lazy load function
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

    // Cart elements
    const cartToggle = document.querySelector('.cart-toggle');
    const cartMenu = document.querySelector('.cart-menu');
    const closeCart = document.querySelector('.close-cart');

  // Map elements
    const mapContainer = document.querySelector('.map-container');
    const loadMapButton = mapContainer ? mapContainer.querySelector('.load-map-button') : null;

    // Initialize modal elements
    modal = document.getElementById('imageModal');
    modalImg = document.getElementById('enlargedImage');
    captionText = document.getElementById('imageCaption');
    const closeBtn = document.getElementsByClassName('close')[0];

    // Scroll behavior
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // Initialize map
    if (mapContainer && loadMapButton) {
        loadMapButton.addEventListener('click', () => {
            const iframe = document.createElement('iframe');
            iframe.setAttribute('src', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3890.0647748982713!2d79.70246931482038!3d12.835543990947!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3a52c1df7e7c2c6d%3A0x5c0d8d688e5aea9f!2s37%2C%20Mettu%20St%2C%20Ennaikkaran%2C%20Kanchipuram%2C%20Tamil%20Nadu%20631501%2C%20India!5e0!3m2!1sen!2sus!4v1623344120978!5m2!1sen!2sus');
            iframe.setAttribute('width', '100%');
            iframe.setAttribute('height', '300');
            iframe.setAttribute('style', 'border:0;');
            iframe.setAttribute('allowfullscreen', '');
            iframe.setAttribute('loading', 'lazy');
            
            // Clear the existing content and append the iframe
            mapContainer.innerHTML = '';
            mapContainer.appendChild(iframe);
            
            // Hide the button after loading the map
            loadMapButton.style.display = 'none';
        });
    }

    // Mobile menu toggle
    if (menuToggle) {
        menuToggle.addEventListener('click', (e) => {
            e.preventDefault();
            document.body.classList.toggle('menu-open');
            mainNav.classList.toggle('active');
        });
    }

    // Close mobile menu when a nav item is clicked
    mainNav.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            document.body.classList.remove('menu-open');
            mainNav.classList.remove('active');
        });
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', (event) => {
        if (!header.contains(event.target) && mainNav.classList.contains('active')) {
            document.body.classList.remove('menu-open');
            mainNav.classList.remove('active');
        }
    });

    // Modal close button
    if (closeBtn) {
        closeBtn.onclick = function() {
            modal.style.display = "none";
        }
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Cart toggle
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

    // Close cart when clicking outside
    document.addEventListener('click', (event) => {
        if (cartMenu && cartToggle && !cartMenu.contains(event.target) && !cartToggle.contains(event.target)) {
            cartMenu.classList.remove('active');
        }
    });

    // Load products
    if (productGrid) {
        loadProducts(productGrid);
    }

    // Handle contact form submission
    if (contactForm) {
        contactForm.addEventListener('submit', handleFormSubmit);
    }

    // Load more products
    if (moreProductsButton) {
        moreProductsButton.addEventListener('click', loadMoreProducts);
    }

    // Add smooth scrolling to all links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            smoothScroll(this.getAttribute('href'), 1000);
        });
    });

    // Initialize cart UI
    updateCartUI();

    // Lazy load YouTube video and Google Maps
    lazyLoad('.video-container', initializeVideo);
    lazyLoad('#map-container', initializeMap);
});

function initializeVideo(videoContainer) {
    const playButton = videoContainer.querySelector('.play-button');
    if (!playButton) return;

    playButton.addEventListener('click', () => {
        const iframe = document.createElement('iframe');
        iframe.setAttribute('width', '100%');
        iframe.setAttribute('height', '100%');
        iframe.setAttribute('src', `${videoContainer.dataset.src}?autoplay=1`);
        iframe.setAttribute('frameborder', '0');
        iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
        iframe.setAttribute('allowfullscreen', '');
        videoContainer.innerHTML = '';
        videoContainer.appendChild(iframe);
    });
}

/*function initializeMap(mapContainer) {
    const loadMapButton = mapContainer.querySelector('.load-map-button');
    if (!loadMapButton) return;

    loadMapButton.addEventListener('click', () => {
        const iframe = document.createElement('iframe');
        iframe.setAttribute('src', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3890.0647748982713!2d79.70246931482038!3d12.835543990947!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3a52c1df7e7c2c6d%3A0x5c0d8d688e5aea9f!2s37%2C%20Mettu%20St%2C%20Ennaikkaran%2C%20Kanchipuram%2C%20Tamil%20Nadu%20631501%2C%20India!5e0!3m2!1sen!2sus!4v1623344120978!5m2!1sen!2sus');
        iframe.setAttribute('width', '100%');
        iframe.setAttribute('height', '300');
        iframe.setAttribute('style', 'border:0;');
        iframe.setAttribute('allowfullscreen', '');
        iframe.setAttribute('loading', 'lazy');
          // Clear the existing content and append the iframe
        mapContainer.innerHTML = '';
        mapContainer.appendChild(iframe);
        
        // Hide the button after loading the map
        loadMapButton.style.display = 'none';
    });
}
*/
async function loadProductsFromServer() {
    try {
        const response = await fetch('get_products.php');
        if (!response.ok) {
            throw new Error('Failed to fetch products');
        }
        return await response.json();
    } catch (error) {
        console.error('Error loading products:', error);
        return [];
    }
}

async function loadProducts(container, start = 0, limit = 3) {
    if (products.length === 0) {
        products = await loadProductsFromServer();
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

    // Add click event listeners to the newly added images
    container.querySelectorAll('.product-image').forEach(img => {
        img.addEventListener('click', () => openModal(img));
    });

    // Add click event listeners to the "Add to Cart" buttons
    container.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', (e) => {
            const productId = e.target.getAttribute('data-id');
            const product = products.find(p => p.id === productId);
            addToCart(product);
        });
    });

    currentProductCount += productsToShow.length;
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
    
    if (!cartItems || !cartCount || !totalAmount) return;

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
                <button class="cart-item-remove" data-id="${item.id}">Remove</button>
            </div>
        `;
    });
    
    cartCount.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
    totalAmount.textContent = `₹${total.toFixed(2)}`;
    
    // Add event listeners to remove buttons
    document.querySelectorAll('.cart-item-remove').forEach(button => {
        button.addEventListener('click', removeFromCart);
    });
}

function removeFromCart(event) {
    const productId = event.target.dataset.id;
    const index = cart.findIndex(item => item.id === productId);
    
    if (index !== -1) {
        if (cart[index].quantity > 1) {
            cart[index].quantity -= 1;
        } else {
            cart.splice(index, 1);
        }
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

// Load cart from localStorage on page load
document.addEventListener('DOMContentLoaded', () => {
    const savedCart = localStorage.getItem('cart');
    if (savedCart) {
        cart = JSON.parse(savedCart);
        updateCartUI();
    }
});