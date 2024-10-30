<?php

namespace HQLib\Field;

use HQLib\Field\Base;

class Html extends Base {

    /**
     *
     * @var boolean
     */
    protected $disable_label = true;

    /**
     *
     * @var boolean
     */
    protected $storable = false;

    /**
     *
     * @var string
     */
    protected $html = '';

    public function render_field($value) {
        echo $this->html;
    }

    public function set_html($html) {
        $this->html = $html;
        return $this;
    }

}
