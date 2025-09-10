<?php
/**
 * AJAX Handlers for Beauty Shop Rio
 */

if (!defined('ABSPATH')) {
    exit;
}

class BSR_Ajax_Handlers {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Newsletter handlers
        add_action('wp_ajax_bsr_newsletter_signup', array($this, 'handle_newsletter_signup'));
        add_action('wp_ajax_nopriv_bsr_newsletter_signup', array($this, 'handle_newsletter_signup'));
        add_action('wp_ajax_bsr_newsletter_unsubscribe', array($this, 'handle_newsletter_unsubscribe'));
        add_action('wp_ajax_nopriv_bsr_newsletter_unsubscribe', array($this, 'handle_newsletter_unsubscribe'));
        
        // Product handlers
        add_action('wp_ajax_bsr_get_product_data', array($this, 'handle_get_product_data'));
        add_action('wp_ajax_nopriv_bsr_get_product_data', array($this, 'handle_get_product_data'));
        add_action('wp_ajax_bsr_get_featured_products', array($this, 'handle_get_featured_products'));
        add_action('wp_ajax_nopriv_bsr_get_featured_products', array($this, 'handle_get_featured_products'));
        
        // Category handlers
        add_action('wp_ajax_bsr_filter_categories', array($this, 'handle_filter_categories'));
        add_action('wp_ajax_nopriv_bsr_filter_categories', array($this, 'handle_filter_categories'));
        add_action('wp_ajax_bsr_get_category_products', array($this, 'handle_get_category_products'));
        add_action('wp_ajax_nopriv_bsr_get_category_products', array($this, 'handle_get_category_products'));
        
        // Hero carousel handlers
        add_action('wp_ajax_bsr_get_hero_products', array($this, 'handle_get_hero_products'));
        add_action('wp_ajax_nopriv_bsr_get_hero_products', array($this, 'handle_get_hero_products'));
        
