<?php

namespace Nf;

abstract class Singleton
{

    protected static $_instance=null;

    protected function __construct()
    {
    }

    public static function getInstance()
    {
        if (static::$_instance===null) {
            $className = get_called_class();
            static::$_instance = new $className;
        }

        return static::$_instance;
    }

    public function __clone()
    {
        throw new Exception('Cloning not allowed on a singleton object', E_USER_ERROR);
    }
}
