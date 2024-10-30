<?php

namespace HQLib;

class Options {

    public static function display_container($container_key) {

        self::save_options();

        $hqlib_container_key = \HQLib\HQLIB_PREFIX . $container_key;

        if (!$container = Options\Container::get($hqlib_container_key)) {
            return;
        }
        do_action('hqt/container/' . $container_key . '/before');
        $containerClasses = array_merge(['hqt-container'], (array) apply_filters('hqt/container/' . $container_key . '/classes', []));
        ?>
        <div id="hqt-container-<?php echo $container->get_container_name(true); ?>" class="<?php echo implode(' ', $containerClasses); ?>">
            <?php do_action('hqt/container/' . $container_key . '/top'); ?>
            <?php if ($container->is_submitable()) : ?>
                <form class="hqt-form" method="post" data-ajax-submit="<?php echo $container->get_ajax_submit() ? 'true' : 'false'; ?>">
                    <?php
                    wp_nonce_field(\HQLib\HQLIB_PREFIX . 'options_nonce', 'hqt_options_nonce');
                    echo '<input type="hidden" name="hqt-container[' . $hqlib_container_key . ']" value="' . $hqlib_container_key . '">';
                    \HQLib\Options::render_container($container);
                    ?>
                    <div class="hqt-container-footer">
                        <div class="hqt-container-footer-buttons">
                            <?php \HQLib\Options::render_container_footer_buttons(apply_filters('hqt/container/' . $container_key . '/footer/buttons', $container->get_buttons())); ?>
                        </div>
                    </div>
                </form>
            <?php else : ?>
                <?php \HQLib\Options::render_container($container); ?>
            <?php endif; ?>
            <?php do_action('hqt/container/' . $container_key . '/bottom'); ?>
        </div>
        <?php
        do_action('hqt/container/' . $container_key . '/after');
    }

    private static function render_container_footer_buttons($buttons) {
        if (count($buttons)) {
            foreach ($buttons as $button) {
                if ('link' == $button['type']) {
                    ?>
                    <a href="<?php echo!empty($button['link']) ? $button['link'] : '#'; ?>"
                       class="button btn-hqt-container <?php echo $button['class']; ?>"
                       <?php echo isset($button['id']) ? 'id="' . $button['id'] . '"' : ''; ?>
                       <?php echo isset($button['target']) ? 'target="' . $button['target'] . '"' : ''; ?>>
                           <?php echo $button['label']; ?>
                    </a>
                    <?php
                } else {
                    ?>
                    <button type="<?php echo $button['type']; ?>"
                    <?php echo isset($button['id']) ? 'id="' . $button['id'] . '"' : ''; ?>
                            class="button btn-hqt-container <?php echo $button['class']; ?>"
                            name="<?php echo $button['name']; ?>">
                                <?php echo $button['label']; ?>
                    </button>
                    <?php
                }
            }
        }
    }

    private static function render_container($container) {
        ?>
        <div class="hqt-container-body">
            <?php $container->render_title(); ?>
            <?php do_action('hqt/container/' . $container->get_container_name(true) . '/before_fields'); ?>
            <div class="hqt-container__fields">
                <?php
                $group = $container->is_grouped_options() ? $container->get_container_name() : '';
                $storage = $container->get_storage();
                foreach ($container->get_fields() as $field) :
                    \HQLib\Options::render_field($field, $storage, $group);
                endforeach;
                ?>
            </div>
        </div>
        <?php
    }