        // Admin handlers
        add_action('wp_ajax_bsr_export_subscribers', array($this, 'handle_export_subscribers'));
        add_action('wp_ajax_bsr_bulk_delete_subscribers', array($this, 'handle_bulk_delete_subscribers'));
    }
    
    /**
     * Handle newsletter signup
     */
    public function handle_newsletter_signup() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'bsr_nonce')) {
            wp_send_json_error(array(
                'message' => 'Security check failed',
                'code' => 'security_error'
            ));
        }
        
        $email = sanitize_email($_POST['email']);
        $source = sanitize_text_field($_POST['source'] ?? 'website');
        
        if (!is_email($email)) {
            wp_send_json_error(array(
                'message' => 'Neplatná emailová adresa',
                'code' => 'invalid_email'
            ));
        }
        
        // Check if email already exists
        global $wpdb;
        $table_name = $wpdb->prefix . 'bsr_subscribers';
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE email = %s",
            $email
        ));
        
        if ($existing) {
            if ($existing->status === 'unsubscribed') {
                // Reactivate unsubscribed user
                $result = $wpdb->update(
                    $table_name,
                    array(
                        'status' => 'active',
                        'date_subscribed' => current_time('mysql')
                    ),
                    array('email' => $email),
                    array('%s', '%s'),
                    array('%s')
                );
                
                if ($result !== false) {
                    $this->send_welcome_email($email);
                    do_action('bsr_newsletter_resubscribe', $email);
                    
                    wp_send_json_success(array(
                        'message' => 'Úspešne ste sa znovu prihlásili na odber noviniek!'
                    ));
                }
            } else {
                wp_send_json_error(array(
                    'message' => 'Tento email je už prihlásený na odber',
                    'code' => 'already_subscribed'
                ));
            }
        }
        
        // Insert new subscriber
        $result = $wpdb->insert(
            $table_name,
            array(
                'email' => $email,
                'date_subscribed' => current_time('mysql'),
                'source' => $source,
                'status' => 'active'
            ),
            array('%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error(array(
                'message' => 'Chyba pri ukladaní prihlásenia',
                'code' => 'database_error'
            ));
        }
        
        // Send confirmation email
        $email_sent = $this->send_welcome_email($email);
        
        // Hook for third-party integrations (MailChimp, ConvertKit, etc.)
        do_action('bsr_newsletter_signup', $email, $source);
        
        wp_send_json_success(array(
            'message' => 'Úspešne ste sa prihlásili na odber noviniek!',
            'email_sent' => $email_sent
        ));
    }
    
    /**
     * Handle newsletter unsubscribe
     */
    public function handle_newsletter_unsubscribe() {
        if (!wp_verify_nonce($_POST['nonce'], 'bsr_nonce')) {
            wp_send_json_error(array(
                'message' => 'Security check failed',
                'code' => 'security_error'
            ));
        }
        
        $email = sanitize_email($_POST['email']);
        
        if (!is_email($email)) {
            wp_send_json_error(array(
                'message' => 'Neplatná emailová adresa',
                'code' => 'invalid_email'
            ));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'bsr_subscribers';
        
        $result = $wpdb->update(
            $table_name,
            array('status' => 'unsubscribed'),
            array('email' => $email),
            array('%s'),
            array('%s')
        );
        
        if ($result === false) {
            wp_send_json_error(array(
                'message' => 'Chyba pri odhlasovaní',
                'code' => 'database_error'
            ));
        }
        
        do_action('bsr_newsletter_unsubscribe', $email);
        
        wp_send_json_success(array(
            'message' => 'Úspešne ste sa odhlásili z odberu noviniek'
        ));
    }
    
    /**
     * Handle get product data
     */
    public function handle_get_product_data() {
        if (!wp_verify_nonce($_POST['nonce'], 'bsr_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $product_id = intval($_POST['product_id']);
        
        if (!$product_id) {
            wp_send_json_error('Invalid product ID');
        }
        
        // Check if WooCommerce is available
        if (!class_exists('WooCommerce')) {
            wp_send_json_error('WooCommerce not available');
        }
        
        $product = wc_get_product($product_id);
        
        if (!$product) {
            wp_send_json_error('Product not found');
        }
        
        $product_data = array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'price' => $product->get_price_html(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'image' => wp_get_attachment_image_url($product->get_image_id(), 'large'),
            'gallery' => $this->get_product_gallery($product),
            'url' => $product->get_permalink(),
            'description' => $product->get_short_description(),
            'in_stock' => $product->is_in_stock(),
            'stock_quantity' => $product->get_stock_quantity(),
            'on_sale' => $product->is_on_sale(),
            'featured' => $product->is_featured(),
            'categories' => $this->get_product_categories($product),
            'attributes' => $this->get_product_attributes($product)
        );
        
        wp_send_json_success($product_data);
    }
    
    /**
     * Handle get featured products
     */
    public function handle_get_featured_products() {
        if (!wp_verify_nonce($_POST['nonce'], 'bsr_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!class_exists('WooCommerce')) {
            wp_send_json_error('WooCommerce not available');
        }
        
        $limit = intval($_POST['limit']) ?: 4;
        $category = sanitize_text_field($_POST['category'] ?? '');
        $exclude = array_map('intval', $_POST['exclude'] ?? array());
        
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $limit,
            'post__not_in' => $exclude,
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
        $product_data = array();
        
        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);
            if (!$product) continue;
            
            $product_data[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_price_html(),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'medium'),
                'url' => $product->get_permalink(),
                'on_sale' => $product->is_on_sale(),
                'featured' => $product->is_featured()
            );
        }
        
        wp_send_json_success($product_data);
    }
    
    /**
     * Handle filter categories
     */
    public function handle_filter_categories() {
        if (!wp_verify_nonce($_POST['nonce'], 'bsr_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $category_slug = sanitize_text_field($_POST['category'] ?? '');
        $limit = intval($_POST['limit']) ?: 6;
        $hide_empty = filter_var($_POST['hide_empty'] ?? true, FILTER_VALIDATE_BOOLEAN);
        
        if (!class_exists('WooCommerce')) {
            wp_send_json_error('WooCommerce not available');
        }
        
        $args = array(
            'taxonomy' => 'product_cat',
            'hide_empty' => $hide_empty,
            'number' => $limit,
            'exclude' => array(15) // Exclude 'Uncategorized'
        );
        
        if ($category_slug && $category_slug !== 'all') {
            $args['slug'] = array($category_slug);
        }
        
        $categories = get_terms($args);
        
        if (empty($categories)) {
            wp_send_json_error('No categories found');
        }
        
        $category_data = array();
        $card_styles = array('ilcsi', 'luxoya', 'farmavita', 'lifestyle', 'makeup', 'lashes');
        
        foreach ($categories as $index => $category) {
            $style_class = $card_styles[$index % count($card_styles)];
            $category_image = get_term_meta($category->term_id, 'thumbnail_id', true);
            
            $category_data[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'count' => $category->count,
                'url' => get_term_link($category),
                'image' => $category_image ? wp_get_attachment_image_url($category_image, 'medium') : '',
                'style_class' => $style_class
            );
        }
        
        wp_send_json_success($category_data);
    }
    
    /**
     * Handle get category products
     */
    public function handle_get_category_products() {
        if (!wp_verify_nonce($_POST['nonce'], 'bsr_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!class_exists('WooCommerce')) {
            wp_send_json_error('WooCommerce not available');
        }
        
        $category_id = intval($_POST['category_id']);
        $limit = intval($_POST['limit']) ?: 12;
        $orderby = sanitize_text_field($_POST['orderby'] ?? 'date');
        $order = sanitize_text_field($_POST['order'] ?? 'DESC');
        
        if (!$category_id) {
            wp_send_json_error('Invalid category ID');
        }
        
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $limit,
            'orderby' => $orderby,
            'order' => $order,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category_id
                )
            ),
            'meta_query' => array(
                array(
                    'key' => '_stock_status',
                    'value' => 'instock'
                )
            )
        );
        
        $products = get_posts($args);
        $product_data = array();
        
        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);
            if (!$product) continue;
            
            $product_data[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_price_html(),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'medium'),
                'url' => $product->get_permalink(),
                'on_sale' => $product->is_on_sale(),
                'featured' => $product->is_featured(),
                'rating' => $product->get_average_rating()
            );
        }
        
        wp_send_json_success($product_data);
    }
    
    /**
     * Handle get hero products for carousel
     */
    public function handle_get_hero_products() {
        if (!wp_verify_nonce($_POST['nonce'], 'bsr_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!class_exists('WooCommerce')) {
            wp_send_json_error('WooCommerce not available');
        }
        
        $limit = intval($_POST['limit']) ?: 5;
        $category = sanitize_text_field($_POST['category'] ?? '');
        $featured_only = filter_var($_POST['featured_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $limit,
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
            
            $hero_products[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => strip_tags($product->get_price_html()),
                'regular_price' => $product->get_regular_price() ? wc_price($product->get_regular_price()) : '',
                'sale_price' => $product->get_sale_price() ? wc_price($product->get_sale_price()) : '',
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'large'),
                'url' => $product->get_permalink(),
                'on_sale' => $product->is_on_sale(),
                'featured' => $product->is_featured()
            );
        }
        
        wp_send_json_success($hero_products);
    }
    
    /**
     * Handle export subscribers (Admin only)
     */
    public function handle_export_subscribers() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'bsr_admin_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'bsr_subscribers';
        
        $status = sanitize_text_field($_POST['status'] ?? 'all');
        $where_clause = '';
        
        if ($status !== 'all') {
            $where_clause = $wpdb->prepare(" WHERE status = %s", $status);
        }
        
        $subscribers = $wpdb->get_results("SELECT * FROM $table_name $where_clause ORDER BY date_subscribed DESC");
        
        if (empty($subscribers)) {
            wp_send_json_error('No subscribers found');
        }
        
        // Create CSV content
        $csv_content = "Email,Date Subscribed,Source,Status\n";
        foreach ($subscribers as $subscriber) {
            $csv_content .= sprintf(
                "%s,%s,%s,%s\n",
                $subscriber->email,
                $subscriber->date_subscribed,
                $subscriber->source,
                $subscriber->status
            );
        }
        
        // Create temporary file
        $upload_dir = wp_upload_dir();
        $filename = 'bsr-subscribers-' . date('Y-m-d-H-i-s') . '.csv';
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        file_put_contents($filepath, $csv_content);
        
        wp_send_json_success(array(
            'download_url' => $upload_dir['url'] . '/' . $filename,
            'filename' => $filename,
            'count' => count($subscribers)
        ));
    }
    
    /**
     * Handle bulk delete subscribers (Admin only)
     */
    public function handle_bulk_delete_subscribers() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'bsr_admin_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $subscriber_ids = array_map('intval', $_POST['subscriber_ids'] ?? array());
        
        if (empty($subscriber_ids)) {
            wp_send_json_error('No subscribers selected');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'bsr_subscribers';
        
        $ids_placeholder = implode(',', array_fill(0, count($subscriber_ids), '%d'));
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE id IN ($ids_placeholder)",
            ...$subscriber_ids
        ));
        
        if ($deleted === false) {
            wp_send_json_error('Database error occurred');
        }
        
        wp_send_json_success(array(
            'message' => sprintf('Successfully deleted %d subscribers', $deleted),
            'deleted_count' => $deleted
        ));
    }
    
    /**
     * Send welcome email to new subscriber
     */
    private function send_welcome_email($email) {
        $subject = get_option('bsr_welcome_email_subject', 'Vitajte v Beauty Shop Rio!');
        $message = get_option('bsr_welcome_email_message', 
            'Ďakujeme za prihlásenie sa na odber noviniek. Budete dostávať informácie o nových produktoch a špeciálnych ponukách.'
        );
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        // Allow customization via filter
        $subject = apply_filters('bsr_welcome_email_subject', $subject, $email);
        $message = apply_filters('bsr_welcome_email_message', $message, $email);
        $headers = apply_filters('bsr_welcome_email_headers', $headers, $email);
        
        return wp_mail($email, $subject, $message, $headers);
    }
    
    /**
     * Get product gallery images
     */
    private function get_product_gallery($product) {
        $gallery_ids = $product->get_gallery_image_ids();
        $gallery = array();
        
        foreach ($gallery_ids as $image_id) {
            $gallery[] = array(
                'id' => $image_id,
                'url' => wp_get_attachment_image_url($image_id, 'large'),
                'thumb' => wp_get_attachment_image_url($image_id, 'thumbnail')
            );
        }
        
        return $gallery;
    }
    
    /**
     * Get product categories
     */
    private function get_product_categories($product) {
        $categories = wp_get_post_terms($product->get_id(), 'product_cat');
        $category_data = array();
        
        foreach ($categories as $category) {
            $category_data[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'url' => get_term_link($category)
            );
        }
        
        return $category_data;
    }
    
    /**
     * Get product attributes
     */
    private function get_product_attributes($product) {
        $attributes = $product->get_attributes();
        $attribute_data = array();
        
        foreach ($attributes as $attribute) {
            if ($attribute->is_taxonomy()) {
                $terms = wp_get_post_terms($product->get_id(), $attribute->get_name());
                $values = array();
                foreach ($terms as $term) {
                    $values[] = $term->name;
                }
                $attribute_data[$attribute->get_name()] = implode(', ', $values);
            } else {
                $attribute_data[$attribute->get_name()] = $attribute->get_options();
            }
        }
        
        return $attribute_data;
    }
    
    /**
     * Get newsletter statistics
     */
    public function get_newsletter_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bsr_subscribers';
        
        $stats = array(
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name"),
            'active' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'active'"),
            'unsubscribed' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'unsubscribed'"),
            'today' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE DATE(date_subscribed) = CURDATE()"),
            'this_week' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE YEARWEEK(date_subscribed) = YEARWEEK(CURDATE())"),
            'this_month' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE MONTH(date_subscribed) = MONTH(CURDATE()) AND YEAR(date_subscribed) = YEAR(CURDATE())"),
            'this_year' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE YEAR(date_subscribed) = YEAR(CURDATE())")
        );
        
        return $stats;
    }
}