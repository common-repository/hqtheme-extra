<?php

namespace HQLib\Options;

class Repeater {

    /**
     * Save containers
     * @var array
     */
    protected static $options_repeaters = [];

    /**
     *
     * @var string
     */
    protected $id;

    /**
     *
     * @var array
     */
    protected $fields = [];

    /**
     * 
     * @param type $id
     * @return $this
     * @throws Exception
     */
    public function __construct($id) {

        $id = \HQLib\HQLIB_PREFIX . $id;

        if (isset(self::$options_repeaters[$id])) {
            throw new \Exception('Field "' . $id . '" already exists');
        }
        $this->id = $id;

        self::$options_repeaters[$id] = $this;
        return $this;
    }

    public static function mk($id) {
        return new self($id);
    }

    public static function get($id) {
        if (isset(self::$options_repeaters[$id])) {
            return self::$options_repeaters[$id];
        }
        return null;
    }

    public function add_field($field) {
        if (is_array($field)) {
            foreach ($field as $item) {
                $this->fields[$item->get_field_name(true)] = $item;
            }
        } else {
            $this->fields[$field->get_field_name(true)] = $field;
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

    public function get_repeater_name($remove_prefix = false) {
        if ($remove_prefix) {
            return \HQLib\Helper::remove_hqlib_prefix($this->id);
        }
        return $this->id;
    }

}
