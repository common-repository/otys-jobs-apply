<?php

/**
 * Hooks
 * Provides a central point to register Actions and Filters
 * @since 1.0.0
 */

namespace Otys\OtysPlugin\Includes\Core;

class Hooks extends Base
{
    /**
     * Collection of Actions to be registered with Wordpress
     *
     * @var array
     * @since 1.0.0
     */
    protected static $actions = array();

    /**
     * Collection of Filters to be registered with Wordpress
     *
     * @var array
     * @since 1.0.0
     */
    protected static $filters = array();

    /**
     * Add a action to the actions collection which we will later register with Wordpress
     *
     * @param  mixed $hook          The name (tag) of the Wordpress action that is being registered
     * @param  mixed $component     A reference to the instance of the object on which the filter or action is defined
     * @param  mixed $callback      The name of the function definition on the $component
     * @param  mixed $priority      The priority at which the function should be fired
     * @param  mixed $acceptedArgs  The number of arguments that should be passed to the $callback
     * @return void
     *
     * @since 1.0.0
     */
    public static function addAction($hook, $component, $callback, int $priority = 10, int $accepted_args = 1): void
    {
        self::$actions = self::add(self::$actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Add a filter to the filter collection which we will later register with Wordpress
     *
     * @param  mixed $hook          The name (tag) of the Wordpress filter that is being registered
     * @param  mixed $component     A reference to the instance of the object on which the filter or action is defined
     * @param  mixed $callback      The name of the function definition on the $component
     * @param  mixed $priority      The priority at which the function should be fired
     * @param  mixed $acceptedArgs  The number of arguments that should be passed to the $callback
     * @return void
     *
     * @since 1.0.0
     */
    public static function addFilter($hook, $component, string $callback, int $priority = 10, int $accepted_args = 1): void
    {
        self::$filters = self::add(self::$filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * A utility function that is used to register the actions and filters into a single collection
     * which is either within self::$actions or self::$filters
     *
     * @param  mixed $hooks         The collection of hooks that is being registered (either actions of filters)
     * @param  mixed $hook          The name (tag) of the Wordpress action/filter that is being registered
     * @param  mixed $component     A reference to the instance of the object on which the filter or action is defined
     * @param  mixed $callback      The name of the function definition on the $component
     * @param  mixed $priority      The priority at which the function should be fired
     * @param  mixed $accepted_args The number of arguments that should be passed to the $callback
     * @return void
     *
     * @since 1.0.0
     */
    private static function add($hooks, $hook, $component, string $callback, int $priority = 10, int $accepted_args = 1): array
    {
        $hooks[] = [
            'hook' => $hook,
            'component' => $component,
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args
        ];

        return $hooks;
    }

    /**
     * Register the actions and filters collections with Wordpress
     *
     * @since 1.0.0
     */
    public function init(): void
    {
        // Register all actions with Wordpress
        foreach (self::$actions as $hook) {
            add_action(
                $hook['hook'],
                array(
                    $hook['component'],  $hook['callback']
                ),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        // Register all filters with Wordpress
        foreach (self::$filters as $hook) {
            add_filter(
                $hook['hook'],
                array(
                    $hook['component'],  $hook['callback']
                ),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
}
