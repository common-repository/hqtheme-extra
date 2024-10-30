<?php

namespace HQExtra\Admin\Page;

defined('ABSPATH') || exit;

use const Marmot\THEME_VERSION;

class Theme_Setup_Wizzard {

    /**
     * Instance
     * 
     * @since 1.0.0
     * 
     * @var Theme_Setup_Wizzard 
     */
    private static $_instance = null;
    public $wizard_step = null;
    protected $recommended_plugins;

    /**
     * Get class instance
     *
     * @since 1.0.0
     *
     * @return Theme_Setup_Wizzard
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
        $this->recommended_plugins = [
            'hqtheme-extra' => [
                'name' => 'HQTheme Extra',
                'required' => true,
                'logo_url' => '',
                'description' => _x('HQTheme Extra adds extra features and options to Marmot theme and allows you to import beautiful pre-made demos. With the one-click demo import feature you can import all our professional demo sites.', 'setup wizzard', 'hqtheme-extra'),
                'init' => 'hqtheme-extra/hqtheme-extra.php',
                'constant' => '\HQExtra\VERSION',
            ],
            'elementor' => [
                'name' => 'Elementor',
                'required' => true,
                'logo_url' => '',
                'description' => _x('Elementor\'s robust editor empowers professionals to edit and style WordPress websites visually, eliminating all the guesswork involved in writing code.', 'setup wizzard', 'hqtheme-extra'),
                'init' => 'elementor/elementor.php',
                'constant' => '\ELEMENTOR_VERSION',
            ],
            'hq-widgets-for-elementor' => [
                'name' => 'HQ Widgets for Elementor',
                'required' => true,
                'logo_url' => '',
                'description' => _x('Beautiful widgets for Elementor. Includes some special widgets that will help you to customize your single posts and archive pages. Responsive navigation widget is also included.', 'setup wizzard', 'hqtheme-extra'),
                'init' => 'hq-widgets-for-elementor/hq-widgets-for-elementor.php',
                'constant' => '\HQWidgetsForElementor\VERSION',
            ],
        ];
    }

    public function get_recommended_plugins() {
        return $this->recommended_plugins;
    }

    public function theme_setup() {
        // Enable setup again
        set_theme_mod('marmot_setup', 1);

        // Get step
        $step = get_option('marmot_setup_step', 1); // 1 - step plugins

        if (!empty($_GET['wizzard-page'])) {
            $this->wizard_step = absint($_GET['wizzard-page']);
            if ($this->wizard_step < 1 || $this->wizard_step > 4) {
                $this->wizard_step = 1;
            }
        } else {
            $this->wizard_step = $step;
        }
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
                <h2 class="mb-1"><?php _ex('Setup Wizzard', 'admin', 'hqtheme-extra'); ?></h2>
                <p class="mb-1">
                    <?php _ex('Marmot Theme Setup Wizzard will assist you through the initial website setup.', 'admin', 'hqtheme-extra'); ?><br>
                </p>

                <div class="hqt-container">
                    <?php
                    $this->display_notices();
                    $this->display_steps($step);

                    switch ($this->wizard_step) {
                        case 1:
                            $this->plugins($step);
                            break;
                        case 2:
                            add_filter('hqt/container/license/classes', [$this, 'hqt_container_license_classes']);
                            $this->license($step);
                            break;
                        case 3:
                            $this->demo($step);
                            break;
                        case 4:
                            $this->customize($step);
                            break;
                    }
                    ?>  
                </div>
            </div>
        </div>
        <?php
    }

    public function hqt_container_license_classes($classes) {
        $classes[] = 'mt-4 box-shadow';
        return $classes;
    }

    private function plugins($step) {
        ?>
        <h2><?php _ex('Installing Highly Recommended Plugins', 'admin', 'hqtheme-extra'); ?></h2>
        <p class="m-0"><?php _ex('Marmot theme works with Elementor templates, it also can be used without Elementor, but design and features will be very poor.', 'admin', 'hqtheme-extra'); ?></p>
        <p class="mt-0 mb-4"><?php _ex('The theme also needs our free plugin called HQTheme Extra (available in official WordPress repository). It includes library and some features like one-click demo import. ', 'admin', 'hqtheme-extra'); ?></p>
        <?php
        $missing_plugin = false;
        ?>
        <div class="hqt-row mb-4">
            <?php
            foreach ($this->recommended_plugins as $recommended_plugin_slug => $recommended_plugin) {
                ?>
                <div class="hqt-col-1-2">
                    <div class="border-rad-10 hqt-box-shadow p-3 mb-2" style="border-left: solid 4px #2096F3;">
                        <h3 class="m-0"><?php echo esc_html($recommended_plugin['name']); ?></h3>
                        <p class="my-2"><?php echo esc_html($recommended_plugin['description']); ?></p>
                        <?php
                        if (!defined($recommended_plugin['constant'])) {
                            if ($recommended_plugin['required']) {
                                $missing_plugin = true;
                            }
                            $install_url = '';
                            if (!\HQLib\is_plugin_installed($recommended_plugin['init'])) {
                                $install_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=' . $recommended_plugin_slug), 'install-plugin_' . $recommended_plugin_slug);
                            }
                            $activate_url = wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $recommended_plugin['init'] . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $recommended_plugin['init']);
                            ?>
                            <a href="#" 
                               data-hqt-btn="install-activate-plugin"
                               data-action-label="replace"
                               data-plugin-name="<?php echo esc_attr($recommended_plugin['name']) ?>" 
                               data-install-url="<?php echo esc_attr($install_url); ?>" 
                               data-activate-url="<?php echo esc_attr($activate_url); ?>" 
                               data-callback="refresh-page"
                               class="btn btn-primary"
                               ><?php echo esc_html($recommended_plugin['name']) ?>
                            </a>
                            <?php
                        } else {
                            ?>
                            <span class="d-iblock text-success px-1"><?php _ex('Activated', 'admin', 'hqtheme-extra'); ?></span>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
        // Set next step if current is ready
        if (!$missing_plugin && $step == 1) {
            update_option('marmot_setup_step', 2); // 2 - step license
        }
        if (!$missing_plugin) {
            // Skip license page
            if (defined('\WP_FS__DEMO_MODE') && \WP_FS__DEMO_MODE) {
                $next_step = 3;
            } else {
                $next_step = 2;
            }
            ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-theme-setup&wizzard-page=' . $next_step)); ?>" class="btn btn-primary">
                <?php _ex('Next step', 'admin', 'hqtheme-extra'); ?>
            </a>
            <?php
        }
    }

    private function license($step) {
        ?>
        <h2><?php _ex('Marmot License', 'admin', 'hqtheme-extra'); ?></h2>
        <p><?php _ex('License activation will enable more features, more pre-made websites and access to all premium plugins.', 'admin', 'hqtheme-extra'); ?></p>
        <?php
        if ($step < 2 || !defined('\HQExtra\VERSION')) {
            // Guarantee HQExtra
            update_option('marmot_setup_step', 1); // 2 - step plugins
            ?>
            <p class="p-2 text-danger">
                <?php _ex('You have to complete previous steps!', 'admin', 'hqtheme-extra'); ?>
            </p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-theme-setup&wizzard-page=1')); ?>" class="btn btn-primary">
                <?php _ex('Go to uncompleted step', 'admin', 'hqtheme-extra'); ?>
            </a>
            <?php
            return;
        }

        // Hide license
        if (defined('\WP_FS__DEMO_MODE') && \WP_FS__DEMO_MODE) {
            die;
        }

        // Skip license text, button
        if (!\HQLib\License::is_activated()) {
            ?>
            <p>
                <?php _ex('You can skip this step, if you will use only free features and demos.', 'admin', 'hqtheme-extra'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-theme-setup&wizzard-page=3&skip-license=1')); ?>">
                    <?php _ex('Skip this step', 'admin', 'hqtheme-extra'); ?>
                </a>
            </p>
            <?php
        } else {
            ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-theme-setup&wizzard-page=3')); ?>" class="btn btn-primary">
                <?php _ex('Next step', 'admin', 'hqtheme-extra'); ?>
            </a>
            <?php
        }

        // Display container
        \HQLib\Options::display_container('license');

        // Set next step if current is ready
        if (\HQLib\License::is_activated() && $step == 2) {
            update_option('marmot_setup_step', 3); // 3 - step demo
        }
    }

    private function demo($step) {
        if (!empty($_GET['skip-license'])) {
            update_option('marmot_setup_step', 3); // 3 - step demo
            $step = 3;
        }
        ?>
        <h2><?php _ex('Import Pre-made Demo Website', 'admin', 'hqtheme-extra'); ?></h2>
        <p>
            <?php _ex('Importing pre-made demo is best and easiest way to build your website.', 'admin', 'hqtheme-extra'); ?>
        </p>
        <?php
        if ($step < 3 || !defined('\HQExtra\VERSION')) {
            ?>
            <p class="p-2 text-danger">
                <?php _ex('You have to complete previous steps!', 'admin', 'hqtheme-extra'); ?>
            </p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-theme-setup&wizzard-page=' . (defined('\HQExtra\VERSION') ? $step : 1))); ?>" class="btn btn-primary">
                <?php _ex('Go to uncompleted step', 'admin', 'hqtheme-extra'); ?>
            </a>
            <?php
            return;
        }
        if (get_theme_mod('marmot_demo_imported', 0)) {
            ?>
            <p>
                <?php _ex('Demo is already imported you can  go to next step or choose and import another demo below.', 'admin', 'hqtheme-extra'); ?> <a href="#hqt-import-demo"><i class="dashicons dashicons-arrow-down-alt2"></i></a>
            </p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-theme-setup&wizzard-page=4')); ?>" class="btn btn-primary">
                <?php _ex('Next step', 'admin', 'hqtheme-extra'); ?>
            </a>
            <?php
        } else {
            ?>
            <p>
                <?php _ex('Choose and import demo that fits your needs below.', 'admin', 'hqtheme-extra'); ?>
            </p>
            <p>
                <a href="#hqt-import-demo" class="btn btn-primary">
                    <?php _ex('Choose Demo Website', 'admin', 'hqtheme-extra'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-theme-setup&wizzard-page=4&skip-demo=1')); ?>" class="ml-2">
                    <?php _ex('Skip this step', 'admin', 'hqtheme-extra'); ?>
                </a>
            </p>
            <?php
        }
        ?>
        <div class="wrap hqt-templates-import-screen" id="hqt-import-demo" data-template-type="sites">
            <?php
            \HQExtra\Demos\Ui::instance()->ready_sites_page();
            ?>
        </div>
        <?php
    }

    private function customize($step) {
        if (!empty($_GET['skip-demo'])) {
            update_option('marmot_setup_step', 4); // 4 - step customize
            $step = 4;
        }
        if ($step < 2 || !defined('\HQExtra\VERSION')) {
            // Guarantee HQExtra
            update_option('marmot_setup_step', 1); // 2 - step plugins
            ?>
            <p class="p-2 text-danger">
                <?php _ex('You have to complete previous steps!', 'admin', 'hqtheme-extra'); ?>
            </p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-theme-setup&wizzard-page=1')); ?>" class="btn btn-primary">
                <?php _ex('Go to uncompleted step', 'admin', 'hqtheme-extra'); ?>
            </a>
            <?php
            return;
        }
        // finish setup
        set_theme_mod('marmot_setup', 0);
        ?>
        <h2 class="mb-0"><?php _ex('Congratulations!', 'admin', 'hqtheme-extra'); ?></h2>

        <p><?php _ex('Your website is almost ready.', 'admin', 'hqtheme-extra'); ?></p>
        <h3>1. <?php _ex('Create and setup your templates (Skip this step if you already have imporded a demo)', 'admin', 'hqtheme-extra'); ?></h3>
        <p class="m-0"><?php _ex('There is three ways you can do that:', 'admin', 'hqtheme-extra'); ?></p>

        <ul class="pl-2">
            <li><p><?php echo sprintf(_x('<b>A)</b> Easy setup with pre-made demo. Go to %1$sReady Sites%2$s, find the one that best fits your needs and import it. Our one click import will make your website looks like the pre-made demo.', 'admin', 'hqtheme-extra'), '<a href="' . esc_attr(admin_url('admin.php?page=marmot-ready-sites')) . '">', '</a>'); ?></p></li>
            <li><p><?php echo sprintf(_x('<b>B)</b> Setup default templates for further customization. Go to %1$sTheme Templates%2$s, create and setup default templates. Then you can start designing your pages.', 'admin', 'hqtheme-extra'), '<a href="' . esc_attr(admin_url('admin.php?page=marmot-theme-templates')) . '">', '</a>'); ?></p></li>
            <li><p><?php echo sprintf(_x('<b>C)</b> Hardest way. Create Elementor templates by yourself and attach them in Customizer. <b>WARNING:</b> It is important to use the right widgets or some features may not work as expected.', 'admin', 'hqtheme-extra'), '<a href="' . esc_attr(admin_url('admin.php?page=marmot-theme-templates')) . '">', '</a>'); ?></p></li>
        </ul>

        <h3>2. <?php _ex('Your website is ready for customizations', 'admin', 'hqtheme-extra'); ?></h3>

        <p class="mt-0"><?php echo sprintf('%1$s <a target="_blank" href="%2$s">%3$s</a>. %4$s', _x('Just follow the tutorial', 'admin', 'hqtheme-extra'), 'https://marmot.hqwebs.net/documentation/how-to-edit-header-template/', _x('How to edit header template', 'admin', 'hqtheme-extra'), _x('All other elements work same way.', 'admin', 'hqtheme-extra')); ?></p>
        <p class="mt-0"><?php _ex('Go to page -> from Top admin bar go to Edit with Elementor dropdown (you must be logged in as an administrator) choose template and edit.', 'admin', 'hqtheme-extra'); ?></p>
        <p class="mt-0"><?php echo sprintf(_x('More information and help you can find on our %1$swebsite%2$s.', 'admin', 'hqtheme-extra'), '<a href="https://marmot.hqwebs.net/" target="_blank">', '</a>'); ?></p>
        <p>
            <?php echo sprintf('<a target="_blank" class="btn btn-primary" href="%1$s">%2$s</a>', 'https://marmot.hqwebs.net/documentation/', _x('Full documentation', 'admin', 'hqtheme-extra')); ?>
            <?php if (\HQLib\License::is_activated()) { ?>
                <?php echo sprintf('<a target="_blank" class="btn ml-2" href="%1$s">%2$s</a>', 'https://marmot.hqwebs.net/support/', _x('Support', 'admin', 'hqtheme-extra')); ?>
            <?php } ?>
        </p>
        <?php
    }

    protected function display_steps($step) {
        ?>
        <div class="hqt-row">
            <div class="hqt-col-1-1">
                <div>
                    <div class="hqt-row">
                        <div class="<?php echo esc_attr($this->getHeaderStepClass(1)) ?>">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-theme-setup&wizzard-page=1')); ?>" class="step-box step-1">
                                <div class="step">
                                    <span class="step-arrow step-arrow-left"></span>
                                    <span class="step-number">01</span>
                                    <span class="step-arrow step-arrow-right"></span>
                                </div>
                                <div class="step-body hqt-hidden hqt-visible__sm">
                                    <h3 class="mt-3 mb-1 text-bold"><?php _ex('Install Recommended Plugins', 'admin', 'hqtheme-extra'); ?></h3>
                                </div>
                            </a>
                        </div>
                        <?php if (defined('\WP_FS__DEMO_MODE') && \WP_FS__DEMO_MODE) : ?>
                            <div class="<?php echo esc_attr($this->getHeaderStepClass(3)) ?>">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-theme-setup&wizzard-page=3')); ?>" class="step-box step-3">
                                    <div class="step">
                                        <span class="step-arrow step-arrow-left"></span>
                                        <span class="step-number">02</span>
                                        <span class="step-arrow step-arrow-right"></span>
                                    </div>
                                    <div class="step-body hqt-hidden hqt-visible__sm">
                                        <h3 class="mt-3 mb-1 text-bold"><?php _ex('Import demo', 'admin', 'hqtheme-extra'); ?></h3>
                                    </div>
                                </a>
                            </div>
                            <div class="<?php echo esc_attr($this->getHeaderStepClass(4)) ?>">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-theme-setup&wizzard-page=4')); ?>" class="step-box step-4">
                                    <div class="step">
                                        <span class="step-arrow step-arrow-left"></span>
                                        <span class="step-number">03</span>
                                        <span class="step-arrow step-arrow-right"></span>
                                    </div>
                                    <div class="step-body hqt-hidden hqt-visible__sm">
                                        <h3 class="mt-3 mb-1 text-bold"><?php _ex('Customize Your Website', 'admin', 'hqtheme-extra'); ?></h3>
                                    </div>

                                </a>
                            </div>
                        <?php else : ?>
                            <div class="<?php echo esc_attr($this->getHeaderStepClass(2)) ?>">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-theme-setup&wizzard-page=2')); ?>" class="step-box step-2">
                                    <div class="step">
                                        <span class="step-arrow step-arrow-left"></span>
                                        <span class="step-number">02</span>
                                        <span class="step-arrow step-arrow-right"></span>
                                    </div>
                                    <div class="step-body hqt-hidden hqt-visible__sm">
                                        <h3 class="mt-3 mb-1 text-bold"><?php _ex('Enter Purchase license', 'admin', 'hqtheme-extra'); ?></h3>
                                    </div>
                                </a>
                            </div>
                            <div class="<?php echo esc_attr($this->getHeaderStepClass(3)) ?>">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-theme-setup&wizzard-page=3')); ?>" class="step-box step-3">
                                    <div class="step">
                                        <span class="step-arrow step-arrow-left"></span>
                                        <span class="step-number">03</span>
                                        <span class="step-arrow step-arrow-right"></span>
                                    </div>
                                    <div class="step-body hqt-hidden hqt-visible__sm">
                                        <h3 class="mt-3 mb-1 text-bold"><?php _ex('Import demo', 'admin', 'hqtheme-extra'); ?></h3>
                                    </div>
                                </a>
                            </div>
                            <div class="<?php echo esc_attr($this->getHeaderStepClass(4)) ?>">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-theme-setup&wizzard-page=4')); ?>" class="step-box step-4">
                                    <div class="step">
                                        <span class="step-arrow step-arrow-left"></span>
                                        <span class="step-number">04</span>
                                        <span class="step-arrow step-arrow-right"></span>
                                    </div>
                                    <div class="step-body hqt-hidden hqt-visible__sm">
                                        <h3 class="mt-3 mb-1 text-bold"><?php _ex('Customize Your Website', 'admin', 'hqtheme-extra'); ?></h3>
                                    </div>

                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    protected function display_notices() {
        $theme = wp_get_theme();

        if ('Marmot' === $theme->name || 'Marmot' !== $theme->parent_theme) {
            ?>
            <div class="hqt-row border-rad-10 hqt-box-shadow mt-1 mb-4 mx-0 py-3 px-2" style="border-left: solid 4px #2096F3;">
                <div class="hqt-col-1-1">
                    <h3 class="p-0 mt-0 mb-2"><?php _ex('Not using a child theme?', 'admin', 'hqtheme-extra'); ?></h3>
                    <p class="m-0">
                        <?php _ex('A child theme allows you to change small aspects of your site`s appearance yet still preserve your theme’s look and functionality. It protects your changes to be overwritten on update of main theme.', 'admin', 'hqtheme-extra'); ?>
                    </p>
                    <p class="mt-0 mb-3">
                        <?php echo sprintf('<a href="%1$s" target="_blank">%2$s</a> %3$s', 'https://developer.wordpress.org/themes/advanced-topics/child-themes/', _x('Learn more', 'admin', 'hqtheme-extra'), _x('what is a child theme and how it works.', 'admin', 'hqtheme-extra')); ?>
                    </p>
                    <a href="https://github.com/hqwebs/marmot-theme-child/releases/download/1.0.0/marmot-theme-child.zip" class="btn btn-primary mr-1"><?php _ex('Download Marmot Child', 'admin', 'hqtheme-extra'); ?></a>
                </div>
            </div>
            <?php
        }
    }

    private function getHeaderStepClass($step) {
        $classes = ['hqt-col-1-4 mb-3'];
        if ($step == $this->wizard_step) {
            $classes[] = 'step-current';
        }
        if ($step > $this->wizard_step) {
            $classes[] = 'step-disabled';
        }
        if ($step < $this->wizard_step) {
            $classes[] = 'step-completed';
        }
        return implode(' ', $classes);
    }

}
