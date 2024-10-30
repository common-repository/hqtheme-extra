<?php

namespace HQExtra\Admin\Page;

defined('ABSPATH') || exit;

use const Marmot\THEME_VERSION;

class Manage_Plugins {

    /**
     * Instance
     * @since 1.0.10
     * @var Manage_Plugins 
     */
    private static $_instance = null;

    /**
     * Recommended and included Plugins
     * @var array
     */
    private $plugins = [
        'marmot-enhancer-pro' => [
            'name' => 'Marmot Enhancer PRO',
            'type' => 'pro',
            'badge' => 'pro',
            'tag' => ['all', 'pro', 'customization'],
            'description' => 'Import awesome templates with a click, create stunning popups, attach custom headers and footers on each page, use advanced widgets for Elementor with Dynamic Tags, create unique WooCommerce checkout flow and more, and more...',
            'link' => 'https://marmot.hqwebs.net/marmot-theme-pro/?utm_source=wp-admin&utm_medium=link&utm_campaign=default&utm_term=marmot&utm_content=plugin-page',
            'plugin_file' => 'marmot-enhancer-pro/marmot-enhancer-pro.php',
            'plugin_name' => 'marmot-enhancer-pro',
            'constant' => '\HQPro\VERSION',
        ],
        'hq-widgets-for-elementor' => [
            'name' => 'HQ Widgets for Elementor',
            'type' => 'free',
            'badge' => 'recommended',
            'tag' => ['all', 'free', 'recommended', 'customization'],
            'description' => 'The HQ Widgets for Elementor is an elementor addons package for Elementor page builder plugin for WordPress and works Best with Marmot theme.',
            'link' => 'https://marmot.hqwebs.net/hq-widgets-for-elementor/?utm_source=wp-admin&utm_medium=link&utm_campaign=default&utm_term=marmot&utm_content=plugin-page',
            'plugin_file' => 'hq-widgets-for-elementor/hq-widgets-for-elementor.php',
            'plugin_name' => 'hq-widgets-for-elementor',
            'constant' => '\HQWidgetsForElementor\VERSION',
        ],
        'contact-form-7' => [
            'name' => 'Contact Form 7',
            'type' => 'free',
            'tag' => ['all', 'free', 'recommended', 'forms'],
            'description' => 'Contact Form 7 can manage multiple contact forms, plus you can customize the form and the mail contents flexibly with simple markup.',
            'link' => 'https://wordpress.org/plugins/contact-form-7/',
            'plugin_file' => 'contact-form-7/wp-contact-form-7.php',
            'plugin_name' => 'contact-form-7',
            'constant' => '\WPCF7_VERSION',
        ],
        'elementor' => [
            'name' => 'Elementor',
            'type' => 'free',
            'badge' => 'recommended',
            'tag' => ['all', 'free', 'recommended', 'customization'],
            'description' => 'A website builder that delivers high-end page designs and advanced capabilities, never before seen on WordPress.',
            'plugin_file' => 'elementor/elementor.php',
            'plugin_name' => 'elementor',
            'constant' => '\ELEMENTOR_VERSION',
        ],
        'layerslider' => [
            'name' => 'LayerSlider',
            'type' => 'pro',
            'badge' => 'pro',
            'tag' => ['all', 'pro', 'sliders'],
            'description' => 'Premium multi-purpose animation platform. Sliders, image galleries, slideshows with mind-blowing effects.',
            'link' => 'https://layerslider.kreaturamedia.com/',
            'plugin_file' => 'LayerSlider/layerslider.php',
            'plugin_name' => 'layerslider',
            'constant' => '\LS_MINIMUM_PHP_VERSION',
        ],
        'pods' => [
            'name' => 'Pods – Custom Content Types and Fields',
            'type' => 'free',
            'tag' => ['all', 'free', 'customization'],
            'description' => 'Manage all your custom content needs in one location with the Pods Framework.',
            'link' => 'https://wordpress.org/plugins/pods/',
            'plugin_file' => 'pods/init.php',
            'plugin_name' => 'pods',
            'constant' => '\PODS_VERSION',
        ],
        'seo-by-rank-math' => [
            'name' => 'Rank Math SEO',
            'type' => 'free',
            'tag' => ['all', 'free', 'seo'],
            'description' => 'Rank Math is created to help every website owner get access to the SEO tools they need to improve their SEO and attract more traffic to their website.',
            'link' => 'https://wordpress.org/plugins/seo-by-rank-math/',
            'plugin_file' => 'seo-by-rank-math/rank-math.php',
            'plugin_name' => 'seo-by-rank-math',
            'constant' => '\RANK_MATH_VERSION',
        ],
        'modula-best-grid-gallery' => [
            'name' => 'Customizable WordPress Gallery Plugin – Modula Image Gallery',
            'type' => 'free',
            'tag' => ['all', 'free', 'gallery'],
            'description' => 'Impress your potential clients with a fully customizable WordPress gallery plugin that’s fully mobile responsive, doesn’t slow down your website and doesn’t require a single line of code to work.',
            'link' => 'https://wordpress.org/plugins/modula-best-grid-gallery/',
            'plugin_file' => 'modula-best-grid-gallery/Modula.php',
            'plugin_name' => 'modula-best-grid-gallery',
            'constant' => '\MODULA_PATH',
        ],
        'revslider' => [
            'name' => 'Slider Revolution',
            'type' => 'pro',
            'badge' => 'pro',
            'tag' => ['all', 'pro', 'slider'],
            'description' => 'Slider Revolution is more than just a WordPress slider. It helps beginner-and mid-level designers WOW their clients with pro-level visuals.',
            'link' => 'https://www.sliderrevolution.com/',
            'plugin_file' => 'revslider/revslider.php',
            'plugin_name' => 'revslider',
            'constant' => '\RS_REVISION',
        ],
        'woocommerce' => [
            'name' => 'WooCommerce',
            'type' => 'free',
            'tag' => ['all', 'free', 'e-commerce'],
            'description' => 'WooCommerce is the world’s most popular open-source eCommerce solution. The core platform is free, flexible, and amplified by a global community.',
            'link' => 'https://wordpress.org/plugins/woocommerce/',
            'plugin_file' => 'woocommerce/woocommerce.php',
            'plugin_name' => 'woocommerce',
            'constant' => '\WC_VERSION',
        ],
        'wpforms-lite' => [
            'name' => 'WPForms',
            'type' => 'free',
            'tag' => ['all', 'free', 'forms'],
            'description' => 'WPForms allows you to create beautiful contact forms, feedback form, subscription forms, payment forms in minutes.',
            'link' => 'https://wordpress.org/plugins/wpforms-lite/',
            'plugin_file' => 'wpforms-lite/wpforms.php',
            'plugin_name' => 'wpforms-lite',
            'constant' => '\WPFORMS_VERSION',
        ],
        'wordpress-seo' => [
            'name' => 'Yoast SEO',
            'type' => 'free',
            'tag' => ['all', 'free', 'recommended', 'seo'],
            'description' => 'Yoast SEO is the most-used WordPress SEO plugin, and has helped millions of people like you to get ahead, and to stay ahead.',
            'link' => 'https://wordpress.org/plugins/wordpress-seo/',
            'plugin_file' => 'wordpress-seo/wp-seo.php',
            'plugin_name' => 'wordpress-seo',
            'constant' => '\WPSEO_VERSION',
        ],
        'w3-total-cache' => [
            'name' => 'W3 Total Cache',
            'type' => 'free',
            'badge' => 'recommended',
            'tag' => ['all', 'free', 'recommended', 'performance'],
            'description' => 'W3 Total Cache (W3TC) improves the SEO and user experience of your site by increasing website performance and reducing load times by leveraging features like content delivery network (CDN) integration and the latest best practices.',
            'link' => 'https://wordpress.org/plugins/w3-total-cache/',
            'plugin_file' => 'w3-total-cache/w3-total-cache.php',
            'plugin_name' => 'w3-total-cache',
        ],
        'polylang' => [
            'name' => 'Polylang',
            'type' => 'free',
            'tag' => ['all', 'free', 'translate'],
            'description' => 'Adds multilingual capability to WordPress.',
            'link' => 'https://wordpress.org/plugins/polylang/',
            'plugin_file' => 'polylang/polylang.php',
            'plugin_name' => 'polylang',
        ],
        'loco-translate' => [
            'name' => 'Loco Translate',
            'type' => 'free',
            'tag' => ['all', 'free', 'translate'],
            'description' => 'Translate themes and plugins directly in WordPress.',
            'link' => 'https://wordpress.org/plugins/loco-translate/',
            'plugin_file' => 'loco-translate/loco.php',
            'plugin_name' => 'loco-translate',
        ],
        'wordfence' => [
            'name' => 'Wordfence Security',
            'type' => 'free',
            'badge' => 'recommended',
            'tag' => ['all', 'free', 'recommended', 'security'],
            'description' => 'Wordfence Security - Anti-virus, Firewall and Malware Scan.',
            'link' => 'https://wordpress.org/plugins/wordfence/',
            'plugin_file' => 'wordfence/wordfence.php',
            'plugin_name' => 'wordfence',
            'constant' => '\WORDFENCE_VERSION',
        ],
    ];

