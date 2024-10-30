<?php

namespace HQExtra\Demos;

defined('ABSPATH') || exit;

use \HQExtra\Demos\Customizer_Import;

class Import {

    public static $api_url;
    private static $_instance = null;
    public static $template_type = null;

    public static function instance() {
        if (empty(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        // Load logger
        Import_Log::instance();

        add_action('wp_ajax_hqtheme-import-start', [$this, 'start']);
        add_action('wp_ajax_hqtheme-import-set-general-configs', [$this, 'set_general_configs']);
        add_action('wp_ajax_hqtheme-import-fix-custom-fonts', [$this, 'fix_custom_fonts']);
        add_action('wp_ajax_hqtheme-import-required-plugins', [$this, 'required_plugin']);
        add_action('wp_ajax_hqtheme-import-required-plugin-activate', [$this, 'required_plugin_activate']);
        add_action('wp_ajax_hqtheme-import-get-reset-data', [$this, 'get_reset_data']);

        // Import AJAX.
        add_action('wp_ajax_hqtheme-import-customizer-settings', [$this, 'import_customizer_settings']);
        add_action('wp_ajax_hqtheme-import-content', [$this, 'import_content']);
        add_action('wp_ajax_hqtheme-import-fixes', [$this, 'import_fixes']);
        add_action('wp_ajax_hqtheme-import-fix-elementor-post', [$this, 'fix_elementor_post']);
        add_action('wp_ajax_hqtheme-import-finish', [$this, 'import_finish']);

        // Reset Post & Terms.
        add_action('wp_ajax_hqtheme-import-delete-posts', [$this, 'delete_imported_posts']);
        add_action('wp_ajax_hqtheme-import-delete-terms', [$this, 'delete_imported_terms']);


        // If importing
        if ('yes' === get_transient('hqt_installing_demo')) {
            // Do not create WooCommerce Pages
            add_filter('woocommerce_create_pages', function ($pages) {
                return [];
            }, 10);
        }

        // Init wxr importer
        Wxr_Importer::instance();
    }

    public function start() {
        check_ajax_referer('hqt-templates', '_ajax_nonce');

        if (!current_user_can('customize')) {
            wp_send_json_error(_x('You are not allowed to perform this action', 'import message', 'hqtheme-extra'));
        }

        $template_id = ( isset($_REQUEST['template_id']) ) ? sanitize_key($_REQUEST['template_id']) : 0;
        if ($template_id) {
            // Will be used to know if import is running at the moment
            set_transient('hqt_installing_demo', 'yes', MINUTE_IN_SECONDS * 20);
            // Clear mappings
            delete_transient('hqt_id_mappings');
            // Clear latest imported posts
            delete_transient('hqt_latest_imports');
            // Start
            do_action('hqt_start_import', $template_id);
            wp_send_json_success();
        } else {
            Import_Log::add('Invalid template: ' . $template_id);
            wp_send_json_error(_x('Invalid template!', 'import message', 'hqtheme-extra'));
        }
    }

    public function set_general_configs() {

        check_ajax_referer('hqt-templates', '_ajax_nonce');

        if (!current_user_can('customize')) {
            wp_send_json_error(_x('You are not allowed to perform this action', 'import message', 'hqtheme-extra'));
        }

        $template_id = ( isset($_REQUEST['template_id']) ) ? sanitize_key($_REQUEST['template_id']) : 0;

        if ($template_id) {

            // Fix WooComerce db tables
            //\WooCommerce::instance();
            //\WC_Install::install();


            $last_imported_demo_details = get_option('_hqt_import_last_importer_demo_details');

            if (
                    !empty($last_imported_demo_details) &&
                    !empty($last_imported_demo_details->required_plugins)
            ) {
                $pods_active = false;
                $woocommerce_active = false;
                foreach ($last_imported_demo_details->required_plugins as $required_plugin) {
                    if ('pods/init.php' === $required_plugin->init) {
                        $pods_active = true;
                    }

                    if ('woocommerce/woocommerce.php' === $required_plugin->init) {
                        $woocommerce_active = true;
                    }
                }

                if ($pods_active) {
                    $url = \HQLib\HQLib::get_templates_api_url() . '/get-pods-package.php?key=' . \HQLib\License::get_user_license() . '&domain=' . \HQLib\License::get_site_domain() . '&id=' . $template_id;
                    $response = wp_remote_get($url, [
                        'timeout' => 20,
                        'headers' => [
                            'Accept' => 'application/json'
                        ]
                    ]);
                    if (!is_wp_error($response) && isset($response['response']['code']) && $response['response']['code'] == 200 && !empty($response['body'])) {
                        try {
                            $pods_packade_data = $response['body'];
                        } catch (\Exception $e) {
                            Import_Log::add('Pods package error: ' . $e->getMessage());
                            wp_send_json_error($e->getMessage());
                        }
                    } else {
                        Import_Log::add('Remote API Error (' . $response['response']['code'] . '), url: ' . $url);
                        wp_send_json_error('Remote API Error - ' . $response['response']['code']);
                    }

                    if (!empty($pods_packade_data)) {
                        require_once (WP_CONTENT_DIR . '/plugins/pods/components/Migrate-Packages/Migrate-Packages.php');
                        $activate_package = \PodsInit::$components->activate_component('migrate-packages');
                        $import_packade = \PodsAPI::init()->import_package($pods_packade_data);
                        pods_cache_clear();
                        Import_Log::add('Pods packages imported');
                    } else {
                        Import_Log::add('Pods packages data is empty');
                        wp_send_json_error(_x('Pods Packages data is empty!', 'import message', 'hqtheme-extra'));
                    }
                }

                if ($woocommerce_active) {
                    $url = \HQLib\HQLib::get_templates_api_url() . '/get-woo.php?key=' . \HQLib\License::get_user_license() . '&domain=' . \HQLib\License::get_site_domain() . '&id=' . $template_id;
                    $response = wp_remote_get($url, [
                        'timeout' => 20,
                        'headers' => [
                            'Accept' => 'application/json'
                        ]
                    ]);

                    if (!is_wp_error($response) && isset($response['response']['code']) && $response['response']['code'] == 200 && !empty($response['body'])) {
                        try {
                            $woo_data = json_decode($response['body'], true);
                        } catch (\Exception $e) {
                            Import_Log::add('Woo error: ' . $e->getMessage());
                            wp_send_json_error($e->getMessage());
                        }
                    } else {
                        if (is_wp_error($response)) {
                            Import_Log::add('ERROR Remote API (' . $response->get_error_message() . '), url: ' . $url);
                        } else {
                            Import_Log::add('ERROR Remote API (' . $response['response']['code'] . '), url: ' . $url);
                        }
                        wp_send_json_error('Remote API Error - ' . $response['response']['code']);
                    }

                    if (!empty($woo_data)) {
                        if (!empty($woo_data['woo_all_product_attrs']) && function_exists('wc_create_attribute')) {
                            Import_Log::add('Woo woo_all_product_attrs: ' . print_r($woo_data['woo_all_product_attrs'], true));
                            foreach ($woo_data['woo_all_product_attrs'] as $key => $attribute) {
                                $args = array(
                                    'name' => $attribute['name'],
                                    'slug' => $attribute['slug'],
                                    'type' => $attribute['type'],
                                    'order_by' => $attribute['order_by'],
                                    'has_archives' => $attribute['has_archives'],
                                );

                                $id = wc_create_attribute($args);
                                if (is_wp_error($id)) {
                                    Import_Log::add('ERROR: Woo attr - ' . $id->get_error_message() . ' Attrs: ' . print_r($args, true));
                                } else {
                                    Import_Log::add('Woo attr OK ID: ' . $id);
                                }
                            }
                        }
                        Import_Log::add('Woo imported');
                    } else {
                        Import_Log::add('Woo data is empty');
                        wp_send_json_error(_x('Woo data is empty!', 'import message', 'hqtheme-extra'));
                    }
                }
            }

            Import_Log::add('Set general configs done');
            wp_send_json_success();
        } else {
            Import_Log::add('Invalid template: ' . $template_id);
            wp_send_json_error(_x('Invalid template!', 'import message', 'hqtheme-extra'));
        }
    }

    public function fix_custom_fonts() {

        check_ajax_referer('hqt-templates', '_ajax_nonce');

        if (!current_user_can('customize')) {
            wp_send_json_error(_x('You are not allowed to perform this action', 'import message', 'hqtheme-extra'));
        }

        $template_id = ( isset($_REQUEST['template_id']) ) ? sanitize_key($_REQUEST['template_id']) : 0;

        if ($template_id) {

            $ec_icons_fonts = get_option('ec_icons_fonts');
            if ($ec_icons_fonts) {
                if (!class_exists('ZipArchive')) {
                    Import_Log::add('ZipArchive missing');
                    wp_send_json_error('ZipArchive missing');
                }

                $url = \HQLib\HQLib::get_templates_api_url() . '/get-custom-fonts.php?key=' . \HQLib\License::get_user_license() . '&domain=' . \HQLib\License::get_site_domain() . '&id=' . $template_id;

                $tmpfile = download_url($url, $timeout = 300);
                if (!is_wp_error($tmpfile)) {
                    try {
                        $zip = new \ZipArchive();
                        $res = $zip->open($tmpfile);

                        if ($res === true) {
                            $upload = wp_upload_dir();
                            $upload_dir = $upload['basedir'];
                            $ex = $zip->extractTo($upload_dir . '/elementor_icons_files');
                            $zip->close();
                            if ($ex === false) {
                                $result['status_save'] = 'failedextract';
                            } else {
                                unlink($tmpfile);
                                $this->ec_icons_regenerate();
                                Import_Log::add('Custom fonts imported');
                                wp_send_json_success();
                            }
                        } else {
                            $result['status_save'] = 'failedopen';
                        }
                        unlink($tmpfile);
                        Import_Log::add('Custom fonts error: ' . print_r($result, true));
                        wp_send_json_error($result);
                    } catch (\Exception $e) {
                        Import_Log::add('Custom fonts error: ' . $e->getMessage());
                        wp_send_json_error($e->getMessage());
                    }
                } else {
                    // Sent success on error in case of multiple demos import - wrong option may leave from prev import
                    wp_send_json_success('Remote API Error');
                    //wp_send_json_error('Remote API Error');
                }
            } else {
                wp_send_json_success();
            }
        } else {
            Import_Log::add('Invalid template: ' . $template_id);
            wp_send_json_error(_x('Invalid template!', 'import message', 'hqtheme-extra'));
        }
    }

    private function ec_icons_regenerate() {

        $options = get_option('ec_icons_fonts');

        if (!empty($options) && is_array($options)) {

            $newoptions = array();

            foreach ($options as $key => $font) {

                if (empty($font['data'])) {
                    continue;
                }

                $font_decode = json_decode($font['data'], true);

                $font_data = \ECIcons::getInstance()->get_config_font($font_decode['file_name']);

                if (!$font_data) {
                    continue;
                }

                $newoptions[$font_data['name']] = array(
                    'status' => '1',
                    'data' => json_encode($font_data),
                );
            }
            update_option('ec_icons_fonts', $newoptions);
        }

        new \MergeCss_ECIcons();
    }

    public function get_reset_data() {

        check_ajax_referer('hqt-templates', '_ajax_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(_x('Error: You don\'t have the required permissions.', 'import message', 'hqtheme-extra'));
        }

        global $wpdb;

        $post_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_hqt_import_post'");
        $term_ids = $wpdb->get_col("SELECT term_id FROM {$wpdb->termmeta} WHERE meta_key='_hqtheme_imported_term'");

        $data = [
            'posts' => $post_ids,
            'terms' => $term_ids,
        ];

        wp_send_json_success($data);
    }

