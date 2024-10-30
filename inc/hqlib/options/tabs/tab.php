<?php

namespace HQLib\Options\Tabs;

class Tab {

    /**
     * Tab container
     * @var \HQLib\Options\Container
     */
    protected $container;

    /**
     * Tab navigation label
     * @var string
     */
    protected $label;

    /**
     * 
     * @param \HQLib\Options\Container $container
     * @return $this
     * @throws \Exception
     */
    public function __construct($container, $label = '') {
        $this->container = $container;
        $this->label = !empty($label) ? $label : $container->get_title();

        return $this;
    }

    public static function mk($container, $label = '') {
        return new self($container, $label);
    }

    public function set_label($label) {
        $this->label = $label;
        return $this;
    }

    public function get_label() {
        return $this->label;
    }

    /**
     * 
     * @param \HQLib\Options\Container $container
     * @return $this
     */
    public function set_container($container) {
        $this->container = $container;
        return $this;
    }

    /**
     * 
     * @return \HQLib\Options\Container
     */
    public function get_container() {
        return $this->container;
    }

}