    /**
     * Get class instance
     * @since 1.0.10
     * @return Manage_Plugins
     */
    public static function instance() {
        if (empty(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Class constructor
     * @since 1.0.10
     */
    private function __construct() {
        $this->init_plugins_container();
    }

    public function get_plugins($slug = null) {
        if (null !== $slug) {
            if (isset($this->plugins[$slug])) {
                return $this->plugins[$slug];
            }
            return false;
        }
        return $this->plugins;
    }

    /**
     * Theme Plugins Page
     * @since 1.0.10
     */
    public function manage_plugins() {
        // phpcs:disable
        ?>
        <div class="hqt-admin-page">
            <div class="wrap">
                <h1 class="hqt-invisible"></h1>
                <div class="hqt-logo-wrap">
                    <a href="https://marmot.hqwebs.net/?utm_source=wp-admin&utm_medium=logo&utm_campaign=default&utm_content=manage-plugins-top" target="_blank">
                        <img src="<?php echo MARMOT_THEME_URL; ?>/assets/images/admin/logo-marmot.png">
                    </a>
                </div>
                <p class="mt-0">Version <?php echo THEME_VERSION; ?></p>
                <h2 class="mb-1"><?php _ex('Manage Plugins', 'admin', 'hqtheme-extra'); ?></h2>

                <?php \HQLib\Options::display_container('manage_plugins'); ?>
            </div>
        </div>
        <?php
// phpcs:enable
    }

    private function init_plugins_container() {
        // Add container filter buttons
        add_action('hqt/container/manage_plugins/before_fields', [$this, 'manage_plugins_header']);

        // Manage container content
        $container = \HQLib\Options\Container::mk('manage_plugins', _x('Plugins List', 'Manage Plugins', 'hqtheme-extra'))->disable_title()->disable_submit();
        $filter = filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_STRING);

        $plugins = $this->get_plugins();
        // Sort by name
        uasort($plugins, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        foreach ($plugins as $plugin_key => $plugin_config) {
            if ($filter && !empty($plugin_config['tag']) && !in_array($filter, $plugin_config['tag'])) {
                continue;
            }
            $badge = !empty($plugin_config['badge']) ? $plugin_config['badge'] : false;
            $classes = 'hqt-field-border-box hqt-col-1-1';
            if (\HQLib\is_plugin_active($plugin_config['plugin_file'])) {
                $classes .= ' bg-primary';
            }

            // Display simple html info box with no switch control
            $container->add_field(
                    \HQLib\Field::mk('html', 'plugin_' . $plugin_key)
                            ->set_classes($classes)
                            ->set_html($this->get_plugin_info($plugin_config))
                            ->set_args(['badge' => $badge])
                            ->set_content_after($this->get_plugin_content_after($plugin_config))
            );
        }
    }

    public function manage_plugins_header() {
        ?>
        <div class="hqt-container-buttons mb-2">
            <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-manage-plugins&filter=all')); ?>" class="btn btn-primary btn-xs"><?php _e('All', 'admin widgets', 'hqtheme-extra'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-manage-plugins&filter=free')); ?>" class="btn btn-border btn-xs ml-1"><?php _e('Free', 'admin widgets', 'hqtheme-extra'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-manage-plugins&filter=pro')); ?>" class="btn btn-danger btn-xs ml-1"><?php _e('Pro', 'admin widgets', 'hqtheme-extra'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-manage-plugins&filter=recommended')); ?>" class="btn btn-border btn-xs ml-1"><?php _e('Recommended', 'admin widgets', 'hqtheme-extra'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-manage-plugins&filter=seo')); ?>" class="btn btn-border btn-xs ml-1"><?php _e('Seo', 'admin widgets', 'hqtheme-extra'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-manage-plugins&filter=e-commerce')); ?>" class="btn btn-border btn-xs ml-1"><?php _e('E-commerce', 'admin widgets', 'hqtheme-extra'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-manage-plugins&filter=forms')); ?>" class="btn btn-border btn-xs ml-1"><?php _e('Forms', 'admin widgets', 'hqtheme-extra'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-manage-plugins&filter=performance')); ?>" class="btn btn-border btn-xs ml-1"><?php _e('Performance', 'admin widgets', 'hqtheme-extra'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-manage-plugins&filter=security')); ?>" class="btn btn-border btn-xs ml-1"><?php _e('Security', 'admin widgets', 'hqtheme-extra'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=marmot-manage-plugins&filter=translate')); ?>" class="btn btn-border btn-xs ml-1"><?php _e('Translate', 'admin widgets', 'hqtheme-extra'); ?></a>
        </div>
        <?php
    }

