<?php

/**
 * Hooks
 * Provides a central point to register Actions and Filters
 * @since 1.0.0
 */
namespace Otys\OtysPlugin\Includes\Core;

use Otys\OtysPlugin\Includes\OtysApi\OtysApi;

class Shortcodes extends Base
{
    /**
     * Collection of Shortcodes to be registered with Wordpress
     *
     * @var array
     * @since 1.0.0
     */
    protected static $shortcodes = array();

    /**
     * Add a action to the actions collection which we will later register with Wordpress
     *
     * @param  mixed $tag          The name (tag) of the Wordpress action that is being registered
     * @param  mixed $component     A reference to the instance of the object on which the filter or action is defined
     * @param  mixed $callback      The name of the function definition on the $component
     * @return void
     *
     * @since 1.0.0
     */
    public static function add(string $tag, $component, string $callback): void
    {
        self::$shortcodes[] = [
            'tag' => $tag,
            'component' => $component,
            'callback' => $callback
        ];
    }

    /**
     * Register the actions and filters collections with Wordpress
     *
     * @since 1.0.0
     */
    public static function init(): void
    {
        // Register all filters with Wordpress
        foreach (self::$shortcodes as $shortcode) {
            $callback = [$shortcode['component'], $shortcode['callback']];
            add_shortcode($shortcode['tag'], $callback);
        }
    }
}
