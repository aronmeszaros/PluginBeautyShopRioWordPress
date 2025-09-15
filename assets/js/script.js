/* Beauty Shop Rio Plugin JavaScript */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        initBeautyShopRio();
    });

    function initBeautyShopRio() {
        // Initialize hero carousel
        initHeroCarousel();
        
        // Initialize newsletter form
        initNewsletterForm();
        
        // Add loading animations
        initAnimations();
    }

    // Hero Carousel Functionality - Legacy support
    function initHeroCarousel() {
        // This is kept for backward compatibility with older implementations
        // The main hero functionality is now handled by BSRHeroCarousel class below
    }

    function updateHeroSlide(slide) {
        const $productInfo = $('.bsr-product-info');
        
        $productInfo.fadeOut(300, function() {
            $productInfo.find('h3').text(slide.title);
            
            const $priceContainer = $productInfo.find('.bsr-price');
            $priceContainer.empty();
            $priceContainer.append('<span class="bsr-price-current">' + slide.price + '</span>');
            
            $productInfo.fadeIn(300);
        });
    }

    // Newsletter Form Functionality
    function initNewsletterForm() {
        $('.bsr-signup-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $email = $form.find('.bsr-email-input');
            const $button = $form.find('.bsr-submit-btn');
            const email = $email.val().trim();
            
            // Basic email validation
            if (!isValidEmail(email)) {
                showNotification('Prosím zadajte platnú emailovú adresu', 'error');
                $email.focus();
                return;
            }
            
            // Simulate form submission
            $button.prop('disabled', true).text('Odosielanie...');
            
            setTimeout(function() {
                showNotification('Ďakujeme! Úspešne ste sa prihlásili na odber noviniek.', 'success');
                $email.val('');
                $button.prop('disabled', false).text('Poslať');
            }, 1500);
        });
    }

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function showNotification(message, type) {
        // Remove existing notifications
        $('.bsr-notification').remove();
        
        const notificationClass = type === 'error' ? 'bsr-notification-error' : 'bsr-notification-success';
        const notification = $('<div class="bsr-notification ' + notificationClass + '">' + message + '</div>');
        
        // Add notification styles
        notification.css({
            position: 'fixed',
            top: '20px',
            right: '20px',
            background: type === 'error' ? '#ff6b6b' : '#51cf66',
            color: 'white',
            padding: '15px 20px',
            borderRadius: '8px',
            boxShadow: '0 4px 20px rgba(0, 0, 0, 0.15)',
            zIndex: '9999',
            fontSize: '14px',
            maxWidth: '300px',
            animation: 'bsrSlideIn 0.3s ease'
        });
        
        $('body').append(notification);
        
        // Auto-remove after 4 seconds
        setTimeout(function() {
            notification.fadeOut(300, function() {
                notification.remove();
            });
        }, 4000);
    }

    // Animations on Scroll
    function initAnimations() {
        // Add CSS for animations
        $('<style>')
            .prop('type', 'text/css')
            .html(`
                @keyframes bsrSlideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                
                @keyframes bsrFadeInUp {
                    from { transform: translateY(30px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
                
                .bsr-animate {
                    opacity: 0;
                    transform: translateY(30px);
                    transition: all 0.6s ease;
                }
                
                .bsr-animate.bsr-visible {
                    opacity: 1;
                    transform: translateY(0);
                }
                
                .bsr-stagger-1 { transition-delay: 0.1s; }
                .bsr-stagger-2 { transition-delay: 0.2s; }
                .bsr-stagger-3 { transition-delay: 0.3s; }
                .bsr-stagger-4 { transition-delay: 0.4s; }
                .bsr-stagger-5 { transition-delay: 0.5s; }
                .bsr-stagger-6 { transition-delay: 0.6s; }
            `)
            .appendTo('head');

        // Add animation classes to elements
        $('.bsr-values h2, .bsr-categories h2, .bsr-newsletter h2').addClass('bsr-animate');
        $('.bsr-value-item').each(function(index) {
            $(this).addClass('bsr-animate bsr-stagger-' + (index + 1));
        });
        $('.bsr-category-card').each(function(index) {
            $(this).addClass('bsr-animate bsr-stagger-' + ((index % 6) + 1));
        });

        // Intersection Observer for animations
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('bsr-visible');
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            $('.bsr-animate').each(function() {
                observer.observe(this);
            });
        } else {
            // Fallback for older browsers
            $('.bsr-animate').addClass('bsr-visible');
        }
    }

    // Utility function for responsive behavior
    function handleResize() {
        const windowWidth = $(window).width();
        
        // Adjust grid layouts for different screen sizes
        if (windowWidth <= 768) {
            $('.bsr-categories-grid').css('grid-template-columns', '1fr');
        } else if (windowWidth <= 1024) {
            $('.bsr-categories-grid').css('grid-template-columns', 'repeat(2, 1fr)');
        } else {
            $('.bsr-categories-grid').css('grid-template-columns', 'repeat(2, 1fr)');
        }
    }

    // Handle window resize
    $(window).on('resize', handleResize);
    handleResize(); // Initial call

    // Lazy loading for images (if needed)
    function initLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            imageObserver.unobserve(img);
                        }
                    }
                });
            });

            $('img[data-src]').each(function() {
                imageObserver.observe(this);
            });
        }
    }

    // Initialize lazy loading
    initLazyLoading();

    // Add to global scope for external access
    window.BeautyShopRio = {
        updateHeroSlide: updateHeroSlide,
        showNotification: showNotification
    };

})(jQuery);


