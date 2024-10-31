<?php

/**
 * Enqueue styles & scripts class
 *
 * Used for loading styles and scripts for specific view files
 * @package otys-jobs-apply
 */

namespace Otys\OtysPlugin\Includes\Core;

class Enqueue extends Base
{
    private static $scripts = array();
    private static $styles = array();

    public function __construct()
    {
        Hooks::addAction('wp_enqueue_scripts', Enqueue::class, 'load', 1);
        Hooks::addAction('otys_add_style', Enqueue::class, 'addStyle', 1, 5);
        Hooks::addAction('otys_add_script', Enqueue::class, 'addScript', 1, 5);
        Hooks::addAction('init', Enqueue::class, 'init');
    }

    /**
     * Init styles
     *
     * @return void
     */
    public static function init()
    {
        // Enqueue default plugin styling
        static::addStyle(['handle' => 'otys-jobs-apply-css', 'src' => OTYS_PLUGIN_ASSETS_URL . '/css/otys-jobs-apply.css', false, '1.2.15']);

        if (current_user_can('manage_options')) {
            static::addStyle(['handle' => 'otys-jobs-apply-admin-front-css', 'src' => OTYS_PLUGIN_ASSETS_URL . '/css/admin/admin_front.css', false, '1.2.15']);
        }

        static::addScript(['handle' => 'otys-jobs-apply-js', 'src' => OTYS_PLUGIN_ASSETS_URL . '/js/otys-jobs-apply.min.js']);
    }

    /**
     * Add style to list of enqueue
     *
     * @param array $args
     * @return void
     * @since 1.0.0
     */
    public static function addStyle(array $args)
    {
        $args = wp_parse_args(
            $args,
            array(
                'handle' => false,
                'src' => false,
                'deps' => array(),
                'ver' => false,
                'media' => 'all',
                'type' => 'style'
            )
        );

        self::$styles[] = $args;
    }

    /**
     * Add script file to list to enqueue
     *
     * @param [type] $args
     * @return void
     * @since 1.0.0
     */
    public static function addScript(array $args): void
    {
        $args = wp_parse_args(
            $args,
            array(
                'handle' => false,
                'src' => false,
                'deps' => array(),
                'ver' => false,
                'in_footer' => true,
            )
        );

        self::$scripts[] = $args;
    }

    /**
     * Enqueue scripts
     *
     * @return void
     * @since 1.0.0
     */
    public static function load(): void
    {
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        $pluginData = get_plugin_data(OTYS_PLUGIN_FILE_URL);
        $pluginversion = $pluginData['Version'];

        foreach (self::$styles as $style) {
            if ($style['ver'] === false) {
                $style['ver'] = $pluginversion;
            }

            wp_enqueue_style($style['handle'], $style['src'], $style['deps'], $style['ver'], $style['media']);
        }
        
        foreach (self::$scripts as $key => $script) {
            if ($script['ver'] === false) {
                $script['ver'] = $pluginversion;
            }

            wp_enqueue_script($script['handle'], $script['src'], $script['deps'], $script['ver'], $script['in_footer']);
            if ($key === 0) {
                // Make ajax_url available for front-end
                wp_localize_script($script['handle'], 'otys_rest', array('end_point' => get_home_url() . '/wp-json/otys/v1/'));
            }
        }
    }
}
