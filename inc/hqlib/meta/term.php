<?php

namespace HQLib\Meta;

class Term {

    /**
     * Save fields
     * @var array
     */
    public static $meta_term = [];

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
    protected $taxonomies = [];

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
     * @param type $taxonomies
     * @return Post
     * @throws Exception
     */
    public function __construct($id, $title, $description, $taxonomies) {

        $id = \HQLib\HQLIB_PREFIX . $id;

        if (isset(self::$meta_term[$id])) {
            throw new \Exception('Field "' . $id . '" already exists');
        }

        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->taxonomies = $taxonomies;

        foreach ($taxonomies as $taxonomy) {
            add_action($taxonomy . '_add_form_fields', ['\HQLib\Meta', 'add_taxonomy_form_fields'], 10, 1);
            add_action($taxonomy . '_edit_form_fields', ['\HQLib\Meta', 'edit_taxonomy_form_fields'], 50, 2);
        }

        self::$meta_term[$id] = $this;

        return $this;
    }

    public static function mk($id, $title, $description, $taxonomies) {
        return new self($id, $title, $description, $taxonomies);
    }

    public static function get_by_taxonomy($tax) {
        $meta_terms = [];
        foreach (self::$meta_term as $term) {
            if (in_array($tax, $term->get_taxonomies())) {
                $meta_terms[] = $term;
            }
        }
        return !empty($meta_terms) ? $meta_terms : false;
    }

    public static function get_by_id($container_id) {

        $container_id = \HQLib\HQLIB_PREFIX . $container_id;

        if (isset(self::$meta_term[$container_id])) {
            return self::$meta_term[$container_id];
        }
        return false;
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

    public function get_taxonomies() {
        return $this->taxonomies;
    }

    public function set_is_grouped($is_grouped = true) {
        $this->is_grouped_options = $is_grouped;
        return $this;
    }

    public function is_grouped_options() {
        return $this->is_grouped_options;
    }

}