/* Hero Section JavaScript Functionality */

(function($) {
    'use strict';

    // Hero Carousel Class
    class BSRHeroCarousel {
        constructor() {
            this.currentIndex = 0;
            this.featuredProducts = [];
            this.autoplayInterval = null;
            this.autoplayDelay = 5000; // 5 seconds
            this.isTransitioning = false;
            
            this.init();
        }
        
        init() {
            this.loadHeroData();
            this.bindEvents();
            this.startAutoplay();
            this.preloadImages();
        }
        
        loadHeroData() {
            const heroDataElement = document.getElementById('bsr-hero-data');
            if (heroDataElement) {
                try {
                    const data = JSON.parse(heroDataElement.textContent);
                    this.featuredProducts = data.featured_products || [];
                    this.currentIndex = data.current_index || 0;
                    this.shopUrl = data.shop_url || '#';
                } catch (e) {
                    console.warn('Could not parse hero data:', e);
                }
            }
            
            // If no data found, try to load from AJAX
            if (this.featuredProducts.length === 0) {
                this.loadFeaturedProducts();
            }
        }
        
        loadFeaturedProducts() {
            if (typeof bsr_ajax === 'undefined') return;
            
            $.ajax({
                url: bsr_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bsr_get_hero_products',
                    limit: 5,
                    featured_only: true,
                    nonce: bsr_ajax.nonce
                },
                success: (response) => {
                    if (response.success && response.data.length > 0) {
                        this.featuredProducts = response.data;
                        this.updateDotsCount();
                    }
                },
                error: (xhr, status, error) => {
                    console.warn('Failed to load featured products:', error);
                }
            });
        }
        
        bindEvents() {
            // Desktop navigation
            $(document).on('click', '.bsr-nav-btn[data-direction="prev"]', (e) => {
                e.preventDefault();
                this.previousProduct();
            });
            
            $(document).on('click', '.bsr-nav-btn[data-direction="next"]', (e) => {
                e.preventDefault();
                this.nextProduct();
            });
            
            // Mobile navigation
            $(document).on('click', '.bsr-mobile-prev', (e) => {
                e.preventDefault();
                this.previousProduct();
            });
            
            $(document).on('click', '.bsr-mobile-next', (e) => {
                e.preventDefault();
                this.nextProduct();
            });
            
            // Dot navigation (desktop only)
            $(document).on('click', '.bsr-dot[data-index]', (e) => {
                e.preventDefault();
                const index = parseInt($(e.currentTarget).data('index'));
                this.goToProduct(index);
            });
            
            // Add to cart buttons (both desktop and mobile)
            $(document).on('click', '.bsr-add-to-cart-btn, .bsr-mobile-buy-btn', (e) => {
                e.preventDefault();
                this.handleAddToCart($(e.currentTarget));
            });
            
            // Keyboard navigation
            $(document).on('keydown', (e) => {
                if (!$('.bsr-hero').length) return;
                
                switch(e.key) {
                    case 'ArrowLeft':
                        e.preventDefault();
                        this.previousProduct();
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        this.nextProduct();
                        break;
                }
            });
            
            // Pause autoplay on hover
            $('.bsr-hero').on('mouseenter', () => {
                this.pauseAutoplay();
            }).on('mouseleave', () => {
                this.startAutoplay();
            });
            
            // Pause autoplay when tab is not visible
            $(document).on('visibilitychange', () => {
                if (document.hidden) {
                    this.pauseAutoplay();
                } else {
                    this.startAutoplay();
                }
            });
        }
        
        nextProduct() {
            if (this.isTransitioning || this.featuredProducts.length <= 1) return;
            
            this.currentIndex = (this.currentIndex + 1) % this.featuredProducts.length;
            this.updateProduct();
        }
        
        previousProduct() {
            if (this.isTransitioning || this.featuredProducts.length <= 1) return;
            
            this.currentIndex = this.currentIndex === 0 ? 
                this.featuredProducts.length - 1 : 
                this.currentIndex - 1;
            this.updateProduct();
        }
        
        goToProduct(index) {
            if (this.isTransitioning || index === this.currentIndex || 
                index < 0 || index >= this.featuredProducts.length) return;
            
            this.currentIndex = index;
            this.updateProduct();
        }
        
        updateProduct() {
            if (this.featuredProducts.length === 0) return;
            
            this.isTransitioning = true;
            const currentProduct = this.featuredProducts[this.currentIndex];
            
            // Animate out current product
            $('.bsr-featured-product, .bsr-mobile-product').addClass('transitioning-out');
            
            setTimeout(() => {
                this.updateProductContent(currentProduct);
                this.updateDots();
                
                // Animate in new product
                $('.bsr-featured-product, .bsr-mobile-product')
                    .removeClass('transitioning-out')
                    .addClass('transitioning-in');
                
                setTimeout(() => {
                    $('.bsr-featured-product, .bsr-mobile-product')
                        .removeClass('transitioning-in');
                    this.isTransitioning = false;
                }, 300);
            }, 300);
            
            // Reset autoplay
            this.restartAutoplay();
        }
        
        updateProductContent(product) {
            // Update desktop version
            this.updateDesktopProduct(product);
            
            // Update mobile version
            this.updateMobileProduct(product);
            
            // Trigger custom event
            $(document).trigger('bsr:hero:product-changed', [product, this.currentIndex]);
        }
        
        updateDesktopProduct(product) {
            const $desktop = $('.bsr-hero-desktop');
            
            // Update image
            const $imageContainer = $desktop.find('.bsr-product-image-container');
            if (product.image) {
                $imageContainer.html(`
                    <img src="${product.image}" 
                         alt="${this.escapeHtml(product.title)}" 
                         class="bsr-product-image">
                `);
            } else {
                $imageContainer.html(`
                    <div class="bsr-product-placeholder">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5" stroke="currentColor" stroke-width="2"/>
                            <polyline points="21,15 16,10 5,21" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                `);
            }
            
            // Update title
            $desktop.find('.bsr-product-title').text(product.title);
            
            // Update price (no old price)
            const $priceDisplay = $desktop.find('.bsr-price-display');
            $priceDisplay.html(`<span class="bsr-current-price">${product.price}</span>`);
            
            // Update cart button
            $desktop.find('.bsr-add-to-cart-btn').attr('data-product-url', product.url);
        }
        
        updateMobileProduct(product) {
            const $mobile = $('.bsr-hero-mobile');
            
            // Update image
            const $imageContainer = $mobile.find('.bsr-mobile-product').first();
            if (product.image) {
                $imageContainer.find('img, .bsr-mobile-product-placeholder').remove();
                $imageContainer.prepend(`
                    <img src="${product.image}" 
                         alt="${this.escapeHtml(product.title)}" 
                         class="bsr-mobile-product-image">
                `);
            } else {
                $imageContainer.find('img, .bsr-mobile-product-placeholder').remove();
                $imageContainer.prepend(`
                    <div class="bsr-mobile-product-placeholder">Product Image</div>
                `);
            }
            
            // Update title
            $mobile.find('.bsr-mobile-product-info h3').text(product.title);
            
            // Update price in the button structure (no old price logic)
            const $priceSection = $mobile.find('.bsr-price-section');
            $priceSection.html(`<span class="bsr-mobile-current-price">${product.price}</span>`);
            
            // Update the button's product URL
            $mobile.find('.bsr-mobile-buy-btn').attr('data-product-url', product.url);
        }
        
        updateDots() {
            // Update desktop dots
            $('.bsr-dot').removeClass('active');
            $(`.bsr-dot[data-index="${this.currentIndex}"]`).addClass('active');
            
            // Update mobile dots
            $('.bsr-mobile-dot').removeClass('active');
            $('.bsr-mobile-dot').eq(this.currentIndex).addClass('active');
        }
        
        updateDotsCount() {
            if (this.featuredProducts.length <= 1) return;
            
            // Update desktop dots
            const $desktopDots = $('.bsr-hero-dots');
            let dotsHtml = '';
            for (let i = 0; i < this.featuredProducts.length; i++) {
                const activeClass = i === this.currentIndex ? 'active' : '';
                dotsHtml += `<div class="bsr-dot ${activeClass}" data-index="${i}"></div>`;
            }
            $desktopDots.html(dotsHtml);
            
            // Update mobile dots
            const $mobileDots = $('.bsr-mobile-dots');
            let mobileDotsHtml = '';
            for (let i = 0; i < this.featuredProducts.length; i++) {
                const activeClass = i === this.currentIndex ? 'active' : '';
                mobileDotsHtml += `<div class="bsr-mobile-dot ${activeClass}"></div>`;
            }
            $mobileDots.html(mobileDotsHtml);
        }
        
        handleAddToCart($button) {
            const productUrl = $button.data('product-url');
            
            if (!productUrl || productUrl === '#') {
                this.showNotification('Product link not available', 'error');
                return;
            }
            
            // Add loading state
            const originalText = $button.find('.bsr-buy-text').length ? 
                $button.find('.bsr-buy-text').text() : 
                $button.text();
            
            $button.prop('disabled', true);
            
            if ($button.find('.bsr-buy-text').length) {
                // Mobile button
                $button.find('.bsr-buy-text').text('Presmerujem...');
            } else {
                // Desktop button
                $button.text('Presmerujem...');
            }
            
            // Simulate cart addition (replace with actual WooCommerce AJAX)
            setTimeout(() => {
                // In real implementation, this would be WooCommerce AJAX call
                // For now, just redirect to product page
                window.location.href = productUrl;
            }, 500);
            
            // Trigger custom event for tracking
            $(document).trigger('bsr:hero:add-to-cart', [this.featuredProducts[this.currentIndex]]);
        }
        
        preloadImages() {
            this.featuredProducts.forEach(product => {
                if (product.image) {
                    const img = new Image();
                    img.src = product.image;
                }
            });
        }
        
        startAutoplay() {
            if (this.featuredProducts.length <= 1) return;
            
            this.pauseAutoplay();
            this.autoplayInterval = setInterval(() => {
                this.nextProduct();
            }, this.autoplayDelay);
        }
        
        pauseAutoplay() {
            if (this.autoplayInterval) {
                clearInterval(this.autoplayInterval);
                this.autoplayInterval = null;
            }
        }
        
        restartAutoplay() {
            this.startAutoplay();
        }
        
        showNotification(message, type = 'info') {
            // Remove existing notifications
            $('.bsr-hero-notification').remove();
            
            const notificationClass = type === 'error' ? 'bsr-notification-error' : 'bsr-notification-success';
            const notification = $(`
                <div class="bsr-hero-notification ${notificationClass}">
                    ${this.escapeHtml(message)}
                </div>
            `);
            
            // Add styles
            notification.css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                background: type === 'error' ? '#ff6b6b' : '#51cf66',
                color: 'white',
                padding: '12px 20px',
                borderRadius: '8px',
                boxShadow: '0 4px 20px rgba(0, 0, 0, 0.15)',
                zIndex: '9999',
                fontSize: '14px',
                maxWidth: '300px',
                opacity: '0',
                transform: 'translateX(100%)',
                transition: 'all 0.3s ease'
            });
            
            $('body').append(notification);
            
            // Animate in
            setTimeout(() => {
                notification.css({
                    opacity: '1',
                    transform: 'translateX(0)'
                });
            }, 100);
            
            // Auto-remove after 4 seconds
            setTimeout(() => {
                notification.css({
                    opacity: '0',
                    transform: 'translateX(100%)'
                });
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }
        
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Public methods for external access
        getCurrentProduct() {
            return this.featuredProducts[this.currentIndex] || null;
        }
        
        getCurrentIndex() {
            return this.currentIndex;
        }
        
        getTotalProducts() {
            return this.featuredProducts.length;
        }
        
        destroy() {
            this.pauseAutoplay();
            $(document).off('click', '.bsr-nav-btn, .bsr-mobile-prev, .bsr-mobile-next, .bsr-dot, .bsr-add-to-cart-btn, .bsr-mobile-buy-btn');
            $(document).off('keydown');
            $('.bsr-hero').off('mouseenter mouseleave');
        }
    }
    
    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.bsr-hero').length) {
            window.bsrHeroCarousel = new BSRHeroCarousel();
        }
    });
    
    // CSS for transitions
    $('<style>').prop('type', 'text/css').html(`
        .bsr-featured-product.transitioning-out,
        .bsr-mobile-product.transitioning-out {
            opacity: 0;
            transform: translateX(-20px);
            transition: all 0.3s ease;
        }
        
        .bsr-featured-product.transitioning-in,
        .bsr-mobile-product.transitioning-in {
            opacity: 0;
            transform: translateX(20px);
            transition: all 0.3s ease;
        }
        
        .bsr-featured-product,
        .bsr-mobile-product {
            transition: all 0.3s ease;
        }
        
        .bsr-add-to-cart-btn:disabled,
        .bsr-mobile-buy-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .bsr-nav-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        @media (prefers-reduced-motion: reduce) {
            .bsr-featured-product,
            .bsr-mobile-product,
            .bsr-nav-btn,
            .bsr-dot,
            .bsr-mobile-dot {
                transition: none !important;
            }
        }
    `).appendTo('head');

})(jQuery);


