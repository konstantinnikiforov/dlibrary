<?php

/*
 * Autoloader and dependency injection initialization for D classes
 */

if (class_exists('D', false)) {
    return;
}

require dirname(__FILE__).'/classes/D.php';


D::registerAutoload();
