/**
 * Beauty Shop Rio - Categories Section JavaScript
 * Handles tab switching, AJAX loading, and read more functionality
 */

(function($) {
    'use strict';

    // BSR Categories Module
    window.BSRCategories = {
        
        // Cache DOM elements
        elements: {
            tabButtons: null,
            brandsGrid: null,
            typesGrid: null,
            section: null
        },
        
        // Module state
        state: {
            categoriesLoaded: false,
            currentTab: 'brands'
        },
        
        /**
         * Initialize the categories section
         */
        init: function() {
            // Cache elements
            this.elements.section = $('.bsr-categories-section');
            
            if (this.elements.section.length === 0) {
                return; // No categories section on this page
            }
            
            this.elements.tabButtons = $('.bsr-tab-button');
            this.elements.brandsGrid = $('#bsr-brands-grid');
            this.elements.typesGrid = $('#bsr-types-grid');
            
            // Bind events
            this.bindEvents();
            
            // Initialize read more buttons
            this.initReadMore();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            const self = this;
            
            // Tab switching
            this.elements.tabButtons.on('click', function(e) {
                e.preventDefault();
                self.handleTabSwitch($(this));
            });
            
            // Read more functionality
            $(document).on('click', '.bsr-read-more-btn', function(e) {
                e.preventDefault();
                self.handleReadMore($(this));
            });
            
            // Handle window resize
            $(window).on('resize', this.debounce(function() {
                self.handleResize();
            }, 250));
        },
        
        /**
         * Initialize read more buttons
         */
        initReadMore: function() {
            $('.bsr-read-more-btn').each(function() {
                const $btn = $(this);
                const $text = $btn.siblings('.bsr-description-text');
                
                // Store original states
                $text.data('short', $text.text());
                $text.data('expanded', false);
            });
        },
        
        /**
         * Handle tab switching
         */
        handleTabSwitch: function($button) {
            const tab = $button.data('tab');
            
            // Check if already active
            if ($button.hasClass('active')) {
                return;
            }
            
            // Update active states
            this.elements.tabButtons.removeClass('active');
            $button.addClass('active');
            
            // Switch grids
            $('.bsr-category-grid').removeClass('active');
            
            if (tab === 'brands') {
                this.elements.brandsGrid.addClass('active');
                this.state.currentTab = 'brands';
            } else if (tab === 'types') {
                this.elements.typesGrid.addClass('active');
                this.state.currentTab = 'types';
                
                // Load categories if not already loaded
                if (!this.state.categoriesLoaded) {
                    this.loadProductTypes();
                }
            }
        },
        
        /**
         * Handle read more/less toggle
         */
        handleReadMore: function($button) {
            const $card = $button.closest('.bsr-category-card');
            const $text = $card.find('.bsr-description-text');
            const isExpanded = $button.hasClass('expanded');
            
            if (isExpanded) {
                // Collapse
                const shortText = $text.data('short');
                $text.text(shortText);
                $button.removeClass('expanded');
                $button.find('.bsr-more-text').text($button.data('more-text'));
            } else {
                // Expand
                const fullText = $text.data('full');
                $text.text(fullText);
                $button.addClass('expanded');
                $button.find('.bsr-more-text').text($button.data('less-text'));
            }
        },
        
        /**
         * Load product types via AJAX
         */
        loadProductTypes: function() {
            const self = this;
            
            // Check if ajax object exists
            if (typeof bsr_categories === 'undefined') {
                console.error('BSR Categories: Ajax object not found');
                return;
            }
            
            // Show loading state
            this.elements.typesGrid.html(
                '<div class="bsr-categories-loading">' +
                '<div class="bsr-loading-spinner"></div>' +
                '</div>'
            );
            
            $.ajax({
                url: bsr_categories.ajax_url,
                type: 'POST',
                data: {
                    action: 'bsr_get_product_types',
                    nonce: bsr_categories.nonce
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        self.renderCategories(response.data);
                        self.state.categoriesLoaded = true;
                    } else {
                        self.showEmptyState('types');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('BSR Categories Error:', error);
                    self.showErrorState('types');
                }
            });
        },
        
        /**
         * Render categories in the grid
         */
        renderCategories: function(categories) {
            let html = '';
            
            categories.forEach(function(category, index) {
                html += this.createCategoryCard(category, index);
            }.bind(this));
            
            this.elements.typesGrid.html(html);
        },
        
        /**
         * Create a category card HTML
         */
        createCategoryCard: function(category, index) {
            // Parent name display - use top_parent_name
            let parentNameHtml = '';
            if (category.top_parent_name) {
                parentNameHtml = `<span class="bsr-card-parent-name">${this.escapeHtml(category.top_parent_name)}</span>`;
            }
            
            // Image HTML
            const imageHtml = category.image 
                ? `<img src="${this.escapeHtml(category.image)}" 
                        alt="${this.escapeHtml(category.name)}" 
                        class="bsr-card-image" 
                        loading="lazy">`
                : `<div class="bsr-placeholder-image">
                     <svg width="60" height="60" viewBox="0 0 60 60" fill="none">
                       <rect width="60" height="60" rx="8" fill="#f0f0f0"/>
                       <path d="M30 20v20M20 30h20" stroke="#ccc" stroke-width="2" stroke-linecap="round"/>
                     </svg>
                   </div>`;
            
            return `
                <div class="bsr-category-card">
                    <div class="bsr-card-content">
                        ${parentNameHtml}
                        
                        <h3 class="bsr-card-title ${category.top_parent_name ? 'has-parent' : ''}">
                            ${this.escapeHtml(category.name)}
                        </h3>
                        
                        <div class="bsr-card-image-wrapper">
                            ${imageHtml}
                        </div>
                        
                        <a href="${this.escapeHtml(category.link)}" class="bsr-card-button">
                            K produktom
                        </a>
                    </div>
                </div>
            `;
        },
        
        /**
         * Refresh brands via AJAX (optional functionality)
         */
        refreshBrands: function() {
            const self = this;
            
            if (typeof bsr_categories === 'undefined') {
                console.error('BSR Categories: Ajax object not found');
                return;
            }
            
            $.ajax({
                url: bsr_categories.ajax_url,
                type: 'POST',
                data: {
                    action: 'bsr_get_brands',
                    nonce: bsr_categories.nonce
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        self.renderBrands(response.data);
                    } else {
                        self.showEmptyState('brands');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('BSR Categories Error:', error);
                    self.showErrorState('brands');
                }
            });
        },
        
        /**
         * Render brands in the grid
         */
        renderBrands: function(brands) {
            let html = '';
            
            brands.forEach(function(brand, index) {
                html += this.createBrandCard(brand, index);
            }.bind(this));
            
            this.elements.brandsGrid.html(html);
            
            // Reinitialize read more buttons
            this.initReadMore();
        },
        
        /**
         * Create a brand card HTML
         */
        createBrandCard: function(brand, index) {
            const imageHtml = brand.image 
                ? `<img src="${this.escapeHtml(brand.image)}" 
                        alt="${this.escapeHtml(brand.name)}" 
                        class="bsr-card-image" 
                        loading="lazy">`
                : `<div class="bsr-placeholder-image">
                     <svg width="60" height="60" viewBox="0 0 60 60" fill="none">
                       <rect width="60" height="60" rx="8" fill="#f0f0f0"/>
                       <path d="M30 20v20M20 30h20" stroke="#ccc" stroke-width="2" stroke-linecap="round"/>
                     </svg>
                   </div>`;
            
            let descriptionHtml = '';
            if (brand.description) {
                const needsReadMore = brand.full_description && 
                                    brand.full_description.length > brand.description.length;
                descriptionHtml = `
                    <div class="bsr-card-description">
                        <p class="bsr-description-text" 
                           data-full="${this.escapeHtml(brand.full_description)}"
                           data-short="${this.escapeHtml(brand.description)}">
                            ${this.escapeHtml(brand.description)}
                        </p>
                        ${needsReadMore ? `
                            <button class="bsr-read-more-btn" 
                                    data-more-text="Viac o značke"
                                    data-less-text="Menej">
                                <span class="bsr-more-text">Viac o značke</span>
                                <svg class="bsr-arrow-icon" width="20" height="20" viewBox="0 0 20 20">
                                    <path d="M10 14l-5-5h10l-5 5z" fill="currentColor"/>
                                </svg>
                            </button>
                        ` : ''}
                    </div>
                `;
            }
            
            return `
                <div class="bsr-category-card">
                    <div class="bsr-card-content">
                        <h3 class="bsr-card-title">${this.escapeHtml(brand.name)}</h3>
                        
                        <div class="bsr-card-image-wrapper">
                            ${imageHtml}
                        </div>
                        
                        <a href="${this.escapeHtml(brand.link)}" class="bsr-card-button">
                            K produktom
                        </a>
                        
                        ${descriptionHtml}
                    </div>
                </div>
            `;
        },
        
        /**
         * Show empty state
         */
        showEmptyState: function(type) {
            const message = type === 'brands' 
                ? 'Žiadne značky nie sú momentálne k dispozícii.'
                : 'Žiadne kategórie produktov nie sú momentálne k dispozícii.';
            
            const $grid = type === 'brands' ? this.elements.brandsGrid : this.elements.typesGrid;
            $grid.html(`
                <div class="bsr-empty-state">
                    <p>${message}</p>
                </div>
            `);
        },
        
        /**
         * Show error state
         */
        showErrorState: function(type) {
            const $grid = type === 'brands' ? this.elements.brandsGrid : this.elements.typesGrid;
            $grid.html(`
                <div class="bsr-empty-state">
                    <p>Nastala chyba pri načítavaní. Prosím, skúste to znova.</p>
                </div>
            `);
        },
        
        /**
         * Handle window resize for responsive adjustments
         */
        handleResize: function() {
            const windowWidth = $(window).width();
            
            // Add any responsive JavaScript adjustments here
            if (windowWidth < 768) {
                // Mobile adjustments
                this.adjustMobileLayout();
            } else if (windowWidth < 1024) {
                // Tablet adjustments
                this.adjustTabletLayout();
            } else {
                // Desktop adjustments
                this.adjustDesktopLayout();
            }
        },
        
        /**
         * Adjust layout for mobile
         */
        adjustMobileLayout: function() {
            // Mobile-specific adjustments if needed
        },
        
        /**
         * Adjust layout for tablet
         */
        adjustTabletLayout: function() {
            // Tablet-specific adjustments if needed
        },
        
        /**
         * Adjust layout for desktop
         */
        adjustDesktopLayout: function() {
            // Desktop-specific adjustments if needed
        },
        
        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml: function(text) {
            if (!text) return '';
            
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            
            return String(text).replace(/[&<>"']/g, function(m) {
                return map[m];
            });
        },
        
        /**
         * Debounce function for resize events
         */
        debounce: function(func, wait) {
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
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        BSRCategories.init();
    });
    
    // Also initialize on Elementor frontend init (if using Elementor)
    $(window).on('elementor/frontend/init', function() {
        if (window.elementorFrontend && window.elementorFrontend.hooks) {
            elementorFrontend.hooks.addAction('frontend/element_ready/widget', function() {
                BSRCategories.init();
            });
        }
    });

})(jQuery);