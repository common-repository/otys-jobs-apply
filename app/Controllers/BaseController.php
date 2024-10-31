<?php

namespace Otys\OtysPlugin\Controllers;

use Otys\OtysPlugin\Includes\Core\Base;
use Otys\OtysPlugin\Includes\Core\Routes;

abstract class BaseController extends Base
{
    /**
     * Store all intances, used to keep track of all instances created
     *
     * @since 1.0.0
     */
    protected static $instances = [];

    /**
     * Used to store the templatepath
     *
     * @var string
     */
    protected static $template = '';
    
    /**
     * args
     *
     * @var mixed
     */
    protected $args = [];

    /**
     * Function that allows to register i.e. hooks & shortcodes using the Includes\Hooks class
     *
     * Make sure to avoid creating instances in beforeInit since this gets always called within the Wordpress
     * Admin panel!
     *
     * @return void
     * @since 1.0.0
     */
    public static function initCallback($route = [])
    {
        /**
         * Check if route is defined (not is NULL)
         * Make sure the initCallback
         */
        if (
            $route['initCallback'] !== NULL &&
            class_exists($route['initCallback'][0]) &&
            is_callable(
                [$route['initCallback'][0],
                $route['initCallback'][1]]
            )
        ) {
            call_user_func([$route['initCallback'][0], $route['initCallback'][1]]);
        }
    }

    public static function jsonResponse(array $args, bool $die = true)
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

        if ($die) {
            echo json_encode($response);
            die();
        }

        return json_encode($response);
    }

    /**
     * Load template using the Wordpress load_template function
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public static function loadTemplate($template, $args): bool
    {
        if (($templatePath = Routes::locateTemplate($template)) !== '') {
            load_template($templatePath, false, $args);

            return true;
        }

        return false;
    }

     /**
     * setTemplate Change the current template to be loaded
     *
     * @param  mixed $template
     * @return void
     *
     * @since 1.0.0
     */
    public function setTemplate($template): void
    {
        self::$template = $template;
    }

    /**
     * Get the current the current template file to be used
     *
     * @return string Returns filepath
     *
     * @since 1.0.0
     */
    public static function getTemplate($template = NULL): string
    {
        return static::$template;
    }

    /**
     * Parse args to template using the Wordpress load_template function
     *
     * The loadTemplate function uses the wordpress load_template function which allows to to parse
     * args to the template to load. Using parseArgs() we are saving args to the $this->args array
     * which we can later on parse to the template.
     *
     * @param string $name Name of argument this is later on used as $args[$name] in the template
     * @param mixed $data The data you want to parse to the template
     * @return void
     *
     * @since 1.0.0
     */
    public function parseArgs(string $name, $data): void
    {
        $this->args[$name] = $data;
    }

    /**
     * Get arguments
     *
     * @return array
     * @since 1.0.0
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * Get Vacancy UID From URL
     *
     * @param int $offset
     * @param string $url
     * @return string
     * @since 1.0.0
     */
    public static function getVacancyUidFromUrl($url = ''): string
    {
        $slug = Routes::getRouteParameter('slug', $url);

        $uid = substr($slug, -16);

        return esc_attr($uid);
    }
}
