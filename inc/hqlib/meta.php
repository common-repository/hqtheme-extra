<?php

namespace HQLib;

class Meta {

    private static $_instance = null;

    public static function instance() {

        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        add_action('admin_init', [$this, 'admin_init']);
    }

    public function admin_init() {
        // Post Meta
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_post_meta']);

        /**
         * Term Meta
         * Fields hook is added on add new container
         */
        add_action('created_term', [$this, 'save_taxonomy_custom_fields'], 10, 3);
        add_action('edited_term', [$this, 'save_taxonomy_custom_fields'], 10, 3);
    }

    public static function add_taxonomy_form_fields($taxonomy) {
        self::add_taxonomy_fields($taxonomy);
    }

    public static function edit_taxonomy_form_fields($tag, $taxonomy) {
        self::edit_taxonomy_fields($taxonomy);
    }

    private static function add_taxonomy_fields($taxonomy) {
        $containers = Meta\Term::get_by_taxonomy($taxonomy);

        if (!$containers) {
            return;
        }

        wp_nonce_field(\HQLib\HQLIB_PREFIX . 'options_nonce', 'hqt_options_nonce');
        foreach ($containers as $container) :
            $group = $container->is_grouped_options() ? $container->get_container_name() : '';
            ?>
            <div class="form-field <?php echo $container->get_container_name(true); ?>-wrap">
                <h2><?php echo $container->get_title(); ?></h2>
                <?php if (!empty($container->get_description())) : ?>
                    <p><?php echo $container->get_description(); ?></p>
                <?php endif; ?>
                <div class="hqt-container__fields mx-0">
                    <?php foreach ($container->get_fields() as $field) : ?>
                        <div <?php self::render_field_attributes($field); ?>>
                            <div class="hqt-field-inner">
                                <div class="hqt-field-body">
                                    <?php $field->render_label(); ?>
                                    <?php $value = $field->get_value('tax_meta', $group); ?>
                                    <?php $field->render_field($value); ?>
                                    <?php if (!empty($field->get_description())) : ?>
                                        <div class="hqt-description"><?php $field->render_description(); ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($field->get_content_after())) : ?>
                                    <div class="hqt-content-after"><?php $field->render_content_after(); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php
    }

    private static function edit_taxonomy_fields($taxonomy) {
        $containers = Meta\Term::get_by_taxonomy($taxonomy);

        if (!$containers) {
            return;
        }

        wp_nonce_field(\HQLib\HQLIB_PREFIX . 'options_nonce', 'hqt_options_nonce');
        foreach ($containers as $container) :
            $group = $container->is_grouped_options() ? $container->get_container_name() : '';
            ?>
            <tr class="form-field <?php echo $container->get_container_name(true); ?>-wrap">
                <th scope="row">
                    <label><?php echo $container->get_title(); ?></label>
                    <?php if (!empty($container->get_description())) : ?>
                        <p><?php echo $container->get_description(); ?></p>
                    <?php endif; ?>
                </th>
                <td>
                    <div class="hqt-container__fields mx-0">
                        <?php foreach ($container->get_fields() as $field) : ?>
                            <div <?php self::render_field_attributes($field); ?>>
                                <div class="hqt-field-inner">
                                    <div class="hqt-field-body">
                                        <?php $field->render_label(); ?>
                                        <?php $value = $field->get_value('tax_meta', $group); ?>
                                        <?php $field->render_field($value); ?>
                                        <?php if (!empty($field->get_description())) : ?>
                                            <div class="hqt-description"><?php $field->render_description(); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($field->get_content_after())) : ?>
                                        <div class="hqt-content-after"><?php $field->render_content_after(); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </td>
            </tr>
            <?php
        endforeach;
    }

    public function add_meta_box() {
        foreach (Meta\Post::$meta_post as $meta_box) {
            foreach ($meta_box->get_screens() as $screen) {
                add_meta_box(
                        $meta_box->get_container_name(),
                        $meta_box->get_title(),
                        [$this, 'render_meta_box'],
                        $screen,
                        $meta_box->get_context(),
                        $meta_box->get_priotity()
                );
            }
        }
    }

    public function render_meta_box($post, $callback) {
        $container = Meta\Post::$meta_post[$callback['id']];
        if (!empty($container)) {
            $group = $container->is_grouped_options() ? $container->get_container_name() : '';
            wp_nonce_field(\HQLib\HQLIB_PREFIX . 'options_nonce', 'hqt_options_nonce');
            if (!empty($container->get_description())) :
                ?>
                <p><?php echo $container->get_description(); ?></p>
            <?php endif; ?>
            <input type="hidden" name="hqt-metabox[<?php echo $callback['id']; ?>]" value="<?php echo esc_attr($callback['id']); ?>">
            <div class="hqt-container__fields m-0 mt-2">
                <?php foreach ($container->get_fields() as $field) : ?>
                    <?php $field->render_clearfix(['left', 'both']); ?>
                    <div <?php $this->render_field_attributes($field); ?>>
                        <div class="hqt-field-inner">
                            <div class="hqt-field-body">
                                <?php if ($field->is_label_inline()) : ?>
                                    <div class="hqt-label-inline-control">
                                    <?php endif; ?>
                                    <?php $field->render_label(); ?>
                                    <?php $value = $field->get_value('post_meta', $group); ?>
                                    <?php $field->render_field($value); ?>
                                    <?php if ($field->is_label_inline()) : ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($field->get_description())) : ?>
                                    <div class="hqt-description"><?php $field->render_description(); ?></div>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($field->get_content_after())) : ?>
                                <div class="hqt-content-after"><?php $field->render_content_after(); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php $field->render_clearfix(['right', 'both']); ?>
                <?php endforeach; ?>
            </div>
            <?php
        }
    }

    public static function render_field_attributes($field) {
        $default_value = false;
        if (!empty($field->get_default_value())) {
            if (is_array($field->get_default_value())) {
                $default_value = esc_attr(json_encode($field->get_default_value()));
            } else {
                $default_value = esc_attr($field->get_default_value());
            }
        }
        $attributes = [
            'class' => join(' ', array_filter(['hqt-field', 'hqt-field__' . $field->get_type(), $field->get_classes()])),
            'data-conditions' => !empty($field->get_conditions()) ? esc_attr(json_encode($field->get_conditions())) : false,
            'data-default-value' => $default_value,
            'hidden' => !empty($field->get_conditions()) ? true : false,
        ];
        echo join(' ', array_map(function($key) use ($attributes) {
                    if (is_bool($attributes[$key])) {
                        return $attributes[$key] ? $key : '';
                    }
                    return $key . '="' . $attributes[$key] . '"';
                }, array_keys($attributes)));
    }

    function save_post_meta($post_id) {

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['hqt_options_nonce']) && !wp_verify_nonce($_POST['hqt_options_nonce'], \HQLib\HQLIB_PREFIX . 'options_nonce')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (!isset($_POST['hqt-metabox'])) {
            return;
        }

        foreach ($_POST['hqt-metabox'] as $metabox) {
            $metabox = sanitize_key($metabox);
            if (isset(Meta\Post::$meta_post[$metabox])) {

                $container = Meta\Post::$meta_post[$metabox];

                $group = $container->is_grouped_options() ? $container->get_container_name() : '';

                if (empty($group)) {
                    foreach ($container->get_fields() as $field) {
                        if (!$field->is_storable()) {
                            continue;
                        }
                        if (isset($_POST[$field->get_field_name()])) {
                            if (is_array($_POST[$field->get_field_name()])) {
                                update_post_meta($post_id, $field->get_field_name(), filter_var_array($_POST[$field->get_field_name()], FILTER_SANITIZE_STRING));
                            } else {
                                update_post_meta($post_id, $field->get_field_name(), sanitize_text_field($_POST[$field->get_field_name()]));
                            }
                        }
                    }
                } else {
                    $data = [];
                    foreach ($container->get_fields() as $field) {
                        if (!$field->is_storable()) {
                            continue;
                        }
                        if (isset($_POST[$field->get_field_name()])) {
                            if (is_array($_POST[$field->get_field_name()])) {
                                $data[$field->get_field_name(true)] = filter_var_array($_POST[$field->get_field_name()], FILTER_SANITIZE_STRING);
                            } else {
                                $data[$field->get_field_name(true)] = sanitize_text_field($_POST[$field->get_field_name()]);
                            }
                        }
                    }
                    if (count($data)) {
                        update_post_meta($post_id, $group, $data);
                    }
                }
            }
        }
    }

    // TODO add validation

    public function save_taxonomy_custom_fields($term_id, $tt_id, $taxonomy) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['hqt_options_nonce']) && !wp_verify_nonce($_POST['hqt_options_nonce'], \HQLib\HQLIB_PREFIX . 'options_nonce')) {
            return;
        }

        if (!current_user_can('manage_categories')) {
            return;
        }

        $containers = Meta\Term::get_by_taxonomy($taxonomy);

        if (!$containers) {
            return;
        }
        foreach ($containers as $container) {
            $group = $container->is_grouped_options() ? $container->get_container_name() : '';
            if (empty($group)) {
                foreach ($container->get_fields() as $field) {
                    if (!$field->is_storable()) {
                        continue;
                    }
                    if (isset($_POST[$field->get_field_name()])) {
                        if (is_array($_POST[$field->get_field_name()])) {
                            update_term_meta($term_id, $field->get_field_name(), filter_var_array($_POST[$field->get_field_name()], FILTER_SANITIZE_STRING));
                        } else {
                            update_term_meta($term_id, $field->get_field_name(), sanitize_text_field($_POST[$field->get_field_name()]));
                        }
                    }
                }
            } else {
                $data = [];
                foreach ($container->get_fields() as $field) {
                    if (!$field->is_storable()) {
                        continue;
                    }
                    if (isset($_POST[$field->get_field_name()])) {
                        if (is_array($_POST[$field->get_field_name()])) {
                            $data[$field->get_field_name(true)] = filter_var_array($_POST[$field->get_field_name()], FILTER_SANITIZE_STRING);
                        } else {
                            $data[$field->get_field_name(true)] = sanitize_text_field($_POST[$field->get_field_name()]);
                        }
                    }
                }
                if (count($data)) {
                    update_term_meta($term_id, $group, $data);
                }
            }
        }
    }

}
