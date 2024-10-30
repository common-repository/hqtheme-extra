<?php

namespace HQExtra\Admin\Page;

defined('ABSPATH') || exit;

use const Marmot\THEME_VERSION;

class Theme_Options {

    /**
     * Instance
     * 
     * @since 1.0.0
     * 
     * @var Theme_Options 
     */
    private static $_instance = null;

    /**
     * Get class instance
     *
     * @since 1.0.0
     *
     * @return Theme_Options
     */
    public static function instance() {
        if (empty(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Theme Options page
     * 
     * @since 1.0.0
     */
    public function theme_options() {
        // phpcs:disable
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
                <h2 class="mb-1"><?php _ex('Theme Options', 'admin', 'hqtheme-extra'); ?></h2>

                <?php \HQLib\Options::display_tabs('hq-theme-options'); ?>
            </div>
        </div>
        <?php
// phpcs:enable
    }

}
