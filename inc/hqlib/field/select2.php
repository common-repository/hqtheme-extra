<?php

namespace HQLib\Field;

use HQLib\Field\Select;

class Select2 extends Select {

    public function render_field($value) {
        $this->populate_ajax_select2($value);
        if ($this->placeholder) {
            $this->set_attributes(['data-allow-clear' => 'true', 'data-placeholder' => $this->get_placeholder()]);
        }
        ?>
        <div class="hqt-control">
            <?php $this->render_input_addon('prepend'); ?>
            <input type="hidden" name="<?php echo esc_attr($this->get_field_name()); ?>" value="">
            <select id="<?php echo esc_attr($this->get_field_name()); ?>" 
                    name="<?php echo $this->get_field_name() . ($this->is_multiple() ? '[]' : ''); ?>" 
                    class="hqt-form-control __select2"
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
            echo '<option></option>';
        }
    }

    private function populate_ajax_select2($fieldValue) {
        // Check if select2 has remote source
        if ($this->get_attributes('data-ajax')) {
            if (!empty($fieldValue)) {
                // Get option and object types
                $optionsType = $this->get_attributes('data-options-type');
                $objectType = $this->get_attributes('data-object-type');
                if ($optionsType && $objectType) {
                    // Get results by option and object types
                    $results = \HQLib\Helper::get_search_results($optionsType, $objectType);

                    if ($this->is_multiple()) {
                        if (is_array($fieldValue)) {
                            foreach ($fieldValue as $value) {
                                if ($label = $this->get_results_value($value, $results)) {
                                    $this->add_option($value, $label);
                                }
                            }
                        }
                    } else {
                        if ($label = $this->get_results_value($fieldValue, $results)) {
                            $this->add_option($fieldValue, $label);
                        }
                    }
                }
            }
        }
    }

    private function get_results_value($needle, $results) {
        foreach ($results as $row) {
            if ($needle == $row['value']) {
                return $row['label'];
            }
        }
        return false;
    }

}
