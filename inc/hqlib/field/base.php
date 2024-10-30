<?php

namespace HQLib\Field;

abstract class Base {

    /**
     *
     * @var string
     */
    protected $id;

    /**
     *
     * @var string
     */
    protected $label;

    /**
     *
     * @var boolean|string
     */
    protected $label_inline = false;

    /**
     *
     * @var boolean
     */
    protected $allow_label_inline = true;

    /**
     *
     * @var boolean
     */
    protected $autoload = false;

    /**
     *
     * @var boolean
     */
    protected $storable = true;

    /**
     * Include or exclude prefix in the field name when store it in the database
     * @var boolean
     */
    protected $disable_prefix = false;

    /**
     *
     * @var boolean
     */
    protected $disable_label = false;

    /**
     *
     * @var string
     */
    protected $description;

    /**
     *
     * @var string
     */
    protected $content_before;

    /**
     *
     * @var string
     */
    protected $content_after;

    /**
     *
     * @var string
     */
    protected $classes = false;

    /**
     *
     * @var array
     */
    protected $args = [];

    /**
     * Key-value array of attributes and their values
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Array of attributes the user is allowed to change
     *
     * @var array<string>
     */
    protected $allowed_attributes = ['disabled'];

    /**
     * Field display conditions
     * @var array
     */
    protected $conditions = [];

    /**
     *
     * @var mixed
     */
    protected $default_value = '';

    /**
     * Place add-on on either side of an input
     * ['prepend' => 'text', 'append' => 'text']
     * @var array
     */
    protected $input_addon;

    /**
     * Whether or not this field is required.
     *
     * @var bool
     */
    protected $required = false;

    /**
     * Field clearfix
     * Accept 'left', 'right', 'both' or false
     * @var boolean|string
     */
    protected $clearfix = false;

    public function __construct($id, $label = '') {
        $this->id = $id;
        $this->label = $label;

        return $this;
    }

    abstract public function render_field($value);

    public function disable_prefix($disable = true) {
        $this->disable_prefix = $disable;
        return $this;
    }

    public function disable_label($disable = true) {
        $this->disable_label = $disable;
        return $this;
    }

    public function render_label() {
        if (false == $this->disable_label) :
            $label_classes = 'hqt-label' . ($this->is_label_inline() ? ' ' . $this->get_label_inline() : '');
            ?>
            <label class="<?php echo $label_classes; ?>" for="<?php echo esc_attr($this->get_field_name()); ?>"><?php echo esc_html($this->label); ?></label>
            <?php
        endif;
    }

    protected function render_attributes() {
        $attributes = [];
        foreach ($this->get_attributes() as $key => $value) {
            $attributes[$key] = $value;
        }
        echo join(' ', array_map(function($key) use ($attributes) {
                    if (is_bool($attributes[$key])) {
                        return $attributes[$key] ? $key : '';
                    }
                    return esc_html($key) . '="' . esc_attr($attributes[$key]) . '"';
                }, array_keys($attributes)));
    }

    public function set_description($description) {
        $this->description = $description;
        return $this;
    }

    public function get_description() {
        return $this->description;
    }

    public function render_description() {
        echo $this->description;
    }

    public function set_content_before($content) {
        $this->content_before = $content;
        return $this;
    }

    public function get_content_before() {
        return $this->content_before;
    }

    public function render_content_before() {
        echo $this->content_before;
    }

    public function set_content_after($content) {
        $this->content_after = $content;
        return $this;
    }

    public function get_content_after() {
        return $this->content_after;
    }

    public function render_content_after() {
        echo $this->content_after;
    }

    public function render_badge() {
        if (isset($this->args['badge']) && !empty($this->args['badge'])) {
            if (is_array($this->args['badge'])) {
                foreach ($this->args['badge'] as $badge) {
                    $badge_class = strtolower(str_replace(' ', '-', esc_attr($badge)));
                    echo '<span class="hqt-field-badge badge-' . $badge_class . '">' . esc_html($badge) . '</span>';
                }
            } else {
                $badge_class = strtolower(str_replace(' ', '-', esc_attr($this->args['badge'])));
                echo '<span class="hqt-field-badge badge-' . $badge_class . '">' . esc_html($this->args['badge']) . '</span>';
            }
        }
    }

