<?php
/**
 * Footer Section Component
 */

if (!defined('ABSPATH')) {
    exit;
}

class BSR_Footer_Section {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function render($atts) {
        $atts = shortcode_atts(array(
            'company_name' => 'Beauty Shop Rio',
            'show_social' => 'true',
            'show_links' => 'true'
        ), $atts);
        
        ob_start();
        ?>
        <footer class="bsr-footer">
            <div class="bsr-container">
                <div class="bsr-footer-content">
                    <div class="bsr-footer-brand">
                        <div class="bsr-logo">🌿 <?php echo esc_html($atts['company_name']); ?></div>
                        <div class="bsr-footer-info">
                            <p><?php echo esc_html($atts['company_name']); ?><br>
                            SIROG 11<br>
                            1016 Bratislava Mestské ústredie 22<br>
                            1230 Budapest</p>
                            <p>Tel: +421057084<br>
                            ICO: 56 42200048<br>
                            e-mail: info@bsr.sk</p>
                        </div>
                    </div>
                    
                    <?php if ($atts['show_links'] === 'true' || $atts['show_social'] === 'true'): ?>
                    <div class="bsr-footer-links">
                        <?php if ($atts['show_links'] === 'true'): ?>
                        <div class="bsr-footer-col">
                            <h4>Pre partnerov</h4>
                            <ul>
                                <li><a href="#">Reklamácie</a></li>
                                <li><a href="#">Doprava</a></li>
                                <li><a href="#">Ochrana osobných údajov</a></li>
                                <li><a href="#">Obchodné podmienky</a></li>
                                <li><a href="#">O nás</a></li>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($atts['show_social'] === 'true'): ?>
                        <div class="bsr-footer-social">
                            <a href="#" class="bsr-social-link">📘 Instagram</a>
                            <a href="#" class="bsr-social-link">📘 Facebook</a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="bsr-botanical-footer">
                    <div class="bsr-leaf bsr-leaf-footer"></div>
                </div>
                
                <div class="bsr-footer-bottom">
                    <p>© <?php echo date('Y'); ?> <?php echo esc_html($atts['company_name']); ?></p>
                </div>
            </div>
        </footer>
        <?php
        return ob_get_clean();
    }
}