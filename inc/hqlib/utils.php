<?php

namespace HQLib;

defined('ABSPATH') || exit;

use Elementor\Plugin;

/**
 * Utils Class
 *
 * @since 1.0.0
 */
class Utils {

    const SAVED_TEMPLATES_CACHE_KEY = 'hqelementortemplates';
    const BUILDER_CONTENT_FOR_DISPLAY_CACHE_KEY = 'hqelementorcontentfordisplay';

    /**
     * Cache some data
     * 
     * @since 1.0.0
     * 
     * @var array
     */
    public static $data = [];

    /**
     * Get post types
     * 
     * @since 1.0.0
     * 
     * @param array $args
     * @return array
     */
    public static function get_post_types($args = []) {
        $post_type_args = [
            // Default is the value $public.
            'show_in_nav_menus' => true,
        ];

        $post_type_args = wp_parse_args($post_type_args, $args);

        $_post_types = get_post_types($post_type_args, 'objects');

        $post_types = [];

        foreach ($_post_types as $post_type => $object) {
            $post_types[$post_type] = $object->label;
        }

        return apply_filters('hqt/utils/get_public_post_types', $post_types);
    }

    /**
     * Get posts
     * 
     * @since 1.0.0
     * 
     * @param array $args
     * @return array
     */
    public static function get_posts($args = []) {
        $_args = [
            'post_type' => !empty($args['post_type']) ? $args['post_type'] : 'post',
            'orderby' => 'ID',
            'post_status' => 'publish',
            'order' => 'DESC',
            'posts_per_page' => -1 // this will retrive all the post that is published 
        ];

        $posts_args = wp_parse_args($_args, $args);

        $_posts = get_posts($posts_args);

        $posts = [];

        foreach ($_posts as $post) {
            $posts[$post->ID] = $post->post_title;
        }

        return apply_filters('hqt/utils/get_public_posts', $posts);
    }