    private function get_plugin_info($plugin_config) {
        ob_start();
        ?>
        <div class="hqt-row">
            <div class="hqt-col-1-3__md hqt-col-1-4__lg"><b><?php _ex($plugin_config['name'], 'Manage Plugins', 'hqtheme-extra'); ?></b></div>
            <div class="hqt-col-2-3__md hqt-col-3-4__lg"><?php _ex($plugin_config['description'], 'Manage Plugins', 'hqtheme-extra'); ?></div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_plugin_content_after($plugin_config) {
        $install_url = $deactivate_url = '';
        $activate_url = wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $plugin_config['plugin_file'] . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin_config['plugin_file']);
        if (\HQLib\is_plugin_installed($plugin_config['plugin_file'])) {
            if (\HQLib\is_plugin_active($plugin_config['plugin_file'])) {
                $deactivate_url = wp_nonce_url('plugins.php?action=deactivate&amp;plugin=' . $plugin_config['plugin_file'] . '&amp;plugin_status=all&amp;paged=1&amp;s', 'deactivate-plugin_' . $plugin_config['plugin_file']);
                // Deactivate
                $btn_label = _x('Deactivate', 'admin', 'hqtheme-extra');
            } else {
                // Activate
                $btn_label = _x('Activate', 'admin', 'hqtheme-extra');
            }
        } else {
            // Install
            $btn_label = _x('Install', 'admin', 'hqtheme-extra');
            $install_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=' . $plugin_config['plugin_name']), 'install-plugin_' . $plugin_config['plugin_name']);
        }
        ob_start();
        ?>
        <div class="mt-1 pt-1 border-top-dotted">
            <div class="d-iblock">
                <?php if ('pro' == $plugin_config['type'] && !\HQLib\License::is_activated()) : ?>
                    <span class="text-italic text-brighter"><?php _ex('Invalid license key', 'admin', 'hqtheme-extra'); ?></span>
        <?php else : ?>
                    <a href="#"
                       data-hqt-action-btn
                       data-action="install-activate"
                       data-install-url="<?php echo esc_attr($install_url); ?>" 
                       data-activate-url="<?php echo esc_attr($activate_url); ?>"
                       data-deactivate-url="<?php echo esc_attr($deactivate_url); ?>"
                       data-callback="refresh-page">
                    <?php echo esc_html($btn_label); ?>
                    </a>
            <?php endif; ?>
            </div>
                <?php if (!empty($plugin_config['link'])) : ?>
                <div class="d-iblock ml-2 pl-2 border-left-dotted">
                <?php echo sprintf('<a href="%s" target="_blank">%s</a>', esc_url($plugin_config['link']), _x('Learn more', 'Manage Plugins', 'hqtheme-extra')) ?>
                </div>
        <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

}