    public function required_plugin_activate($init = '', $options = [], $enabled_extensions = []) {

        check_ajax_referer('hqt-templates', '_ajax_nonce');

        if (!current_user_can('install_plugins') || empty($_POST['init'])) {
            wp_send_json_error(_x('Error: You don\'t have the required permissions.', 'import message', 'hqtheme-extra'));
        }

        $plugin_init = ( isset($_POST['init']) ) ? sanitize_text_field($_POST['init']) : $init;

        $activation_url = admin_url() . wp_nonce_url('plugins.php?action=activate&plugin=' . $plugin_init . '&plugin_status=all&paged=1&s', 'activate-plugin_' . $plugin_init);

        wp_send_json_success(['activation_url' => $activation_url]);

        die;
    }

    public function required_plugin() {

        check_ajax_referer('hqt-templates', '_ajax_nonce');

        if (!current_user_can('customize')) {
            wp_send_json_error(_x('Error: You don\'t have the required permissions.', 'import message', 'hqtheme-extra'));
        }

        $required_plugins = isset($_POST['required_plugins']) ? $_POST['required_plugins'] : [];

        $response = [
            'active' => [],
            'inactive' => [],
            'notinstalled' => [],
        ];

        foreach ($required_plugins as $key => $plugin) {
            if (\HQLib\is_plugin_active($plugin['init'])) {
                $response['active'][] = $plugin;
            } elseif (\HQLib\is_plugin_installed($plugin['init'])) {
                $response['inactive'][] = $plugin;
            } else {
                $response['notinstalled'][] = $plugin;
            }
        }

        wp_send_json_success([
            'required_plugins' => $response,
        ]);
    }

