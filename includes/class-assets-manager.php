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
    private $section_scripts = array(); // Track which section scripts are loaded
    
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
            $this->enqueue_section_scripts(); // New method for section-specific JS
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
            $this->enqueue_section_script('categories'); // Load categories JS
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
    
    /**
     * Enqueue section-specific JavaScript files
     */
    private function enqueue_section_script($section) {
        if (in_array($section, $this->section_scripts)) {
            return; // Already loaded
        }
        
        $js_path = BSR_PLUGIN_PATH . 'assets/js/' . $section . '.js';
        $js_url = BSR_PLUGIN_URL . 'assets/js/' . $section . '.js';
        
        if (file_exists($js_path)) {
            // Dependencies array - add jQuery and main script
            $deps = array('jquery');
            if (file_exists(BSR_PLUGIN_PATH . 'assets/js/script.js')) {
                $deps[] = 'beauty-shop-rio-script';
            }
            
            wp_enqueue_script(
                'bsr-' . $section,
                $js_url,
                $deps,
                filemtime($js_path),
                true
            );
            
            // Add section-specific localizations
            $this->add_section_localizations($section);
            
            $this->section_scripts[] = $section;
        }
    }
    
    /**
     * Add section-specific JavaScript localizations
     */
    private function add_section_localizations($section) {
        switch ($section) {
            case 'categories':
                wp_localize_script('bsr-categories', 'bsr_categories', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('bsr_categories_nonce'),
                    'loading_text' => __('Načítava sa...', 'beauty-shop-rio'),
                    'error_text' => __('Nastala chyba pri načítavaní.', 'beauty-shop-rio'),
                    'no_brands_text' => __('Žiadne značky nie sú momentálne k dispozícii.', 'beauty-shop-rio'),
                    'no_categories_text' => __('Žiadne kategórie produktov nie sú momentálne k dispozícii.', 'beauty-shop-rio')
                ));
                break;
            
            // Add other section localizations as needed
            case 'newsletter':
                // If newsletter has its own JS in the future
                break;
        }
    }
    
    /**
     * Enqueue section-specific scripts for dynamic content
     */
    private function enqueue_section_scripts() {
        global $post;
        
        if (!is_a($post, 'WP_Post')) {
            return;
        }
        
        $content = $post->post_content;
        
        // Check if any section needs additional JS files
        // This is separate from the main script.js to keep things modular
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
            
            // Add general AJAX localization
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
            'text_dark' => '#2D2D2D',
            'primary_color' => '#00b5a5', // Add primary color for categories
            'section_bg' => '#c8d4d0' // Add section background
        ));
        
        ?>
        <style id="bsr-custom-colors">
            :root {
                --bsr-mint: <?php echo esc_attr($colors['mint']); ?>;
                --bsr-cream: <?php echo esc_attr($colors['cream']); ?>;
                --bsr-accent: <?php echo esc_attr($colors['accent']); ?>;
                --bsr-text-dark: <?php echo esc_attr($colors['text_dark']); ?>;
                --bsr-primary-color: <?php echo esc_attr($colors['primary_color']); ?>;
                --bsr-section-bg: <?php echo esc_attr($colors['section_bg']); ?>;
                --bsr-text-primary: #1a3d3d;
                --bsr-text-secondary: #6b7c78;
                --bsr-container-width: 1200px;
                --bsr-container-padding: 20px;
                --bsr-font-primary: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
        </style>
        <?php
    }
    
    /**
     * Force load specific section styles and scripts (for AJAX or dynamic content)
     */
    public function force_load_section($section, $with_script = false) {
        $this->enqueue_section_style($section);
        
        if ($with_script) {
            $this->enqueue_section_script($section);
        }
    }
    
    /**
     * Get loaded sections for debugging
     */
    public function get_loaded_sections() {
        return array(
            'styles' => $this->loaded_sections,
            'scripts' => $this->section_scripts
        );
    }
    
    /**
     * Check if a specific section is loaded
     */
    public function is_section_loaded($section, $type = 'style') {
        if ($type === 'style') {
            return in_array($section, $this->loaded_sections);
        } elseif ($type === 'script') {
            return in_array($section, $this->section_scripts);
        }
        
        return false;
    }
    
    /**
     * Load all section styles (for full page layouts)
     */
    public function load_all_sections() {
        $sections = array('hero', 'values', 'categories', 'newsletter', 'footer');
        
        foreach ($sections as $section) {
            $this->enqueue_section_style($section);
            
            // Check if section has its own JS file
            if (file_exists(BSR_PLUGIN_PATH . 'assets/js/' . $section . '.js')) {
                $this->enqueue_section_script($section);
            }
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