<?php
/**
 * Categories Section Component
 */

if (!defined('ABSPATH')) {
    exit;
}

class BSR_Categories_Section {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function render($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Objavte podľa kategórie',
            'show_sidebar' => 'true'
        ), $atts);
        
        ob_start();
        ?>
        <section class="bsr-categories">
            <div class="bsr-container">
                <h2><?php echo esc_html($atts['title']); ?></h2>
                
                <div class="bsr-categories-layout">
                    <?php if ($atts['show_sidebar'] === 'true'): ?>
                    <div class="bsr-categories-sidebar">
                        <h3>Značky ↓</h3>
                        <ul class="bsr-brand-list">
                            <li><a href="#" class="active">Ilcsi →</a></li>
                            <li><a href="#">Luxoya ↓</a></li>
                            <li class="bsr-submenu">
                                <span>Luxoya Hair</span>
                                <span>FarmaVita →</span>
                            </li>
                        </ul>
                        
                        <div class="bsr-product-types">
                            <h3>Typy produktov →</h3>
                        </div>
                        
                        <button class="bsr-all-categories">Všetky kategórie</button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="bsr-categories-grid">
                        <div class="bsr-category-card bsr-card-ilcsi">
                            <h3>Ilcsi</h3>
                            <p>Za krásnu štíhlu žmielkach slep všetky budúcnosti. V progede prírozené kozmársky vyrobené podľa prirodzenej.</p>
                            <a href="#" class="bsr-category-btn">Viac o značke</a>
                        </div>
                        
                        <div class="bsr-category-card bsr-card-luxoya">
                            <h3>Luxoya</h3>
                        </div>
                        
                        <div class="bsr-category-card bsr-card-farmavita">
                            <h3>FarmaVita</h3>
                        </div>
                        
                        <div class="bsr-category-card bsr-card-lifestyle">
                            <h3>Luxoya Way of Life</h3>
                        </div>
                        
                        <div class="bsr-category-card bsr-card-makeup">
                            <h3>Luxoya Make Up</h3>
                        </div>
                        
                        <div class="bsr-category-card bsr-card-lashes">
                            <h3>Luxlash Mihalnice</h3>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}