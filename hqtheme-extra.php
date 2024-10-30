<?php

/**
 * Plugin Name:       HQTheme Extra
 * Plugin URI:        https://marmot.hqwebs.net/marmot/?utm_source=wp-admin&utm_medium=link&utm_campaign=default&utm_term=hqtheme-extra&utm_content=plugin-uri
 * Description:       HQTheme Extra adds extras for Marmot Theme - Ready sites one click import
 * Version:           1.0.19
 * Requires at least: 5.3
 * Requires PHP:      7.2
 * Author:            HQWebS
 * Author URI:        https://marmot.hqwebs.net/?utm_source=wp-admin&utm_medium=link&utm_campaign=default&utm_term=hqtheme-extra&utm_content=plugin-author
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       hqtheme-extra
 */

namespace HQExtra;

defined('ABSPATH') || exit;

/**
 * Plugin URL
 *
 * @since 1.0.0
 * @var string
 */
define(__NAMESPACE__ . '\PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Plugin Directory Path
 *
 * @since 1.0.0
 * @var string
 */
define(__NAMESPACE__ . '\PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Plugin Unique slug
 *
 * @since 1.0.0
 * @var string
 */
const PLUGIN_SLUG = 'hqtheme-extra';

/**
 * Plugin Unique Name
 *
 * @since 1.0.0
 * @var string
 */
const PLUGIN_NAME = 'HQTheme Extra';

/**
 * Plugin Version
 *
 * @since 1.0.0
 * @var string
 */
const VERSION = '1.0.19';

// Load Autoloader
require_once PLUGIN_PATH . '/inc/autoloader.php';
Autoloader::run();

/**
 * Main HQExtra Class
 *
 * Run plugin
 *
 * @since 1.0.0
 */
class HQExtra {

    /**
     * Instance
     * 
     * @since 1.0.0
     * 
     * @var HQExtra 
     */
    private static $_instance = null;

    /**
     * Get class instance
     *
     * @since 1.0.0
     *
     * @return HQExtra
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

        // Load Library
        require_once PLUGIN_PATH . 'inc/hqlib/init.php';

        /**
         * Plugin activation
         */
        register_activation_hook(__FILE__, [$this, 'after_plugin_activation']);
        add_action('plugins_loaded', [$this, 'run']);
    }

    public function run() {
        // Check dependencies
        $dependencies = new Dependencies(PLUGIN_NAME);
        if (!$dependencies->is_dependencies_met()) {
            return;
        }

        /**
         * I18n
         * Used in HQTheme Extra plugin and HQLib
         * @since 1.0.0
         */
        add_action('init', [$this, 'load_plugin_textdomain']);

        add_action('mar_fs_loaded', [$this, 'init_freemius_client_migration']);

        add_filter('hqt/modules_control/get_all', [Modules\Modules_Control::instance(), 'hqt_modules_control_get_all']);

        // Load modules for elementors
        add_action('elementor/init', [Modules\Modules_Control::instance(), 'load_active_modules']);
        add_action('elementor/widgets/widgets_registered', [Modules\Modules_Control::instance(), 'load_active_modules_assets']);

        if (is_admin()) {
            // After activation action
            //add_action('admin_init', [$this, 'redirect_after_activation']);
            // Admin scripts
            add_action('admin_enqueue_scripts', [$this, 'admin_enqueue']);
            //
            Admin\Page\Theme_Templates::instance();
        }

        $this->_init();
    }

    /**
     * I18n
     * 
     * @since 1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain('hqtheme-extra');
    }

    public function init_freemius_client_migration() {
        \HQExtra\Client_Migration\Freemius::instance();
    }

    /**
     * Init Post Type
     *
     * @since 1.0.0
     */
    protected function _init() {
        // TODO move here theme demos import page
        // May be add action and hook it here

        if (\HQLib\is_plugin_active('clever-mega-menu-for-elementor/clever-mega-menu-for-elementor.php')) {
            Menu::instance();
        }

        Admin\Theme_Options::instance();

        if (is_admin()) {
            Demos\Import::instance();
            Admin\Menu::instance();
        }

        // Load plugin premium features only if module is enabled
        $options = \HQLib\hq_get_option('theme_modules');
        foreach (Modules\Modules_Control::$modules as $module_key => $module_config) {
            $class_name = '\\' . __NAMESPACE__ . '\\Modules\\' . str_replace('-', '_', ucwords($module_key, '-')) . '\\Module';

            if ((!empty($module_config['early_load']) && $module_config['early_load']) && class_exists($class_name) && \HQLib\Helper::is_module_active($module_key, $options)) {
                $class_name::instance();
            }
        }
    }

    /**
     * Admin scripts and styles
     * 
     * @since 1.0.0
     */
    public function admin_enqueue() {

        wp_register_script('eventsource', PLUGIN_URL . '/assets/js/lib/eventsource.js', [], VERSION, true);

        wp_register_script(PLUGIN_SLUG . '-templates-api', PLUGIN_URL . '/assets/js/admin/templates-api.js', ['jquery'], VERSION, true);

        $data = [
            '_ajax_nonce' => wp_create_nonce('hqt-templates'),
        ];

        wp_localize_script(PLUGIN_SLUG . '-templates-api', 'hqtTemplatesData', $data);

        wp_register_script(PLUGIN_SLUG . '-templates-grid', PLUGIN_URL . '/assets/js/admin/templates-grid.js', [PLUGIN_SLUG . '-templates-api', 'wp-util', 'imagesloaded', 'jquery'], VERSION, true);

        wp_register_script(PLUGIN_SLUG . '-templates-details', PLUGIN_URL . '/assets/js/admin/templates-details.js', [PLUGIN_SLUG . '-templates-grid', 'jquery', 'wp-util', 'updates', 'wp-url'], VERSION, true);

        $screen = get_current_screen();
        $data = [
            'current_page_id' => $screen->id,
            'has_active_license' => \HQLib\License::is_activated(),
        ];

        wp_localize_script(PLUGIN_SLUG . '-templates-grid', 'hqtTemplatesGridData', $data);

        wp_register_script(PLUGIN_SLUG . '-templates-install', PLUGIN_URL . '/assets/js/admin/templates-install.js', ['jquery', 'wp-util', 'updates', 'wp-url', PLUGIN_SLUG . '-templates-grid', PLUGIN_SLUG . '-templates-details'], VERSION, true);

        // Styles
        wp_enqueue_style('admin_css', PLUGIN_URL . 'assets/css/admin/import.css', false, VERSION);
    }

    /**
     * Hooked after plugin activation
     * 
     * @since 1.0.0
     */
    function after_plugin_activation($network_wide) {
        if (is_multisite() && $network_wide) {
            return;
        }

        set_transient(PLUGIN_SLUG . '_activation_redirect', true, MINUTE_IN_SECONDS);
    }

    /**
     * @since 1.0.0
     * @access public
     * @deprecated since 1.1.0
     */
    public function redirect_after_activation() {
        if (!get_transient(PLUGIN_SLUG . '_activation_redirect')) {
            return;
        }

        if (wp_doing_ajax()) {
            return;
        }

        delete_transient(PLUGIN_SLUG . '_activation_redirect');

        if (is_network_admin() || isset($_GET['activate-multi'])) {
            return;
        }

        // Redirect to theme dashboard if Marmot is installed
        $theme = wp_get_theme();

        if ('Marmot' === $theme->name || 'Marmot' === $theme->parent_theme) {
            wp_safe_redirect(admin_url('admin.php?page=marmot'));
            exit;
        } elseif (
                defined('\HQWidgetsForElementor\VERSION') &&
                defined('\ELEMENTOR_VERSION')) {
            // Redirect to widget dashboard if HQ Widgets plugin is installed
            wp_safe_redirect(admin_url('admin.php?page=hq-elementor-widgets'));
            exit;
        }
    }

}

// Run Plugin
HQExtra::instance();
