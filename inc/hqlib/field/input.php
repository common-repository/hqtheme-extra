<?php

namespace HQLib\Field;

use HQLib\Field\Base;

class Input extends Base {

    /**
     *
     * @var string
     */
    protected $input_type = 'text';

    /**
     *
     * @var array
     */
    protected $allowed_input_types = [
        'text',
        'tel',
        'number',
        'password',
        'email',
        'url',
    ];

    public function render_field($value) {
        ?>
        <div class="hqt-control">
            <?php $this->render_input_addon('prepend'); ?>
            <input 
                type="<?php echo $this->input_type; ?>" 
                id="<?php echo esc_attr($this->get_field_name()); ?>" 
                class="hqt-form-control __input"
                name="<?php echo $this->get_field_name(); ?>" 
                value="<?php echo esc_attr($value); ?>"
                <?php $this->render_attributes(); ?>/>
                <?php $this->render_input_addon('append'); ?>
        </div>
        <?php
    }

    public function set_input_type($type) {
        if (!in_array($type, $this->allowed_input_types)) {
            throw new \Exception('Invalid Input type');
        }
        $this->input_type = $type;

        return $this;
    }

}
