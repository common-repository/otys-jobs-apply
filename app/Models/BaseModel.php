<?php

namespace Otys\OtysPlugin\Models;

use Otys\OtysPlugin\Includes\Core\Base;

abstract class BaseModel extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Does validation using array_filter
     *
     * @param array $params
     * @param array $rules
     * @return array
     */
    public static function doValidation(array $params, array $rules): array
    {
        $validated = [];

        foreach ($rules as $name => $callback) {
            $value = array_key_exists($name, $params) ? $params[$name] : null;

            $validated[$name] = call_user_func($callback, $value);
        }

        $validated = array_filter($validated, function ($v, $k) {
            return ($v !== null && $v !== false);
        }, ARRAY_FILTER_USE_BOTH);

        return $validated;
    }

    /**
     * Does validation using filter_var_array
     *
     * @param array $params
     * @param array $rules
     * @return array
     */
    public static function doSanitiziation(array $params, array $rules): array
    {
        $return = filter_var_array($params, $rules);

        $return = array_filter($return, function ($v, $k) {
            return ($v !== null && $v !== false && !empty($v) && !is_array($v));
        }, ARRAY_FILTER_USE_BOTH);

        return $return;
    }
}