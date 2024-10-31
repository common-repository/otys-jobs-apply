<?php

namespace Otys\OtysPlugin\Includes\Core;

use Otys\OtysPlugin\Includes\OtysApi;

class AdminPages extends Base
{
    /**
     * Stores all Admin Pages
     *
     * @var array
     * @since 1.0.0
     */
    private static $pages = array();

    /**
     * Stores all Admin Sub Pages
     *
     * @var array
     * @since 1.0.0
     */
    private static $subPages = array();

    /**
     * Provides access to a single instance of the module using the singleton pattern
     *
     * @return object
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        if (is_admin()) {
            Hooks::addAction('admin_menu', $this, 'init', 10, 0);
            Hooks::addAction('current_screen', $this, 'onInit', 10, 0);

            $this->beforeInit();
        }
    }

    public function onInit()
    {
        if (is_admin()) {
            $currentPage = get_current_screen();

            foreach (self::$pages as $page) {
                if ($currentPage->id !== 'otys_page_' . $page['menu_slug']) {
                    continue;
                }

                if (is_callable($page['callback_function'][0]::onInit())) {
                    $page['callback_function'][0]::onInit();
                }
            }

            foreach (self::$subPages as $page) {
                if ($currentPage->id !== 'otys_page_' . $page['menu_slug']) {
                    continue;
                }

                if (is_callable($page['callback_function'][0]::onInit())) {
                    $page['callback_function'][0]::onInit();
                }
            }
        }
    }

    /**
     * Register all Adminpages
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function init()
    {
        $validSession = OtysApi::check();

        foreach (self::$pages as $page) {
            add_menu_page(
                $page['page_title'],
                $page['menu_title'],
                $page['capability'],
                $page['menu_slug'],
                $page['callback_function'],
                $page['icon_url'],
                $page['position']
            );
        }

        foreach (self::$subPages as $page) {
            if ($page['requireValidApi'] && !$validSession) {
                continue;
            }

            if (isset($page['callback_function'][0]) && is_callable([$page['callback_function'][0], 'filterMenuTitle'])) {
                $page['menu_title'] = $page['callback_function'][0]::filterMenuTitle($page['page_title']);
            }

            add_submenu_page(
                $page['parent_slug'],
                $page['page_title'],
                $page['menu_title'],
                $page['capability'],
                $page['menu_slug'],
                $page['callback_function'],
                $page['position']
            );
        }

        remove_submenu_page('otys', 'otys');
    }

    /**
     * Call the register hooks function of AdminPage(s) instance(s) before the
     * hook init function gets loaded so we can register hooks using the Hook class
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function beforeInit()
    {
        foreach (self::$subPages as $page) {
            if (is_callable($page['callback_function'][0]::beforeInit())) {
                $page['callback_function'][0]::beforeInit();
            }
        }
    }

    /**
     * Add a page to the list which later on will be included in the admin menu
     *
     * @param  string $page_title Title displayed in the page head
     * @param  string $menu_title Title of the page displayed in the sidemenu of the admin panel
     * @param  string $capability What capability a user should have before being able to access the page
     * @param  string $menu_slug The slug used in the url
     * @param  callable $callback_function What callback function should be called; Should be a AdminController
     * @param  string $icon_url Url of the icon used in the menu
     * @param  int $position At what position in the menu the page should be displayed
     *
     * @return void
     *
     * @since 1.0.0
     */
    public static function add(
        string $page_title, 
        string $menu_title, 
        string $capability, 
        string $menu_slug, 
        callable $callback_function, 
        string $icon_url = '', 
        int $position = null)
    {
        self::$pages[] = [
            'page_title' => $page_title,
            'menu_title' => $menu_title,
            'capability' => $capability,
            'menu_slug' => $menu_slug,
            'callback_function' => $callback_function,
            'icon_url' => $icon_url,
            'position' => $position,
        ];
    }

    /**
     * Add a page to the list which later on will be included in the admin menu
     *
     * @param  string $page_title Title displayed in the page head
     * @param  string $menu_title Title of the page displayed in the sidemenu of the admin panel
     * @param  string $capability What capability a user should have before being able to access the page
     * @param  string $menu_slug The slug used in the url
     * @param  callable $callback_function What callback function should be called; Should be a AdminController
     * @param  string $icon_url Url of the icon used in the menu
     * @param  int $position At what position in the menu the page should be displayed
     *
     * @return void
     *
     * @since 1.0.0
     */
    public static function addSub(
        string $parent_slug,
        string $page_title,
        string $menu_title,
        string $capability,
        string $menu_slug,
        callable $callback_function,
        int $position = null,
        bool $requireValidApi = false)
    {
        self::$subPages[] = [
            'parent_slug' => $parent_slug,
            'page_title' => $page_title,
            'menu_title' => $menu_title,
            'capability' => $capability,
            'menu_slug' => $menu_slug,
            'callback_function' => $callback_function,
            'position' => $position,
            'requireValidApi' => $requireValidApi
        ];
    }
}
