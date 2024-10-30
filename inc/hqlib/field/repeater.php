<?php

namespace HQLib\Field;

use HQLib\Field\Base;

class Repeater extends Base {

    /**
     *
     * @var \HQLib\Options\Repeater
     */
    protected $repeater;

    public function render_field($repeater_values) {
        ?>
        <div class="hqt-repeater-control">
            <?php
            if (is_array($repeater_values)) {
                $repeater_values = array_values($repeater_values);
            }
            $count = is_array($repeater_values) ? count($repeater_values) : 1;
            ?>
            <?php for ($i = 0; $i < $count; $i++) : ?> 
                <?php
                $container_classes = ['hqt-repeater__container'];
                if (0 < $i) {
                    $container_classes[] = 'collapsed';
                }
                ?>
                <div class="<?php echo join(' ', $container_classes) ?>" data-repeater-field="<?php echo $this->get_field_name(); ?>" data-repeater-item="<?php echo $i; ?>">
                    <div class="hqt-repeater__header">
                        <div class="hqt-repeater__id"><?php _ex('Item #', 'admin repeater', 'hqtheme-extra'); ?><span><?php echo $i + 1; ?></span></div>
                        <div class="hqt-repeater__actions">
                            <span class="hqt-repeater-action __new" data-text="New"><span class="dashicons dashicons-plus-alt"></span></span>
                            <span class="hqt-repeater-action __copy" data-text="Copy"><span class="dashicons dashicons-admin-page"></span></span>
                            <span class="hqt-repeater-action __remove" data-text="Remove"><span class="dashicons dashicons-no-alt"></span></span>
                            <span class="hqt-repeater-action __toggle" data-text="Collapse" data-text-collapsed="Expand"><span class="dashicons dashicons-arrow-up-alt2"></span></span></span>
                        </div>
                    </div>
                    <div class="hqt-repeater__fields">
                        <?php foreach ($this->repeater->get_fields() as $field) : ?>
                            <div <?php \HQLib\Options::render_field_attributes($field); ?>>
                                <div class="hqt-field-inner">
                                    <div class="hqt-field-body">
                                        <?php
                                        $value = isset($repeater_values[$i][$field->get_field_name(true)]) ? $repeater_values[$i][$field->get_field_name(true)] : $field->get_default_value();
                                        ob_start();
                                        $field->render_label();
                                        $field_label = ob_get_clean();

                                        ob_start();
                                        $field->render_field($value);
                                        $field_html = ob_get_clean();

                                        if (preg_match('/name="([^"]+)"/', $field_html, $m)) {
                                            $repeater_field_id = $this->get_field_name() . "__{$i}__" . \HQLib\Helper::remove_hqlib_prefix($m[1]);
                                            $repeater_field_name = $this->get_field_name() . "[{$i}][" . \HQLib\Helper::remove_hqlib_prefix($m[1]) . "]";

                                            if ('select2' == $field->get_type() && $field->is_multiple()) {
                                                $repeater_field_name .= '[]';
                                            }

                                            $field_label = preg_replace('/for="([^"]+)"/', 'for="' . $repeater_field_id . '"', $field_label);
                                            $field_html = preg_replace(['/id="([^"]+)"/', '/name="([^"]+)"/'], ['id="' . $repeater_field_id . '"', 'name="' . $repeater_field_name . '"'], $field_html);
                                        }

                                        echo $field_label, $field_html;
                                        ?>
                                    </div>
                                    <?php if (!empty($field->get_description())) : ?>
                                        <div class="hqt-description"><?php $field->render_description(); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
        <?php
    }

    public function render_label() {
        if (false == $this->disable_label) :
            $label_classes = 'hqt-label' . ($this->is_label_inline() ? ' ' . $this->get_label_inline() : '');
            ?>
            <div class="<?php echo $label_classes; ?>"><?php echo esc_html($this->label); ?></div>
            <?php
        endif;
    }

    /**
     * 
     * @param \HQLib\Options\Repeater $repeater
     * @return $this
     */
    public function set_repeater($repeater) {
        $this->repeater = $repeater;
        return $this;
    }

    /**
     * 
     * @return \HQLib\Options\Repeater
     */
    public function get_repeater() {
        return $this->repeater;
    }

}
