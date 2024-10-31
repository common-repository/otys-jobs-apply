<?php

namespace Otys\OtysPlugin\Helpers;

class ArrayHelper
{
    /**
     * Flatten an array
     *
     * @param mixed $array
     * @param mixed $prefix
     * @return array
     */
    public static function flatten($array, $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value)
        {
            $new_key = $prefix . (empty($prefix) ? '' : '.') . $key;

            if (is_array($value))
            {
                $result = array_merge($result, static::flatten($value, $new_key));
            }
            else
            {
                $result[$new_key] = $value;
            }
        }

        return $result;
    }
}