    /**
     * Returns Elementor templates by type
     * 
     * @since 1.0.0
     * 
     * @param string $type
     * @param bool $add_default_option
     * @param bool $add_allow_empty_option
     * @return array
     */
    public static function get_elementor_templates($type, $add_default_option = false, $add_allow_empty_option = false) {
        $cache_key = self::SAVED_TEMPLATES_CACHE_KEY . '_' . $type . '_' . $add_default_option;

        if (isset(self::$data[$cache_key])) {
            return self::$data[$cache_key];
        }

        // Load templates
        // TODO Load all template types at first call
        $args = [
            'post_type' => 'elementor_library',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_elementor_template_type',
                    'value' => $type,
                    'compare' => '==',
                    'type' => 'post',
                ],
            ],
        ];

        $templatesRaw = new \WP_Query(
                $args
        );

        $templates = [];

        if ($add_allow_empty_option) {
            $templates[''] = __('', 'marmot');
        }

        if ($add_default_option) {
            $templates['default'] = __('Default', 'marmot');
        }

        $templates['noeltmp'] = __('No Template', 'marmot');

        if ($templatesRaw->have_posts()) {
            $original_id = get_the_ID();
            while ($templatesRaw->have_posts()) {
                $templatesRaw->the_post();
                $templates[get_the_ID()] = get_the_title();
            }
            // Restore to current post
            $GLOBALS['post'] = get_post($original_id);
            setup_postdata($GLOBALS['post']);
        }
        self::$data[$cache_key] = $templates;

        return $templates;
    }

    /**
     * Render Elementor template
     * 
     * @since 1.0.0
     * 
     * @param string $template_id
     */
    public static function load_elementor_template($template_id) {
        if ($template_id != 'noeltmp') {
            if (empty($template_id)) {
                return;
            }

            /**
             * Filter suitable for translation templates
             */
            $template_id = apply_filters('hqt/elementor/display/template/id', $template_id);

            $elementor_instance = \Elementor\Plugin::instance();
            $html = $elementor_instance->frontend->get_builder_content_for_display($template_id);
            if (Plugin::instance()->editor->is_edit_mode()) {
                // Remove class - remove animations
                $html = str_replace(['elementor-invisible'], '', $html);
            }

            echo $html;
        }
    }

    /**
     * Render Elementor template with help if template is not selected
     * 
     * @since 1.0.12
     * 
     * @param string $template_id
     * @param string $control_possition
     */
    public static function load_elementor_template_with_help($template_id, $control_possition = '') {
        if ($template_id != 'noeltmp') {
            self::load_elementor_template($template_id);
        } else {
            ?>
            <div class="elementor-alert elementor-alert-info" role="alert">
                <span class="elementor-alert-description">
                    <?php echo esc_html(_x('Please select a template.', 'widget template help', 'hqtheme-extra') . ' ' . (empty($control_possition) ? '' : _x('Go to: ', 'widget template help', 'hqtheme-extra') . _x($control_possition, 'widget template help', 'hqtheme-extra'))); ?>
                </span>
            </div>
            <?php
        }
    }

    /**
     * 
     * @since 1.0.0
     * 
     * @global type $wp_taxonomies
     * @param type $args
     * @param type $output
     * @param type $operator
     * @return type
     */
    public static function get_taxonomies($args = [], $output = 'names', $operator = 'and') {
        global $wp_taxonomies;

        $field = ( 'names' === $output ) ? 'name' : false;

        if (isset($args['object_type'])) {
            $object_type = (array) $args['object_type'];
            unset($args['object_type']);
        }

        $taxonomies = wp_filter_object_list($wp_taxonomies, $args, $operator);

        if (isset($object_type)) {
            foreach ($taxonomies as $tax => $tax_data) {
                if (!array_intersect($object_type, $tax_data->object_type)) {
                    unset($taxonomies[$tax]);
                }
            }
        }

        if ($field) {
            $taxonomies = wp_list_pluck($taxonomies, $field);
        }

        return $taxonomies;
    }

    public static function get_nav_menus() {
        $menus = wp_get_nav_menus();
        $items = ['' => esc_html(_x('Select Menu', 'admin', 'hqtheme-extra'))];
        foreach ($menus as $menu) {
            $items[$menu->slug] = $menu->name;
        }

        return $items;
    }

    public static function editor_switch_to_post($post_id, $post_type = 'post') {
        if (Plugin::instance()->editor->is_edit_mode()) {
            if (!$post_id) {
                ?>
                <div class="elementor-alert elementor-alert-info" role="alert">
                    <span class="elementor-alert-title">
                        <?php echo esc_html_x('Please select Test Item', 'admin', 'hqtheme-extra'); ?>
                    </span>
                    <span class="elementor-alert-description">
                        <?php echo esc_html_x('Test Item is used only in edit mode for better customization. On live page it will be ignored.' . 'admin', 'hqtheme-extra'); ?>
                    </span>
                </div>
                <?php
                return;
            }

            Plugin::instance()->db->switch_to_post($post_id);
        }
    }

    public static function editor_restore_to_current_post() {
        if (Plugin::instance()->editor->is_edit_mode()) {
            Plugin::instance()->db->restore_current_post();
        }
    }

    public static function editor_start_woocommerce_section() {
        if (Plugin::instance()->editor->is_edit_mode()) {
            echo '<div class="woocommerce">';
        }
    }

    public static function editor_end_woocommerce_section() {
        if (Plugin::instance()->editor->is_edit_mode()) {
            echo '</div>';
        }
    }

    public static function editor_alert_box($alert) {
        if (Plugin::instance()->editor->is_edit_mode()) {
            ?>
            <div class="elementor-alert elementor-alert-info" role="alert">
                <span class="elementor-alert-description">
                    <?php echo esc_html_x($alert, 'admin', 'hqtheme-extra'); ?>
                </span>
            </div>
            <?php
        }
    }

    /**
     * Generate Elementor template description text
     * 
     * @since 1.0.2
     * 
     * @param string $type
     * @return string
     */
    public static function get_elementor_tempalates_howto($type = '') {
        /* translators: %1$s is replaced with "one <a> tag" %2$s is replaced with "close </a> tag" */
        return sprintf(_x('Before choosing template, you have to create it %1$shere%2$s. (New templates will appear after refresh.)',
                        'settings',
                        'hqtheme-extra'),
                '<a target="_blank" href="' . esc_url(admin_url('/edit.php?post_type=elementor_library&tabs_group=library&elementor_library_type=' . $type)) . '">',
                '</a>'
        );
    }

    /**
     * Generate menus description text
     * 
     * @since 1.0.15
     * 
     * @return string
     */
    public static function get_menu_howto() {
        /* translators: %1$s is replaced with "one <a> tag" %2$s is replaced with "close </a> tag" */
        return sprintf(_x('Before choosing menu, you have to create it %1$shere%2$s. (New menus will appear after refresh.)',
                        'settings',
                        'hqtheme-extra'),
                '<a target="_blank" href="' . esc_url(admin_url('nav-menus.php')) . '">',
                '</a>'
        );
    }

    /**
     * Generate Contact From 7 description text
     * 
     * @since 1.0.15
     * 
     * @return string
     */
    public static function get_cf7_howto() {
        /* translators: %1$s is replaced with "one <a> tag" %2$s is replaced with "close </a> tag" */
        return sprintf(_x('Before choosing form, you have to create it %1$shere%2$s. (New forms will appear after refresh.)',
                        'settings',
                        'hqtheme-extra'),
                '<a target="_blank" href="' . esc_url(admin_url('admin.php?page=wpcf7')) . '">',
                '</a>'
        );
    }

}
