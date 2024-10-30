<?php

namespace HQExtra\Demos;

defined('ABSPATH') || exit;

/**
 * Demos Cusromizer Import Class
 *
 * @since 1.0.0
 */
class Customizer_Import {

    /**
     * Instance
     * 
     * @since 1.0.0
     * 
     * @var Customizer_Import 
     */
    private static $_instance = null;

    /**
     * Get class instance
     *
     * @since 1.0.0
     *
     * @return Customizer_Import
     */
    public static function instance() {

        if (!isset(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    /**
     * Imports Customizer settings
     * 
     * @since 1.0.0
     * 
     * @global WP_Customize_Manager $wp_customize
     * @param array $data
     */
    static public function import($data) {
        // Load WP_Customize_Manager before start import
        self::_wp_customize_include();

        global $wp_customize;

        // Make sure WordPress upload support is loaded.
        if (!function_exists('wp_handle_upload')) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }

        // Get the upload data.
        // Import images.
        $data['mods'] = self::_import_images($data['mods']);

        // Import custom options.
        if (isset($data['options'])) {

            foreach ($data['options'] as $option_key => $option_value) {

                $option = new WP_Customize_Setting($wp_customize, $option_key, array(
                    'default' => '',
                    'type' => 'option',
                    'capability' => 'edit_theme_options'
                ));

                $option->update($option_value);
            }
        }

        // Call the customize_save action.
        do_action('customize_save', $wp_customize);

        // Loop through the mods.
        foreach ($data['mods'] as $key => $val) {

            // Call the customize_save_ dynamic action.
            do_action('customize_save_' . $key, $wp_customize);

            // Save the mod.
            set_theme_mod($key, $val);
        }

        // Call the customize_save_after action.
        do_action('customize_save_after', $wp_customize);
    }

    /**
     * Imports Images
     * 
     * @since 1.0.0
     * 
     * @param array $mods
     */
    static private function _import_images($mods) {
        foreach ($mods as $key => $val) {

            if (self::_is_image_url($val)) {

                $data = self::_sideload_image($val);

                if (!is_wp_error($data)) {

                    $mods[$key] = $data->url;

                    // Handle header image controls.
                    if (isset($mods[$key . '_data'])) {
                        $mods[$key . '_data'] = $data;
                        update_post_meta($data->attachment_id, '_wp_attachment_is_custom_header', get_stylesheet());
                    }
                }
            }
        }

        return $mods;
    }

    /**
     * Checks to see whether a string is an image url or not.
     *
     * @since 1.0.0
     * 
     * @access private
     * @param string $string The string to check.
     * @return bool Whether the string is an image url or not.
     */
    static private function _is_image_url($string = '') {
        if (is_string($string)) {

            if (preg_match('/\.(jpg|jpeg|png|gif)/i', $string)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Taken from the core media_sideload_image function and
     * modified to return an array of data instead of html.
     *
     * @since 1.0.0
     * @access private
     * @param string $file The image file path.
     * @return array An array of image data.
     */
    static private function _sideload_image($file) {
        $data = new stdClass();

        if (!function_exists('media_handle_sideload')) {
            require_once( ABSPATH . 'wp-admin/includes/media.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
        }

        if (!empty($file)) {

            // Set variables for storage, fix file filename for query strings.
            preg_match('/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches);
            $file_array = array();
            $file_array['name'] = basename($matches[0]);

            // Download file to temp location.
            $file_array['tmp_name'] = download_url($file);

            // If error storing temporarily, return the error.
            if (is_wp_error($file_array['tmp_name'])) {
                return $file_array['tmp_name'];
            }

            // Do the validation and storage stuff.
            $id = media_handle_sideload($file_array, 0);

            // If error storing permanently, unlink.
            if (is_wp_error($id)) {
                @unlink($file_array['tmp_name']);
                return $id;
            }

            // Build the object to return.
            $meta = wp_get_attachment_metadata($id);
            $data->attachment_id = $id;
            $data->url = wp_get_attachment_url($id);
            $data->thumbnail_url = wp_get_attachment_thumb_url($id);
            $data->height = $meta['height'];
            $data->width = $meta['width'];
        }

        return $data;
    }

    /**
     * Loads WP_Customize_Manager
     * 
     * @since 1.0.0
     */
    static function _wp_customize_include() {
        require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
        $GLOBALS['wp_customize'] = new \WP_Customize_Manager(compact('changeset_uuid', 'theme', 'messenger_channel', 'settings_previewed', 'autosaved', 'branching'));
    }

}
