<?php
/**
 * Newsletter Section Component
 */

if (!defined('ABSPATH')) {
    exit;
}

class BSR_Newsletter_Section {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function render($atts) {
        $atts = shortcode_atts(array(
            'product_image' => '',
            'title' => 'Prihlaste sa na odber noviniek',
            'description' => 'Zasleve stranu e-mailovú adresu a buďte medzi prvými ktorí dostanú informácie o zľavách a novinkách.',
            'show_form' => 'true'
        ), $atts);
        
        ob_start();
        ?>
        <section class="bsr-newsletter">
            <div class="bsr-container">
                <div class="bsr-newsletter-content">
                    <div class="bsr-newsletter-image">
                        <?php if ($atts['product_image']): ?>
                            <img src="<?php echo esc_url($atts['product_image']); ?>" alt="Products">
                        <?php else: ?>
                            <div class="bsr-newsletter-placeholder">Newsletter Image</div>
                        <?php endif; ?>
                        <div class="bsr-botanical-newsletter">
                            <div class="bsr-leaf bsr-leaf-tl"></div>
                            <div class="bsr-leaf bsr-leaf-tr"></div>
                        </div>
                    </div>
                    
                    <div class="bsr-newsletter-form">
                        <h2><?php echo esc_html($atts['title']); ?></h2>
                        <p><?php echo esc_html($atts['description']); ?></p>
                        
                        <?php if ($atts['show_form'] === 'true'): ?>
                        <form class="bsr-signup-form" data-bsr-newsletter-form>
                            <input type="email" placeholder="Váš email" class="bsr-email-input" required>
                            <button type="submit" class="bsr-submit-btn">Poslať</button>
                        </form>
                        
                        <div class="bsr-form-messages" style="display: none;">
                            <div class="bsr-success-message" style="display: none;">
                                ✅ Ďakujeme! Úspešne ste sa prihlásili na odber noviniek.
                            </div>
                            <div class="bsr-error-message" style="display: none;">
                                ❌ <span class="bsr-error-text">Chyba pri prihlasovaní.</span>
                            </div>
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