    public function import_fixes() {

        check_ajax_referer('hqt-templates', '_ajax_nonce');

        if (!current_user_can('customize')) {
            wp_send_json_error(_x('You are not allowed to perform this action', 'import message', 'hqtheme-extra'));
        }


        $template_type = isset($_POST['template_type']) ? sanitize_text_field($_POST['template_type']) : '';

        switch ($template_type) {
            case 'sites':
                $matched_ids = [];
                $id_mappings = $this->get_id_mappings('refresh');

                $this->match_template_in_customizer($id_mappings, $matched_ids);
                $this->match_template_in_post_meta($id_mappings, $matched_ids);
                $this->match_template_in_term_meta($id_mappings, $matched_ids);
                //$this->fix_clever_menu($id_mappings);
                // TODO add CF7 match ids

                wp_send_json_success([
                    'elementor_posts_ids' => $this->get_elementor_posts(),
                ]);

                break;
            case 'popup':

                $latest_imports = get_transient('hqt_latest_imports');

                $elementor_posts_ids = [];
                if (isset($latest_imports['post-hqpopup'])) {
                    foreach ($latest_imports['post-hqpopup'] as $v) {
                        $elementor_posts_ids[] = $v['new'];
                    }
                }

                wp_send_json_success([
                    'elementor_posts_ids' => $elementor_posts_ids,
                ]);

                break;

            default:
                Import_Log::add('Invalid template type: ' . $template_type);
                wp_send_json_error(_x('Invalid template type!', 'import message', 'hqtheme-extra'));
                break;
        }
    }

