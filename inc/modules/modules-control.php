<?php

namespace HQExtra\Modules;

defined('ABSPATH') || exit;

use const HQExtra\PLUGIN_URL;
use const HQExtra\VERSION;

class Modules_Control {

    private static $_instance = null;
    public static $modules = [
        'popup' => [
            'name' => 'Popup for Elementor',
            'type' => 'pro',
            'description' => 'Create awesome popups for your website. Import stunning popups from our library with just one click!',
            'link' => 'https://marmot.hqwebs.net/hq-popup-for-elementor/?utm_source=wp-admin&utm_medium=link&utm_campaign=default&utm_term=marmot-enhancer-pro&utm_content=features-learn-more',
            'requires' => [
                'marmot-enhancer-pro' => [
                    'type' => 'plugin',
                    'plugin_file' => 'marmot-enhancer-pro/marmot-enhancer-pro.php',
                    'plugin_name' => 'marmot-enhancer-pro',
                    'label' => 'Marmot Enhancer Pro',
                    'link' => 'https://marmot.hqwebs.net/marmot-theme-pro/',
                ],
            ]
        ],
        'woocommerce-checkout' => [
            'name' => 'WooCommerce Checkout',
            'type' => 'pro',
            'description' => 'Customize your Woocommerce cart and checkout flow.',
            'link' => 'https://marmot.hqwebs.net/hq-woocommerce-checkout-customizations/?utm_source=wp-admin&utm_medium=link&utm_campaign=default&utm_term=marmot-enhancer-pro&utm_content=features-learn-more',
            'requires' => [
                'marmot-enhancer-pro' => [
                    'type' => 'plugin',
                    'plugin_file' => 'marmot-enhancer-pro/marmot-enhancer-pro.php',
                    'plugin_name' => 'marmot-enhancer-pro',
                    'label' => 'Marmot Enhancer Pro',
                    'link' => 'https://marmot.hqwebs.net/marmot-theme-pro/',
                ],
            ]
        ],
        'dismiss-widget' => [
            'name' => 'Dismiss Widget',
            'type' => 'pro',
            'description' => 'Create elements, which user can dismiss and hide from the content, useful for ad content, notifications, product upsells, etc.',
            //'link' => 'https://marmot.hqwebs.net/',
            'requires' => [
                'marmot-enhancer-pro' => [
                    'type' => 'plugin',
                    'plugin_file' => 'marmot-enhancer-pro/marmot-enhancer-pro.php',
                    'plugin_name' => 'marmot-enhancer-pro',
                    'label' => 'Marmot Enhancer Pro',
                    'link' => 'https://marmot.hqwebs.net/marmot-theme-pro/',
                ],
            ]
        ],
        'dynamic-tags' => [
            'name' => 'Dynamic Tags',
            'type' => 'pro',
            'description' => 'Display content from the current page or post, changing dynamically according to the post type it’s on, for example title, content, featured image and many more.',
            //'link' => 'https://marmot.hqwebs.net/',
            'requires' => [
                'marmot-enhancer-pro' => [
                    'type' => 'plugin',
                    'plugin_file' => 'marmot-enhancer-pro/marmot-enhancer-pro.php',
                    'plugin_name' => 'marmot-enhancer-pro',
                    'label' => 'Marmot Enhancer Pro',
                    'link' => 'https://marmot.hqwebs.net/marmot-theme-pro/',
                ],
            ]
        ],
        'dynamic-conditions' => [
            'name' => 'Dynamic Conditions',
            'type' => 'pro',
            'description' => 'Gives you flexibility to dynamically display or hide content based on your custom rules​.',
            //'link' => 'https://marmot.hqwebs.net/',
            'requires' => [
                'marmot-enhancer-pro' => [
                    'type' => 'plugin',
                    'plugin_file' => 'marmot-enhancer-pro/marmot-enhancer-pro.php',
                    'plugin_name' => 'marmot-enhancer-pro',
                    'label' => 'Marmot Enhancer Pro',
                    'link' => 'https://marmot.hqwebs.net/marmot-theme-pro/',
                ],
            ]
        ],
        'shortcodes' => [
            'name' => 'Shortcodes',
            'type' => 'pro',
            'description' => 'Shortcodes module description',
            //'link' => 'https://marmot.hqwebs.net/',
            'requires' => [
                'marmot-enhancer-pro' => [
                    'type' => 'plugin',
                    'plugin_file' => 'marmot-enhancer-pro/marmot-enhancer-pro.php',
                    'plugin_name' => 'marmot-enhancer-pro',
                    'label' => 'Marmot Enhancer Pro',
                    'link' => 'https://marmot.hqwebs.net/marmot-theme-pro/',
                ],
            ]
        ],
    ];

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function hqt_modules_control_get_all($modules) {
        return array_merge($modules, self::$modules);
    }

    public function load_active_modules() {
        $options = \HQLib\hq_get_option('theme_modules');
        foreach (self::$modules as $module_key => $module_config) {
            $class_name = '\\' . __NAMESPACE__ . '\\' . str_replace('-', '_', ucwords($module_key, '-')) . '\\Module';
            if ((empty($module_config['early_load']) || !$module_config['early_load']) && class_exists($class_name) && \HQLib\Helper::is_module_active($module_key, $options)) {
                $class_name::instance();
            }
        }
    }

    public function load_active_modules_assets() {
        foreach (self::$modules as $module_key => $module_config) {
            // Register module CSS
            if ($this->has_module_style($module_key)) {
                wp_register_style('hqt-extra-' . $module_key, PLUGIN_URL . 'assets/modules/' . $module_key . '/style.css', [], VERSION);
            }

            // Register module JavaScript
            if ($this->has_module_script($module_key)) {
                wp_register_script('hqt-extra-' . $module_key, PLUGIN_URL . 'assets/modules/' . $module_key . '/script.js', [
                    'jquery',
                    'elementor-frontend',
                        ], VERSION, true);
            }
        }
    }

    private function has_module_style($module_id) {
        $module_data = \HQLib\Helper::get_module_data($module_id);
        if (isset($module_data['has_style'])) {
            return $module_data['has_style'];
        } else {
            return false;
        }
    }

    private function has_module_script($module_id) {
        $module_data = \HQLib\Helper::get_module_data($module_id);
        if (isset($module_data['has_script'])) {
            return $module_data['has_script'];
        } else {
            return false;
        }
    }

}