    public function set_input_addon($addon) {
        if (!is_array($addon)) {
            throw new \Exception('Array expected');
        }
        $this->input_addon = $addon;

        return $this;
    }

    protected function render_input_addon($type) {
        if (!in_array($type, ['prepend', 'append'])) {
            return;
        }
        if (isset($this->input_addon[$type])) {
            ?>
            <span class="hqt-control-addon __<?php echo $type; ?>"><?php echo esc_html($this->input_addon[$type]); ?></span>
            <?php
        }
    }

    public function get_field_name($remove_prefix = false) {
        if ($remove_prefix || $this->disable_prefix) {
            return \HQLib\Helper::remove_hqlib_prefix($this->id);
        }
        return $this->id;
    }

    public function get_type() {
        return strtolower(str_replace('_', '-', (new \ReflectionClass($this))->getShortName()));
    }

    public function set_default_value($value) {
        $this->default_value = $value;
        return $this;
    }

    public function get_default_value() {
        return $this->default_value;
    }

    public function get_value($storage, $group = '') {
        $field_id = empty($group) ? $this->get_field_name() : $group;
        $data = '';

        if ('options' === $storage) {
            $data = \HQLib\hq_get_option($field_id, '', $this->default_value, 'options', false);
        } else if ('theme_mods' === $storage) {
            $data = \HQLib\hq_get_option($field_id, '', $this->default_value, 'theme_mods', false);
        } else if ('post_meta' === $storage) {
            $post_id = get_the_ID();
            $data = \HQLib\get_post_meta($post_id, $field_id, '', $this->default_value, false);
        } else if ('tax_meta' === $storage) {
            $term_id = isset($_REQUEST['tag_ID']) ? sanitize_key($_REQUEST['tag_ID']) : false;
            $data = \HQLib\get_term_meta($term_id, $field_id, '', $this->default_value, false);
        }

        if ($group) {
            if (isset($data[$this->get_field_name(true)])) {
                return $data[$this->get_field_name(true)];
            }
            return $this->default_value;
        }

        return $data;
    }

    public function get_label() {
        return $this->label;
    }

    public function set_label_inline($label_classes = 'hqt-col-1-3 hqt-col-1-5__xl') {
        if ($this->allow_label_inline) {
            $this->label_inline = $label_classes;
        }
        return $this;
    }

    public function get_label_inline() {
        return $this->label_inline;
    }

    public function is_label_inline() {
        return $this->label_inline;
    }

    public function set_autoload($autoload) {
        $this->autoload = $autoload;
        return $this;
    }

    public function is_autoload() {
        return $this->autoload;
    }

    public function is_storable() {
        return $this->storable;
    }

    public function is_disable_prefix() {
        return $this->disable_prefix;
    }

    public function set_classes($classes) {
        $this->classes = $classes;
        return $this;
    }

    public function get_classes() {
        return $this->classes;
    }

    public function set_args($args) {
        $this->args = $args;
        return $this;
    }

    public function get_args($name = null) {
        if (null !== $name) {
            return isset($this->args[$name]) ? $this->args[$name] : null;
        }
        return $this->args;
    }

    /**
     * Set an attribute and its value
     *
     * @param  string $name
     * @param  string $value
     * @return self   $this
     */
    public function add_attribute($name, $value = '') {
        if (!empty($name)) {
            $is_data_attribute = substr(strtolower($name), 0, 5) === 'data-';
            if ($is_data_attribute) {
                $name = strtolower($name);
                $name = preg_replace('/[^a-z\-]/', '-', $name);
                $name = preg_replace('/\-{2,}/', '-', $name);
                $name = preg_replace('/^\-+|\-+$/', '', $name);
            }

            if (!$is_data_attribute && !in_array($name, $this->allowed_attributes)) {
                throw new \Exception('Only the following attributes are allowed: ' . implode(', ', array_merge($this->allowed_attributes, array('data-*'))));
            }

            $this->attributes[$name] = $value;
        }
        return $this;
    }