    protected function fix_clever_menu($id_mappings) {
        if (\HQLib\is_plugin_active('clever-mega-menu-for-elementor/clever-mega-menu-for-elementor.php')) {
            $meta_keys_for_update = [
                'cmm4e_menu_post_id',
                'cmm4e_menu_item_id',
            ];

            global $wpdb;

            $results = $wpdb->get_results($wpdb->prepare("SELECT pm.post_id FROM {$wpdb->postmeta} as pm "
                            . "WHERE pm.meta_key = %s", '_hqt_import_post'));

            foreach ($meta_keys_for_update as $key) {
                foreach ($results as $r) {
                    $value = get_post_meta($r->post_id, $key, true);
                    if ($value && isset($id_mappings[$value])) {
                        $value = $id_mappings[$value];
                        update_post_meta($r->post_id, $key, $value);
                    }
                }
            }
            require_once WP_PLUGIN_DIR . '/clever-mega-menu-for-elementor/includes/meta/menu-theme.php';
            \MenuThemeMeta::init()->generate_css();
        }
    }

    public function import_finish() {
        // Update url with elementor function
        $from = isset($_POST['old_url']) ? esc_url_raw($_POST['old_url']) : '';
        $to = '';
        if (!empty($from)) {
            try {
                $to = get_site_url();
                $results = \Elementor\Utils::replace_urls(rtrim($from, '/'), rtrim($to, '/'));
                Import_Log::add('Elementor replace urls: FROM:' . rtrim($from, '/') . ' TO: ' . rtrim($to, '/') . ' RESULT: ' . $results);
                //wp_send_json_success($results);
            } catch (\Exception $e) {
                //wp_send_json_error($e->getMessage());
            }
        }

        // Force Yoast index regneration if Yoast is active
        if (defined('WPSEO_VERSION')) {
            Helper\Yoast::reset_indexables();
        }

        // Presave permalinks
        global $wp_rewrite;
        $wp_rewrite->flush_rules(true);

        // Clear Cache
        \Elementor\Plugin::$instance->files_manager->clear_cache();

        // Generate css for widgets plugin
        if (defined('\HQWidgetsForElementor\VERSION')) {
            \HQWidgetsForElementor\Responsive::compile_stylesheet_templates();
        }

        do_action('hqt_finish_import');

        // Finish import - remove state
        delete_transient('hqt_installing_demo');

        set_theme_mod('marmot_demo_imported', 1);

        wp_send_json_success([$from, $to]);
    }

