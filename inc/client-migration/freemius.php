<?php

namespace HQExtra\Client_Migration;

defined('ABSPATH') || exit;

class Freemius {

    /**
     * Freemius Instance
     * @var Freemius 
     */
    private static $_instance = null;

    public static function instance() {

        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        
    }

}
