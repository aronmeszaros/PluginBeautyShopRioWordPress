<?php
/**
 * Admin Panel for Beauty Shop Rio
 */

if (!defined('ABSPATH')) {
    exit;
}

class BSR_Admin_Panel {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            'Beauty Shop Rio',
            'Beauty Shop Rio',
            'manage_options',
            'beauty-shop-rio',
            array($this, 'main_admin_page'),
            'dashicons-admin-appearance',
            30
        );
        
        // Subscribers submenu
        add_submenu_page(
            'beauty-shop-rio',
            'Newsletter Subscribers',
            'Subscribers',
            'manage_options',
            'bsr-subscribers',
            array($this, 'subscribers_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'beauty-shop-rio',
            'Settings',
            'Settings',
            'manage_options',
            'bsr-settings',
            array($this, 'settings_page')
        );
        
        // Shortcode Helper submenu
        add_submenu_page(
            'beauty-shop-rio',
            'Shortcode Helper',
            'Shortcode Helper',
            'manage_options',
            'bsr-shortcodes',
            array($this, 'shortcodes_page')
        );
    }
    
    public function init_settings() {
        register_setting('bsr_settings_group', 'bsr_colors');
        register_setting('bsr_settings_group', 'bsr_newsletter_enabled');
        register_setting('bsr_settings_group', 'bsr_welcome_email_subject');
        register_setting('bsr_settings_group', 'bsr_welcome_email_message');
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'beauty-shop-rio') !== false) {
            wp_enqueue_style('bsr-admin-style', BSR_PLUGIN_URL . 'assets/css/admin.css', array(), BSR_VERSION);
            wp_enqueue_script('bsr-admin-script', BSR_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), BSR_VERSION, true);
            wp_enqueue_media(); // For image uploads
        }
    }
    
    public function main_admin_page() {
        $ajax_handlers = BSR_Ajax_Handlers::get_instance();
        $stats = $ajax_handlers->get_newsletter_stats();
        ?>
        <div class="wrap">
            <h1>Beauty Shop Rio Dashboard</h1>
            
            <div class="bsr-admin-dashboard">
                <div class="bsr-stats-grid">
                    <div class="bsr-stat-box">
                        <h3>Total Subscribers</h3>
                        <div class="bsr-stat-number"><?php echo $stats['total']; ?></div>
                    </div>
                    
                    <div class="bsr-stat-box">
                        <h3>Active Subscribers</h3>
                        <div class="bsr-stat-number"><?php echo $stats['active']; ?></div>
                    </div>
                    
                    <div class="bsr-stat-box">
                        <h3>New Today</h3>
                        <div class="bsr-stat-number"><?php echo $stats['today']; ?></div>
                    </div>
                    
                    <div class="bsr-stat-box">
                        <h3>This Month</h3>
                        <div class="bsr-stat-number"><?php echo $stats['this_month']; ?></div>
                    </div>
                </div>
                
                <div class="bsr-quick-actions">
                    <h2>Quick Actions</h2>
                    <div class="bsr-action-buttons">
                        <a href="<?php echo admin_url('admin.php?page=bsr-shortcodes'); ?>" class="button button-primary">
                            View Shortcodes
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=bsr-subscribers'); ?>" class="button">
                            Manage Subscribers
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=bsr-settings'); ?>" class="button">
                            Settings
                        </a>
                    </div>
                </div>
                
                <div class="bsr-recent-activity">
                    <h2>Recent Subscribers</h2>
                    <?php $this->display_recent_subscribers(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function subscribers_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bsr_subscribers';
        
        // Handle bulk actions
        if (isset($_POST['action']) && $_POST['action'] === 'delete_selected') {
            $selected = $_POST['subscriber_ids'];
            if (!empty($selected)) {
                $ids = implode(',', array_map('intval', $selected));
                $wpdb->query("DELETE FROM $table_name WHERE id IN ($ids)");
                echo '<div class="notice notice-success"><p>Selected subscribers deleted.</p></div>';
            }
        }
        
        $subscribers = $wpdb->get_results("SELECT * FROM $table_name ORDER BY date_subscribed DESC LIMIT 100");
        ?>
        <div class="wrap">
            <h1>Newsletter Subscribers</h1>
            
            <form method="post">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column">
                                <input id="cb-select-all-1" type="checkbox">
                            </td>
                            <th>Email</th>
                            <th>Date Subscribed</th>
                            <th>Source</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscribers as $subscriber): ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="subscriber_ids[]" value="<?php echo $subscriber->id; ?>">
                            </th>
                            <td><?php echo esc_html($subscriber->email); ?></td>
                            <td><?php echo esc_html($subscriber->date_subscribed); ?></td>
                            <td><?php echo esc_html($subscriber->source); ?></td>
                            <td>
                                <span class="bsr-status bsr-status-<?php echo $subscriber->status; ?>">
                                    <?php echo esc_html($subscriber->status); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="tablenav bottom">
                    <div class="alignleft actions bulkactions">
                        <select name="action">
                            <option value="">Bulk Actions</option>
                            <option value="delete_selected">Delete</option>
                        </select>
                        <input type="submit" class="button action" value="Apply">
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
    
    public function settings_page() {
        if (isset($_POST['submit'])) {
            $colors = array(
                'mint' => sanitize_hex_color($_POST['bsr_colors']['mint']),
                'cream' => sanitize_hex_color($_POST['bsr_colors']['cream']),
                'accent' => sanitize_hex_color($_POST['bsr_colors']['accent']),
                'text_dark' => sanitize_hex_color($_POST['bsr_colors']['text_dark'])
            );
            
            update_option('bsr_colors', $colors);
            update_option('bsr_newsletter_enabled', isset($_POST['bsr_newsletter_enabled']));
            update_option('bsr_welcome_email_subject', sanitize_text_field($_POST['bsr_welcome_email_subject']));
            update_option('bsr_welcome_email_message', wp_kses_post($_POST['bsr_welcome_email_message']));
            
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        $colors = get_option('bsr_colors', array(
            'mint' => '#B8D4C7',
            'cream' => '#F5F1E8',
            'accent' => '#4ECDC4',
            'text_dark' => '#2D2D2D'
        ));
        
        $newsletter_enabled = get_option('bsr_newsletter_enabled', true);
        $welcome_subject = get_option('bsr_welcome_email_subject', 'Welcome to Beauty Shop Rio Newsletter!');
        $welcome_message = get_option('bsr_welcome_email_message', 'Thank you for subscribing to our newsletter.');
        ?>
        <div class="wrap">
            <h1>Beauty Shop Rio Settings</h1>
            
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row">Color Scheme</th>
                        <td>
                            <fieldset>
                                <label>
                                    Mint Color: 
                                    <input type="color" name="bsr_colors[mint]" value="<?php echo esc_attr($colors['mint']); ?>">
                                </label><br><br>
                                
                                <label>
                                    Cream Color: 
                                    <input type="color" name="bsr_colors[cream]" value="<?php echo esc_attr($colors['cream']); ?>">
                                </label><br><br>
                                
                                <label>
                                    Accent Color: 
                                    <input type="color" name="bsr_colors[accent]" value="<?php echo esc_attr($colors['accent']); ?>">
                                </label><br><br>
                                
                                <label>
                                    Text Color: 
                                    <input type="color" name="bsr_colors[text_dark]" value="<?php echo esc_attr($colors['text_dark']); ?>">
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Newsletter</th>
                        <td>
                            <label>
                                <input type="checkbox" name="bsr_newsletter_enabled" <?php checked($newsletter_enabled); ?>>
                                Enable Newsletter Signup
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Welcome Email Subject</th>
                        <td>
                            <input type="text" name="bsr_welcome_email_subject" value="<?php echo esc_attr($welcome_subject); ?>" class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Welcome Email Message</th>
                        <td>
                            <textarea name="bsr_welcome_email_message" rows="5" class="large-text"><?php echo esc_textarea($welcome_message); ?></textarea>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function shortcodes_page() {
        ?>
        <div class="wrap">
            <h1>Beauty Shop Rio Shortcodes</h1>
            
            <div class="bsr-shortcodes-help">
                <div class="bsr-shortcode-section">
                    <h2>Basic Shortcodes</h2>
                    
                    <div class="bsr-shortcode-item">
                        <h3>Full Page Layout</h3>
                        <code>[bsr_full_page hero_product_image="URL" model_image="URL" newsletter_image="URL"]</code>
                        <p>Displays all sections in one complete page layout.</p>
                    </div>
                    
                    <div class="bsr-shortcode-item">
                        <h3>Hero Section</h3>
                        <code>[bsr_hero product_image="URL" product_title="Product Name" product_price="€19.90" shop_url="/shop"]</code>
                        <p>Displays the hero section with product showcase.</p>
                    </div>
                    
                    <div class="bsr-shortcode-item">
                        <h3>Values Section</h3>
                        <code>[bsr_values model_image="URL" title="Your Title" organic_text="Your text" usage_text="Your text"]</code>
                        <p>Displays the values/benefits section with model image.</p>
                    </div>
                    
                    <div class="bsr-shortcode-item">
                        <h3>Categories Section</h3>
                        <code>[bsr_categories]</code>
                        <p>Displays the categories grid with sidebar.</p>
                    </div>
                    
                    <div class="bsr-shortcode-item">
                        <h3>Newsletter Section</h3>
                        <code>[bsr_newsletter product_image="URL" title="Newsletter Title" description="Your description"]</code>
                        <p>Displays newsletter signup form with product image.</p>
                    </div>
                    
                    <div class="bsr-shortcode-item">
                        <h3>Footer Section</h3>
                        <code>[bsr_footer company_name="Your Company" show_social="true" show_links="true"]</code>
                        <p>Displays the footer with company information.</p>
                    </div>
                </div>
                
                <?php if (class_exists('WooCommerce')): ?>
                <div class="bsr-shortcode-section">
                    <h2>WooCommerce Integration Shortcodes</h2>
                    
                    <div class="bsr-shortcode-item">
                        <h3>WooCommerce Hero</h3>
                        <code>[bsr_woo_hero product_id="123"]</code>
                        <p>Display specific product in hero section.</p>
                        
                        <code>[bsr_woo_hero category="skincare" show_featured="true"]</code>
                        <p>Display featured product from specific category.</p>
                    </div>
                    
                    <div class="bsr-shortcode-item">
                        <h3>WooCommerce Categories</h3>
                        <code>[bsr_woo_categories limit="6" hide_empty="true"]</code>
                        <p>Display WooCommerce product categories with product counts.</p>
                    </div>
                    
                    <div class="bsr-shortcode-item">
                        <h3>Featured Products</h3>
                        <code>[bsr_woo_featured_products limit="4" columns="4" category="skincare"]</code>
                        <p>Display featured products in a grid layout.</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="bsr-shortcode-section">
                    <h2>Usage Examples</h2>
                    
                    <div class="bsr-usage-example">
                        <h3>Homepage Layout</h3>
                        <textarea readonly class="large-text code">
[bsr_woo_hero category="featured"]
[bsr_values model_image="/wp-content/uploads/model.jpg"]
[bsr_woo_categories limit="6"]
[bsr_newsletter newsletter_image="/wp-content/uploads/products.jpg"]
[bsr_footer]</textarea>
                    </div>
                    
                    <div class="bsr-usage-example">
                        <h3>Landing Page</h3>
                        <textarea readonly class="large-text code">
[bsr_full_page 
    hero_product_image="/wp-content/uploads/hero-product.jpg"
    model_image="/wp-content/uploads/model.jpg"
    newsletter_image="/wp-content/uploads/newsletter-products.jpg"]</textarea>
                    </div>
                    
                    <div class="bsr-usage-example">
                        <h3>Category Page Enhancement</h3>
                        <textarea readonly class="large-text code">
[bsr_hero product_image="/wp-content/uploads/category-hero.jpg" product_title="Skincare Collection" shop_url="/product-category/skincare/"]

<!-- Your WooCommerce products display here -->
[products category="skincare" limit="12"]

[bsr_newsletter]</textarea>
                    </div>
                </div>
                
                <div class="bsr-tips-section">
                    <h2>Tips & Best Practices</h2>
                    <ul>
                        <li><strong>Image Optimization:</strong> Use WebP format, 1200px width max for hero images</li>
                        <li><strong>Performance:</strong> Shortcodes only load CSS/JS when needed</li>
                        <li><strong>Mobile:</strong> All sections are fully responsive</li>
                        <li><strong>SEO:</strong> Use descriptive alt text for images</li>
                        <li><strong>Colors:</strong> Customize colors in Settings to match your brand</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <style>
        .bsr-shortcodes-help { margin-top: 20px; }
        .bsr-shortcode-section { margin-bottom: 40px; }
        .bsr-shortcode-item { 
            background: #f9f9f9; 
            padding: 20px; 
            margin-bottom: 20px; 
            border-left: 4px solid #00a0d2;
        }
        .bsr-shortcode-item h3 { margin-top: 0; }
        .bsr-shortcode-item code { 
            background: #fff; 
            padding: 10px; 
            display: block; 
            margin: 10px 0;
            border: 1px solid #ddd;
        }
        .bsr-usage-example { margin-bottom: 20px; }
        .bsr-usage-example textarea { height: 100px; }
        .bsr-tips-section ul { margin-left: 20px; }
        .bsr-tips-section li { margin-bottom: 8px; }
        </style>
        <?php
    }
    
    private function display_recent_subscribers() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bsr_subscribers';
        $recent = $wpdb->get_results("SELECT * FROM $table_name ORDER BY date_subscribed DESC LIMIT 5");
        
        if (empty($recent)) {
            echo '<p>No recent subscribers.</p>';
            return;
        }
        ?>
        <table class="wp-list-table widefat">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $subscriber): ?>
                <tr>
                    <td><?php echo esc_html($subscriber->email); ?></td>
                    <td><?php echo esc_html(human_time_diff(strtotime($subscriber->date_subscribed))); ?> ago</td>
                    <td>
                        <span class="bsr-status bsr-status-<?php echo $subscriber->status; ?>">
                            <?php echo esc_html($subscriber->status); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
}