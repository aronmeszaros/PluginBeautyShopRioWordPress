<?php
/**
 * Assets Manager - Handles CSS and JS loading with modular structure
 */

if (!defined('ABSPATH')) {
    exit;
}

class BSR_Assets_Manager {
    
    private static $instance = null;
    private $loaded_sections = array();
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_head', array($this, 'add_custom_css_variables'));
        add_action('wp_footer', array($this, 'enqueue_section_styles'), 5);
    }
    
    public function enqueue_scripts() {
        // Always load core styles first
        $this->enqueue_core_styles();
        
        // Only load section-specific styles and JS if shortcodes are present
        if ($this->should_load_assets()) {
            $this->detect_and_load_sections();
            $this->enqueue_scripts_js();
        }
    }
    
    private function enqueue_core_styles() {
        $css_path = BSR_PLUGIN_PATH . 'assets/css/style.css';
        $css_url = BSR_PLUGIN_URL . 'assets/css/style.css';
        
        if (file_exists($css_path)) {
            wp_enqueue_style(
                'beauty-shop-rio-core',
                $css_url,
                array(),
                filemtime($css_path)
            );
        }
    }
    
    private function detect_and_load_sections() {
        global $post;
        
        if (!is_a($post, 'WP_Post')) {
            return;
        }
        
        $content = $post->post_content;
        
        // Check for hero sections
        if (has_shortcode($content, 'bsr_hero') || 
            has_shortcode($content, 'bsr_woo_hero') || 
            has_shortcode($content, 'bsr_full_page')) {
            $this->enqueue_section_style('hero');
        }
        
        // Check for values section
        if (has_shortcode($content, 'bsr_values') || 
            has_shortcode($content, 'bsr_full_page')) {
            $this->enqueue_section_style('values');
        }
        
        // Check for categories section
        if (has_shortcode($content, 'bsr_categories') || 
            has_shortcode($content, 'bsr_woo_categories') || 
            has_shortcode($content, 'bsr_full_page')) {
            $this->enqueue_section_style('categories');
        }
        
        // Check for newsletter section
        if (has_shortcode($content, 'bsr_newsletter') || 
            has_shortcode($content, 'bsr_full_page')) {
            $this->enqueue_section_style('newsletter');
        }
        
        // Check for footer section
        if (has_shortcode($content, 'bsr_footer') || 
            has_shortcode($content, 'bsr_full_page')) {
            $this->enqueue_section_style('footer');
        }
    }
    
    private function enqueue_section_style($section) {
        if (in_array($section, $this->loaded_sections)) {
            return; // Already loaded
        }
        
        $css_path = BSR_PLUGIN_PATH . 'assets/css/sections/' . $section . '.css';
        $css_url = BSR_PLUGIN_URL . 'assets/css/sections/' . $section . '.css';
        
        if (file_exists($css_path)) {
            wp_enqueue_style(
                'beauty-shop-rio-' . $section,
                $css_url,
                array('beauty-shop-rio-core'),
                filemtime($css_path)
            );
            
            $this->loaded_sections[] = $section;
        }
    }
    
    private function should_load_assets() {
        global $post;
        
        if (!is_a($post, 'WP_Post')) {
            return false;
        }
        
        $shortcodes = array(
            'bsr_hero', 'bsr_values', 'bsr_categories', 
            'bsr_newsletter', 'bsr_footer', 'bsr_full_page',
            'bsr_woo_hero', 'bsr_woo_categories'
        );
        
        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function enqueue_scripts_js() {
        $js_path = BSR_PLUGIN_PATH . 'assets/js/script.js';
        $js_url = BSR_PLUGIN_URL . 'assets/js/script.js';
        
        if (file_exists($js_path)) {
            wp_enqueue_script(
                'beauty-shop-rio-script',
                $js_url,
                array('jquery'),
                filemtime($js_path),
                true
            );
            
            // Add AJAX localization
            wp_localize_script('beauty-shop-rio-script', 'bsr_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bsr_nonce')
            ));
        }
    }
    
    public function enqueue_section_styles() {
        // This method can be called directly by sections if needed
        // Useful for dynamic content or AJAX-loaded sections
    }
    
    public function add_custom_css_variables() {
        if (!$this->should_load_assets()) {
            return;
        }
        
        $colors = get_option('bsr_colors', array(
            'mint' => '#B8D4C7',
            'cream' => '#F5F1E8',
            'accent' => '#4ECDC4',
            'text_dark' => '#2D2D2D'
        ));
        
        ?>
        <style id="bsr-custom-colors">
            :root {
                --bsr-mint: <?php echo esc_attr($colors['mint']); ?>;
                --bsr-cream: <?php echo esc_attr($colors['cream']); ?>;
                --bsr-accent: <?php echo esc_attr($colors['accent']); ?>;
                --bsr-text-dark: <?php echo esc_attr($colors['text_dark']); ?>;
            }
        </style>
        <?php
    }
    
    /**
     * Force load specific section styles (for AJAX or dynamic content)
     */
    public function force_load_section($section) {
        $this->enqueue_section_style($section);
    }
    
    /**
     * Get loaded sections for debugging
     */
    public function get_loaded_sections() {
        return $this->loaded_sections;
    }
    
    /**
     * Check if a specific section is loaded
     */
    public function is_section_loaded($section) {
        return in_array($section, $this->loaded_sections);
    }
    
    /**
     * Load all section styles (for full page layouts)
     */
    public function load_all_sections() {
        $sections = array('hero', 'values', 'categories', 'newsletter', 'footer');
        foreach ($sections as $section) {
            $this->enqueue_section_style($section);
        }
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles() {
        $admin_css_path = BSR_PLUGIN_PATH . 'assets/css/admin.css';
        $admin_css_url = BSR_PLUGIN_URL . 'assets/css/admin.css';
        
        if (file_exists($admin_css_path)) {
            wp_enqueue_style(
                'beauty-shop-rio-admin',
                $admin_css_url,
                array(),
                filemtime($admin_css_path)
            );
        }
    }
    
    /**
     * Inline critical CSS for above-the-fold content
     */
    public function add_critical_css() {
        if (!$this->should_load_assets()) {
            return;
        }
        
        // Add critical CSS for hero section to improve loading
        if (has_shortcode(get_post()->post_content, 'bsr_hero') || 
            has_shortcode(get_post()->post_content, 'bsr_woo_hero') ||
            has_shortcode(get_post()->post_content, 'bsr_full_page')) {
            ?>
            <style id="bsr-critical-css">
                .bsr-hero-desktop { background: linear-gradient(135deg, #B8D4C7 0%, #A8C8BB 100%); min-height: 100vh; }
                .bsr-hero-mobile { display: none; }
                @media (max-width: 1024px) {
                    .bsr-hero-desktop { display: none; }
                    .bsr-hero-mobile { display: block; background: linear-gradient(135deg, #B8D4C7 0%, #A8C8BB 100%); }
                }
            </style>
            <?php
        }
    }
}