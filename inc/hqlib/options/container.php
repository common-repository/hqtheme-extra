<?php

namespace HQLib\Options;

class Container {

    /**
     * Save containers
     * @var array
     */
    protected static $options_containers = [];

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
     * @var boolean
     */
    protected $disable_title = false;

    /**
     *
     * @var boolean
     */
    protected $disable_submit = false;
    
    /**
     *
     * @var boolean
     */
    protected $ajax_submit = false;

    /**
     * options / theme_mods
     * @var string
     */
    protected $storage = 'options';

    /**
     *
     * @var bool
     */
    protected $is_grouped_options = false;

    /**
     *
     * @var boolean
     */
    protected $autoload = false;

    /**
     *
     * @var string
     */
    protected $fieldset_started = null;

    /**
     *
     * @var array
     */
    protected $fields = [];

    /**
     * Default footer buttons
     * @var array
     */
    protected $buttons = [
        'save' => [
            'type' => 'submit',
            'class' => 'btn-primary',
            'name' => 'submit',
            'label' => 'Save Settings',
        ]
    ];

    /**
     * 
     * @param type $id
     * @param type $title
     * @return $this
     * @throws Exception
     */
    public function __construct($id, $title = '', $check_exists = true) {

        $id = \HQLib\HQLIB_PREFIX . $id;

        if ($check_exists && isset(self::$options_containers[$id])) {
            throw new \Exception('Container "' . $id . '" already exists');
        }

        $this->id = $id;
        $this->title = $title;

        self::$options_containers[$id] = $this;
        return $this;
    }

    public static function mk($id, $title = '', $check_exists = true) {
        return new self($id, $title, $check_exists);
    }

    public static function get($id) {
        if (isset(self::$options_containers[$id])) {
            return self::$options_containers[$id];
        }
        return null;
    }

    public function start_fieldset($id, $title = '') {
        $fieldset = $id . '__start';

        if (isset($this->fields[$fieldset])) {
            throw new \Exception('Fieldset "' . $id . '" already exists');
        }

        $this->fields[$fieldset] = new \HQLib\Field\Fieldset($fieldset, true, $title);
        $this->fieldset_started = $id;
        return $this;
    }

    public function end_fieldset() {
        if (is_null($this->fieldset_started)) {
            throw new \Exception('Fieldset not started');
        }
        $id = $this->fieldset_started . '__end';
        $this->fields[$id] = new \HQLib\Field\Fieldset($id, false);
        $this->fieldset_started = null;
    }

    public function remove_fieldset($id) {
        if (isset($this->fields[$id . '__start'])) {
            unset($this->fields[$id . '__start']);
        }
        if (isset($this->fields[$id . '__end'])) {
            unset($this->fields[$id . '__end']);
        }
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

    public function set_title($title) {
        $this->title = $title;
        return $this;
    }

    public function get_title() {
        return $this->title;
    }

    public function disable_title($disable = true) {
        $this->disable_title = $disable;
        return $this;
    }

    public function disable_submit($disable = true) {
        $this->disable_submit = $disable;
        return $this;
    }

    public function render_title() {
        if (false == $this->disable_title) :
            ?>
            <div class="hqt-container__title">
                <h3><?php echo $this->title; ?></h3>
            </div>
            <?php
        endif;
    }

    public function set_storage($storage) {
        $this->storage = $storage;
        return $this;
    }

    public function get_storage() {
        return $this->storage;
    }

    public function set_is_grouped($is_grouped = true) {
        $this->is_grouped_options = $is_grouped;
        return $this;
    }

    public function is_grouped_options() {
        return $this->is_grouped_options;
    }

    public function set_autoload($autoload) {
        $this->autoload = $autoload;
        return $this;
    }

    public function is_autoload() {
        return $this->autoload;
    }

    public function is_submitable() {
        return !$this->disable_submit;
    }
    
    public function set_ajax_submit($ajax = true) {
        $this->ajax_submit = $ajax;
        return $this;
    }
    
    public function get_ajax_submit() {
        return $this->ajax_submit;
    }

    public function set_buttons($buttons) {
        if (is_array($buttons) && !empty($buttons)) {
            foreach ($buttons as $key => $args) {
                $this->add_button($key, $args);
            }
        } else {
            // Remove container buttons
            $this->buttons = [];
        }
        return $this;
    }

    public function add_button($key, $args) {
        if (isset($args['type'], $args['class'])) {
            $add_button = [
                'type' => $args['type'],
                'class' => $args['class'],
                'name' => isset($args['name']) ? $args['name'] : strtolower(str_replace(' ', '_', $args['label'])),
                'id' => isset($args['id']) ? $args['id'] : '',
                'label' => isset($args['label']) ? $args['label'] : 'Change me!',
            ];
            if ('link' == $args['type']) {
                $add_button['link'] = isset($args['link']) ? $args['link'] : '';
                $add_button['target'] = isset($args['target']) ? $args['target'] : '';
            }
            $this->buttons[$key] = $add_button;
        }
        return $this;
    }

    public function get_buttons() {
        return $this->buttons;
    }

}
