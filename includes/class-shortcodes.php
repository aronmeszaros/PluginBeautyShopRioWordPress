<?php
/**
 * Shortcodes Manager - Coordinates all shortcode registrations
 */

if (!defined('ABSPATH')) {
    exit;
}

class BSR_Shortcodes {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('init', array($this, 'register_shortcodes'));
    }
    
    public function register_shortcodes() {
        // Basic shortcodes
        add_shortcode('bsr_hero', array($this, 'hero_shortcode'));
        add_shortcode('bsr_values', array($this, 'values_shortcode'));
        add_shortcode('bsr_categories', array($this, 'categories_shortcode'));
        add_shortcode('bsr_newsletter', array($this, 'newsletter_shortcode'));
        add_shortcode('bsr_footer', array($this, 'footer_shortcode'));
        add_shortcode('bsr_full_page', array($this, 'full_page_shortcode'));
    }
    
    public function hero_shortcode($atts) {
        return BSR_Hero_Section::get_instance()->render($atts);
    }
    
    public function values_shortcode($atts) {
        return BSR_Values_Section::get_instance()->render($atts);
    }
    
    public function categories_shortcode($atts) {
        return BSR_Categories_Section::get_instance()->render($atts);
    }
    
    public function newsletter_shortcode($atts) {
        return BSR_Newsletter_Section::get_instance()->render($atts);
    }
    
    public function footer_shortcode($atts) {
        return BSR_Footer_Section::get_instance()->render($atts);
    }
    
    public function full_page_shortcode($atts) {
        $atts = shortcode_atts(array(
            'hero_product_image' => '',
            'hero_product_title' => 'AHA medlar',
            'hero_product_price' => '14.90€',
            'model_image' => '',
            'newsletter_image' => ''
        ), $atts);
        
        $output = '';
        $output .= BSR_Hero_Section::get_instance()->render($atts);
        $output .= BSR_Values_Section::get_instance()->render($atts);
        $output .= BSR_Categories_Section::get_instance()->render($atts);
        $output .= BSR_Newsletter_Section::get_instance()->render($atts);
        
        return $output;
    }
}