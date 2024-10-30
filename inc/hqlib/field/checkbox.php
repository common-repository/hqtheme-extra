<?php

namespace HQLib\Field;

use HQLib\Field\Base;

class Checkbox extends Base {

    /**
     *
     * @var boolean
     */
    protected $disable_label = true;

    /**
     *
     * @var boolean
     */
    //protected $allow_label_inline = false;

    /**
     *
     * @var string 
     */
    protected $option_value = 'on';

    /**
     *
     * @var string 
     */
    protected $option_unchecked_value = 'off';

    public function set_option_value($value) {
        if (!is_string($value)) {
            throw new \Exception('String expected');
        }
        $this->option_value = $value;

        return $this;
    }

    public function set_option_unchecked_value($value) {
        if (!is_string($value)) {
            throw new \Exception('String expected');
        }
        $this->option_unchecked_value = $value;

        return $this;
    }

    public function get_option_value() {
        return $this->option_value;
    }

    public function render_field($value) {
        $class = ($this->get_args('switch') ? 'hqt-label-switch' : 'hqt-label-checkbox') . ($this->is_label_inline() ? ' ' . $this->get_label_inline() : '');
        $checked = false;
        if ($value === $this->option_value && $this->option_unchecked_value !== $this->option_value) {
            $checked = true;
        }
        ?>
        <div class="hqt-control">
            <label class="<?php echo esc_attr($class); ?>">
                <input type="hidden" name="<?php echo esc_attr($this->get_field_name()); ?>" value="">
                <input type="checkbox"
                       id="<?php echo esc_attr($this->get_field_name()); ?>"
                       name="<?php echo $this->get_field_name(); ?>" 
                       value="<?php echo esc_attr($this->option_value); ?>"
                       <?php echo $checked ? 'checked="checked"' : ''; ?>
                       <?php $this->render_attributes(); ?>/>
                <span><?php echo esc_html($this->label); ?></span>
            </label>
        </div>

        <?php
    }

}
