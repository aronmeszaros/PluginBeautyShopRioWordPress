<?php
/**
 * Hero Section Component
 */

if (!defined('ABSPATH')) {
    exit;
}

class BSR_Hero_Section {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function render($atts) {
        $atts = shortcode_atts(array(
            'product_image' => BSR_PLUGIN_URL . 'assets/images/aha-medlar.png',
            'product_title' => 'AHA medlar',
            'product_price' => '14.90€',
            'shop_url' => '/shop',
            'featured_products' => '', // JSON array of featured products
            'current_index' => 0
        ), $atts);
        
        // Parse featured products if provided
        $featured_products = array();
        if (!empty($atts['featured_products'])) {
            $featured_products = json_decode($atts['featured_products'], true);
        }
        
        // Default products if no featured products (now includes 4 products total)
        if (empty($featured_products)) {
            $featured_products = array(
                array(
                    'image' => $atts['product_image'],
                    'title' => $atts['product_title'],
                    'price' => $atts['product_price'],
                    'url' => $atts['shop_url']
                ),
                array(
                    'image' => BSR_PLUGIN_URL . 'assets/images/aha-medlar.png',
                    'title' => 'Vitamin C Brightening Serum',
                    'price' => '22.50€',
                    'url' => 'https://cornflowerblue-monkey-838717.hostingersite.com/product/hyaluron-peptid-kolagen-maska-100ml'
                ),
                array(
                    'image' => BSR_PLUGIN_URL . 'assets/images/aha-medlar.png',
                    'title' => 'Hydrating Night Cream',
                    'price' => '18.75€',
                    'url' => 'https://cornflowerblue-monkey-838717.hostingersite.com/product/hyaluron-peptid-kolagen-maska-100ml'
                ),
                array(
                    'image' => BSR_PLUGIN_URL . 'assets/images/aha-medlar.png',
                    'title' => 'Gentle Cleansing Oil',
                    'price' => '16.20€',
                    'url' => 'https://cornflowerblue-monkey-838717.hostingersite.com/product/hyaluron-peptid-kolagen-maska-100ml'
                )
            );
        }
        
        $current_product = $featured_products[$atts['current_index']] ?? $featured_products[0];
        
        ob_start();
        ?>
        
        <!-- Desktop Hero Layout -->
        <section class="bsr-hero bsr-hero-desktop">
            <div class="bsr-container">
                <!-- Background decorative images -->
                <div class="bsr-hero-bg-top">
                    <img src="<?php echo BSR_PLUGIN_URL . 'assets/images/hero-bg-top.png'; ?>" alt="" class="bsr-bg-decoration">
                </div>
                <div class="bsr-hero-bg-bottom">
                    <img src="<?php echo BSR_PLUGIN_URL . 'assets/images/hero-bg-bottom.png'; ?>" alt="" class="bsr-bg-decoration">
                </div>
                
                <div class="bsr-hero-content-desktop">
                    <!-- Left Side: Product Showcase -->
                    <div class="bsr-hero-left">
                        <div class="bsr-navigation-wrapper">
                            <!-- Navigation arrows at left edge -->
                            <div class="bsr-hero-nav-vertical">
                                <button class="bsr-nav-btn bsr-nav-prev" data-direction="prev">
                                    <svg width="20" height="15" viewBox="0 0 4 3" version="1.1" xmlns="http://www.w3.org/2000/svg" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10; transform: rotate(180deg);">
                                        <g>
                                            <path d="M2.109,0.135l1.096,1.097l-1.096,1.096" style="fill:none;fill-rule:nonzero;stroke:currentColor;stroke-width:0.27px;"></path>
                                            <path d="M0.135,1.232l3.039,0" style="fill:none;fill-rule:nonzero;stroke:currentColor;stroke-width:0.27px;"></path>
                                        </g>
                                    </svg>
                                </button>
                                <!-- Navigation dots -->
                                <div class="bsr-hero-dots">
                                    <?php for ($i = 0; $i < count($featured_products); $i++): ?>
                                        <div class="bsr-dot <?php echo $i === $atts['current_index'] ? 'active' : ''; ?>" data-index="<?php echo $i; ?>"></div>
                                    <?php endfor; ?>
                                </div>
                                <button class="bsr-nav-btn bsr-nav-next" data-direction="next">
                                    <svg width="20" height="15" viewBox="0 0 4 3" version="1.1" xmlns="http://www.w3.org/2000/svg" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;">
                                        <g>
                                            <path d="M2.109,0.135l1.096,1.097l-1.096,1.096" style="fill:none;fill-rule:nonzero;stroke:currentColor;stroke-width:0.27px;"></path>
                                            <path d="M0.135,1.232l3.039,0" style="fill:none;fill-rule:nonzero;stroke:currentColor;stroke-width:0.27px;"></path>
                                        </g>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Featured Product -->
                        <div class="bsr-featured-product">
                            <div class="bsr-product-image-container">
                                <?php if (!empty($current_product['image'])): ?>
                                    <img src="<?php echo esc_url($current_product['image']); ?>" 
                                         alt="<?php echo esc_attr($current_product['title']); ?>" 
                                         class="bsr-product-image">
                                <?php else: ?>
                                    <div class="bsr-product-placeholder">
                                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                                            <circle cx="8.5" cy="8.5" r="1.5" stroke="currentColor" stroke-width="2"/>
                                            <polyline points="21,15 16,10 5,21" stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Product Info Below Image -->
                            <div class="bsr-product-details">
                                <h3 class="bsr-product-title"><?php echo esc_html($current_product['title']); ?></h3>
                                
