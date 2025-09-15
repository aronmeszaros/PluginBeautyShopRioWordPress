<?php
/**
 * Categories Section Component with Pagination
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
        add_action('wp_ajax_bsr_load_more_categories', array($this, 'ajax_load_more_categories'));
        add_action('wp_ajax_nopriv_bsr_load_more_categories', array($this, 'ajax_load_more_categories'));
    }
    
    /**
     * Get brands with published products
     */
    private function get_active_brands($offset = 0, $limit = 9) {
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
            return array('items' => $brands, 'total' => 0, 'has_more' => false);
        }
        
        $brand_terms = get_terms(array(
            'taxonomy' => $brand_taxonomy,
            'hide_empty' => false,
        ));
        
        $all_brands = array();
        
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
                    
                    $all_brands[] = array(
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
        
        $total = count($all_brands);
        $paginated_brands = array_slice($all_brands, $offset, $limit);
        $has_more = ($offset + $limit) < $total;
        
        return array(
            'items' => $paginated_brands,
            'total' => $total,
            'has_more' => $has_more
        );
    }
    
    /**
     * Get active categories with pagination
     */
    private function get_active_categories($offset = 0, $limit = 9) {
        $categories = array();
        
        // Get ALL product categories, not just top-level
        $cat_terms = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));
        
        $all_categories = array();
        
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
                    'fields' => 'ids',
                ));
                
                // Only process categories WITHOUT children (leaf categories)
                if (empty($children)) {
                    // Check if this leaf category has published products
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
                        
                        $all_categories[] = array(
                            'id' => $category->term_id,
                            'name' => $category->name,
                            'slug' => $category->slug,
                            'image' => $image_url,
                            'link' => get_term_link($category),
                            'count' => $category->count,
                            'parent_id' => $category->parent,
                            'top_parent_name' => $top_parent_name,
                        );
                    }
                }
            }
        }
        
        // Sort categories by name
        usort($all_categories, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        $total = count($all_categories);
        $paginated_categories = array_slice($all_categories, $offset, $limit);
        $has_more = ($offset + $limit) < $total;
        
        return array(
            'items' => $paginated_categories,
            'total' => $total,
            'has_more' => $has_more
        );
    }
    
    /**
     * Check if category has published products
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
        
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 9;
        
        $result = $this->get_active_brands($offset, $limit);
        wp_send_json_success($result);
    }
    
    /**
     * AJAX handler for getting product types
     */
    public function ajax_get_product_types() {
        check_ajax_referer('bsr_categories_nonce', 'nonce');
        
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 9;
        
        $result = $this->get_active_categories($offset, $limit);
        wp_send_json_success($result);
    }
    
    /**
     * AJAX handler for loading more categories (unified)
     */
    public function ajax_load_more_categories() {
        check_ajax_referer('bsr_categories_nonce', 'nonce');
        
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'types';
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 9;
        
        if ($type === 'brands') {
            $result = $this->get_active_brands($offset, $limit);
        } else {
            $result = $this->get_active_categories($offset, $limit);
        }
        
        wp_send_json_success($result);
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
            'load_more_text' => 'Načítať viac',
            'items_per_page' => 9,
        ), $atts);
        
        // Get initial brands (first 9)
        $brands_data = $this->get_active_brands(0, intval($atts['items_per_page']));
        $brands = $brands_data['items'];
        $brands_has_more = $brands_data['has_more'];
        
        // Enqueue necessary assets
        wp_localize_script('bsr-categories', 'bsr_categories', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bsr_categories_nonce'),
            'plugin_url' => BSR_PLUGIN_URL,
            'items_per_page' => intval($atts['items_per_page']),
            'load_more_text' => $atts['load_more_text']
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
                    <div id="bsr-brands-grid" class="bsr-category-grid active" data-type="brands">
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
                                                <img src="<?php echo BSR_PLUGIN_URL . 'assets/images/placeholder-products.png'; ?>" 
                                                     alt="<?php echo esc_attr($brand['name']); ?>" 
                                                     class="bsr-card-image bsr-placeholder-img"
                                                     loading="lazy">
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
                        
                        <?php if ($brands_has_more): ?>
                            <div class="bsr-load-more-container">
                                <button class="bsr-load-more-btn" data-type="brands">
                                    <span class="bsr-load-more-text"><?php echo esc_html($atts['load_more_text']); ?></span>
                                    <div class="bsr-load-more-spinner" style="display: none;">
                                        <div class="bsr-spinner"></div>
                                    </div>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Product Types Grid -->
                    <div id="bsr-types-grid" class="bsr-category-grid" data-type="types">
                        <div class="bsr-categories-loading">
                            <div class="bsr-loading-spinner"></div>
                        </div>
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