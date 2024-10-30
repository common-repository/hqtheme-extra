<?php

namespace HQExtra\Demos;

defined('ABSPATH') || exit;

use const \HQExtra\PLUGIN_SLUG;
use const \HQExtra\PLUGIN_URL;
use const \HQExtra\VERSION;

/**
 * Demos Import Ui Class
 *
 * @since 1.0.0
 */
class Ui {

    /**
     * Instance
     * 
     * @since 1.0.0
     * 
     * @var Ui 
     */
    private static $_instance = null;

    /**
     * Template type for current import
     * 
     * @since 1.0.0
     * 
     * @var string
     */
    private static $template_type;

    /**
     * Get class instance
     *
     * @since 1.0.0
     *
     * @return Ui
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

        add_action('wp_ajax_hqt_search_templates', [$this, 'search_templates']);
        add_action('wp_ajax_hqt_template_details', [$this, 'template_details']);
    }

    /**
     * Renders Ready Sites Page
     * 
     * @since 1.0.0
     */
    public function ready_sites_page() {
        wp_enqueue_script(PLUGIN_SLUG . '-templates-details');
        wp_enqueue_script(PLUGIN_SLUG . '-templates-install');
        self::$template_type = 'sites';
        require_once dirname(__FILE__) . '/admin-page-templates/page.php';
    }

    /**
     * Renders Ready Templates Page
     * 
     * @since 1.0.0
     */
    public function ready_templates_page() {
        self::$template_type = 'templates';
        ?>
        <div class="wrap hqt-templates-import-screen"  data-template-type="templates">
            <h1><?php _ex('Templates', 'admin demos listing', 'hqtheme-extra'); ?></h1>
            <?php
            require_once dirname(__FILE__) . '/admin-page-templates/page.php';
            ?>
        </div>
        <?php
    }

    /**
     * Return filters by template type
     * 
     * @since 1.0.0
     * 
     * @return array
     */
    public static function get_filters() {
        if ('sites' === self::$template_type) {
            return [
                'all' => [
                    'title' => 'All',
                ],
                'free' => [
                    'title' => 'Free',
                ],
                'pro' => [
                    'title' => 'Pro',
                ],
                'blog' => [
                    'title' => 'Blog',
                ],
                'business' => [
                    'title' => 'Business',
                ],
                'ecommerce' => [
                    'title' => 'Ecommerce',
                ],
                'portfolio' => [
                    'title' => 'Portfolio',
                ],
            ];
        } elseif ('templates' === self::$template_type) {
            return [
                'all' => [
                    'title' => 'All',
                ],
                'f1' => [
                    'title' => 'f1',
                ],
            ];
        }
        return [];
    }

    /**
     * Gets templates for search template ajax
     * 
     * TODO cache result
     * 
     * @since 1.0.0
     */
    public function search_templates() {

        check_ajax_referer('hqt-templates', '_ajax_nonce');

        if (!current_user_can('customize')) {
            wp_send_json_error(_x('You are not allowed to perform this action', 'import message', 'hqtheme-extra'));
        }

        $type = in_array($_REQUEST['template_type'], ['sites', 'templates', 'popups']) ? $_REQUEST['template_type'] : 'sites';

        switch ($type) {
            case 'templates':
            case 'popups':
                $url = \HQLib\HQLib::get_templates_api_url() . '/get-available-templates.php?template_type=' . $type . '&key=' . \HQLib\License::get_user_license();
                break;
            default: // Sites
                $url = \HQLib\HQLib::get_templates_api_url() . '/get-available-sites.php?key=' . \HQLib\License::get_user_license();
                break;
        }

        if (false == $data = get_transient('ahqt_templates_library_' . $type)) {

            $response = wp_remote_get($url, [
                'timeout' => 20,
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);

            if (!is_wp_error($response) && isset($response['response']['code']) && $response['response']['code'] == 200 && !empty($response['body'])) {
                try {
                    $data = json_decode($response['body'], true);
                    set_transient('hqt_templates_library_' . $type, $data, DAY_IN_SECONDS);
                } catch (Exception $ex) {
                    wp_send_json_error();
                }
            } else {
                wp_send_json_error();
            }
        }

        // Filter results
        $params = isset($_REQUEST['params']) ? $_REQUEST['params'] : null;
        if ($params) {
            foreach ($data['results'] as $key => $result) {
                $unset = true;
                // Search string with priority
                if (!empty($params['search'])) {
                    $params['search'] = sanitize_text_field($params['search']);
                    if (false !== stripos($result['title'], $params['search'])) {
                        continue;
                    }
                    if (!empty($result['categories'])) {
                        foreach ($result['categories'] as $category) {
                            if (false !== stripos($category, $params['search'])) {
                                $unset = false;
                                break;
                            }
                        }
                    }
                } else {
                    // Category select
                    if (!empty($params['category'])) {
                        $params['category'] = sanitize_key($params['category']);
                        if (!empty($result['categories']) && in_array($params['category'], $result['categories'])) {
                            continue;
                        }
                    } else {
                        continue;
                    }
                }
                if ($unset) {
                    unset($data['results'][$key]);
                }
            }
        }

        // Order results by `priority` and `id` key
        usort($data['results'], function($a, $b) {
            // Order by higher `priority` DESC
            $retval = $b['priority'] <=> $a['priority'];
            if ($retval == 0) {
                // Order by higher `id` DESC in case of equal `order`
                $retval = $b['id'] <=> $a['id'];
            }
            return $retval;
        });

        wp_send_json_success($data);
    }

    /**
     * Gets template details
     * 
     * TODO add cache
     * 
     * @since 1.0.0
     */
    public function template_details() {
        if (isset($_GET['params'], $_GET['params']['template_id'])) {
            $id = sanitize_key($_GET['params']['template_id']);

            $url = \HQLib\HQLib::get_templates_api_url() . '/get-site-data.php?id=' . $id . '&key=' . \HQLib\License::get_user_license();
            $response = wp_remote_get($url, [
                'timeout' => 20,
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);
            if (!is_wp_error($response) && isset($response['response']['code']) && $response['response']['code'] == 200 && !empty($response['body'])) {
                try {
                    $data = json_decode($response['body']);
                } catch (Exception $e) {
                    wp_send_json_error();
                }
            } else {
                wp_send_json_error();
            }

            update_option('_hqt_import_last_importer_demo_details', $data);

            wp_send_json_success($data);
        }
        wp_send_json_error();
    }

}
