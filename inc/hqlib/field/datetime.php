<?php

namespace HQLib\Field;

use HQLib\Field\Base;

class Datetime extends Base {

    /**
     * Datepicker options
     * @var array
     */
    protected $options = [];

    public function render_field($value) {
        ?>
        <div class="hqt-control">
            <?php $this->render_input_addon('prepend'); ?>
            <input 
                type="text" 
                id="<?php echo esc_attr($this->get_field_name()); ?>" 
                class="hqt-form-control __input __datetime datepicker-here"
                name="<?php echo $this->get_field_name(); ?>" 
                value="<?php echo esc_attr($value); ?>"
                <?php $this->render_attributes(); ?>/>
                <?php $this->render_input_addon('append'); ?>
        </div>
        <?php
    }

    public function set_options($options) {
        if (!is_array($options)) {
            throw new \Exception('Array expected');
        }
        if (!isset($options['language'])) {
            $options['language'] = 'en';
        }
        $this->options = $options;
        // Set options as data-* attributes
        foreach ($options as $key => $option) {
            $this->attributes['data-' . $key] = $option;
        }

        return $this;
    }

    public function get_options() {
        return $this->options;
    }

}