    protected function get_id_mappings($refresh = '') {
        if ('refresh' === $refresh || false == $id_mappings = get_transient('hqt_id_mappings')) {
            global $wpdb;

            $results = $wpdb->get_results($wpdb->prepare("SELECT pm.post_id as id, pm.meta_value as original_id FROM {$wpdb->postmeta} pm
                WHERE pm.meta_key = %s", '_hqt_import_original_id'));

            $id_mappings = [];
            foreach ($results as $r) {
                $id_mappings[$r->original_id] = $r->id;
            }
            set_transient('hqt_id_mappings', $id_mappings, HOUR_IN_SECONDS); // 1 hour cache

            Import_Log::add('Get mappings:' . print_r($id_mappings, true));
        }

        return $id_mappings;
    }

    protected function match_template_in_customizer($id_mappings, &$matched_ids) {
        $mods = get_theme_mods();

        foreach ($mods as $mod_key => $mod) {
            if (
                    (false !== strpos($mod_key, '_layout') || false !== strpos($mod_key, '_template')) &&
                    is_numeric($mod) &&
                    !empty($id_mappings[$mod])
            ) {
                $matched_ids[] = [
                    'key' => $mod_key,
                    'old' => $mod,
                    'new' => $id_mappings[$mod],
                ];
                Import_Log::add('Match template - OLD:' . $mod . ' NEW: ' . $id_mappings[$mod]);
                set_theme_mod($mod_key, $id_mappings[$mod]);
            }
        }
    }

    protected function get_elementor_posts() {
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare("SELECT pm.post_id FROM {$wpdb->postmeta} as pm "
                        . "WHERE pm.meta_key = %s", '_hqt_import_post'));

        $ids = [];
        foreach ($results as $r) {
            $elementor_data = get_post_meta($r->post_id, '_elementor_data', true);
            if (empty($elementor_data)) {
                continue;
            }
            $ids[] = $r->post_id;
        }

        Import_Log::add('Get elementor post ids:' . print_r($ids, true));

        return $ids;
    }

    public function fix_elementor_post($id_mappings) {

        $post_id = ( isset($_REQUEST['id']) ) ? absint($_REQUEST['id']) : 0;

        if (!$post_id) {
            wp_send_json_error();
        }

        $matched_ids = [];
        $id_mappings = $this->get_id_mappings();

        $elementor_post_processing = new Processing_Elementor_Post();

        $elementor_post_processing->import_single_post($post_id, $id_mappings, $matched_ids);

        Import_Log::add('Fixed elementor post id:' . $post_id);

        wp_send_json_success();
    }

    protected function match_template_in_post_meta($id_mappings, &$matched_ids) {
        $meta_keys_for_update = [
            'header_template',
            'footer_template',
            'single_template',
        ];

        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare("SELECT pm.post_id FROM {$wpdb->postmeta} as pm "
                        . "WHERE pm.meta_key = %s", '_hqt_import_post'));

        foreach ($meta_keys_for_update as $key) {
            foreach ($results as $r) {
                $value = get_post_meta($r->post_id, $key, true);
                if ($value && isset($id_mappings[$value])) {
                    $new_value = $id_mappings[$value];
                    Import_Log::add('Match template - KEY: ' . $key . ' VALUE:' . $value . ' NEW: ' . $new_value);
                    update_post_meta($r->post_id, $key, $new_value);
                }
            }
        }
    }