/*Insta*/
document.addEventListener('DOMContentLoaded', () => {
  const feed = document.querySelector('.uagb-block-903e8ae4 #sb_instagram');
  if (!feed) return;

  // Fade-in when images load
  const reveal = el => { el.style.opacity = '1'; el.style.transform = 'translateY(0)'; };
  const styleCard = el => {
    el.style.opacity = '0'; el.style.transform = 'translateY(6px)';
    el.style.transition = 'opacity .4s ease, transform .4s ease';
    const img = el.querySelector('img');
    if (img && img.complete) requestAnimationFrame(() => reveal(el));
    else if (img) img.addEventListener('load', () => reveal(el), { once:true });
  };

  const localizeButtons = () => {
    const loadBtn = feed.querySelector('.sbi_load_btn .sbi_btn_text');
    if (loadBtn) loadBtn.textContent = 'Zobraziť viac';
    const followSpan = feed.querySelector('.sbi_follow_btn span');
    if (followSpan) followSpan.textContent = 'Sledovať na Instagrame';
  };

  // Initial pass
  feed.querySelectorAll('#sbi_images .sbi_item').forEach(styleCard);
  localizeButtons();

  // Watch for new items after "Load More"
  const mo = new MutationObserver(muts => {
    muts.forEach(m => {
      m.addedNodes.forEach(n => {
        if (n.nodeType === 1 && n.matches('.sbi_item')) styleCard(n);
      });
    });
    localizeButtons();
  });
  mo.observe(feed.querySelector('#sbi_images'), { childList: true });

});
