<?php

namespace HQExtra\Admin\Page;

defined('ABSPATH') || exit;

use const Marmot\THEME_VERSION;

class License {

    /**
     * Instance
     * @since 1.0.10
     * @var License 
     */
    private static $_instance = null;
    public $wizard_step = null;

    /**
     * Get class instance
     * @since 1.0.10
     * @return License
     */
    public static function instance() {
        if (empty(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function license() {
        ?>

        <div class="hqt-admin-page">
            <div class="wrap">
                <h1 class="hqt-invisible"></h1>
                <div class="hqt-logo-wrap">
                    <a href="https://marmot.hqwebs.net/?utm_source=wp-admin&utm_medium=logo&utm_campaign=default&utm_content=theme-options-top" target="_blank">
                        <img src="<?php echo MARMOT_THEME_URL; ?>/assets/images/admin/logo-marmot.png">
                    </a>
                </div>
                <p class="mt-0">Version <?php echo THEME_VERSION; ?></p>
                <h2 class="mb-1"><?php _ex('License', 'admin', 'hqtheme-extra'); ?></h2>

                <?php
                // Hide license
                if (defined('\WP_FS__DEMO_MODE') && \WP_FS__DEMO_MODE) {
                    die;
                }

                // Display container
                \HQLib\Options::display_container('license');
                ?>
            </div>
        </div>
        <?php
    }

}
