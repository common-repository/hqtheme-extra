<?php

namespace HQLib;

class Import {

    /**
     * 
     * @param type $template_type
     * @param type $template_id
     * @return \WP_Error|array An array of items on success, 'WP_Error' on failure.
     */
    public static function import_elementor_template($template_type, $template_id) {
        $url = \HQLib\HQLib::get_templates_api_url() . '/get-template-elementor-json.php?key=' . \HQLib\License::get_user_license() . '&domain=' . \HQLib\License::get_site_domain() . '&template_type=' . $template_type . '&id=' . $template_id;

        $response = wp_remote_get($url, [
            'timeout' => 10
        ]);
        $body = wp_remote_retrieve_body($response);

        return \Elementor\Plugin::instance()->templates_manager->import_template([
                    'fileData' => base64_encode($body),
                    'fileName' => 'test.json',
                        ]
        );
    }

}
