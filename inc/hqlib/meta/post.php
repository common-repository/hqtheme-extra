<?php

namespace HQLib\Meta;

class Post {

    /**
     * Save configs
     * @var array
     */
    public static $meta_post = [];

    /**
     *
     * @var string
     */
    protected $id;

    /**
     *
     * @var string
     */
    protected $title;

    /**
     *
     * @var string
     */
    protected $description;

    /**
     *
     * @var array
     */
    protected $screens = [];

    /**
     *
     * @var string
     */
    protected $context;

    /**
     *
     * @var string
     */
    protected $priority;

    /**
     *
     * @var array
     */
    protected $fields = [];

    /**
     *
     * @var bool
     */
    protected $is_grouped_options = false;

    /**
     * 
     * @param type $id
     * @param type $title
     * @param type $screen
     * @param type $context
     * @param type $priority
     * @return Post
     * @throws Exception
     */
    public function __construct($id, $title, $description, $screens, $context = 'advanced', $priority = 'default') {

        $id = \HQLib\HQLIB_PREFIX . $id;

        if (isset(self::$meta_post[$id])) {
            throw new \Exception('Field "' . $id . '" already exists');
        }

        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->screens = $screens;
        $this->context = $context;
        $this->priority = $priority;

        self::$meta_post[$id] = $this;

        return $this;
    }

    public static function mk($id, $title, $description, $screens, $context = 'advanced', $priority = 'default') {
        return new self($id, $title, $description, $screens, $context, $priority);
    }

    public function add_field($field, $prepend = false) {
        if (is_array($field)) {
            $tmp = [];
            foreach ($field as $item) {
                if ($prepend) {
                    $tmp[$item->get_field_name(true)] = $item;
                } else {
                    $this->fields[$item->get_field_name(true)] = $item;
                }
            }
            if ($prepend) {
                $this->fields = $tmp + $this->fields;
            }
        } else {
            if ($prepend) {
                $this->fields = [$field->get_field_name(true) => $field] + $this->fields;
            } else {
                $this->fields[$field->get_field_name(true)] = $field;
            }
        }
        return $this;
    }

    public function get_fields($id = null) {
        if ($id && isset($this->fields[$id])) {
            return $this->fields[$id];
        }
        return $this->fields;
    }

    public function remove_field($id) {
        if (isset($this->fields[$id])) {
            unset($this->fields[$id]);
        }
        return $this;
    }

    public static function get_by_id($container_id) {

        $container_id = \HQLib\HQLIB_PREFIX . $container_id;

        if (isset(self::$meta_post[$container_id])) {
            return self::$meta_post[$container_id];
        }
        return false;
    }

    public function get_container_name($remove_prefix = false) {
        if ($remove_prefix) {
            return \HQLib\Helper::remove_hqlib_prefix($this->id);
        }
        return $this->id;
    }

    public function get_title() {
        return $this->title;
    }

    public function get_description() {
        return $this->description;
    }

    public function get_screens() {
        return $this->screens;
    }

    public function get_context() {
        return $this->context;
    }

    public function get_priotity() {
        return $this->priority;
    }

    public function set_is_grouped($is_grouped = true) {
        $this->is_grouped_options = $is_grouped;
        return $this;
    }

    public function is_grouped_options() {
        return $this->is_grouped_options;
    }

}
