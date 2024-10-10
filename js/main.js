// Remove the duplicate declaration of products and currentProductCount
// let products = [];
// let currentProductCount = 0;

document.addEventListener('DOMContentLoaded', async () => {
    const header = document.querySelector('.header');
    const menuToggle = document.querySelector('.menu-toggle');
    const nav = header.querySelector('nav');

    // Scroll behavior
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // Mobile menu toggle
    if (menuToggle) {
        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            nav.classList.toggle('active');
            menuToggle.innerHTML = nav.classList.contains('active') ? '✕' : '☰';
        });
    }

    // Close mobile menu when clicking outside
    document.addEventListener('click', (event) => {
        if (nav.classList.contains('active') && !header.contains(event.target)) {
            nav.classList.remove('active');
            menuToggle.innerHTML = '☰';
        }
    });

    // Close mobile menu when a nav item is clicked
    nav.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            nav.classList.remove('active');
            menuToggle.innerHTML = '☰';
        });
    });

    const productGrid = document.querySelector('.product-grid');
    if (productGrid) {
        await loadProducts(productGrid);
    }

    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', handleFormSubmit);
    }

    const moreProductsButton = document.querySelector('.more-products-button a');
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
});

let products = [];
let currentProductCount = 0;

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
            <img src="${product.image}" alt="${product.title}" onerror="this.src='images/placeholder.jpg';">
            <div class="product-info">
                <h3>${product.title}</h3>
                <p class="price">₹${product.price}</p>
                <p class="description">${product.description}</p>
                <a href="#" class="btn btn-secondary">Add to Cart</a>
            </div>
        </div>
    `).join('');

    if (start === 0) {
        container.innerHTML = productHTML;
    } else {
        container.insertAdjacentHTML('beforeend', productHTML);
    }

    currentProductCount += productsToShow.length;
    return products.length > currentProductCount;
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