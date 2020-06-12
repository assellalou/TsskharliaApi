<?php

namespace App\Core;

use Exception;

class App
{
    private static $registry = [];

    public static function bind($key, $value)
    {
        static::$registry[$key] = $value;
    }

    public static function get($key)
    {
        if (!array_key_exists($key, static::$registry)) {
            throw new Exception("No {$key} is bound in the registry!");
        }
        return static::$registry[$key];
    }
}
