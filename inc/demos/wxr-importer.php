<?php

namespace HQExtra\Demos;

defined('ABSPATH') || exit;

use const HQExtra\PLUGIN_PATH;

/**
 * Wxr_Importer Class
 *
 * Run plugin
 *
 * @since 1.0.0
 */
class Wxr_Importer {

    /**
     * Instance
     * 
     * @since 1.0.0
     * 
     * @var Wxr_Importer 
     */
    private static $_instance = null;

    /**
     * Get class instance
     *
     * @since 1.0.0
     *
     * @return Wxr_Importer
     */
    public static function instance() {
        if (!isset(self::$_instance)) {
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

        require_once ABSPATH . '/wp-admin/includes/class-wp-importer.php';
        require_once PLUGIN_PATH . '/inc/demos/importers/class-logger.php';
        require_once PLUGIN_PATH . '/inc/demos/importers/class-wp-importer-logger-serversentevents.php';
        require_once PLUGIN_PATH . '/inc/demos/importers/class-wxr-importer.php';
        require_once PLUGIN_PATH . '/inc/demos/importers/class-wxr-import-info.php';

        add_filter('upload_mimes', [$this, 'custom_upload_mimes']);
        add_action('wp_ajax_hqtheme-wxr-import', [$this, 'import']);
        add_filter('wxr_importer.pre_process.user', '__return_null');
        add_filter('wxr_importer.pre_process.post', [$this, 'gutenberg_content_fix'], 10, 4);


        // Import Content or import Templates
        add_filter('wxr_importer.pre_process.post', [$this, 'pre_process_post'], 10, 4);
        add_filter('wxr_importer.pre_process.term', [$this, 'pre_process_term'], 10, 2);

        if (version_compare(get_bloginfo('version'), '5.1.0', '>=')) {
            add_filter('wp_check_filetype_and_ext', [$this, 'real_mime_types_5_1_0'], 10, 5);
        } else {
            add_filter('wp_check_filetype_and_ext', [$this, 'real_mime_types'], 10, 4);
        }
    }

    /**
     * Control post import
     * 
     * TODO import attachments only by type. Now we are importing all files
     * 
     * @since 1.0.0
     * 
     * @param array $data
     * @param $meta
     * @param $comments
     * @param $terms
     * @return boolean or array
     */
    public function pre_process_post($data, $meta, $comments, $terms) {
        $template_type = self::get_template_type();

        if (!$template_type || 'revision' === $data['post_type']) {
            return false;
        }

        return $data;


        // TODO - remove below
        if ($data['post_type'] == 'attachment') {
            return $data;
        }

        if ('elementor-templates' == $template_type && $data['post_type'] == 'elementor_library') {
            return $data;
        }
        if ('elementor-templates' != $template_type && $data['post_type'] != 'elementor_library') {
            return $data;
        }
        return false;
    }

    /**
     * Control terms import
     * 
     * @since 1.0.0
     * 
     * @param array $data
     * @param $meta
     * @return boolean / array
     */
    public function pre_process_term($data, $meta) {
        $template_type = self::get_template_type();

        // Do not import without post_type
        if (!$template_type) {
            return false;
        }

        // In popup import - import everything
        if ('popup' === $template_type) {
            return $data;
        }

        return $data;

        // TODO - remove below
        // Import only elementor templates
        if ('elementor-templates' == $template_type && $data['taxonomy'] == 'elementor_library_type') {
            return $data;
        }

        // Import everything but elementor templates
        if ('elementor-templates' != $template_type && $data['taxonomy'] != 'elementor_library_type') {
            return $data;
        }
        return false;
    }

    /**
     * Extract template_type from request
     * 
     * Possible values '', 'content', 'elementor-templates', 'templates', 'popup'
     * 
     * @since 1.0.0
     * 
     * @return string
     */
    public static function get_template_type() {
        $template_type = in_array($_REQUEST['template_type'], ['content', 'elementor-templates', 'templates', 'popup']) ? $_REQUEST['template_type'] : '';
        return $template_type;
    }

    /**
     * Mark post as imported
     * 
     * Adds _hqt_import_post meta to post
     * 
     * @since 1.0.0
     * 
     * @param int $post_id
     */
    public function mark_post_as_imported($post_id, $data) {
        update_post_meta($post_id, '_hqt_import_post', true);
        // Save some data for later processing
        $this->add_latest_imports('post-' . $data['post_type'], [
            'old' => $data['post_id'],
            'new' => $post_id,
        ]);
    }

    /**
     * Save some data for 6 hour for later use in import like fixing templates
     * 
     * @since 1.0.0
     * 
     * @param string $type
     * @param any $value
     */
    protected function add_latest_imports($type, $value) {
        // Get old data
        $latest_imports = get_transient('hqt_latest_imports');

        // Create array if not exists
        if (empty($latest_imports)) {
            $latest_imports = [];
        }
        // Create sub array if not exists
        if (empty($latest_imports[$type])) {
            $latest_imports[$type] = [];
        }

        // Add new one
        $latest_imports[$type][] = $value;

        // Save
        set_transient('hqt_latest_imports', $latest_imports, 6 * HOUR_IN_SECONDS);
    }

    /**
     * Mark term as imported
     * 
     * Adds _hqt_import_post meta to term
     * 
     * @since 1.0.0
     * 
     * @param int $term_id
     */
    public function mark_term_as_imported($term_id) {
        update_term_meta($term_id, '_hqtheme_imported_term', true);
    }

    /**
     * Fix Gutenberg Content
     * 
     * @since 1.0.0
     * 
     * @param array $data
     * @param $meta
     * @param $comments
     * @param $terms
     * 
     * @return array
     */
    public function gutenberg_content_fix($data, $meta, $comments, $terms) {
        if (isset($data['post_content'])) {
            $data['post_content'] = wp_slash($data['post_content']);
        }
        return $data;
    }

    /**
     * Different MIME type of different PHP version
     *
     * Filters the "real" file type of the given file.
     * 
     * @since 1.0.0
     *
     * @param array  $defaults File data array containing 'ext', 'type', and
     *                                          'proper_filename' keys.
     * @param string $file                      Full path to the file.
     * @param string $filename                  The name of the file (may differ from $file due to
     *                                          $file being in a tmp directory).
     * @param array  $mimes                     Key is the file extension with value as the mime type.
     * @param string $real_mime                Real MIME type of the uploaded file.
     */
    function real_mime_types_5_1_0($defaults, $file, $filename, $mimes, $real_mime) {
        return $this->real_mimes($defaults, $filename);
    }

    /**
     * Different MIME type of different PHP version
     *
     * Filters the "real" file type of the given file.
     *
     * @since 1.0.0
     *
     * @param array  $defaults File data array containing 'ext', 'type', and
     *                                          'proper_filename' keys.
     * @param string $file                      Full path to the file.
     * @param string $filename                  The name of the file (may differ from $file due to
     *                                          $file being in a tmp directory).
     * @param array  $mimes                     Key is the file extension with value as the mime type.
     */
    function real_mime_types($defaults, $file, $filename, $mimes) {
        return $this->real_mimes($defaults, $filename);
    }

    /**
     * Real Mime Type
     *
     * @since 1.0.0
     *
     * @param array  $defaults File data array containing 'ext', 'type', and
     *                                          'proper_filename' keys.
     * @param string $filename                  The name of the file (may differ from $file due to
     *                                          $file being in a tmp directory).
     */
    function real_mimes($defaults, $filename) {

        // Set EXT and real MIME type only for the file name `wxr.xml`.
        if ('wxr.xml' === $filename) {
            $defaults['ext'] = 'xml';
            $defaults['type'] = 'text/xml';
        }

        // Set EXT and real MIME type only for the file name `wpforms.json`.
        if ('wpforms.json' === $filename) {
            $defaults['ext'] = 'json';
            $defaults['type'] = 'text/plain';
        }

        return $defaults;
    }

    /**
     * Import xml from url
     * 
     * Streams the output during the import
     * 
     * @since 1.0.0
     * 
     * @param string $xml_url
     */
    function import($xml_url = '') {
        check_ajax_referer('hqt-templates', '_ajax_nonce');
        header('Content-Type: text/event-stream, charset=UTF-8');

        // Turn off PHP output compression.
        $previous = error_reporting(error_reporting() ^ E_WARNING);
        ini_set('output_buffering', 'off');
        ini_set('zlib.output_compression', false);
        error_reporting($previous);

        if ($GLOBALS['is_nginx']) {
            // Setting this header instructs Nginx to disable fastcgi_buffering
            // and disable gzip for this request.
            header('X-Accel-Buffering: no');
            header('Content-Encoding: none');
        }

        // 2KB padding for IE
        echo ':' . str_repeat(' ', 2048) . "\n\n";

        $xml_url = isset($_REQUEST['xml_url']) ? urldecode($_REQUEST['xml_url']) : urldecode($xml_url);
        if (empty($xml_url)) {
            exit;
        }

        // Time to run the import!
        set_time_limit(0);

        // Ensure we're not buffered.
        wp_ob_end_flush_all();
        flush();

        // Are we allowed to create users?
        add_filter('wxr_importer.pre_process.user', '__return_null');

        // Prepare import
        add_action('wxr_importer.pre_process.post', [$this, 'pre_post_import'], 10, 4);
        // Keep track of our progress.
        add_action('wxr_importer.processed.post', [$this, 'imported_post'], 10, 2);
        add_action('wxr_importer.process_failed.post', [$this, 'imported_post'], 10, 2);
        add_action('wxr_importer.process_already_imported.post', [$this, 'already_imported_post'], 10, 2);
        add_action('wxr_importer.process_skipped.post', [$this, 'already_imported_post'], 10, 2);
        add_action('wxr_importer.processed.comment', [$this, 'imported_comment']);
        add_action('wxr_importer.process_already_imported.comment', [$this, 'imported_comment']);
        add_action('wxr_importer.processed.term', [$this, 'imported_term']);
        add_action('wxr_importer.process_failed.term', [$this, 'imported_term']);
        add_action('wxr_importer.process_already_imported.term', [$this, 'imported_term']);
        add_action('wxr_importer.processed.user', [$this, 'imported_user']);
        add_action('wxr_importer.process_failed.user', [$this, 'imported_user']);

        // Save original post id
        add_action('wp_import_insert_post', [$this, 'save_original_post_id'], 10, 4);

        // Mark imported data as imported
        add_action('wxr_importer.processed.post', [$this, 'mark_post_as_imported'], 10, 2);
        add_action('wxr_importer.processed.term', [$this, 'mark_term_as_imported']);

        flush();

        $importer = $this->get_importer();
        $response = $importer->import($xml_url);

        // Let the browser know we're done.
        $complete = [
            'action' => 'complete',
            'error' => false,
        ];
        if (is_wp_error($response)) {
            $complete['error'] = $response->get_error_message();
        }

        $this->emit_message($complete);
        die;
    }

    /**
     * Some fixes before post import
     * 
     * @since 1.0.0
     * 
     * @param type $data
     * @param type $meta
     * @param type $comments
     * @param type $terms
     * @return type
     */
    public function pre_post_import($data, $meta, $comments, $terms) {
        // Remove PODS hooks - fix for personal porfolio demo - parent_post wron setup
        if (class_exists('PodsMeta')) {
            $pods_meta = \PodsMeta::init();
            remove_filter('add_post_metadata', [$pods_meta, 'add_post_meta'], 10);
        }
        return $data;
    }

    /**
     * Save post original id for later id matching
     * 
     * @since 1.0.0
     * 
     * @param type $post_id
     * @param type $original_id
     * @param type $postdata
     * @param type $data
     */
    public function save_original_post_id($post_id, $original_id, $postdata, $data) {
        update_post_meta($post_id, '_hqt_import_original_id', $postdata['import_id']);
    }

    /**
     * Controls Upload Mimes
     * 
     * @since 1.0.0
     * 
     * @param type $mimes
     * @return array
     */
    public function custom_upload_mimes($mimes) {

        // Allow SVG files.
        $mimes['svg'] = 'image/svg+xml';
        $mimes['svgz'] = 'image/svg+xml';

        // Allow XML files.
        $mimes['xml'] = 'text/xml';

        // Allow JSON files.
        $mimes['json'] = 'application/json';

        return $mimes;
    }

    /**
     * Prepare xml data
     * 
     * @since 1.0.0
     * 
     * @param string $path
     * @return array
     */
    public function get_xml_data($path) {

        $args = [
            'action' => 'hqtheme-wxr-import',
            'id' => '1',
            'xml_url' => $path,
        ];
        $url = add_query_arg(urlencode_deep($args), admin_url('admin-ajax.php'));

        $data = $this->get_data($path);

        return [
            'count' => [
                'posts' => $data->post_count,
                'media' => $data->media_count,
                'users' => count($data->users),
                'comments' => $data->comment_count,
                'terms' => $data->term_count,
            ],
            'url' => $url,
            'strings' => [
                'complete' => _x('Import complete!', 'import message', 'hqtheme-extra'),
            ],
        ];
    }

    /**
     * Get data from importer
     * 
     * @since 1.0.0
     * 
     * @param string $url
     * @return array
     */
    function get_data($url) {
        $importer = $this->get_importer();
        $data = $importer->get_preliminary_information($url);
        if (is_wp_error($data)) {
            return $data;
        }
        return $data;
    }

    /**
     * Get importer object
     * 
     * @since 1.0.0
     * 
     * @return \WXR_Importer
     */
    public function get_importer() {

        $importer = new \WXR_Importer([
            'fetch_attachments' => true,
            'default_author' => get_current_user_id(),
        ]);

        $logger = new \WP_Importer_Logger_ServerSentEvents();

        $importer->set_logger($logger);
        return $importer;
    }

    /**
     * Streams data for imported post
     * 
     * @since 1.0.0
     * 
     * @param int $id
     * @param array $data
     */
    public function imported_post($id, $data) {
        $this->emit_message(
                [
                    'action' => 'updateDelta',
                    'type' => ( 'attachment' === $data['post_type'] ) ? 'media' : 'posts',
                    'delta' => 1,
                ]
        );
    }

    /**
     * Streams data for imported post
     * 
     * @since 1.0.0
     * 
     * @param array $data
     */
    public function already_imported_post($data) {
        $this->emit_message(
                [
                    'action' => 'updateDelta',
                    'type' => ( 'attachment' === $data['post_type'] ) ? 'media' : 'posts',
                    'delta' => 1,
                ]
        );
    }

    /**
     * Streams data for imported comment
     * 
     * @since 1.0.0
     */
    public function imported_comment() {
        $this->emit_message(
                [
                    'action' => 'updateDelta',
                    'type' => 'comments',
                    'delta' => 1,
                ]
        );
    }

    /**
     * Streams data for imported term
     * 
     * @since 1.0.0
     */
    public function imported_term() {
        $this->emit_message(
                [
                    'action' => 'updateDelta',
                    'type' => 'terms',
                    'delta' => 1,
                ]
        );
    }

    /**
     * Streams data for imported user
     * 
     * @since 1.0.0
     */
    public function imported_user() {
        $this->emit_message(
                [
                    'action' => 'updateDelta',
                    'type' => 'users',
                    'delta' => 1,
                ]
        );
    }

    /**
     * Streams message for import
     * 
     * @since 1.0.0
     */
    public function emit_message($data) {

        echo "event: message\n";
        echo 'data: ' . wp_json_encode($data) . "\n\n";
        echo ':' . str_repeat(' ', 2048) . "\n\n";

        flush();
    }

}
