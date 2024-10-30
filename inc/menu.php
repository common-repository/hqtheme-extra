<?php

namespace HQExtra;

defined('ABSPATH') || exit;

/**
 * Loaded only if Free clever-mega-menu-for-elementor is active
 * 
 * @since 1.0.0
 */
class Menu {

    /**
     * Instance
     * 
     * @since 1.0.0
     * 
     * @var Menu 
     */
    private static $_instance;

    /**
     * Get class instance
     *
     * @since 1.0.0
     *
     * @return Menu
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
        add_action('init', [$this, 'registerPosttype'], 50, 0);

        add_action('admin_enqueue_scripts', [$this, 'load_admin_assets'], 10);
    }

    /**
     * Register post type
     * 
     * @since 1.0.0
     */
    public function registerPosttype() {
        $labels = [
            'name' => esc_html__('Menu Locations', 'hqtheme-extra'),
            'singular_name' => esc_html__('Menu Location', 'hqtheme-extra'),
            'all_items' => esc_html__('Menu Locations', 'hqtheme-extra'),
            'add_new' => esc_html__('Add New Menu Location', 'hqtheme-extra'),
            'add_new_item' => esc_html__('Add New Menu Location', 'hqtheme-extra'),
            'edit_item' => esc_html__('Edit Menu Location', 'hqtheme-extra'),
        ];

        $args = [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'cmm4e-dashboard-page',
            'supports' => ['title']
        ];

        $this->post_type = register_post_type('cmm4e_menu_location', $args);
    }

    /**
     * Fix Clever menu
     * 
     * @since 1.0.6
     */
    public function load_admin_assets() {
        // Fix Clever Menu in Customizer
        wp_localize_script('cmm4e-admin', 'cleverMenuItems', []);
    }

}
