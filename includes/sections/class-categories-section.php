<?php
/**
 * Categories Section Component
 * Beauty Shop Rio Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class BSR_Categories_Section {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('wp_ajax_bsr_get_brands', array($this, 'ajax_get_brands'));
        add_action('wp_ajax_nopriv_bsr_get_brands', array($this, 'ajax_get_brands'));
        add_action('wp_ajax_bsr_get_product_types', array($this, 'ajax_get_product_types'));
        add_action('wp_ajax_nopriv_bsr_get_product_types', array($this, 'ajax_get_product_types'));
    }
    
    /**
     * Get brands with published products
     */
    private function get_active_brands() {
        $brands = array();
        
        // Try different possible brand taxonomy names
        $possible_taxonomies = array('product_brand', 'pwb-brand', 'brand', 'product-brand');
        $brand_taxonomy = '';
        
        foreach ($possible_taxonomies as $taxonomy) {
            if (taxonomy_exists($taxonomy)) {
                $brand_taxonomy = $taxonomy;
                break;
            }
        }
        
        if (empty($brand_taxonomy)) {
            return $brands; // No brand taxonomy found
        }
        
        $brand_terms = get_terms(array(
            'taxonomy' => $brand_taxonomy,
            'hide_empty' => false,
        ));
        
        if (!is_wp_error($brand_terms) && !empty($brand_terms)) {
            foreach ($brand_terms as $brand) {
                // Check if brand has published products
                $args = array(
                    'post_type' => 'product',
                    'post_status' => 'publish',
                    'posts_per_page' => 1,
                    'tax_query' => array(
                        array(
                            'taxonomy' => $brand_taxonomy,
                            'field' => 'term_id',
                            'terms' => $brand->term_id,
                        ),
                    ),
                );
                
                $products = new WP_Query($args);
                
                if ($products->have_posts()) {
                    // Get brand image
                    $thumbnail_id = get_term_meta($brand->term_id, 'thumbnail_id', true);
                    if (!$thumbnail_id) {
                        // Try PWB brand image field
                        $thumbnail_id = get_term_meta($brand->term_id, 'pwb_brand_image', true);
                    }
                    $image_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : '';
                    
                    // Get brand description
                    $description = term_description($brand->term_id, $brand_taxonomy);
                    $description = wp_strip_all_tags($description);
                    
                    $brands[] = array(
                        'id' => $brand->term_id,
                        'name' => $brand->name,
                        'slug' => $brand->slug,
                        'description' => wp_trim_words($description, 20, '...'),
                        'full_description' => $description,
                        'image' => $image_url,
                        'link' => get_term_link($brand),
                        'count' => $brand->count
                    );
                }
                
                wp_reset_postdata();
            }
        }
        
        return $brands;
    }
    
    /**
     * Replace the existing get_active_categories() method with this one
     * This shows only leaf categories (categories without children)
     */
    private function get_active_categories() {
        $categories = array();
        
        // Get ALL product categories, not just top-level
        $cat_terms = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));
        
        if (!is_wp_error($cat_terms) && !empty($cat_terms)) {
            foreach ($cat_terms as $category) {
                // Skip uncategorized
                if ($category->slug === 'uncategorized' || $category->slug === 'nezaradene') {
                    continue;
                }
                
                // Check if this category has any children
                $children = get_terms(array(
                    'taxonomy' => 'product_cat',
                    'parent' => $category->term_id,
                    'hide_empty' => false,
                    'fields' => 'ids', // We only need IDs to check if any exist
                ));
                
                // Only process categories WITHOUT children (leaf categories)
                if (empty($children)) {
                    // Check if this leaf category has published products (not including children since it has none)
                    if ($this->category_has_published_products($category->term_id, false)) {
                        // Get category image
                        $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
                        $image_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : '';
                        
                        // Get the absolute top-most parent category
                        $top_parent_name = '';
                        
                        if ($category->parent > 0) {
                            // Start from the current category and traverse up
                            $current_term = $category;
                            $top_parent = null;
                            
                            // Keep going up until we find the top (parent = 0)
                            while ($current_term->parent > 0) {
                                $parent_term = get_term($current_term->parent, 'product_cat');
                                if (is_wp_error($parent_term)) {
                                    break;
                                }
                                $top_parent = $parent_term;
                                $current_term = $parent_term;
                            }
                            
                            // If we found a top parent, use its name
                            if ($top_parent) {
                                $top_parent_name = $top_parent->name;
                            }
                        }
                        
                        $categories[] = array(
                            'id' => $category->term_id,
                            'name' => $category->name,
                            'slug' => $category->slug,
                            'image' => $image_url,
                            'link' => get_term_link($category),
                            'count' => $category->count,
                            'parent_id' => $category->parent,
                            'top_parent_name' => $top_parent_name, // This is what we'll display
                        );
                    }
                }
            }
        }
        
        // Optional: Sort categories by name
        usort($categories, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        return $categories;
    }
    
    /**
     * Check if category has published products
     * @param int $category_id Category ID
     * @param bool $include_children Whether to include child categories
     */
    private function category_has_published_products($category_id, $include_children = true) {
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category_id,
                    'include_children' => $include_children,
                ),
            ),
        );
        
        $products = new WP_Query($args);
        $has_products = $products->have_posts();
        wp_reset_postdata();
        
        return $has_products;
    }
    
    /**
     * AJAX handler for getting brands
     */
    public function ajax_get_brands() {
        check_ajax_referer('bsr_categories_nonce', 'nonce');
        
        $brands = $this->get_active_brands();
        wp_send_json_success($brands);
    }
    
    /**
     * AJAX handler for getting product types
     */
    public function ajax_get_product_types() {
        check_ajax_referer('bsr_categories_nonce', 'nonce');
        
        $categories = $this->get_active_categories();
        wp_send_json_success($categories);
    }
    
    /**
     * Render the categories section
     */
    public function render($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Objavte podľa kategórie',
            'tab1_text' => 'Značky',
            'tab2_text' => 'Typy produktov',
            'button_text' => 'K produktom',
            'read_more_text' => 'Viac o značke',
            'read_less_text' => 'Menej',
        ), $atts);
        
        // Get initial brands
        $brands = $this->get_active_brands();
        
        // Enqueue necessary assets
        wp_localize_script('bsr-categories', 'bsr_categories', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bsr_categories_nonce')
        ));
        
        ob_start();
        ?>
        <section class="bsr-categories-section">
            <div class="bsr-container">
                <h2 class="bsr-section-title"><?php echo esc_html($atts['title']); ?></h2>
                
                <div class="bsr-category-tabs">
                    <button class="bsr-tab-button active" data-tab="brands">
                        <?php echo esc_html($atts['tab1_text']); ?>
                    </button>
                    <button class="bsr-tab-button" data-tab="types">
                        <?php echo esc_html($atts['tab2_text']); ?>
                    </button>
                </div>
                
                <div class="bsr-categories-content">
                    <!-- Brands Grid -->
                    <div id="bsr-brands-grid" class="bsr-category-grid active">
                        <?php if (!empty($brands)): ?>
                            <?php foreach ($brands as $index => $brand): ?>
                                <div class="bsr-category-card">
                                    <div class="bsr-card-content">
                                        <h3 class="bsr-card-title"><?php echo esc_html($brand['name']); ?></h3>
                                        
                                        <div class="bsr-card-image-wrapper">
                                            <?php if ($brand['image']): ?>
                                                <img src="<?php echo esc_url($brand['image']); ?>" 
                                                     alt="<?php echo esc_attr($brand['name']); ?>" 
                                                     class="bsr-card-image"
                                                     loading="lazy">
                                            <?php else: ?>
                                                <div class="bsr-placeholder-image">
                                                    <svg width="60" height="60" viewBox="0 0 60 60" fill="none">
                                                        <rect width="60" height="60" rx="8" fill="#f0f0f0"/>
                                                        <path d="M30 20v20M20 30h20" stroke="#ccc" stroke-width="2" stroke-linecap="round"/>
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <a href="<?php echo esc_url($brand['link']); ?>" 
                                           class="bsr-card-button">
                                            <?php echo esc_html($atts['button_text']); ?>
                                        </a>
                                        
                                        <?php if (!empty($brand['description'])): ?>
                                            <div class="bsr-card-description">
                                                <p class="bsr-description-text" 
                                                   data-full="<?php echo esc_attr($brand['full_description']); ?>"
                                                   data-short="<?php echo esc_attr($brand['description']); ?>">
                                                    <?php echo esc_html($brand['description']); ?>
                                                </p>
                                                <?php if (strlen($brand['full_description']) > strlen($brand['description'])): ?>
                                                    <button class="bsr-read-more-btn" 
                                                            data-more-text="<?php echo esc_attr($atts['read_more_text']); ?>"
                                                            data-less-text="<?php echo esc_attr($atts['read_less_text']); ?>">
                                                        <span class="bsr-more-text"><?php echo esc_html($atts['read_more_text']); ?></span>
                                                        <svg class="bsr-arrow-icon" width="20" height="20" viewBox="0 0 20 20">
                                                            <path d="M10 14l-5-5h10l-5 5z" fill="currentColor"/>
                                                        </svg>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="bsr-empty-state">
                                <p>Žiadne značky nie sú momentálne k dispozícii.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Product Types Grid -->
                    <div id="bsr-types-grid" class="bsr-category-grid">
                        <?php 
                        // Load categories initially for testing
                        $initial_categories = $this->get_active_categories();
                        if (!empty($initial_categories)): 
                            foreach ($initial_categories as $category): ?>
                                <div class="bsr-category-card">
                                    <div class="bsr-card-content">
                                        <?php 
                                        // Use top_parent_name if available, otherwise use parent_name
                                        $display_parent = !empty($category['top_parent_name']) ? $category['top_parent_name'] : $category['parent_name'];
                                        if (!empty($display_parent)): ?>
                                            <span class="bsr-card-parent-name"><?php echo esc_html($display_parent); ?></span>
                                        <?php endif; ?>
                                        
                                        <h3 class="bsr-card-title <?php echo !empty($category['parent_name']) ? 'has-parent' : ''; ?>">
                                            <?php echo esc_html($category['name']); ?>
                                        </h3>
                                        
                                        <div class="bsr-card-image-wrapper">
                                            <?php if ($category['image']): ?>
                                                <img src="<?php echo esc_url($category['image']); ?>" 
                                                     alt="<?php echo esc_attr($category['name']); ?>" 
                                                     class="bsr-card-image"
                                                     loading="lazy">
                                            <?php else: ?>
                                                <div class="bsr-placeholder-image">
                                                    <svg width="60" height="60" viewBox="0 0 60 60" fill="none">
                                                        <rect width="60" height="60" rx="8" fill="#f0f0f0"/>
                                                        <path d="M30 20v20M20 30h20" stroke="#ccc" stroke-width="2" stroke-linecap="round"/>
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <a href="<?php echo esc_url($category['link']); ?>" 
                                           class="bsr-card-button">
                                            <?php echo esc_html($atts['button_text']); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach;
                        else: ?>
                            <div class="bsr-categories-loading">
                                <div class="bsr-loading-spinner"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}

// Initialize the class
BSR_Categories_Section::get_instance();