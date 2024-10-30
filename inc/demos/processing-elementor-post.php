<?php

namespace HQExtra\Demos;

defined('ABSPATH') || exit;

use Elementor\Plugin;

class Processing_Elementor_Post extends \Elementor\TemplateLibrary\Source_Local {

    /**
     * Update post meta.
     *
     * @since 1.0.14
     * @param  integer $post_id Post ID.
     * @return void
     */
    public function import_single_post($post_id = 0, $id_mappings, &$matched_ids) {

        $params_sufixes = [
            // Our plus some of BD widgets
            '_template',
            // BD widgets
            'template_id',
            'anywhere_id',
            'template_id_a',
            'anywhere_id_a',
            'template_id_b',
            'anywhere_id_b',
            // Contact Form 7
            'contact_form_id',
            // Clever Menu items
            'nav_menu',
        ];


        if (!empty($post_id)) {
            $data = get_post_meta($post_id, '_elementor_data', true);

            if (!empty($data)) {

                if (!is_array($data)) {
                    $data = json_decode($data, true);
                }

                $document = Plugin::$instance->documents->get($post_id);
                if ($document) {
                    $data = $document->get_elements_raw_data($data, true);
                }

                // Import the data.
                $data = $this->process_export_import_content($data, 'on_import');

                // Match Ids
                $data = $this->update_data($data, $id_mappings, $params_sufixes, $matched_ids);

                // Update processed meta.
                update_metadata('post', $post_id, '_elementor_data', wp_slash(wp_json_encode($data)));

                // Clear the cache after import.
                Plugin::$instance->files_manager->clear_cache();
            }
        }
    }

    protected function update_data($data, $id_mappings, $match_keys, &$matched_ids) {
        if (!is_array($data)) {
            return $data;
        }
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->update_data($data[$key], $id_mappings, $match_keys, $matched_ids);
            } else {
                foreach ($match_keys as $match_key) {
                    if (false !== strpos($key, $match_key)) {
                        if (!empty($id_mappings[$value])) {
                            $data[$key] = $id_mappings[$value];
                            $matched_ids[] = [
                                'key' => $key,
                                'old' => $value,
                                'new' => $id_mappings[$value],
                            ];
                        }
                        break;
                    }
                }
            }
        }
        return $data;
    }

}
