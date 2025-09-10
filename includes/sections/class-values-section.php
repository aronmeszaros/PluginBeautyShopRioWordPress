<?php
/**
 * Values Section Component
 */

if (!defined('ABSPATH')) {
    exit;
}

class BSR_Values_Section {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function render($atts) {
        $atts = shortcode_atts(array(
            'model_image' => '',
            'title' => 'Starostlivost o pleť pomocou prípravkov vyrobených z prírodných látok',
            'organic_text' => 'Zabezpečte každého produktu do poprednej linka prvky používané v prirodzene látky a prírody.',
            'usage_text' => 'Zabezpečte každého produktu do poprednej linka prvky používané v prirodzene látky a prírody.'
        ), $atts);
        
        ob_start();
        ?>
        <section class="bsr-values">
            <div class="bsr-container">
                <div class="bsr-values-content">
                    <div class="bsr-values-text">
                        <h2><?php echo esc_html($atts['title']); ?></h2>
                        
                        <div class="bsr-value-item">
                            <div class="bsr-value-icon">🌿</div>
                            <div class="bsr-value-content">
                                <h3>Organické</h3>
                                <p><?php echo esc_html($atts['organic_text']); ?></p>
                            </div>
                        </div>
                        
                        <div class="bsr-value-item">
                            <div class="bsr-value-icon">⏱</div>
                            <div class="bsr-value-content">
                                <h3>Ľahké používanie</h3>
                                <p><?php echo esc_html($atts['usage_text']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bsr-values-image">
                        <?php if ($atts['model_image']): ?>
                            <img src="<?php echo esc_url($atts['model_image']); ?>" alt="Model" class="bsr-model-img">
                        <?php else: ?>
                            <div class="bsr-model-placeholder">Model Image</div>
                        <?php endif; ?>
                        <div class="bsr-botanical-frame">
                            <div class="bsr-leaf bsr-leaf-left"></div>
                            <div class="bsr-leaf bsr-leaf-right"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}