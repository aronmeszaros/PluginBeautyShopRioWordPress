<?php
/**
 * WooCommerce Integration for Beauty Shop Rio
 */

if (!defined('ABSPATH')) {
    exit;
}

class BSR_WooCommerce_Integration {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('init', array($this, 'register_woo_shortcodes'));
        add_action('woocommerce_loaded', array($this, 'init_woo_features'));
    }
    
    public function register_woo_shortcodes() {
        add_shortcode('bsr_woo_hero', array($this, 'woo_hero_shortcode'));
        add_shortcode('bsr_woo_categories', array($this, 'woo_categories_shortcode'));
        add_shortcode('bsr_woo_featured_products', array($this, 'woo_featured_products_shortcode'));
    }
    
    public function init_woo_features() {
        // Add WooCommerce specific hooks and filters
        add_filter('woocommerce_add_to_cart_fragments', array($this, 'update_cart_fragments'));
    }
    
    public function woo_hero_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product_id' => '',
            'category' => 'featured',
            'show_featured' => 'true'
        ), $atts);
        
        // If specific product ID provided
        if (!empty($atts['product_id'])) {
            $product = wc_get_product($atts['product_id']);
            if ($product) {
                return $this->create_hero_from_product($product);
            }
        }
        
        // Get featured product or from category
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_stock_status',
                    'value' => 'instock'
                )
            )
        );
        
        if ($atts['show_featured'] === 'true') {
            $args['meta_query'][] = array(
                'key' => '_featured',
                'value' => 'yes'
            );
        }
        
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $atts['category']
                )
            );
        }
        
        $products = get_posts($args);
        
        if (!empty($products)) {
            $product = wc_get_product($products[0]->ID);
            return $this->create_hero_from_product($product);
        }
        
        // Fallback to original shortcode
        return BSR_Hero_Section::get_instance()->render(array());
    }
    
    private function create_hero_from_product($product) {
        $product_image = wp_get_attachment_image_url($product->get_image_id(), 'large');
        $product_title = $product->get_name();
        $product_price = strip_tags($product->get_price_html());
        $product_url = $product->get_permalink();
        
        // Get sale price if on sale
        $old_price = '';
        if ($product->is_on_sale()) {
            $old_price = strip_tags(wc_price($product->get_regular_price()));
        }
        
        $hero_atts = array(
            'product_image' => $product_image,
            'product_title' => $product_title,
            'product_price' => $product_price,
            'product_oldprice' => $old_price,
            'shop_url' => $product_url
        );
        
        return BSR_Hero_Section::get_instance()->render($hero_atts);
    }
    
    public function woo_categories_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 6,
            'hide_empty' => true,
            'exclude' => array(15) // Exclude 'Uncategorized'
        ), $atts);
        
        // Get WooCommerce product categories
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => $atts['hide_empty'],
            'number' => $atts['limit'],
            'exclude' => $atts['exclude']
        ));
        
        if (empty($categories)) {
            return BSR_Categories_Section::get_instance()->render(array());
        }
        
        ob_start();
        ?>
        <section class="bsr-categories">
            <div class="bsr-container">
                <h2>Objavte podľa kategórie</h2>
                
                <div class="bsr-categories-layout">
                    <div class="bsr-categories-sidebar">
                        <h3>Kategórie ↓</h3>
                        <ul class="bsr-brand-list">
                            <?php foreach ($categories as $index => $category): ?>
                                <li>
                                    <a href="<?php echo get_term_link($category); ?>" 
                                       class="<?php echo $index === 0 ? 'active' : ''; ?>">
                                        <?php echo $category->name; ?> →
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <div class="bsr-product-types">
                            <h3><a href="<?php echo wc_get_page_permalink('shop'); ?>">Všetky produkty →</a></h3>
                        </div>
                        
                        <a href="<?php echo wc_get_page_permalink('shop'); ?>" class="bsr-all-categories">Všetky kategórie</a>
                    </div>
                    
                    <div class="bsr-categories-grid">
                        <?php 
                        $card_styles = array('ilcsi', 'luxoya', 'farmavita', 'lifestyle', 'makeup', 'lashes');
                        foreach ($categories as $index => $category): 
                            $style_class = $card_styles[$index % count($card_styles)];
                            $category_image = get_term_meta($category->term_id, 'thumbnail_id', true);
                            $category_url = get_term_link($category);
                            $product_count = $category->count;
                        ?>
                            <div class="bsr-category-card bsr-card-<?php echo $style_class; ?>">
                                <?php if ($category_image): ?>
                                    <img src="<?php echo wp_get_attachment_image_url($category_image, 'medium'); ?>" 
                                         alt="<?php echo $category->name; ?>" class="bsr-category-image">
                                <?php endif; ?>
                                
                                <h3><?php echo $category->name; ?></h3>
                                
                                <?php if (!empty($category->description)): ?>
                                    <p><?php echo wp_trim_words($category->description, 20); ?></p>
                                <?php else: ?>
                                    <p><?php echo sprintf(_n('%d produkt', '%d produkty', $product_count, 'beauty-shop-rio'), $product_count); ?></p>
                                <?php endif; ?>
                                
                                <a href="<?php echo $category_url; ?>" class="bsr-category-btn">
                                    Zobraziť produkty (<?php echo $product_count; ?>)
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    public function woo_featured_products_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 4,
            'columns' => 4,
            'category' => ''
        ), $atts);
        
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $atts['limit'],
            'meta_query' => array(
                array(
                    'key' => '_featured',
                    'value' => 'yes'
                ),
                array(
                    'key' => '_stock_status',
                    'value' => 'instock'
                )
            )
        );
        
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $atts['category']
                )
            );
        }
        
        $products = get_posts($args);
        
        if (empty($products)) {
            return '';
        }
        
        ob_start();
        ?>
        <section class="bsr-featured-products">
            <div class="bsr-container">
                <h2>Odporúčané produkty</h2>
                <div class="bsr-products-grid bsr-columns-<?php echo $atts['columns']; ?>">
                    <?php foreach ($products as $product_post): 
                        $product = wc_get_product($product_post->ID);
                        if (!$product) continue;
                        
                        $product_image = wp_get_attachment_image_url($product->get_image_id(), 'medium');
                        $product_title = $product->get_name();
                        $product_price = $product->get_price_html();
                        $product_url = $product->get_permalink();
                    ?>
                        <div class="bsr-product-card">
                            <a href="<?php echo $product_url; ?>" class="bsr-product-link">
                                <?php if ($product_image): ?>
                                    <img src="<?php echo $product_image; ?>" alt="<?php echo esc_attr($product_title); ?>">
                                <?php endif; ?>
                                <h3><?php echo $product_title; ?></h3>
                                <div class="bsr-product-price"><?php echo $product_price; ?></div>
                            </a>
                            <?php echo do_action('woocommerce_after_shop_loop_item'); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    public function update_cart_fragments($fragments) {
        // Update cart count in BSR components if needed
        return $fragments;
    }
    
    /**
     * Get WooCommerce product data for AJAX calls
     */
    public function get_product_data($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return false;
        }
        
        return array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'price' => $product->get_price_html(),
            'image' => wp_get_attachment_image_url($product->get_image_id(), 'large'),
            'url' => $product->get_permalink(),
            'in_stock' => $product->is_in_stock(),
            'on_sale' => $product->is_on_sale()
        );
    }
}

