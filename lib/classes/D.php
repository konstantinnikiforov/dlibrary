<?php

/**
 * General utility class in D library, not to be instantiated.
 *
 *
 * @author Chris Corbyn
 */
abstract class D {
    public static $initialized = FALSE;
    public static $inits = array();

    /**
     * Registers an initializer callable that will be called the first time
     * a SwiftMailer class is autoloaded.
     *
     * This enables you to tweak the default configuration in a lazy way.
     *
     * @param mixed $callable A valid PHP callable that will be called when autoloading the first D library class
     */
    public static function init($callable) {
        self::$inits[] = $callable;
    }

    /**
     * Internal autoloader for spl_autoload_register().
     *
     * @param string $class
     */
    public static function autoload($class) {
        // Don't interfere with other autoloaders
        if (0 !== strpos($class, 'D\\')) {
            return;
        }

        $path = dirname(__FILE__).'/'.str_replace('\\', '/', $class).'.php';

        if (!file_exists($path)) {
            return;
        }

        require $path;

        if (self::$inits && !self::$initialized) {
            self::$initialized = true;
            foreach (self::$inits as $init) {
                call_user_func($init);
            }
        }
    }

    /**
     * Configure autoloading using D library.
     *
     * This is designed to play nicely with other autoloaders.
     *
     * @param mixed $callable A valid PHP callable that will be called when autoloading the first D library class
     */
    public static function registerAutoload($callable = null) {
        if (null !== $callable) {
            self::$inits[] = $callable;
        }
        spl_autoload_register(array('D', 'autoload'));
    }
}
