<?php

namespace Otys\OtysPlugin\Includes\Core;

use WP_Error;

class Errors extends Base
{
    private static $error = [];

    public static function add($code, $message, $data)
    {
        self::$error[$code] = new WP_Error($code, $message, $data);
    }

    public static function get($code = false)
    {
        if (empty(self::$error)) {
            return false;
        }

        if ($code === false) {
            return self::$error;
        }

        if (array_key_exists($code, self::$error)) {
            return self::$error[$code];
        }

        return false;
    }
}