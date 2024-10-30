<?php

namespace HQLib;

class Field {

    /**
     * All fields
     * @var array
     */
    public static $fields = [];

    /**
     * 
     * @param string $type
     * @param string $id
     * @param string $label
     * @return \HQLib\Field\Base
     * @throws Exception
     */
    public static function mk($type, $id, $label = '', $check_exists = true) {

        $id = \HQLib\HQLIB_PREFIX . $id;

        if ($check_exists && isset(self::$fields[$id])) {
            throw new \Exception('Field "' . $id . '" already exists');
        }

        $class = __NAMESPACE__ . '\\Field\\' . ucfirst($type);

        $field = new $class($id, $label);

        self::$fields[$id] = $field;

        return $field;
    }

    public static function update($type, $id, $label = '') {

        $id = \HQLib\HQLIB_PREFIX . $id;

        $class = __NAMESPACE__ . '\\Field\\' . ucfirst($type);

        $field = new $class($id, $label);

        self::$fields[$id] = $field;

        return $field;
    }

    /**
     * 
     * @param string $id
     * @return \HQLib\Field\Base or null
     */
    public static function get($id) {
        $id = \HQLib\HQLIB_PREFIX . $id;
        if (isset(self::$fields[$id])) {
            return self::$fields[$id];
        }
        return null;
    }

}
