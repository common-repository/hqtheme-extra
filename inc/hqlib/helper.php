<?php

namespace HQLib;

/**
 * Helper functions
 */
class Helper {

    private static $_instance = null;

    public static function instance() {

        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Remove HQLib prefix
     * @param string $str
     * @return string
     */
    public static function remove_hqlib_prefix($str) {
        return preg_replace('/^' . \HQLib\HQLIB_PREFIX . '/', '', $str);
    }

    /**
     * Recursive sorting function by array key.
     *
     * @param  array   &$array     The input array.
     * @param  int     $sort_flags Flags for controlling sorting behavior.
     * @return boolean
     */
    public static function ksort_recursive(&$array, $sort_flags = SORT_REGULAR) {
        if (!is_array($array)) {
            return false;
        }
        ksort($array, $sort_flags);
        foreach ($array as $key => $value) {
            self::ksort_recursive($array[$key], $sort_flags);
        }
        return true;
    }

    /**
     * Find the position of the first occurrence of a substring in an array
     * 
     * @param string $haystack The string to search in.
     * @param array $needles Array of string to search for.
     * @param int $offset
     * @return boolean Returns the position of where the needle exists relative to the beginning of the haystack string 
     */
    public static function strposa($haystack, $needles = array(), $offset = 0) {
        $chr = array();
        if (!is_array($needles)) {
            $needles = array($needles);
        }
        foreach ($needles as $needle) {
            $res = strpos($haystack, $needle, $offset);
            if ($res !== false)
                $chr[$needle] = $res;
        }
        if (empty($chr))
            return false;
        return min($chr);
    }

    /**
     * Get the relation type from an array similar to how meta_query works in WP_Query
     *
     * @param  array         $array
     * @param  array<string> $allowed_relations
     * @param  string        $relation_key
     * @return string
     */
    public static function get_relation_type_from_array($array, $allowed_relations = array('AND', 'OR'), $relation_key = 'relation') {
        $allowed_relations = array_values($allowed_relations);
        $allowed_relations = array_map('strtoupper', $allowed_relations);
        $relation = isset($allowed_relations[0]) ? $allowed_relations[0] : '';

        if (isset($array[$relation_key])) {
            $relation = strtoupper($array[$relation_key]);
        }

        if (!in_array($relation, $allowed_relations)) {
            throw new \Exception('Invalid relation type ' . $relation . '. ' .
                    'The rule should be one of the following: "' . implode('", "', $allowed_relations) . '"');
        }

        return $relation;
    }

    /**
     * Get valid input from an input array compared to predefined options
     *
     * @param  array $input
     * @param  array $options
     * @return array
     */
    public static function get_valid_options($input, $options) {
        // enforce comparison to be string so we do not get unexpected matches
        // for cases such as "string without any numbers" == 0
        // in array_search()
        $search_options = array_map('strval', $options);

        $valid_input = array();
        foreach ($input as $raw_value) {
            $index = array_search(strval($raw_value), $search_options, true);

            if ($index === false) {
                continue;
            }

            $valid_input[] = $options[$index];
        }
        return $valid_input;
    }

    /**
     * Get search results with AJAX
     */
    public static function ajax_search() {
        check_ajax_referer('hq-lib', '_ajax_nonce');

        $optionsType = isset($_POST['options_type']) ? sanitize_text_field($_POST['options_type']) : '';
        $objectType = isset($_POST['object_type']) ? sanitize_text_field($_POST['object_type']) : '';
        $search = isset($_POST['q']) ? sanitize_text_field($_POST['q']) : '';

        wp_send_json([
            'results' => self::get_search_results($optionsType, $objectType, $search)
        ]);
    }

    /**
     * Get search results
     * @param type $optionsType
     * @param type $objectType
     * @param type $search
     * @return type
     */
    public static function get_search_results($optionsType, $objectType, $search = '') {
        $result = [];

        switch ($optionsType) {
            case 'post':
                $result = self::search_posts($objectType, $search);
                break;
            case 'tax':
                $result = self::search_terms($objectType, $search);
                break;
            default:
                break;
        }

        return $result;
    }

    /**
     * Search for posts by post type
     * @param type $type
     * @param type $query
     * @return array
     */
    public static function search_posts($type, $query) {
        add_filter('posts_where', array(__CLASS__, 'force_search_by_title'), 10, 2);

        $posts = get_posts([
            'post_type' => $type,
            'ignore_sticky_posts' => true,
            'posts_per_page' => -1,
            'suppress_filters' => false,
            's_title' => $query, // Custom search
            'post_status' => ['publish', 'private'],
        ]);

        remove_filter('posts_where', array(__CLASS__, 'force_search_by_title'), 10, 2);

        $result = [];

        if (!empty($posts)) {
            foreach ($posts as $post) {
                $result[] = [
                    'value' => $post->ID,
                    'label' => $post->post_title,
                ];
            }
        }

        return $result;
    }

    /**
     * Force query to look in post title while searching
     * @global type $wpdb
     * @param type $where
     * @param type $query
     * @return type
     */
    public static function force_search_by_title($where, $query) {

        $args = $query->query;

        if (isset($args['s_title'])) {
            global $wpdb;

            $searh = esc_sql($wpdb->esc_like($args['s_title']));
            $where .= " AND {$wpdb->posts}.post_title LIKE '%$searh%'";
        }

        return $where;
    }

    /**
     * Search terms by taxonomy
     * @param type $tax
     * @param type $query
     * @return array
     */
    public static function search_terms($tax, $query) {

        $terms = get_terms([
            'taxonomy' => $tax,
            'hide_empty' => false,
            'name__like' => $query,
        ]);

        $result = [];

        if (!empty($terms)) {
            foreach ($terms as $term) {
                $result[] = [
                    'value' => $term->term_id,
                    'label' => $term->name,
                ];
            }
        }

        return $result;
    }

    /**
     * 
     * @param type $url
     * @return array|boolean
     */
    public static function get_json($url, $assoc = false) {

        $response = wp_remote_get($url, [
            'timeout' => 20,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);
        if (!is_wp_error($response) && isset($response['response']['code']) && $response['response']['code'] == 200 && !empty($response['body'])) {
            try {
                $data = json_decode($response['body'], $assoc);
            } catch (Exception $ex) {
                return false;
            }
        } else {
            return false;
        }
        return $data;
    }

    /**
     * Check field/option requirements
     * @param array $config
     * @param boolean $check_license
     * @return object
     */
    public static function field_requires_check($config, $check_license = null) {
        $response = new \stdClass();
        $response->success = true;
        $response->html = '';

        $license_check = false;
        if (!empty($config['type']) && 'pro' == $config['type']) {
            $license_check = true;
        }
        if (null !== $check_license) {
            $license_check = boolval($check_license);
        }
        if ($license_check && !\HQLib\License::is_activated()) {
            $response->success = false;
            ob_start();
            ?>
            <div class="pt-1 d-flex justify-content-justify">
                <div class="text-italic text-brighter"><?php _ex('Invalid license key', 'admin', 'hqtheme-extra'); ?></div>
            </div>
            <?php
            $response->html .= ob_get_clean();
        }

        if (!empty($config['requires'])) {
            foreach ($config['requires'] as $require) {
                if ('plugin' == $require['type']) {
                    if (!empty($require['plugin_file']) && !is_plugin_active($require['plugin_file'])) {
                        $install_url = '';
                        $activate_url = wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $require['plugin_file'] . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $require['plugin_file']);
                        if (is_plugin_installed($require['plugin_file'])) {
                            // Activate
                            $btn_label = _x('Activate', 'admin', 'hqtheme-extra');
                        } else {
                            // Install
                            $btn_label = _x('Install', 'admin', 'hqtheme-extra');
                            $install_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=' . $require['plugin_name']), 'install-plugin_' . $require['plugin_name']);
                        }
                        $response->success = false;
                        ob_start();
                        ?>
                        <div class="pt-1 d-flex justify-content-justify">
                            <div class="text-italic text-brighter"><?php echo sprintf($require['label'] . ' %s', _x('plugin is missing', 'admin', 'hqtheme-extra')); ?></div>
                            <div class="ml-2">
                                <a href="#"
                                   data-hqt-action-btn
                                   data-action="install-activate"
                                   data-install-url="<?php echo esc_attr($install_url); ?>" 
                                   data-activate-url="<?php echo esc_attr($activate_url); ?>"
                                   data-callback="refresh-page">
                                       <?php echo $btn_label; ?>
                                </a>
                            </div>
                        </div>
                        <?php
                        $response->html .= ob_get_clean();
                    }
                } elseif ('option' == $require['type']) {
                    if (is_array($require['option'])) {
                        list($group, $key) = array_pad($require['option'], 2, null);
                    } else {
                        $key = $require['option'];
                        $group = null;
                    }
                    if ('on' != \HQLib\hq_get_option($key, $group)) {
                        $response->success = false;
                        ob_start();
                        ?>
                        <div class="pt-1 d-flex justify-content-justify">
                            <div class="text-italic text-brighter"><?php echo sprintf($require['label'] . ' %s', _x('option is disabled', 'admin', 'hqtheme-extra')); ?></div>
                            <?php if (!empty($require['link'])) : ?>
                                <div class="ml-2">
                                    <a href="<?php echo esc_url(admin_url($require['link'])); ?>">Settings</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php
                        $response->html .= ob_get_clean();
                    }
                } elseif ('module' == $require['type']) {
                    if (is_array($require['option'])) {
                        list($group, $key) = array_pad($require['option'], 2, null);
                    } else {
                        $key = $require['option'];
                        $group = null;
                    }
                    $option = \HQLib\hq_get_option($group);
                    if (!self::is_module_active($require['module'], $option)) {
                        $response->success = false;
                        ob_start();
                        ?>
                        <div class="pt-1 d-flex justify-content-justify">
                            <div class="text-italic text-brighter"><?php echo sprintf($require['label'] . ' %s', _x('module is disabled', 'admin', 'hqtheme-extra')); ?></div>
                            <?php if (self::can_module_be_active($require['module'])) : ?>
                                <div class="ml-2">
                                    <a href="#"
                                       data-hqt-action-btn
                                       data-action="enable"
                                       data-option="<?php echo esc_attr(json_encode($require['option'])); ?>"
                                       data-callback="refresh-page">
                                           <?php _ex('Enable', 'admin', 'hqtheme-extra'); ?>
                                    </a>
                                </div>
                            <?php elseif (!empty($require['link'])) : ?>
                                <div class="ml-2">
                                    <a href="<?php echo esc_url(admin_url($require['link'])); ?>"><?php _ex('Settings', 'admin', 'hqtheme-extra'); ?></a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php
                        $response->html .= ob_get_clean();
                    }
                }
            }
        }
        return $response;
    }

    /**
     * Checks if widget is active
     * 
     * @param string $widget_id
     * @param array $options
     * @return boolean
     */
    public static function is_widget_active($widget_id, $options) {
        $widget_data = self::get_widget_data($widget_id);
        if (!\HQLib\Helper::field_requires_check($widget_data)->success) {
            return false;
        }
        if (!isset($options[$widget_id])) {
            return ('on' === $widget_data['default_activation'] ? true : false);
        } else {
            if ('on' === $options[$widget_id]) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Checks if module is active
     * 
     * @param string $module_id
     * @param array $options
     * @return boolean
     */
    public static function is_module_active($module_id, $options) {
        $module_data = self::get_module_data($module_id);

        if (!\HQLib\Helper::can_module_be_active($module_id, $module_data)) {
            return false;
        }

        if (!isset($options[$module_id])) {
            return ('on' === $module_data['default_activation'] ? true : false);
        } else {
            if ('on' === $options[$module_id]) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Check if theme module pass the requirements
     * @param string $module_id
     * @param array $module_data
     * @return boolean
     */
    public static function can_module_be_active($module_id, $module_data = null) {
        if (null === $module_data) {
            $module_data = self::get_module_data($module_id);
        }
        if ($module_data) {
            return \HQLib\Helper::field_requires_check($module_data, false)->success;
        }
        return false;
    }

    /**
     * Get widget config data
     * @param string $widget_id
     * @return array|boolean
     */
    public static function get_widget_data($widget_id) {
        foreach (self::get_all_widgets_data() as $group_key => $group_widgets) {
            if (isset($group_widgets[$widget_id])) {
                return $group_widgets[$widget_id];
            }
        }
        return false;
    }

    /**
     * Get module config data
     * @param string $module_id
     * @return array|boolean
     */
    public static function get_module_data($module_id) {
        foreach (self::get_all_modules_data() as $module_key => $module) {
            if ($module_key == $module_id) {
                return $module;
            }
        }
        return false;
    }

    /**
     * Get all widgets config data
     * @return array
     */
    public static function get_all_widgets_data() {
        $widgets = [
            'core_widgets' => [],
            'third_party_widgets' => [],
        ];
        return apply_filters('hqt/widgets_control/get_all', $widgets);
    }

    /**
     * Get all modules config data
     * @return array
     */
    public static function get_all_modules_data() {
        $modules = [];
        return apply_filters('hqt/modules_control/get_all', $modules);
    }

}
