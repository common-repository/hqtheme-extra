<?php

namespace HQExtra;

defined('ABSPATH') || exit;

class Dependencies {

    /**
     *
     * @var bool
     */
    protected $is_dependencies_met = true;

    /**
     * Required / Recommended Plugins
     * 
     * @since 1.0.0
     * 
     * @var array
     */
    public static $plugins = [
        'elementor' => [
            'name' => 'Elementor',
            'file' => 'elementor/elementor.php',
            'min_version' => '3.1.0',
            'constant' => '\ELEMENTOR_VERSION',
            'required' => false,
            'dismiss' => false,
        ],
    ];

    /**
     * Current plugin name
     * 
     * @since 1.0.0
     * 
     * @var string
     */
    protected $currnet_plugin_name;

    public function __construct($currnet_plugin_name, $requires_hqtheme = false, $allow_dismis = true) {
        $this->currnet_plugin_name = $currnet_plugin_name;
        $this->check_plugins();

        if (!self::is_theme_active()) {

            if ($requires_hqtheme) {
                $this->is_dependencies_met = false;
            }
            if (is_admin()) {
                if ($requires_hqtheme) {
                    $required_recommended = 'requires';
                } else {
                    $required_recommended = 'recommends';
                }
                if (!self::is_theme_installed()) {
                    if (current_user_can('install_themes')) {
                        $theme_wordpress_repo_slug = 'marmot';
                        $theme_details_link = \HQLib\THEME_SITE_URL . '/marmot/?utm_source=wp-admin&utm_medium=link&utm_campaign=default&utm_content=recommended-required';
                        $install_url = wp_nonce_url(self_admin_url('update.php?action=install-theme&theme=' . $theme_wordpress_repo_slug), 'install-theme_' . $theme_wordpress_repo_slug);
                        $message = '<h3>' . $this->currnet_plugin_name . ' ' . $required_recommended . ' Theme - Marmot</h3>'
                                . '<p>' . sprintf(_x('<b>%s</b> works best with <b>%s</b> theme. That\'s why we recommed <a href="%s" target="_blank">%s theme</a>. <br>Learn more about Marmot theme <a href="%s" target="_blank" >here</a>.', 'theme dependency', 'hqtheme-extra'), $this->currnet_plugin_name, 'Marmot', $theme_details_link, 'Marmot', $theme_details_link) . '</p>';
                        $message .= '<p>' . sprintf('<a href="%s" class="button-primary">%s %s theme</a>', $install_url, _x('Install', 'theme dependency', 'hqtheme-extra'), 'Marmot') . '</p>';
                        Admin_Notifications::instance()->add_notice('theme-install', 'info', $message, $allow_dismis);
                    }
                }
            }
        }
    }

    public static function is_theme_installed() {
        foreach ((array) wp_get_themes() as $theme_dir => $theme) {
            if ('Marmot' === $theme->name || 'Marmot' === $theme->parent_theme) {
                return true;
            }
        }
        return false;
    }

    public static function is_theme_active() {
        $theme = wp_get_theme();

        if ('Marmot' === $theme->name || 'Marmot' === $theme->parent_theme) {
            return true;
        }
        return false;
    }

    public function is_dependencies_met() {
        return $this->is_dependencies_met;
    }

    public function check_plugins() {
        foreach (static::$plugins as $plugin_name => $plugin_data) {
            if (!defined($plugin_data['constant'])) {
                $type = '';
                if ($plugin_data['required']) {
                    $this->is_dependencies_met = false;
                    // Check other plugins only in admin
                    if (!is_admin()) {
                        return;
                    }
                    $type = 'error';
                    $message_template_install = _x('<b>%s</b> is not working because you need to install <b>%s</b> plugin.', 'plugin dependency', 'hqtheme-extra');
                    $message_template_activate = _x('<b>%s</b> is not working because you need to activate <b>%s</b> plugin.', 'plugin dependency', 'hqtheme-extra');
                } elseif (is_admin()) {
                    $type = 'info';
                    $message_template_install = _x('<b>%s</b> recommends to install <b>%s</b> plugin.', 'plugin dependency', 'hqtheme-extra');
                    $message_template_activate = _x('<b>%s</b> recommends to activate <b>%s</b> plugin.', 'plugin dependency', 'hqtheme-extra');
                }

                if ($type) { // Prepare notifications for Recommendered only in admin
                    if (\HQLib\is_plugin_installed($plugin_data['file'])) {
                        if (!current_user_can('activate_plugins')) {
                            return;
                        }
                        $activation_url = wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $plugin_data['file'] . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin_data['file']);
                        $message = '<p>' . sprintf($message_template_activate, $this->currnet_plugin_name, $plugin_data['name']) . '</p>';
                        $message .= '<p>' . sprintf('<a href="%s" class="button-primary">%s %s</a>', $activation_url, _x('Activate', 'plugin dependency', 'hqtheme-extra'), $plugin_data['name']) . '</p>';
                    } else {
                        if (!current_user_can('install_plugins')) {
                            return;
                        }
                        if (isset($_GET['action']) && $_GET['action'] == 'install-plugin') {
                            return;
                        }
                        $install_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=' . $plugin_name), 'install-plugin_' . $plugin_name);
                        $message = '<p>' . sprintf($message_template_install, $this->currnet_plugin_name, $plugin_data['name']) . '</p>';
                        $message .= '<p>' . sprintf('<a href="%s" class="button-primary">%s %s</a>', $install_url, _x('Install', 'plugin dependency', 'hqtheme-extra'), $plugin_data['name']) . '</p>';
                    }
                    // Add notice
                    Admin_Notifications::instance()->add_notice('plugin_' . $plugin_name, $type, $message, $plugin_data['dismiss']);
                }
            } else {
                // Check min version
                $installed_plugin_version = self::get_installed_plugin_version($plugin_data['file']);
                if (!empty($plugin_data['min_version']) && version_compare($installed_plugin_version, $plugin_data['min_version'], '<')) {
                    $this->is_dependencies_met = false;
                    // Check other plugins only in admin
                    if (!is_admin()) {
                        return;
                    }

                    $message_template_update = _x('<b>%s</b> is not working because you need to install <b>%s minimum version %s</b> plugin.', 'plugin dependency', 'hq-widgets-for-elementor');

                    $type = 'error';
                    $update_url = wp_nonce_url(self_admin_url('update.php?action=upgrade-plugin&plugin=' . $plugin_data['file']), 'upgrade-plugin_' . $plugin_data['file']);

                    $message = '<p>' . sprintf($message_template_update, $this->currnet_plugin_name, $plugin_data['name'], $plugin_data['min_version']) . '</p>';
                    $message .= '<p>' . sprintf('<a href="%s" class="button-primary">%s %s</a>', $update_url, _x('Update', 'plugin dependency', 'hq-widgets-for-elementor'), $plugin_data['name']) . '</p>';
                    Admin_Notifications::instance()->add_notice('plugin_' . $plugin_name, $type, $message, $plugin_data['dismiss']);
                }
            }
        }
    }

    public static function get_installed_plugin_version($activation) {
        require_once (ABSPATH . 'wp-admin/includes/plugin.php');
        $allPlugins = get_plugins();

        if (empty($allPlugins[$activation])) {
            return false;
        }

        return $allPlugins[$activation]['Version'];
    }

}
