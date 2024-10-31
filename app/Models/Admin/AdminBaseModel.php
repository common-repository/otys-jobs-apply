<?php

namespace Otys\OtysPlugin\Models\Admin;

abstract class AdminBaseModel
{
    private static $moduleName = 'admin';

    public function __construct()
    {
    }

    public function setModuleName($name)
    {
        static::$moduleName = $name;
    }
    
    public static function jsonResponse(array $args)
    {
        header('Content-Type: application/json');
        http_response_code(201);

        $args = wp_parse_args($args, [
            'type' => 'error',
            'code' => 'error',
            'message' => 'no message given',
            'data' => []
        ]);

        $response = [
            'type' => $args['type'],
            'code' => $args['code'],
            'message' => $args['message'],
            'data' => $args['data']
        ];

        echo json_encode($response);
        die();
    }
}
