<?php

namespace HQLib;

defined('ABSPATH') || exit;

define(__NAMESPACE__ . '\LIB_URL', plugin_dir_url(__FILE__));
define(__NAMESPACE__ . '\VERSION', '1.0.0');

/**
 * HQLib Prefix
 *
 * @since 1.0.0
 * 
 * @var string
 */
const HQLIB_PREFIX = '_hqt_';

/**
 * Marmot main website url
 */
const THEME_SITE_URL = 'https://marmot.hqwebs.net';

class HQLib {

    private static $_instance = null;

    const demos_api_url = 'https://marmot.hqwebs.net/demos-api';
    const demos_static_api_url = 'https://demos-api.hqwebs.net';

    public static function instance() {

        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        if (!defined('HQTHEME_WHITELABEL')) {
            define('HQTHEME_WHITELABEL', false);
        }

        $this->setup_hooks();
        Meta::instance();

        if (is_admin()) {
            License::instance();
            Update::instance();
        }
    }

    private function setup_hooks() {
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        add_action('wp_ajax_hqlib_select2', ['\HQLib\Helper', 'ajax_search']);
        add_action('wp_ajax_hqlib_save_options', ['\HQLib\Options', 'save_options']);
        add_filter('admin_footer_text', [$this, 'admin_footer_text'], 999);
    }

    public function admin_enqueue_scripts() {
        wp_enqueue_style('hqlib-style', LIB_URL . 'assets/css/style.css', '', VERSION);
        wp_enqueue_script('hqlib-script', LIB_URL . 'assets/js/script.js', ['jquery'], VERSION, true);

        $data = [
            '_ajax_nonce' => wp_create_nonce('hq-lib'),
            'hqlib_prefix' => HQLIB_PREFIX,
            'translate' => $this->getHqlibDataTranslate(),
        ];
        wp_localize_script('hqlib-script', 'hqlibData', $data);

        // Select2
        wp_enqueue_style('select2', LIB_URL . 'assets/lib/e-select2/css/e-select2.min.css', [], '4.0.6-rc.1');
        wp_enqueue_script('jquery-select2', LIB_URL . 'assets/lib/e-select2/js/e-select2.full.min.js', ['jquery'], '4.0.6-rc.1');

        // Simple Clean Date Picker - http://t1m0n.name/air-datepicker/docs/
        wp_enqueue_style('datepicker', LIB_URL . 'assets/lib/datepicker/css/jquery.datepicker.min.css', '', '2.2.3');
        wp_enqueue_script('datepicker', LIB_URL . 'assets/lib/datepicker/js/jquery.datepicker.js', ['jquery'], '2.2.3');
        wp_enqueue_script('datepicker-en', LIB_URL . 'assets/lib/datepicker/js/i18n/datepicker.en.js', ['jquery']);
    }

    /**
     * 
     * @since 1.0.0
     * 
     * @return string
     */
    public static function get_templates_api_url() {
        return self::demos_api_url;
    }

    /**
     * 
     * @since 1.1.0
     * 
     * @return string
     */
    public static function get_static_api_url() {
        return self::demos_static_api_url;
    }

    /**
     * Admin footer text.
     * Modifies the "Thank you" text displayed in the admin footer.
     * Fired by `admin_footer_text` filter.
     *
     * @param string $footer_text The content that will be printed.
     * @return string The content that will be printed.
     */
    public function admin_footer_text($footer_text) {
        $current_screen = get_current_screen();
        $hq_pages = [
            'hq-elementor-widgets',
            'marmot',
        ];
        $is_hq_screen = ( $current_screen && false !== \HQLib\Helper::strposa($current_screen->id, $hq_pages) );

        if ($is_hq_screen) {
            $footer_text = sprintf(
                    /* translators: 1: Elementor, 2: Link to plugin review */
                    'Enjoyed %1$s? Please leave us a %2$s rating. We really appreciate your support!',
                    '<a href="' . THEME_SITE_URL . '/?utm_source=wp-admin&utm_medium=link&utm_campaign=default&utm_content=footer-enjoy" target="_blank"><strong>Marmot</strong></a>',
                    '<a href="https://wordpress.org/plugins/hqtheme-extra/#reviews" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
            );
        }

        return $footer_text;
    }

    public function getHqlibDataTranslate() {
        return [
            'activate' => _x('Activate', 'admin', 'hqtheme-extra'),
            'activating' => _x('Activating', 'admin', 'hqtheme-extra'),
            'activated' => _x('Activated', 'admin', 'hqtheme-extra'),
            'deactivate' => _x('Deactivate', 'admin', 'hqtheme-extra'),
            'deactivating' => _x('Deactivating', 'admin', 'hqtheme-extra'),
            'install' => _x('Install', 'admin', 'hqtheme-extra'),
            'installing' => _x('Installing', 'admin', 'hqtheme-extra'),
            'enable' => _x('Enable', 'admin', 'hqtheme-extra'),
            'enabling' => _x('Enabling', 'admin', 'hqtheme-extra'),
            'enabled' => _x('Enabled', 'admin', 'hqtheme-extra'),
        ];
    }

}

