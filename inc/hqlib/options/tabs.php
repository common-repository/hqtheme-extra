<?php

namespace HQLib\Options;

class Tabs {

    /**
     * Save configs
     * @var array
     */
    protected static $options_tabs = [];

    /**
     *
     * @var string
     */
    protected $id;

    /**
     *
     * @var array
     */
    protected $tabs = [];

    /**
     * Tabs layout.
     * Horizontal or vertical.
     * @var string 
     */
    protected $layout = 'horizontal';

    /**
     * Layout options
     * @var array
     */
    protected $available_layouts = ['horizontal', 'vertical'];

    /**
     * 
     * @param type $id
     * @return $this
     * @throws Exception
     */
    public function __construct($id) {

        if (isset(self::$options_tabs[$id])) {
            throw new \Exception('Tabs "' . $id . '" already exist');
        }

        $this->id = $id;

        self::$options_tabs[$id] = $this;
        return $this;
    }

    /**
     * Create Tabs element
     * @param string $id Unique identifier
     * @return \self
     */
    public static function mk($id) {
        return new self($id);
    }

    /**
     * Get Tabs element by ID
     * @param string $id
     * @return HQLib\Options\Tabs Instance of Tabs or null in case of invalid ID
     */
    public static function get($id) {
        if (isset(self::$options_tabs[$id])) {
            return self::$options_tabs[$id];
        }
        return null;
    }

    /**
     * Add tab
     * @param \HQLib\Options\Container $container
     * @param string $label
     * @param bool $prepend
     * @return $this
     */
    public function add_tab($container, $label = '', $prepend = false) {
        if ($container) {
            if ($prepend) {
                $this->tabs = array_merge([$container->get_container_name(true) => \HQLib\Options\Tabs\Tab::mk($container, $label)], $this->tabs);
            } else {
                $this->tabs[$container->get_container_name(true)] = \HQLib\Options\Tabs\Tab::mk($container, $label);
            }
        }
        return $this;
    }

    /**
     * Remove tab
     * @param string $container_name
     * @return $this
     */
    public function remove_tab($container_name) {
        if (isset($this->tabs[$container_name])) {
            unset($this->tabs[$container_name]);
        }
        return $this;
    }

    /**
     * Get Tabs
     * @param string $container_name
     * @return \HQLib\Options\Tabs\Tab[]
     */
    public function get_tabs($container_name = null) {
        if ($container_name && isset($this->tabs[$container_name])) {
            return $this->tabs[$container_name];
        }
        return $this->tabs;
    }

    /**
     * Get Tabs ID
     * @return string
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Set Tabs layout
     * @param string $layout
     * @return $this
     */
    public function set_layout($layout) {
        if (in_array($layout, $this->available_layouts)) {
            $this->layout = $layout;
        }
        return $this;
    }

    /**
     * Get Tabs layout
     * @return string
     */
    public function get_layout() {
        return $this->layout;
    }

}