/**
 * Hero WooCommerce Integration Helper
 * Add this to your WooCommerce integration class
 */

// Enhanced WooCommerce hero shortcode
function bsr_woo_hero_enhanced($atts) {
    $atts = shortcode_atts(array(
        'product_ids' => '', // Comma-separated product IDs
        'category' => 'featured',
        'show_featured' => 'true',
        'limit' => 5,
        'auto_rotate' => 'true',
        'show_price' => 'true',
        'show_cart_button' => 'true'
    ), $atts);
    
    $featured_products = array();
    
    // If specific product IDs provided
    if (!empty($atts['product_ids'])) {
        $product_ids = array_map('intval', explode(',', $atts['product_ids']));
        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if ($product && $product->is_in_stock()) {
                $featured_products[] = bsr_format_product_for_hero($product);
            }
        }
    } else {
        // Get products by category or featured
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => intval($atts['limit']),
            'meta_query' => array(
                array(
                    'key' => '_stock_status',
                    'value' => 'instock'
                )
            )
        );
        
        if ($atts['show_featured'] === 'true') {
            $args['meta_query'][] = array(
                'key' => '_featured',
                'value' => 'yes'
            );
        }
        
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $atts['category']
                )
            );
        }
        
        $products = get_posts($args);
        
        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);
            if ($product) {
                $featured_products[] = bsr_format_product_for_hero($product);
            }
        }
    }
    
    // If no products found, return empty
    if (empty($featured_products)) {
        return '';
    }
    
    // Prepare shortcode attributes
    $hero_atts = array(
        'featured_products' => json_encode($featured_products),
        'current_index' => 0,
        'shop_url' => wc_get_page_permalink('shop')
    );
    
    return BSR_Hero_Section::get_instance()->render($hero_atts);
}

