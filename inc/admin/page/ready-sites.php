<?php

namespace HQExtra\Admin\Page;

defined('ABSPATH') || exit;

use const Marmot\THEME_VERSION;

class Ready_Sites {

    /**
     * Instance
     * 
     * @since 1.0.0
     * 
     * @var Ready_Sites 
     */
    private static $_instance = null;

    /**
     * Get class instance
     *
     * @since 1.0.0
     *
     * @return Ready_Sites
     */
    public static function instance() {
        if (empty(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Class constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        
    }

    /**
     * Ready Sites page
     * 
     * @since 1.0.0
     */
    public function ready_sites() {
        // phpcs:disable
        ?>
        <div class="hqt-admin-page">
            <div class="wrap hqt-templates-import-screen" data-template-type="sites">
                <h1 class="hqt-invisible"></h1>
                <div class="hqt-logo-wrap">
                    <a href="https://marmot.hqwebs.net/ready-demos/?utm_source=wp-admin&utm_medium=logo&utm_campaign=default&utm_content=ready-sites-top" target="_blank">
                        <img src="<?php echo MARMOT_THEME_URL; ?>/assets/images/admin/logo-marmot.png" class="img-fluid">
                    </a>
                </div>
                <p class="mt-0">Version <?php echo THEME_VERSION; ?></p>
                <h2 class="mb-1"><?php _ex('Ready Sites', 'admin ready sites', 'hqtheme-extra'); ?></h2>
                <p class="mb-1"><?php _ex('Professionally pre-build website ready for import. One-click import is the easiest way to start building with the Marmot theme.', 'admin ready sites', 'hqtheme-extra'); ?></p>
                <?php
                \HQExtra\Demos\Ui::instance()->ready_sites_page();
                ?>
            </div>
        </div>
        <?php
// phpcs:enable
    }

}