    protected function match_template_in_term_meta($id_mappings, &$matched_ids) {
        $meta_keys_for_update = [
            'header_template',
            'footer_template',
            'archive_template',
        ];
    }

    public function import_customizer_settings() {

        check_ajax_referer('hqt-templates', '_ajax_nonce');

        if (!current_user_can('customize')) {
            wp_send_json_error(_x('You are not allowed to perform this action', 'import message', 'hqtheme-extra'));
        }

        $template_id = ( isset($_REQUEST['template_id']) ) ? sanitize_key($_REQUEST['template_id']) : 0;

        if ($template_id) {
            $url = \HQLib\HQLib::get_templates_api_url() . '/get-customizer-data.php?key=' . \HQLib\License::get_user_license() . '&domain=' . \HQLib\License::get_site_domain() . '&id=' . $template_id;
            $response = wp_remote_get($url, [
                'timeout' => 20,
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);

            if (!is_wp_error($response) && isset($response['response']['code']) && $response['response']['code'] == 200 && !empty($response['body'])) {
                try {
                    $customizer_data = json_decode($response['body'], true);
                } catch (\Exception $e) {
                    Import_Log::add('Customizer settings error:' . $e->getMessage());
                    wp_send_json_error($e->getMessage());
                }
            } else {
                Import_Log::add('Customizer settings error - url: ' . $url);
                wp_send_json_error();
            }

            if (!empty($customizer_data)) {
                Customizer_Import::import($customizer_data);
                $this->import_site_options($template_id);
            } else {
                Import_Log::add('Customizer data is empty');
                wp_send_json_error(_x('Customizer data is empty!', 'import message', 'hqtheme-extra'));
            }
        } else {
            Import_Log::add('Invalid template: ' . $template_id);
            wp_send_json_error(_x('Invalid template!', 'import message', 'hqtheme-extra'));
        }
    }

    protected function import_site_options($template_id) {

        $url = \HQLib\HQLib::get_templates_api_url() . '/get-site-options.php?key=' . \HQLib\License::get_user_license() . '&domain=' . \HQLib\License::get_site_domain() . '&id=' . $template_id;

        $response = wp_remote_get($url, [
            'timeout' => 20,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);
        if (!is_wp_error($response) && isset($response['response']['code']) && $response['response']['code'] == 200 && !empty($response['body'])) {
            try {
                $options_data = json_decode($response['body'], true);
            } catch (\Exception $e) {
                Import_Log::add('Site options error: ' . $e->getMessage());
                wp_send_json_error($e->getMessage());
            }
        } else {
            Import_Log::add('Site options error - url: ' . $url);
            wp_send_json_error();
        }

        if (!empty($options_data)) {

            $id_mappings = $this->get_id_mappings();

            foreach ($options_data as $key => $option) {
                $value = maybe_unserialize($option['value']);

                $old = get_option($key);
                if ('standart' === $option['type']) {
                    Import_Log::add('Site options update:  KEY: ' . $key . ' VALUE: ' . json_encode($value));
                    update_option($key, $value);
                } elseif ('page' == $option['type'] || 'post' == $option['type']) {
                    if (isset($id_mappings[$value])) {
                        Import_Log::add('Site options update:  KEY: ' . $key . ' VALUE: ' . $value . ' NEW VALUE: ' . $id_mappings[$value]);
                        update_option($key, $id_mappings[$value]);
                    }
                }
            }
            wp_send_json_success();
        } else {
            wp_send_json_error(_x('Options data is empty!', 'import message', 'hqtheme-extra'));
        }
    }

    public function import_content() {

        check_ajax_referer('hqt-templates', '_ajax_nonce');

        if (!current_user_can('customize')) {
            wp_send_json_error(_x('You are not allowed to perform this action', 'import message', 'hqtheme-extra'));
        }

        $template_type = in_array($_REQUEST['template_type'], ['content', 'elementor-templates', 'templates', 'popup']) ? $_REQUEST['template_type'] : '';

        if ($template_type) {
            if ('content' === $template_type) {
                $this->import_xml_data($template_type);
            } elseif ('popup' === $template_type) {
                $this->import_xml_data($template_type);
            } elseif ('elementor-templates' === $template_type) {
                $this->import_xml_data($template_type);
            } elseif ('templates' === $template_type) {
                $this->import_elementor_template($template_type);
            }
        }
    }

    private function import_elementor_template($template_type) {
        $template_id = ( isset($_REQUEST['template_id']) ) ? sanitize_key($_REQUEST['template_id']) : 0;

        if ($template_id) {
            
        } else {
            wp_send_json_error(_x('Invalid template!', 'import message', 'hqtheme-extra'));
        }
    }

    /**
     * Import XML Data.
     */
    private function import_xml_data($template_type) {

        if (!class_exists('XMLReader')) {
            Import_Log::add('Error XMLReader missing');
            wp_send_json_error(_x('If XMLReader is not available, it imports all other settings and only skips XML import. This creates an incomplete website. We should bail early and not import anything if this is not present.', 'import message', 'hqtheme-extra'));
        }

        self::$template_type = $template_type;

        $template_id = ( isset($_REQUEST['template_id']) ) ? sanitize_key($_REQUEST['template_id']) : 0;

        if ($template_id) {

            // Download XML file.
            if ('popup' === $template_type) {
                $wxr_url = \HQLib\HQLib::get_templates_api_url() . '/get-template-data-xml.php?key=' . \HQLib\License::get_user_license() . '&domain=' . \HQLib\License::get_site_domain() . '&id=' . $template_id . '&template_type=' . $template_type;
            } else {
                $wxr_url = \HQLib\HQLib::get_templates_api_url() . '/get-site-data-xml.php?key=' . \HQLib\License::get_user_license() . '&domain=' . \HQLib\License::get_site_domain() . '&id=' . $template_id;
            }

            $xml_path = Helper::download_file($wxr_url);

            if ($xml_path['success']) {
                if (isset($xml_path['data']['file'])) {
                    $data = Wxr_Importer::instance()->get_xml_data($xml_path['data']['file']);
                    $data['xml'] = $xml_path['data'];

                    Import_Log::add('Import success ' . $template_type);
                    wp_send_json_success($data);
                } else {
                    Import_Log::add('Error downloading the XML file- URL: ' . $xml_path['data']['file']);
                    wp_send_json_error(_x('There was an error downloading the XML file.', 'import message', 'hqtheme-extra'));
                }
            } else {
                Import_Log::add('XML error - URL: ' . $wxr_url);
                wp_send_json_error($xml_path['data']);
            }
        } else {
            Import_Log::add('Invalid XML file - ' . $template_type);
            wp_send_json_error(_x('Invalid XML file!', 'import message', 'hqtheme-extra'));
        }
    }

    public function delete_imported_posts() {

        check_ajax_referer('hqt-templates', '_ajax_nonce');

        if (!current_user_can('customize')) {
            wp_send_json_error(_x('You are not allowed to perform this action', 'import message', 'hqtheme-extra'));
        }

        $post_id = isset($_REQUEST['id']) ? absint($_REQUEST['id']) : 0;

        $message = '';
        if ($post_id) {
            // Pass by Elementor kit delete confirmation
            $_GET['force_delete_kit'] = 1;
            $message = 'Deleted - Post ID ' . $post_id . ' - ' . get_post_type($post_id) . ' - ' . get_the_title($post_id);
            wp_delete_post($post_id, true);
        }

        Import_Log::add('Deleted post: ' . $post_id);

        wp_send_json_success($message);
    }

    public function delete_imported_terms() {

        check_ajax_referer('hqt-templates', '_ajax_nonce');

        if (!current_user_can('customize')) {
            wp_send_json_error(_x('You are not allowed to perform this action', 'import message', 'hqtheme-extra'));
        }

        $term_id = isset($_REQUEST['id']) ? absint($_REQUEST['id']) : 0;

        $message = '';
        if ($term_id) {
            $term = get_term($term_id);
            if ($term) {
                $message = 'Deleted - Term ' . $term_id . ' - ' . $term->name . ' ' . $term->taxonomy;
                wp_delete_term($term_id, $term->taxonomy);
            }
        }

        Import_Log::add('Deleted term: ' . $term_id);

        wp_send_json_success($message);
    }

}