// Format product data for hero display
function bsr_format_product_for_hero($product) {
    $product_data = array(
        'id' => $product->get_id(),
        'title' => $product->get_name(),
        'price' => strip_tags($product->get_price_html()),
        'image' => wp_get_attachment_image_url($product->get_image_id(), 'large'),
        'url' => $product->get_permalink(),
        'in_stock' => $product->is_in_stock(),
        'featured' => $product->is_featured()
    );
    
    // Get sale price if on sale
    if ($product->is_on_sale() && $product->get_regular_price()) {
        $product_data['old_price'] = strip_tags(wc_price($product->get_regular_price()));
        $product_data['price'] = strip_tags(wc_price($product->get_sale_price()));
    }
    
    return $product_data;
}

// Register the enhanced shortcode
add_shortcode('bsr_woo_hero_enhanced', 'bsr_woo_hero_enhanced');

// AJAX handler for dynamic hero product loading
add_action('wp_ajax_bsr_get_hero_products', 'bsr_handle_get_hero_products');
add_action('wp_ajax_nopriv_bsr_get_hero_products', 'bsr_handle_get_hero_products');

function bsr_handle_get_hero_products() {
    if (!wp_verify_nonce($_POST['nonce'], 'bsr_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce not available');
    }
    
    $limit = intval($_POST['limit']) ?: 5;
    $category = sanitize_text_field($_POST['category'] ?? '');
    $featured_only = filter_var($_POST['featured_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $exclude = array_map('intval', $_POST['exclude'] ?? array());
    
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $limit,
        'post__not_in' => $exclude,
        'meta_query' => array(
            array(
                'key' => '_stock_status',
                'value' => 'instock'
            )
        )
    );
    
    if ($featured_only) {
        $args['meta_query'][] = array(
            'key' => '_featured',
            'value' => 'yes'
        );
    }
    
    if (!empty($category)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $category
            )
        );
    }
    
    $products = get_posts($args);
    $hero_products = array();
    
    foreach ($products as $product_post) {
        $product = wc_get_product($product_post->ID);
        if (!$product) continue;
        
        $hero_products[] = bsr_format_product_for_hero($product);
    }
    
    wp_send_json_success($hero_products);
}

// Usage examples for the enhanced hero:

/*
// Basic usage with featured products
[bsr_woo_hero_enhanced]

// Specific products
[bsr_woo_hero_enhanced product_ids="123,456,789"]

// Products from specific category
[bsr_woo_hero_enhanced category="skincare" limit="3"]

// Non-featured products from category
[bsr_woo_hero_enhanced category="cosmetics" show_featured="false" limit="4"]

// Custom configuration
[bsr_woo_hero_enhanced 
    category="featured-collection" 
    limit="6" 
    auto_rotate="true" 
    show_price="true" 
    show_cart_button="true"]
*/