    /**
     * Set a key=>value array of attributes
     *
     * @param  array $attributes
     * @return self  $this
     */
    public function set_attributes($attributes) {
        if (!is_array($attributes)) {
            throw new \Exception('Array expected');
        }

        foreach ($attributes as $name => $value) {
            $this->add_attribute($name, $value);
        }

        return $this;
    }

    /**
     * Get a key-value array of attributes
     *
     * @return array
     */
    public function get_attributes($name = null) {
        if (null !== $name) {
            return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
        }
        return $this->attributes;
    }

    /**
     * Set the field visibility conditional logic.     * 
     * Array of [
     *    'relation' => 'AND', //AND, OR
     *    ['field' => $field, 'value' => $value, 'compare' => $operator]
     * ]
     * $value can be an array only when operator is `IN`, `NOT IN`, `INCLUDES` or `EXCLUDES`.	
     * $operator could be '=', '<', '>', '<=', '>=', 'IN', 'NOT IN', 'INCLUDES', 'EXCLUDES'
     * @param  array
     * @return self  $this
     */
    public function set_conditions($rules) {
        $this->conditions = $this->parse_conditional_rules($rules);
        return $this;
    }

    public function get_conditions() {
        return $this->conditions;
    }

    /**
     * Whether this field is mandatory for the user
     *
     * @param  bool  $required
     * @return self  $this
     */
    public function set_required($required = true) {
        $this->required = $required;
        return $this;
    }

    /**
     * Return whether this field is mandatory for the user
     *
     * @return bool
     */
    public function is_required() {
        return $this->required;
    }

    public function set_clearfix($clear = 'both') {
        if (in_array($clear, ['left', 'right', 'both']) || false === $clear) {
            $this->clearfix = $clear;
        }
        return $this;
    }

    public function get_clearfix() {
        return $this->clearfix;
    }

    public function render_clearfix($clear) {
        if ($this->clearfix && $clear) {
            if (!is_array($clear)) {
                $clear = (array) $clear;
            }
            if (in_array($this->clearfix, $clear)) {
                ?>
                <div class="hqt-clearfix hqt-col-1-1"></div>
                <?php
            }
        }
    }

    /**
     * Validate and parse conditional logic rules.
     *
     * @param  array $rules
     * @return array
     */
    protected function parse_conditional_rules($rules) {
        if (!is_array($rules)) {
            throw new \Exception('Array expected');
        }

        $parsed_rules = array(
            'relation' => \HQLib\Helper::get_relation_type_from_array($rules),
            'rules' => array(),
        );

        $rules_only = $rules;
        unset($rules_only['relation']); // Skip the relation key as it is already handled above

        foreach ($rules_only as $key => $rule) {
            $rule = $this->parse_conditional_rule($rule);

            if ($rule === null) {
                return array();
            }

            $parsed_rules['rules'][] = $rule;
        }

        return $parsed_rules;
    }

    /**
     * Validate and parse a conditional logic rule.
     *
     * @param  array $rule
     * @return array
     */
    protected function parse_conditional_rule($rule) {
        $allowed_operators = array('=', '!=', '>', '>=', '<', '<=', 'IN', 'NOT IN', 'INCLUDES', 'EXCLUDES');
        $array_operators = array('IN', 'NOT IN');

        // Check if the rule is valid
        if (!is_array($rule) || empty($rule['field'])) {
            throw new \Exception('Invalid conditional logic rule format. The rule should be an array with the "field" key set.');
        }

        // Fill in optional keys with defaults
        $rule = array_merge(array(
            'compare' => '=',
            'value' => '',
                ), $rule);

        if (!in_array($rule['compare'], $allowed_operators)) {
            throw new \Exception('Invalid conditional logic compare operator: <code>' . $rule['compare'] . '</code><br>Allowed operators are: <code>' .
                    implode(', ', $allowed_operators) . '</code>');
        }

        if (in_array($rule['compare'], $array_operators) && !is_array($rule['value'])) {
            throw new \Exception('Invalid conditional logic value format. An array is expected, when using the "' . $rule['compare'] . '" operator.');
        }

        // Prepend HQLib prefix
        $rule['field'] = \HQLib\HQLIB_PREFIX . $rule['field'];

        return $rule;
    }

}
