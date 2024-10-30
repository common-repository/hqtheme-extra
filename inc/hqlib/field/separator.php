<?php

namespace HQLib\Field;

use HQLib\Field\Base;

class Separator extends Base {

    /**
     *
     * @var boolean
     */
    protected $disable_label = true;

    /**
     *
     * @var string
     */
    protected $size = 'sm';

    /**
     *
     * @var array
     */
    protected $allowed_sizes = [
        'xs',
        'sm',
        'md',
        'lg',
        'xl',
    ];

    /**
     *
     * @var boolean
     */
    protected $storable = false;

    public function render_field($value) {
        ?>
        <div class="hqt-separator __<?php echo $this->size; ?>">
            <span></span>
            <?php if (!empty($this->get_label())) : ?>
                <span class="heading"><?php echo $this->get_label(); ?></span>
            <?php endif; ?>
            <span></span>
        </div>
        <?php
    }

    public function set_size($size) {
        if (!in_array($size, $this->allowed_sizes)) {
            throw new \Exception('Invalid Size');
        }
        $this->size = $size;

        return $this;
    }

}
