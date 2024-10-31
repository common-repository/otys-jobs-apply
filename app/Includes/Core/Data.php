<?php

namespace Otys\OtysPlugin\Includes\Core;

class Data extends Base
{
    private static $data = array();

    /**
     * add
     *
     * @param  mixed $indentifier may be a collection or a value itself
     * @param  mixed $data
     * @return void
     *
     * @since 1.0.0
     */
    public static function add($indentifier, $data = null): void
    {
        // Check if the intenfier exists, if so add a new key to the indentifier instead of overwriting it
        if (array_key_exists($indentifier, self::$data)) {
            self::$data[$indentifier][] = $data;
        } else {
            self::$data[$indentifier] = $data;
        }
    }

    /**
     * Get all available data or if the indentifier is used get only the data for the indentifier
     *
     * @param  mixed $indentifier
     * @return mixed
     */
    public function get($indentifier = '')
    {
        if ($indentifier !== '' && array_key_exists($indentifier, self::$data)) {
            return self::$data[$indentifier];
        }

        return self::$data;
    }
}
