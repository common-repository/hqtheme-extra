<?php

namespace HQLib\Field;

use HQLib\Field\Base;

class Select extends Base {

    protected $multiple = false;
    protected $options = [];
    protected $placeholder = '';

    public function render_field($value) {
        ?>
        <div class="hqt-control">
            <?php $this->render_input_addon('prepend'); ?>
            <select id="<?php echo esc_attr($this->get_field_name()); ?>" 
                    name="<?php echo $this->get_field_name(); ?>" 
                    class="hqt-form-control __select"
                    <?php echo $this->is_multiple() ? 'multiple' : ''; ?>
                    <?php $this->render_attributes(); ?>>
                        <?php $this->render_empty_option(); ?>
                        <?php
                        foreach ($this->options as $option_key => $option) {
                            echo '<option ' . ($this->is_selected($option_key, $value) ? 'selected' : '') . ' value="' . esc_attr($option_key) . '">' . esc_html($option) . '</option>';
                        }
                        ?>
            </select>
            <?php $this->render_input_addon('append'); ?>
        </div>
        <?php
    }

    protected function render_empty_option() {
        if ($this->placeholder) {
            echo '<option>' . $this->placeholder . '</option>';
        }
    }

    public function set_options($options) {
        if (!is_array($options)) {
            throw new \Exception('Array expected');
        }
        foreach ($options as $key => $value) {
            $this->add_option($key, $value);
        }

        return $this;
    }

    public function add_option($key, $value) {
        $this->options[$key] = $value;
        return $this;
    }

    public function get_options() {
        return $this->options;
    }

    public function set_placeholder($placeholder) {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function get_placeholder() {
        return $this->placeholder;
    }

    protected function is_selected($option_key, $value) {
        // For new post
        if (empty($value)) {
            return false;
        }

        if ($this->is_multiple()) {
            return in_array($option_key, $value);
        } else {
            return $value == $option_key;
        }
    }

    public function set_multiple($multiple = true) {
        $this->multiple = $multiple;
        return $this;
    }

    public function is_multiple() {
        return $this->multiple;
    }

}
