<?php
/**
 * Plugin Name: Beauty Shop Rio Design
 * Description: Custom shortcodes for Beauty Shop Rio website design with WooCommerce integration
 * Version: 1.0
 * Author: Your Name
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BSR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BSR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('BSR_VERSION', '1.0.1');

class BeautyShopRioMain {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load required files
        $this->load_dependencies();
        
        // Initialize components
        $this->init_components();
        
        // Load textdomain for translations
        load_plugin_textdomain('beauty-shop-rio', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    private function load_dependencies() {
        // Core components
        require_once BSR_PLUGIN_PATH . 'includes/class-assets-manager.php';
        require_once BSR_PLUGIN_PATH . 'includes/class-shortcodes.php';
        require_once BSR_PLUGIN_PATH . 'includes/class-ajax-handlers.php';
        
        // Individual sections
        require_once BSR_PLUGIN_PATH . 'includes/sections/class-hero-section.php';
        require_once BSR_PLUGIN_PATH . 'includes/sections/class-values-section.php';
        require_once BSR_PLUGIN_PATH . 'includes/sections/class-categories-section.php';
        require_once BSR_PLUGIN_PATH . 'includes/sections/class-newsletter-section.php';
        require_once BSR_PLUGIN_PATH . 'includes/sections/class-footer-section.php';
        
        // WooCommerce integration (only if WooCommerce is active)
        if (class_exists('WooCommerce')) {
            require_once BSR_PLUGIN_PATH . 'includes/class-woocommerce-integration.php';
        }
        
        // Admin panel
        if (is_admin()) {
            require_once BSR_PLUGIN_PATH . 'includes/admin/class-admin-panel.php';
        }
    }
    
    private function init_components() {
        // Initialize core components
        BSR_Assets_Manager::get_instance();
        BSR_Shortcodes::get_instance();
        BSR_Ajax_Handlers::get_instance();
        
        // Initialize sections
        BSR_Hero_Section::get_instance();
        BSR_Values_Section::get_instance();
        BSR_Categories_Section::get_instance();
        BSR_Newsletter_Section::get_instance();
        BSR_Footer_Section::get_instance();
        
        // Initialize WooCommerce integration if available
        if (class_exists('WooCommerce')) {
            BSR_WooCommerce_Integration::get_instance();
        }
        
        // Initialize admin panel
        if (is_admin()) {
            BSR_Admin_Panel::get_instance();
        }
    }
    
    public function activate() {
        // Create database tables
        $this->create_database_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Clean up if needed
        flush_rewrite_rules();
    }
    
    private function create_database_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bsr_subscribers';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            email varchar(100) NOT NULL,
            date_subscribed datetime DEFAULT CURRENT_TIMESTAMP,
            source varchar(50) DEFAULT 'website',
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY (id),
            UNIQUE KEY email (email)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function set_default_options() {
        $defaults = array(
            'bsr_colors' => array(
                'mint' => '#B8D4C7',
                'cream' => '#F5F1E8',
                'accent' => '#4ECDC4',
                'text_dark' => '#2D2D2D'
            ),
            'bsr_newsletter_enabled' => true,
            'bsr_woo_integration' => class_exists('WooCommerce')
        );
        
        foreach ($defaults as $option => $value) {
            if (!get_option($option)) {
                add_option($option, $value);
            }
        }
    }
}

// Initialize the plugin
BeautyShopRioMain::get_instance();