<?php

namespace HQExtra\Demos;

defined('ABSPATH') || exit;

class Helper {

    /**
     * Download File Into Uploads Directory
     *
     * @param  string $file Download File URL.
     * @param  int    $timeout_seconds Timeout in downloading the XML file in seconds.
     * @return array        Downloaded file data.
     */
    public static function download_file($file = '', $timeout_seconds = 300) {

        // Gives us access to the download_url() and wp_handle_sideload() functions.
        require_once( ABSPATH . 'wp-admin/includes/file.php' );

        // Download file to temp dir.
        $temp_file = download_url($file, $timeout_seconds);

        // WP Error.
        if (is_wp_error($temp_file)) {
            return array(
                'success' => false,
                'data' => $temp_file->get_error_message(),
            );
        }

        // Array based on $_FILE as seen in PHP file uploads.
        $file_args = array(
            'name' => basename($file),
            'tmp_name' => $temp_file,
            'error' => 0,
            'size' => filesize($temp_file),
        );

        $overrides = array(
            // Tells WordPress to not look for the POST form
            // fields that would normally be present as
            // we downloaded the file from a remote server, so there
            // will be no form fields
            // Default is true.
            'test_form' => false,
            // Setting this to false lets WordPress allow empty files, not recommended.
            // Default is true.
            'test_size' => true,
            // A properly uploaded file will pass this test. There should be no reason to override this one.
            'test_upload' => true,
            'test_type' => false,
            'mimes' => array(
                'xml' => 'text/xml',
                'json' => 'text/plain',
            ),
        );

        // Move the temporary file into the uploads directory.
        $results = wp_handle_sideload($file_args, $overrides);

        if (isset($results['error'])) {
            return array(
                'success' => false,
                'data' => $results,
            );
        }

        // Success.
        return array(
            'success' => true,
            'data' => $results,
        );
    }

    /**
     * Downloads an image from the specified URL.
     *
     * Taken from the core media_sideload_image() function and
     * modified to return an array of data instead of html.
     *
     * @since 1.0.10
     *
     * @param string $file The image file path.
     * @return array An array of image data.
     */
    static public function _sideload_image($file) {
        $data = new stdClass();

        if (!function_exists('media_handle_sideload')) {
            require_once( ABSPATH . 'wp-admin/includes/media.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
        }

        if (!empty($file)) {

            // Set variables for storage, fix file filename for query strings.
            preg_match('/[^\?]+\.(jpe?g|jpe|svg|gif|png)\b/i', $file, $matches);
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
                unlink($file_array['tmp_name']);
                return $id;
            }

            // Build the object to return.
            $meta = wp_get_attachment_metadata($id);
            $data->attachment_id = $id;
            $data->url = wp_get_attachment_url($id);
            $data->thumbnail_url = wp_get_attachment_thumb_url($id);
            $data->height = isset($meta['height']) ? $meta['height'] : '';
            $data->width = isset($meta['width']) ? $meta['width'] : '';
        }

        return $data;
    }

}
