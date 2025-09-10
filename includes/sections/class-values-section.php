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
        // Build a reliable default URL (works even if your constant is missing or lacks a slash)
        $default_model = BSR_PLUGIN_URL . 'assets/images/modelWithIlcsi.webp';

        $defaults = array(
            'model_image'  => $default_model,
            'title'        => 'Starostlivosť o pleť pomocou prípravkov vyrobených z prírodných látok',
            'organic_text' => 'Zabezpečte každého produktu do poprednej linka prvky používané v prirodzene látky a prírody.',
            'usage_text'   => 'Zabezpečte každého produktu do poprednej linka prvky používané v prirodzene látky a prírody.'
        );

        // Merge, but keep defaults when incoming values are empty ("")
        $atts = shortcode_atts($defaults, $atts);
        if (empty($atts['model_image'])) {
            $atts['model_image'] = $defaults['model_image'];
        }
        
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
                    </div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}