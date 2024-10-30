<?php

namespace HQExtra;

defined('ABSPATH') || exit;

class Filesystem {

    /**
     * Stores the initialized WordPress filesystem.
     * 
     * @since 1.0.2
     *
     * @access private
     * @var WP_Filesystem
     */
    private static $file_system = null;

    /**
     * Instance
     * 
     * @since 1.0.2
     * 
     * @var Filesystem 
     */
    private static $_instance = null;

    /**
     * 
     * @return Filesystem
     */
    public static function instance() {
        if (empty(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );

        // Start initiating filesystem.
        global $wp_filesystem;
        $url = wp_nonce_url(admin_url('tools.php?page=your_page'), 'hqtheme-extra');

        // Get filesystem credentials needed for WP_Filesystem.
        $creds = \request_filesystem_credentials($url, '', false, false, null);
        if (false === $creds) {
            return false;
        }

        // Get the upload directory.
        $wp_upload_dir = wp_upload_dir();

        // When credentials are obtained, check to make sure they work.
        if (!WP_Filesystem($creds, $wp_upload_dir['basedir'])) {
            \request_filesystem_credentials($url, '', true, false, null);
            return false;
        }

        global $wp_filesystem;

        self::$file_system = $wp_filesystem;
    }

    /**
     * 
     * @return WP_Filesystem
     */
    public function get_filesystem() {
        return self::$file_system;
    }

    public function get_contents($file) {
        $fs = self::get_filesystem();

        if (false !== $fs) {
            return $fs->get_contents($file);
        }

        return false;
    }

    public function put_contents($file, $contents, $mode = false) {
        $fs = self::get_filesystem();

        if (false !== $fs) {
            return $fs->put_contents($file, $contents, $mode);
        }

        return false;
    }

}