    public static function display_tabs($tabs_key) {

        if (!$tabs = Options\Tabs::get($tabs_key)) {
            return;
        }
        ?>
        <div id="hqt-tabs-<?php echo $tabs->get_id(); ?>" class="hqt-tabs hqt-tabs-<?php echo $tabs->get_layout(); ?>">
            <ul class="hqt-tabs-nav">
                <?php
                $has_active = false;
                foreach ($tabs->get_tabs() as $tab_key => $tab) {
                    $classes = ['hqt-tabs-item'];
                    if (!$has_active) {
                        if (empty($_GET['tab']) || $tab_key === $_GET['tab']) {
                            $classes[] = 'active';
                            $has_active = true;
                        }
                    }
                    ?>
                    <li class="<?php echo join(' ', $classes); ?>">
                        <a href="<?php echo esc_url(add_query_arg('tab', $tab_key)); ?>" class="hqt-tabs-link"><?php echo $tab->get_label(); ?></a>
                    </li>
                    <?php
                }
                ?>
            </ul>
            <div class="hqt-tabs-content">
                <?php
                $has_active = false;
                foreach ($tabs->get_tabs() as $tab_key => $tab) {
                    $classes = ['hqt-tab'];
                    if (!$has_active) {
                        if (empty($_GET['tab']) || $tab_key === $_GET['tab']) {
                            $classes[] = 'active';
                            $has_active = true;
                        }
                    }
                    ?>
                    <div class="<?php echo join(' ', $classes); ?>" id="<?php echo $tab_key; ?>">
                        <?php \HQLib\Options::display_container($tab_key); ?>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php
    }

    private static function render_field($field, $storage, $group) {
        if ('fieldset' == $field->get_type()) {
            $field->render_fieldset();
        } else {
            ?>
            <div <?php self::render_field_attributes($field); ?>>
                <div class="hqt-field-inner">
                    <?php $field->render_badge(); ?>
                    <?php if (!empty($field->get_content_before())) : ?>
                        <div class="hqt-content-before"><?php $field->render_content_before(); ?></div>
                    <?php endif; ?>
                    <div class="hqt-field-body">
                        <?php if ($field->is_label_inline()) : ?>
                            <div class="hqt-label-inline-control">
                            <?php endif; ?>
                            <?php $field->render_label(); ?>
                            <?php $value = $field->get_value($storage, $group); ?>
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

    // TODO Add Validation

    public static function save_options() {

        if (empty($_POST) || empty($_POST['hqt-container'])) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!wp_verify_nonce($_POST['hqt_options_nonce'], \HQLib\HQLIB_PREFIX . 'options_nonce')) {
            return;
        }

        if (!current_user_can('edit_theme_options')) {
            return;
        }

        if (!isset($_POST['hqt-container'])) {
            return;
        }
        foreach ($_POST['hqt-container'] as $container_key) {
            $container_key = sanitize_key($container_key);

            if (!$container = Options\Container::get($container_key)) {
                wp_send_json_error(_x('Options container not found', 'save options', 'hqtheme-extra'));
            }
            $group = $container->is_grouped_options() ? $container->get_container_name() : '';

            if (empty($group)) {
                foreach ($container->get_fields() as $field) {
                    if (!$field->is_storable()) {
                        continue;
                    }
                    if (isset($_POST[$field->get_field_name()])) {
                        if (is_array($_POST[$field->get_field_name()])) {
                            $data = filter_var_array($_POST[$field->get_field_name()], FILTER_SANITIZE_STRING);
                        } else {
                            $data = sanitize_text_field($_POST[$field->get_field_name()]);
                        }
                        if ('options' === $container->get_storage()) {
                            update_option($field->get_field_name(), $data, $field->is_autoload());
                        } else if ('theme_mods' === $container->get_storage()) {
                            set_theme_mod($field->get_field_name(), $data);
                        }
                    }
                }
            } else {
                // Get saved group options if available
                $group_options = \HQLib\hq_get_option(\HQLib\Helper::remove_hqlib_prefix($group));
                $data = [];

                // Get submitted options
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

                // Merge submitted options into saved array
                $data = wp_parse_args($data, $group_options);
                if (count($data)) {
                    if ('options' === $container->get_storage()) {
                        update_option($group, $data, $container->is_autoload());
                    } else if ('theme_mods' === $container->get_storage()) {
                        set_theme_mod($group, $data);
                    }
                }
            }
        }
        if (!wp_doing_ajax()) {
            add_action('admin_print_footer_scripts', 'HQLib\Options::admin_print_scripts');
        } else {
            wp_send_json_success();
        }
    }

    /**
     * Refresh page after save (headers already sent)
     * 
     * @since 1.0.0
     */
    public static function admin_print_scripts() {
        ?>
        <script>
            // similar behavior as an HTTP redirect
            window.location.replace("<?php echo filter_input(INPUT_SERVER, 'REQUEST_URI') ?>");
        </script>

        <?php
    }

}
