<?php

if (!defined('HQLIB_PATH')) {

    define('HQLIB_PATH', dirname(__FILE__));

    // Load Autoloader
    require_once HQLIB_PATH . '/autoloader.php';
    HQLib\Autoloader::run();

    // Run Lib
    HQLib\HQLib::instance();
}