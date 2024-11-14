class ProductImageSlider {
    constructor(containerId, images, options = {}) {
        this.container = document.getElementById(containerId);
        this.images = [images.main, ...images.additional];
        this.currentIndex = 0;
        this.options = {
            autoplay: options.autoplay || true,
            interval: options.interval || 10000,
            showArrows: options.showArrows || true,
            showDots: options.showDots || true
        };
        
        this.init();
    }

    init() {
        // Create slider container
        this.sliderContainer = document.createElement('div');
        this.sliderContainer.className = 'product-slider';
        
        // Create image container
        this.imageContainer = document.createElement('div');
        this.imageContainer.className = 'product-slider__image';
        
        // Create main image
        this.mainImage = document.createElement('img');
        this.mainImage.src = this.images[0];
        this.mainImage.alt = 'Product Image';
        this.imageContainer.appendChild(this.mainImage);
        
        // Add navigation arrows if enabled
        if (this.options.showArrows) {
            const prevBtn = document.createElement('button');
            prevBtn.className = 'product-slider__arrow product-slider__arrow--prev';
            prevBtn.innerHTML = '&#10094;';
            prevBtn.onclick = () => this.prevImage();
            
            const nextBtn = document.createElement('button');
            nextBtn.className = 'product-slider__arrow product-slider__arrow--next';
            nextBtn.innerHTML = '&#10095;';
            nextBtn.onclick = () => this.nextImage();
            
            this.sliderContainer.appendChild(prevBtn);
            this.sliderContainer.appendChild(nextBtn);
        }
        
        // Add dots navigation if enabled
        if (this.options.showDots && this.images.length > 1) {
            const dotsContainer = document.createElement('div');
            dotsContainer.className = 'product-slider__dots';
            
            this.images.forEach((_, index) => {
                const dot = document.createElement('span');
                dot.className = 'product-slider__dot';
                if (index === 0) dot.classList.add('active');
                dot.onclick = () => this.showImage(index);
                dotsContainer.appendChild(dot);
            });
            
            this.sliderContainer.appendChild(dotsContainer);
        }
        
        // Add image container to slider
        this.sliderContainer.appendChild(this.imageContainer);
        
        // Add slider to main container
        this.container.appendChild(this.sliderContainer);
        
        // Start autoplay if enabled
        if (this.options.autoplay && this.images.length > 1) {
            this.startAutoplay();
        }
        
        // Add touch support for mobile
        this.addTouchSupport();
    }

    showImage(index) {
        this.currentIndex = index;
        this.mainImage.src = this.images[index];
        
        // Update dots
        if (this.options.showDots) {
            const dots = this.sliderContainer.querySelectorAll('.product-slider__dot');
            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });
        }
    }

    nextImage() {
        const nextIndex = (this.currentIndex + 1) % this.images.length;
        this.showImage(nextIndex);
    }

    prevImage() {
        const prevIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
        this.showImage(prevIndex);
    }

    startAutoplay() {
        this.stopAutoplay(); // Clear any existing interval
        this.autoplayInterval = setInterval(() => {
            this.nextImage();
        }, this.options.interval);
    }

    stopAutoplay() {
        if (this.autoplayInterval) {
            clearInterval(this.autoplayInterval);
        }
    }

    addTouchSupport() {
        let touchStartX = 0;
        let touchEndX = 0;
        
        this.sliderContainer.addEventListener('touchstart', (e) => {
            touchStartX = e.touches[0].clientX;
            this.stopAutoplay();
        }, false);
        
        this.sliderContainer.addEventListener('touchmove', (e) => {
            touchEndX = e.touches[0].clientX;
        }, false);
        
        this.sliderContainer.addEventListener('touchend', () => {
            const difference = touchStartX - touchEndX;
            if (Math.abs(difference) > 50) { // Minimum swipe distance
                if (difference > 0) {
                    this.nextImage();
                } else {
                    this.prevImage();
                }
            }
            if (this.options.autoplay) {
                this.startAutoplay();
            }
        }, false);
    }
}
