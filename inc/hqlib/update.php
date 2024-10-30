<?php

namespace HQLib;

defined('ABSPATH') || exit;

/**
 * Control premium plugins update
 *
 * @since 1.0.0
 */
class Update {

    private static $_instance = null;

    /**
     * Data for all hqwebs include premium plugins
     * 
     * @since 1.0.0
     * 
     * @var array
     */
    private $plugins = [
        'marmot-enhancer-pro' => [
            'name' => 'Marmot Enhancer Pro',
            'json_url' => '/plugins/marmot-enhancer-pro.json',
            'activation' => 'marmot-enhancer-pro/marmot-enhancer-pro.php',
            'pro' => 1,
        ],
        'layerslider' => [
            'name' => 'LayerSlider',
            'json_url' => '/plugins/layerslider.json',
            'activation' => 'layerslider/layerslider.php',
            'pro' => 1,
        ],
        'revslider' => [
            'name' => 'RevSlider',
            'json_url' => '/plugins/revslider.json',
            'activation' => 'revslider/revslider.php',
            'pro' => 1,
        ],
    ];

    public static function instance() {

        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        // Plugin Information for the Popup
        add_filter('plugins_api', [$this, 'plugins_api'], 20, 3);

        // Push the Update Information into WP Transients
        add_filter('site_transient_update_plugins', [$this, 'push_update']);
        add_action('delete_site_transient_update_plugins', [$this, 'delete_site_transient_update_plugins']);
    }

    protected function get_plugins() {
        return apply_filters('hqt/update/plugins', $this->plugins);
    }

    public function plugins_api($res, $action, $args) {

        // do nothing if this is not about getting plugin information
        if ('plugin_information' !== $action) {
            return false;
        }

        $plugins = $this->get_plugins();

        // do nothing if it is not our plugin
        if (!isset($plugins[$args->slug])) {
            return false;
        }

        $plugin_slug = $args->slug;
        $plugin_name = $plugins[$args->slug]['name'];
        $json_url = HQLib::get_templates_api_url() . $plugins[$args->slug]['json_url'];

        // trying to get from cache first
        if (false == $remote = get_transient('hqt_update_' . $plugin_slug)) {

            $remote = wp_remote_get($json_url, [
                'timeout' => 20,
                'headers' => [
                    'Accept' => 'application/json'
                ]
                    ]
            );

            if (!is_wp_error($remote) && isset($remote['response']['code']) && $remote['response']['code'] == 200 && !empty($remote['body'])) {
                set_transient('hqt_update_' . $plugin_slug, $remote, DAY_IN_SECONDS);
            }
        }

        if (!is_wp_error($remote) && isset($remote['response']['code']) && $remote['response']['code'] == 200 && !empty($remote['body'])) {

            $remote = json_decode($remote['body']);
            $res = new \stdClass();

            $res->name = $plugin_name;
            $res->slug = $plugin_slug;
            $res->version = $remote->version;
            $res->tested = $remote->tested;
            $res->requires = $remote->requires;
            $res->author = '<a href="' . THEME_SITE_URL . '/">HQWebS</a>';
            $res->author_profile = THEME_SITE_URL . '/';
            $res->download_link = $remote->download_url . ($plugins[$args->slug]['pro'] ? $this->get_license_url_params() : '');
            $res->trunk = $remote->download_url;
            $res->requires_php = $remote->requires;
            $res->last_updated = $remote->last_updated;
            $res->sections = json_decode(json_encode($remote->sections), true);

            // in case you want the screenshots tab, use the following HTML format for its content:
            // <ol><li><a href="IMG_URL" target="_blank"><img src="IMG_URL" alt="CAPTION" /></a><p>CAPTION</p></li></ol>
            if (!empty($remote->sections->screenshots)) {
                $res->sections['screenshots'] = $remote->sections->screenshots;
            }

            $res->banners = json_decode(json_encode($remote->banners), true);

            return $res;
        }

        return false;
    }

    protected function get_license_url_params() {
        $license_key = \HQLib\License::get_user_license();
        $site_domain = \HQLib\License::get_site_domain();

        return '&key=' . $license_key . '&domain=' . $site_domain;
    }

    public function push_update($transient) {

        $plugins = $this->get_plugins();

        foreach ($plugins as $plugin_slug => $plugin) {

            if (false == $remote = get_transient('hqt_upgrade_' . $plugin_slug)) {

                // info.json is the file with the actual plugin information on your server
                $remote = wp_remote_get(HQLib::get_templates_api_url() . $plugin['json_url'], [
                    'timeout' => 20,
                    'headers' => [
                        'Accept' => 'application/json'
                    ]
                        ]
                );

                if (!is_wp_error($remote) && isset($remote['response']['code']) && $remote['response']['code'] == 200 && !empty($remote['body'])) {
                    $remote = json_decode($remote['body']);
                    set_transient('hqt_upgrade_' . $plugin_slug, $remote, 12 * HOUR_IN_SECONDS); // 12 hours cache
                } else {
                    return false;
                }
            }

            if ($remote) {
                $installed_plugin_version = self::get_installed_plugin_version($plugin['activation']);
                if (
                        $remote &&
                        $installed_plugin_version &&
                        version_compare($installed_plugin_version, $remote->version, '<') &&
                        version_compare($remote->requires, get_bloginfo('version'), '<')
                ) {
                    $res = new \stdClass();
                    $res->slug = $plugin_slug;
                    $res->plugin = $plugin['activation'];
                    $res->new_version = $remote->version;
                    $res->tested = $remote->tested;
                    $res->package = $remote->download_url . $this->get_license_url_params();
                    @$transient->response[$res->plugin] = $res;
                }
            }
        }

        return $transient;
    }

    function delete_site_transient_update_plugins() {

        $plugins = $this->get_plugins();

        foreach ($plugins as $plugin_slug => $plugin) {
            delete_transient('hqt_upgrade_' . $plugin_slug);
        }
    }

    public static function get_installed_plugin_version($activation) {
        $allPlugins = get_plugins();

        if (empty($allPlugins[$activation])) {
            return false;
        }

        return $allPlugins[$activation]['Version'];
    }

}
