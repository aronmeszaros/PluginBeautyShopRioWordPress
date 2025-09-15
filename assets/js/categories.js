/**
 * Beauty Shop Rio - Categories Section JavaScript with Pagination
 * Handles tab switching, AJAX loading, read more functionality, and Load More
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
            currentTab: 'brands',
            offsets: {
                brands: 9, // Start from 9 since we load first 9 initially
                types: 0
            },
            itemsPerPage: 9,
            hasMore: {
                brands: false,
                types: false
            }
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
            
            // Set items per page from localized data if available
            if (typeof bsr_categories !== 'undefined' && bsr_categories.items_per_page) {
                this.state.itemsPerPage = parseInt(bsr_categories.items_per_page);
                this.state.offsets.brands = this.state.itemsPerPage; // Start from items_per_page since we load first batch initially
            }
            
            // Check if brands has more items initially
            this.state.hasMore.brands = this.elements.brandsGrid.find('.bsr-load-more-btn').length > 0;
            
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
            
            // Load more functionality
            $(document).on('click', '.bsr-load-more-btn', function(e) {
                e.preventDefault();
                self.handleLoadMore($(this));
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
         * Handle Load More button click
         */
        handleLoadMore: function($button) {
            const type = $button.data('type');
            const currentOffset = this.state.offsets[type];
            
            // Show loading state
            this.showLoadMoreLoading($button, true);
            
            // Load more items
            this.loadMoreItems(type, currentOffset);
        },
        
        /**
         * Load more items via AJAX
         */
        loadMoreItems: function(type, offset) {
            const self = this;
            
            if (typeof bsr_categories === 'undefined') {
                console.error('BSR Categories: Ajax object not found');
                return;
            }
            
            $.ajax({
                url: bsr_categories.ajax_url,
                type: 'POST',
                data: {
                    action: 'bsr_load_more_categories',
                    type: type,
                    offset: offset,
                    limit: this.state.itemsPerPage,
                    nonce: bsr_categories.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.appendItems(type, response.data.items);
                        self.state.offsets[type] += self.state.itemsPerPage;
                        self.state.hasMore[type] = response.data.has_more;
                        
                        // Hide load more button if no more items
                        if (!response.data.has_more) {
                            self.hideLoadMoreButton(type);
                        } else {
                            self.showLoadMoreLoading(self.getLoadMoreButton(type), false);
                        }
                        
                        // Reinitialize read more buttons for new items
                        self.initReadMore();
                    } else {
                        console.error('BSR Categories Error:', response);
                        self.showLoadMoreError(type);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('BSR Categories AJAX Error:', error);
                    self.showLoadMoreError(type);
                }
            });
        },
        
        /**
         * Append new items to the grid
         */
        appendItems: function(type, items) {
            const $grid = type === 'brands' ? this.elements.brandsGrid : this.elements.typesGrid;
            const $loadMoreContainer = $grid.find('.bsr-load-more-container');
            
            let html = '';
            
            items.forEach(function(item, index) {
                if (type === 'brands') {
                    html += this.createBrandCard(item, this.state.offsets[type] - this.state.itemsPerPage + index);
                } else {
                    html += this.createCategoryCard(item, this.state.offsets[type] - this.state.itemsPerPage + index);
                }
            }.bind(this));
            
            // Insert new items before the load more container
            if ($loadMoreContainer.length) {
                $loadMoreContainer.before(html);
            } else {
                $grid.append(html);
            }
            
            // Animate new items
            const $newItems = $grid.find('.bsr-category-card').slice(-items.length);
            $newItems.addClass('bsr-animate bsr-new-item');
            
            // Trigger animation
            setTimeout(function() {
                $newItems.addClass('bsr-visible');
            }, 100);
        },
        
        /**
         * Load product types via AJAX (initial load)
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
                    offset: 0,
                    limit: this.state.itemsPerPage,
                    nonce: bsr_categories.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.renderCategories(response.data.items);
                        self.state.categoriesLoaded = true;
                        self.state.offsets.types = self.state.itemsPerPage;
                        self.state.hasMore.types = response.data.has_more;
                        
                        // Add load more button if needed
                        if (response.data.has_more) {
                            self.addLoadMoreButton('types');
                        }
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
            
            // Image HTML with placeholder
            const placeholderUrl = typeof bsr_categories !== 'undefined' && bsr_categories.plugin_url
                ? bsr_categories.plugin_url + 'assets/images/placeholder-products.png'
                : '/wp-content/plugins/beauty-shop-rio/assets/images/placeholder-products.png';
            
            const imageHtml = category.image 
                ? `<img src="${this.escapeHtml(category.image)}" 
                        alt="${this.escapeHtml(category.name)}" 
                        class="bsr-card-image" 
                        loading="lazy">`
                : `<img src="${placeholderUrl}" 
                        alt="${this.escapeHtml(category.name)}" 
                        class="bsr-card-image bsr-placeholder-img" 
                        loading="lazy">`;
            
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
         * Create a brand card HTML
         */
        createBrandCard: function(brand, index) {
            // Image HTML with placeholder
            const placeholderUrl = typeof bsr_categories !== 'undefined' && bsr_categories.plugin_url
                ? bsr_categories.plugin_url + 'assets/images/placeholder-products.png'
                : '/wp-content/plugins/beauty-shop-rio/assets/images/placeholder-products.png';
            
            const imageHtml = brand.image 
                ? `<img src="${this.escapeHtml(brand.image)}" 
                        alt="${this.escapeHtml(brand.name)}" 
                        class="bsr-card-image" 
                        loading="lazy">`
                : `<img src="${placeholderUrl}" 
                        alt="${this.escapeHtml(brand.name)}" 
                        class="bsr-card-image bsr-placeholder-img" 
                        loading="lazy">`;
            
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
         * Add load more button to grid
         */
        addLoadMoreButton: function(type) {
            const $grid = type === 'brands' ? this.elements.brandsGrid : this.elements.typesGrid;
            const loadMoreText = typeof bsr_categories !== 'undefined' && bsr_categories.load_more_text
                ? bsr_categories.load_more_text
                : 'Načítať viac';
            
            const buttonHtml = `
                <div class="bsr-load-more-container">
                    <button class="bsr-load-more-btn" data-type="${type}">
                        <span class="bsr-load-more-text">${loadMoreText}</span>
                        <div class="bsr-load-more-spinner" style="display: none;">
                            <div class="bsr-spinner"></div>
                        </div>
                    </button>
                </div>
            `;
            
            $grid.append(buttonHtml);
        },
        
        /**
         * Get load more button for specified type
         */
        getLoadMoreButton: function(type) {
            const $grid = type === 'brands' ? this.elements.brandsGrid : this.elements.typesGrid;
            return $grid.find('.bsr-load-more-btn');
        },
        
        /**
         * Show/hide loading state on load more button
         */
        showLoadMoreLoading: function($button, loading) {
            if (loading) {
                $button.prop('disabled', true);
                $button.find('.bsr-load-more-text').hide();
                $button.find('.bsr-load-more-spinner').show();
            } else {
                $button.prop('disabled', false);
                $button.find('.bsr-load-more-text').show();
                $button.find('.bsr-load-more-spinner').hide();
            }
        },
        
        /**
         * Hide load more button
         */
        hideLoadMoreButton: function(type) {
            const $grid = type === 'brands' ? this.elements.brandsGrid : this.elements.typesGrid;
            $grid.find('.bsr-load-more-container').fadeOut(300, function() {
                $(this).remove();
            });
        },
        
        /**
         * Show load more error
         */
        showLoadMoreError: function(type) {
            const $button = this.getLoadMoreButton(type);
            this.showLoadMoreLoading($button, false);
            
            // Temporarily show error message
            const originalText = $button.find('.bsr-load-more-text').text();
            $button.find('.bsr-load-more-text').text('Chyba pri načítavaní');
            
            setTimeout(function() {
                $button.find('.bsr-load-more-text').text(originalText);
            }, 3000);
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
                this.adjustMobileLayout();
            } else if (windowWidth < 1024) {
                this.adjustTabletLayout();
            } else {
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