/**
 * Get post meta by key
 * @global \WP_Post $post
 * @param string $key
 * @param string $group
 * @param string $default
 * @param boolean $add_prefix
 * @return any
 */
function get_post_meta($post_id = null, $key = null, $group = null, $default = false, $add_prefix = true) {
    if (!$post_id) {
        global $post;

        if (!$post->ID) {
            return $default;
        }
        $post_id = $post->ID;
    }

    if ($group) {
        $group = \HQLib\HQLIB_PREFIX . $group;
        $options = \get_post_meta($post_id, $group, false);
        if (empty($key)) {
            return $options;
        }
    } else {
        if ($add_prefix) {
            $key = empty($key) ? '' : \HQLib\HQLIB_PREFIX . $key;
        }
        return \get_post_meta($post_id, $key, true);
    }

    if (isset($options[$key])) {
        return $options[$key];
    }

    return $default;
}

/**
 * Get term meta by key
 * @global \WP_Term $term
 * @param string $key
 * @param string $group
 * @param string $default
 * @param boolean $add_prefix
 * @return any
 */
function get_term_meta($term_id, $key = null, $group = null, $default = false, $add_prefix = true) {
    if (!$term_id) {
        return $default;
    }

    if ($group) {
        $group = \HQLib\HQLIB_PREFIX . $group;
        $options = \get_term_meta($term_id, $group, false);
        if (empty($key)) {
            return $options;
        }
    } else {
        if ($add_prefix) {
            $key = empty($key) ? '' : \HQLib\HQLIB_PREFIX . $key;
        }
        return \get_term_meta($term_id, $key, true);
    }

    if (isset($options[$key])) {
        return $options[$key];
    }

    return $default;
}

/**
 * Get terms objects list
 *
 * @param  [type]  $taxonomy
 * @param  boolean $child_of_current
 * @return [type]
 */
function get_terms_objects($taxonomy = null, $child_of_current = false, $custom_args = array()) {

    if (!$taxonomy) {
        return array();
    }

    if (!is_array($custom_args)) {
        $custom_args = array();
    }

    $args = array_merge(array('taxonomy' => $taxonomy), $custom_args);

    if ($child_of_current && is_tax($taxonomy)) {
        $args['child_of'] = get_queried_object_id();
    }

    return \get_terms($args);
}

/**
 * Get terms of passed taxonomy for options list
 *
 * @param  [type]  $taxonomy
 * @param  boolean $child_of_current
 * @return [type]
 */
function get_terms_for_options($taxonomy = null, $child_of_current = false, $custom_args = array()) {

    $terms = \HQLib\get_terms_objects($taxonomy, $child_of_current, $custom_args);
    return wp_list_pluck($terms, 'name', 'term_id');
}

/**
 * Get global option
 * @param string $key
 * @param string $group
 * @param any $default
 * @param string options
 * @return any
 */
function hq_get_option($key = null, $group = null, $default = false, $storage = 'options', $add_prefix = true) {
    if ($group) {
        $group = \HQLib\HQLIB_PREFIX . $group;
        if ('options' === $storage) {
            $options = \get_option($group, $default);
        } else if ('theme_mods' === $storage) {
            $options = \get_theme_mod($group, $default);
        }
        if (empty($key)) {
            return $options;
        }
    } else {
        if ($add_prefix) {
            $key = empty($key) ? '' : \HQLib\HQLIB_PREFIX . $key;
        }
        if ('options' === $storage) {
            return \get_option($key, $default);
        } else if ('theme_mods' === $storage) {
            return \get_theme_mod($key, $default);
        }
    }

    if (isset($options[$key])) {
        return $options[$key];
    }

    return $default;
}

/**
 * Checks if plugin is installed
 *
 * @since 1.0.0
 *
 * @param string $plugin Plugin activation string
 * @return bool
 */
function is_plugin_installed($plugin) {
    require_once ABSPATH . 'wp-includes/pluggable.php';
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    $plugins = \get_plugins();
    return isset($plugins[$plugin]);
}

/**
 * Checks if plugin is active
 *
 * @since 1.0.0
 *
 * @param string $plugin Plugin activation string
 * @return bool
 */
function is_plugin_active($plugin) {
    return in_array($plugin, (array) \get_option('active_plugins', [])) || is_plugin_active_for_network($plugin);
}

/**
 * Checks if plugin is active for network
 *
 * @since 1.0.0
 *
 * @param string $plugin Plugin activation string
 * @return bool
 */
function is_plugin_active_for_network($plugin) {
    if (!is_multisite()) {
        return false;
    }

    $plugins = get_site_option('active_sitewide_plugins');
    if (isset($plugins[$plugin])) {
        return true;
    }

    return false;
}
