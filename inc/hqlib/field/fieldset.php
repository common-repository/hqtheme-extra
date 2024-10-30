<?php

namespace HQLib\Field;

use HQLib\Field\Base;

class Fieldset extends Base {

    /**
     *
     * @var boolean
     */
    protected $fieldset_start = true;

    /**
     *
     * @var boolean
     */
    protected $storable = false;

    public function __construct($id, $fieldset_start = true, $label = '') {
        $this->id = $id;
        $this->fieldset_start = $fieldset_start;
        $this->label = $label;

        return $this;
    }

    public function render_field($value) {
        
    }

    public function render_fieldset() {
        if ($this->fieldset_start) :
            ?>
            <fieldset class="hqt-fieldset <?php echo $this->get_classes(); ?>">
                <?php if (!empty($this->label)) : ?>
                    <legend><?php echo esc_html($this->label); ?></legend>
                <?php endif; ?>
                <div class="hqt-fieldset-fields">
        <?php else : ?>
                </div>
            </fieldset>
        <?php
        endif;
    }

}