                                <div class="bsr-product-actions">
                                    <button class="bsr-add-to-cart-btn" data-product-url="<?php echo esc_url($current_product['url']); ?>">
                                        Kúpiť teraz
                                    </button>
                                    <div class="bsr-price-display">
                                        <span class="bsr-current-price"><?php echo esc_html($current_product['price']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Side: Brand & CTA -->
                    <div class="bsr-hero-right">
                        <div class="bsr-brand-section">
                            <h1 class="bsr-main-title">Beauty Shop Rio</h1>
                            <a href="<?php echo esc_url($atts['shop_url']); ?>" class="bsr-shop-cta">
                                Do obchodu
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Mobile/Tablet Hero Layout -->
        <section class="bsr-hero bsr-hero-mobile">
            <div class="bsr-container">

                <!-- Mobile Botanical Top -->
                <div class="bsr-mobile-botanical-top">
                    <img src="<?php echo BSR_PLUGIN_URL . 'assets/images/bsr-mobile-top.png'; ?>" alt="" class="bsr-mobile-bg-decoration">
                </div>
                
                <!-- Mobile Brand Title -->
                <div class="bsr-mobile-brand-title">
                    <h1>Beauty Shop Rio</h1>
                </div>
                
                <!-- Mobile Product Showcase -->
                <div class="bsr-mobile-product-showcase">
                    <div class="bsr-mobile-nav-arrows">
                        <button class="bsr-mobile-nav-btn bsr-mobile-prev">
                            <svg width="20" height="15" viewBox="0 0 4 3" version="1.1" xmlns="http://www.w3.org/2000/svg" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10; transform: rotate(180deg);">
                                <g>
                                    <path d="M2.109,0.135l1.096,1.097l-1.096,1.096" style="fill:none;fill-rule:nonzero;stroke:currentColor;stroke-width:0.27px;"></path>
                                    <path d="M0.135,1.232l3.039,0" style="fill:none;fill-rule:nonzero;stroke:currentColor;stroke-width:0.27px;"></path>
                                </g>
                            </svg>
                        </button>
                        <button class="bsr-mobile-nav-btn bsr-mobile-next">
                            <svg width="20" height="15" viewBox="0 0 4 3" version="1.1" xmlns="http://www.w3.org/2000/svg" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;">
                                <g>
                                    <path d="M2.109,0.135l1.096,1.097l-1.096,1.096" style="fill:none;fill-rule:nonzero;stroke:currentColor;stroke-width:0.27px;"></path>
                                    <path d="M0.135,1.232l3.039,0" style="fill:none;fill-rule:nonzero;stroke:currentColor;stroke-width:0.27px;"></path>
                                </g>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="bsr-mobile-product">
                        <?php if (!empty($current_product['image'])): ?>
                            <img src="<?php echo esc_url($current_product['image']); ?>" 
                                 alt="<?php echo esc_attr($current_product['title']); ?>" 
                                 class="bsr-mobile-product-image">
                        <?php else: ?>
                            <div class="bsr-mobile-product-placeholder">Product Image</div>
                        <?php endif; ?>
                        
                        <div class="bsr-mobile-product-info">
                            <h3><?php echo esc_html($current_product['title']); ?></h3>
                            <div class="bsr-mobile-price-action">
                                <button class="bsr-mobile-buy-btn" data-product-url="<?php echo esc_url($current_product['url']); ?>">
                                    <span class="bsr-buy-text">Kúpiť teraz</span>
                                    <span class="bsr-price-separator">|</span>
                                    <div class="bsr-price-section">
                                        <span class="bsr-mobile-current-price"><?php echo esc_html($current_product['price']); ?></span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mobile dots indicator -->
                    <div class="bsr-mobile-dots">
                        <?php for ($i = 0; $i < count($featured_products); $i++): ?>
                            <div class="bsr-mobile-dot <?php echo $i === $atts['current_index'] ? 'active' : ''; ?>"></div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <!-- Mobile decorative elements -->
                <div class="bsr-mobile-botanical-bottom">
                    <img src="<?php echo BSR_PLUGIN_URL . 'assets/images/bsr-mobile-bottom.png'; ?>" alt="" class="bsr-mobile-bg-decoration">
                </div>
            </div>
        </section>
        
        <!-- Hidden data for JavaScript -->
        <script type="application/json" id="bsr-hero-data">
        {
            "featured_products": <?php echo json_encode($featured_products); ?>,
            "current_index": <?php echo $atts['current_index']; ?>,
            "shop_url": "<?php echo esc_url($atts['shop_url']); ?>"
        }
        </script>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get featured products from WooCommerce
     */
    public function get_featured_products($limit = 5) {
        if (!class_exists('WooCommerce')) {
            return array();
        }
        
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $limit,
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
        
        $products = get_posts($args);
        $featured_products = array();
        
        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);
            if (!$product) continue;
            
            $featured_products[] = array(
                'id' => $product->get_id(),
                'title' => $product->get_name(),
                'price' => strip_tags($product->get_price_html()),
                'old_price' => $product->is_on_sale() ? strip_tags(wc_price($product->get_regular_price())) : '',
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'large'),
                'url' => $product->get_permalink()
            );
        }
        
        return $featured_products;
    }
}