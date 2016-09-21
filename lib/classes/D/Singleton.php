<?php

namespace D;

class Singleton {
    /**
     * @return mixed
     */
    public static function getInstance() {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @return mixed
     */
    public static function getRefreshedInstance() {
        if (!is_null(static::$instance)) {
            static::$instance = NULL;
        }

        return static::getInstance();
    }
}