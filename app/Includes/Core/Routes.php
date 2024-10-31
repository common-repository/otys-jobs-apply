<?php

namespace Otys\OtysPlugin\Includes\Core;

use Otys\OtysPlugin\Helpers\SettingHelper;
use Otys\OtysPlugin\Models\Shortcodes\VacanciesListModel;
use Otys\OtysPlugin\Includes\OtysApi;

/**
 * Manage routes
 *
 * @since 1.0.0
 */
class Routes extends Base
{
    private static $routes = [];

    public function __construct()
    {
        Hooks::addAction('init', $this, 'initRewriteRules', 1, 1);
        Hooks::addFilter('query_vars', $this, 'addQueryVars', 2, 1);
        Hooks::addFilter('template_include', $this, 'checkRewrites', 3, 1);
        Hooks::addFilter('get_canonical_url', $this, 'checkCanonical', 3, 1);
        Hooks::addAction('wp', $this, 'checkPage', 1);
        Hooks::addFilter('wp_robots', $this, 'checkRobots', 1, 1);
    }

    /**
     * Set robots
     *
     * @since 1.0.0
     * @param mixed $robots
     * @return mixed
     */
    public function checkRobots($robots)
    {
        global $post;

        if (isset($post->robots)) {
            $robots = wp_parse_args($post->robots, $robots);
        }

        return $robots;
    }

    /**
     * Check chanonical changes the canonical for page pages.
     * when creating a non existing page wordpress sets the canonical to
     * be the pageid=-99, which we ofcouse do not want. Therefore we will
     * be changing the canonical for fake pages.
     *
     * @since 1.0.0
     * @param mixed $canonical
     * @return string
     */
    public function checkCanonical($canonical): string {
        global $post;

        /**
         * If the current page is a fake post prevent Wordpress from
         * creating a canonical
         */
        if ($post->ID == -99) {
            return false;
        }

        return $canonical;
    }

    /**
     * Check rewrite and generate fake page if page does not actually exists.
     *
     * @version 1.0.0
     * @return void
     */
    public function checkPage(): void
    {
        if ($route = self::getCurrentRoute()) {
            if (!is_page()) {
                switch_to_locale($route['locale']);
                $this->createFakePage($route);
            }
        }
    }

    /**
     * Get rewrite by slug
     *
     * @since 1.0.0
     * @param string $slug
     * @param array $replacements
     * @param string $locale
     * @return string
     */
    public static function get(string $slug, $replacements = [], string $locale = ''): string
    {
        $locale = OtysApi::getLanguage($locale);

        /**
         * Try getting the route from the routes file
         */
        foreach (static::$routes as $route) {
            $routeLocale = explode('_', $route['locale'])[0];

            $routeLocale = $routeLocale === '' ? 'en' : $routeLocale;

            if ($route['slug'] === $slug && ($routeLocale === $locale || $routeLocale === "")) {
                $value = $route['route'];

                foreach ($replacements as $search => $replacement) {
                    $value = str_replace(':' . $search, $replacement, $value);
                }

                return $value;
            }
        }

        /**
         * Try getting the route from the database
         */
        $routes = (array) get_option('otys_option_language_routes', []);

        $candidate_login_routes = (array) get_option('otys_option_candidate_authentication_routes', []);

        $routes = array_merge($routes, $candidate_login_routes);

        if ($slug && $routes) {
            if (!empty($routes) && !empty($routes[$slug])) {
                foreach ($routes[$slug] as $route) {
                    if (!isset($route['locale'])) {
                        continue;
                    }

                    $routeLocale = $route['locale'] === '' ? 'en' : OtysApi::getLanguage($route['locale']);

                    if ($locale == $routeLocale) {
                        return trailingslashit(home_url($route['slug']));
                    }
                }
            }
        }

        return '';
    }

    /**
     * Create fake page
     *
     * @since 1.0.0
     * @return void
     */
    public function createFakePage($route = [])
    {
        global $wp, $wp_query, $post;

        // Init vars
        $pageType = $route['type'] ?? 'page';

        $isPreview = isset($_GET['preview']);

        // Set status header
        status_header(200);

        // Create our fake post
        $post_id = -99;

        $post = new \stdClass();
        $post->ID = $post_id;
        $post->post_author = 1;
        $post->post_date = current_time('mysql');
        $post->post_date_gmt = current_time('mysql', 1);
        $post->post_title = '';
        $post->post_content = '';
        $post->post_status = $isPreview ? 'draft' : 'publish';
        $post->comment_status = 'closed';
        $post->ping_status = 'closed';
        //$post->post_name = 'fake-page-' . rand(1, 99999); // append random number to avoid clash
        $post->post_type = $pageType;
        $post->filter = 'raw'; // important!

        $wp_post = new \WP_Post($post);

        // Update the main query
        $wp_query->post = $wp_post;
        $wp_query->posts = [$wp_post];
        $wp_query->queried_object = $wp_post;
        $wp_query->queried_object_id = $post_id;
        $wp_query->found_posts = 1;
        $wp_query->post_count = 1;
        $wp_query->max_num_pages = 1;
        $wp_query->is_page = true;
        $wp_query->is_singular = true;
        $wp_query->is_single = false;
        $wp_query->is_attachment = false;
        $wp_query->is_archive = false;
        $wp_query->is_category = false;
        $wp_query->is_tag = false;
        $wp_query->is_tax = false;
        $wp_query->is_author = false;
        $wp_query->is_date = false;
        $wp_query->is_year = false;
        $wp_query->is_month = false;
        $wp_query->is_day = false;
        $wp_query->is_time = false;
        $wp_query->is_search = false;
        $wp_query->is_feed = false;
        $wp_query->is_comment_feed = false;
        $wp_query->is_trackback = false;
        $wp_query->is_home = false;
        $wp_query->is_embed = false;
        $wp_query->is_404 = false;
        $wp_query->is_paged = false;
        $wp_query->is_admin = false;
        $wp_query->is_preview = $isPreview;
        $wp_query->is_robots = false;
        $wp_query->is_posts_page = false;
        $wp_query->is_post_type_archive = false;

        wp_cache_add($post_id, $wp_post, 'posts');

        $GLOBALS['wp_query'] = $wp_query;
        $wp->register_globals();
    }

    /**
     * Add a route
     *
     * @since 1.0.0
     * @param  string $slugUID Unique slug as identifier
     * @param  string $route
     * @param  mixed $class
     * @param  mixed $function
     * @param  callable 
     * @return void
     */
    public static function add(string $slug, array $urls, $function, $methods, string $locale = '', string $type = 'page', callable $initCallback = NULL)
    {
        foreach ($urls as $urlLanguage => $url) {
            $routeLanguage = OtysApi::getLanguage($locale);

            $url = wp_make_link_relative(get_home_url()) . $url;
        
            // Remove double slashes
            $url = preg_replace('#/+#','/',$url);

            if (array_key_exists($routeLanguage, $urls) && $routeLanguage != $urlLanguage) {
                continue;
            }

            $regexPattern = "^" . preg_replace('/\\\:[a-zA-Z0-9\_\-]+/', '([a-zA-Z0-9\-\_%]+)', preg_quote($url)) . "$";

            static::$routes[] = [
                'slug' => $slug,
                'route' => trailingslashit($url),
                'rewrite_rule' => $regexPattern,
                'function' => $function,
                'method' => $methods,
                'locale' => $locale,
                'type' => $type,
                'initCallback' => $initCallback
            ];
        }
    }

    /**
     * addQueryVars adds otys-page to the allowed query vars list
     *
     * @since 1.0.0
     * @param  array $vars
     * @return array
     */
    public static function addQueryVars($vars)
    {
        // Allow the query var ?otys-page
        $vars[] = 'otys';
        $vars[] = 'page-number';

        return $vars;
    }

    /**
     * Add rewrite rules to wordpress
     *
     * @since 1.0.0
     * @return void
     */
    public static function initRewriteRules()
    {
        foreach (static::$routes as $route) {
            add_rewrite_rule($route['rewrite_rule'], 'index.php?otys=1', 'top');
        }

        $currentUrl = parse_url(filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL), PHP_URL_PATH);
        $currentUrl = (substr($currentUrl, -1) === '/') ? $currentUrl : $currentUrl . '/';

        static::checkOldVacancyRedirect($currentUrl);
    }

    /**
     * Run init callback for the current route
     *
     * @since 1.0.0
     * @return void
     */
    public function runInitCallback(): void
    {
        foreach (static::$routes as $route) {
            // Check if the current url matches the route
            if (static::checkRoute($route)) {
                // If the url matches the current route then we run the before init function
                if ($route['initCallback'] !== NULL &&  is_callable([$route['initCallback'][0], $route['initCallback'][1]])) {
                    call_user_func([$route['initCallback'][0], $route['initCallback'][1]]);
                }
            }
        }
    }

    /**
     * Check if current URL equals a custom route
     *
     * @since 1.0.0
     * @param  mixed $template
     * @return mixed
     */
    public static function checkRewrites($template)
    {
        if ($route = self::getCurrentRoute()) {
            /**
             * Check if the current route has the same locale as the current locale
             * if not then we will change the locale
             */
            if (get_locale() != $route['locale']) {
                switch_to_locale($route['locale']);
            }

            if (class_exists($route['function'][0]) && is_callable([$route['function'][0], 'getInstance'])) {
                $instance = call_user_func([$route['function'][0], 'getInstance']);

                // Check if the function is defined && is a callable and cann it if possible;
                $function = (!array_key_exists("1", $route['function'])) ? false : $route['function'][1];

                if ($function && is_callable($instance->$function())) {
                    call_user_func($instance->$function());
                }

                // Gets the template set in the route function, get template is defined in the BaseModel
                if ($instance::loadTemplate($instance::getTemplate(), $instance->getArgs())) {
                    return false;
                } else {
                    self::throwError();
                }
            }
        }

        return $template;
    }

    /**
     * Get if current url exists in routes
     *
     * @since 1.0.0
     * @return mixed : Returns false if no route is found or returns route if route is found
     */
    public static function getCurrentRoute($url = '')
    {
        $currentMethod = is_string($_SERVER['REQUEST_METHOD']) ? preg_replace("/[^a-zA-Z]/", "", $_SERVER['REQUEST_METHOD']) : '';

        $currentUrl = $url === '' ? parse_url(filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL), PHP_URL_PATH) : $url;

        $currentUrl = (substr($currentUrl, -1) === '/') ? $currentUrl : $currentUrl . '/';

        foreach (self::$routes as $route) {
            $pregMatch = preg_match('#' . $route['rewrite_rule'] . '#', $currentUrl, $matches);

            if (in_array($currentMethod, $route['method']) && $pregMatch) {
                unset($matches[0]);
                $matches = array_values($matches);

                preg_match_all('/:[a-zA-Z0-9\_\-]+/', $route['route'], $paramMatches, PREG_PATTERN_ORDER);

                $paramMatchesOrder = $paramMatches[0];

                // Get named param values
                $params = [];
                
                foreach ($matches as $matchKey => $match) {
                    if (isset($paramMatchesOrder[$matchKey])) {
                        $paramMatch = $paramMatchesOrder[$matchKey];

                        $params[$paramMatch] = $match;
                    }
                }

                $route['params'] = $params;

                return $route;
            }
        }

        return false;
    }

    /**
     * Get route parameter
     *
     * @since 1.0.0
     * @param string $param
     * @return string|null
     */
    public static function getRouteParameter(string $param = '', $url = ''): ?string
    {
        if ($param === '') {
            return null;
        }

        $route  = static::getCurrentRoute($url);

        if ($route === null) {
            return null;
        }
        
        if (isset($route['params'][':' . $param])) {
            return $route['params'][':' . $param];
        }

        return null;
    }

    /**
     * Check if old vacancy url is used and redirect to new vacancy url
     *
     * @param string $currentUrl
     * @return void
     */
    private static function checkOldVacancyRedirect(string $currentUrl): void
    {
        // Check for old OTYS url with dash
        preg_match('/(\d+)-(\d+)(\d+).html/', $currentUrl, $oldVacancyMatch);

        // Check for old OTYS url with underscore if the dash url is not found
        if (empty($oldVacancyMatch) || count($oldVacancyMatch) !== 4) {
            preg_match('/(\d+)_(\d+)(\d+).html/', $currentUrl, $oldVacancyMatch);
        }

        // Check if the current url is an old vacancy url
        if (!empty($oldVacancyMatch) && count($oldVacancyMatch) === 4) {

            // Get the vacancy by the internal id
            $vacancies = VacanciesListModel::get([], [], [
                'condition' => [
                    'type' => 'AND',
                    'items' => [
                        [
                            'type' => 'COND',
                            'field' => 'internalId',
                            'op' => 'EQ',
                            'param' => $oldVacancyMatch[1]
                        ],
                    ]
                ],
                'search' => [
                    'ACTONOMY' => [
                        'OPTIONS' => [
                            'limit' => 100000
                        ]
                    ]
                ],
                'what' => [
                    'internalId' => 1
                ]
            ]);
       
            // Check if the vacancy exists
            if (
                !is_wp_error($vacancies) && 
                !empty($vacancies) && 
                isset($vacancies['listOutput']) &&
                !empty($vacancies['listOutput'])
            ) {
                $vacancy = $vacancies['listOutput'][0];
                $language = OtysApi::getLanguageByCodeByOtysLanguageId((int) $oldVacancyMatch[2]);

                $customSlugIsActive = static::customSlugIsActive();

                // Get website id
                $website = SettingHelper::getSiteId();

                // Create new vacancy url
                $newDetailUrl = Routes::get('vacancy-detail', [
                    'slug' => $customSlugIsActive ? $vacancy['slug'][$website] : sanitize_title($vacancy['title']) . '-' . $vacancy['uid']
                ], $language);

                // Redirect to new vacancy url
                if (wp_redirect($newDetailUrl, 301)) {
                    exit;
                }
            }
        }

    }

    /**
     * Check route against current REQUEST
     * If route matches the current request return true if
     * route does not match the curren request return false.
     *
     * @since 1.0.0
     * @param array $route
     * @return boolean
     */
    public static function checkRoute(array $route): bool
    {
        $currentMethod = is_string($_SERVER['REQUEST_METHOD']) ? preg_replace("/[^a-zA-Z]/", "", $_SERVER['REQUEST_METHOD']) : '';

        $currentUrl = parse_url(filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL), PHP_URL_PATH);
        $currentUrl = (substr($currentUrl, -1) === '/') ? $currentUrl : $currentUrl . '/';

        preg_match('#' . $route['rewrite_rule'] . '#', $currentUrl, $matches);

        if (in_array($currentMethod, $route['method']) && preg_match('#' . $route['rewrite_rule'] . '#', $currentUrl)) {
            return true;
        }

        return false;
    }

    /**
     * Throw error page. Loads in 404 page
     *
     * @since 1.0.0
     * @param string $errorCode
     * @param string $template
     * @param array $args
     * @param boolean $die
     * @return void
     */
    public static function throwError($errorCode = '404', $template = '404', $args = [], bool $die = true): void
    {
        // Set status header with set status code
        status_header($errorCode);
        nocache_headers();

        $errors = Errors::get();

        $templatePath = false;
        
        if (($path = self::locateTemplate($template, true)) !== false) {
            $templatePath = $path;
        } else {
            $templatePath = get_query_template('404');
        }

        if ($templatePath !== '') {
            load_template($templatePath, true, array_merge([
                'errors' => $errors
            ], $args));
        }

        // Now stop the rest of the script execution
        if ($die != false) {
            die();
        }
    }

    /**
     * locateTemplate
     * Locate template file in theme directories
     *
     * @since 1.0.0
     * @param string $template
     * @return string
     */
    public static function locateTemplate(string $template = '', $checkRoot = false): string
    {
        if ($template === '') {
            return '';
        }

        $extension = pathinfo($template, PATHINFO_EXTENSION);

        // Check if the template has .php at the end if not add .php
        $template = ($extension === 'php') ? $template : $template . '.php';

        if ($file_path = locate_template('otys-jobs-apply/' . $template)) {
            return $file_path;
        } elseif ($checkRoot && $file_path = locate_template($template)) { 
            return $file_path;
        } elseif (is_file(OTYS_PLUGIN_TEMPLATE_URL . '/' . $template)) {
            return OTYS_PLUGIN_TEMPLATE_URL . '/' . $template;
        }
        
        return '';
    }

    /**
     * Check if custom slug is active
     *
     * @return boolean
     */
    public static function customSlugIsActive(): bool
    {
        // Get website option
        $website = SettingHelper::getSiteId();

        // Setting a website is required
        if ($website === 0) {
            return false;
        }

        $checkResponse = OtysApi::post([
            'method' => 'check'
        ], true, false);

        if (is_wp_error($checkResponse)) {
            return false;
        }

        $response = OtysApi::post([
            'method' => 'Otys.Services.CsmService.getValue',
            'params' => [
                ['SE3452'],
                $checkResponse['clientId']
            ]
        ], true, true);

        if (
            !is_wp_error($response) && 
            isset($response['SE3452']) &&
            $response['SE3452']['value'] == 1
        ) {
            return true;
        }

        return false;
    }

    /**
     * Set OTYS url format
     *
     * This method communicates with the OTYS API to set the url format.
     * This is used to let OTYS know how the urls are formatted in the WordPress website.
     *
     * @param bool $force Force the communication to OTYS
     * 
     * @return void
     */
    public static function setOtysUrlFormat(bool $force = false): void
    {
        // Do not communicate urls to OTYS if the website is not in production mode
        if (!$force && !get_option('otys_option_is_production_website')) {
            return;
        }

        // Check if custom slug is active this is required
        $slugsActive = static::customSlugIsActive();

        if (!$slugsActive) {
            return;
        }

        $clientCode = OtysApi::getClientId();
        $website = SettingHelper::getSiteId();
        $languages = OtysApi::getLanguages();

        $domain = home_url();

        $apiUser = OtysApi::getApiUser();
        $defaultLanguage = $apiUser['defaultContentLanguage'] ?? 'en';

        // Loop through all OTYS Cms languages and set the url format
        foreach ($languages as $localeCode => $language) {
            $locale = $localeCode;

            // Get vacancy detail format
            $vacancyDetailFormat = Routes::get('vacancy-detail', [
                'slug' => '{{slug}}'
            ], $locale);

            // If the first url failed in this language change the locale to the default language
            $locale = $vacancyDetailFormat !== '' ? $locale : $defaultLanguage;

            // If vacancy detail format is empty for this language get detail url format for default language
            $vacancyDetailFormat = $vacancyDetailFormat !== '' ? $vacancyDetailFormat : Routes::get('vacancy-detail', [
                'slug' => '{{slug}}'
            ], $defaultLanguage);

            // Get vacancy apply format
            $vacancyApplyFormat = Routes::get('vacancy-apply', [
                'slug' => '{{slug}}'
            ], $locale);

            OtysApi::post([
                'method' => 'Otys.Services.VacancyService.saveUrlFormat',
                'params' => [
                    $clientCode,
                    $website,
                    $localeCode,
                    [
                        'id' => '1',
                        'domain' => $domain,
                        'vacancy_apply' => $vacancyApplyFormat,
                        'vacancy_detail' => $vacancyDetailFormat,
                        'vacancy_preview' => $vacancyDetailFormat . '?preview=true',
                    ]
                ]
            ], false, $localeCode);
        }
    }

    /**
     * Add routes
     *
     * @return void
     */
    public static function addRoutes()
    {
        static::$routes = [];

        /**
         *
         * Public pages
         *
         */
        $routes = (array) get_option('otys_option_language_routes', []);
        $candidateLoginRoutes = (array) get_option('otys_option_candidate_authentication_routes', []);
        $routes = array_merge($routes, $candidateLoginRoutes);
        
        if (!empty($routes) && is_array($routes)) {
            foreach ($routes as $module => $moduleRoutes) {
                if (!is_array($moduleRoutes)) {
                    continue;
                }

                foreach ($moduleRoutes as $route) {
                    if (!isset($route['locale'])) {
                        continue;
                    }

                    /**
                     * Vacancies overview routes
                     */
                    if ($module === 'vacancy') {
                        // Vacancy apply
                        static::add(
                            'vacancy-apply',
                            [
                                'en' => '/' . $route['slug'] . '/:slug/apply/',
                                'nl' => '/' . $route['slug'] . '/:slug/solliciteer/',
                                'de' => '/' . $route['slug'] . '/:slug/bewerben/',
                                'fr' => '/' . $route['slug'] . '/:slug/postuler/',
                                'es' => '/' . $route['slug'] . '/:slug/aplicar/'
                            ],
                            ['\Otys\OtysPlugin\Controllers\VacanciesApplyController', 'callback'],
                            ['POST', 'GET'],
                            $route['locale']
                        );

                        // Vacancy Detail
                        static::add(
                            'vacancy-detail',
                            ['/' . $route['slug'] . '/:slug/'],
                            ['\Otys\OtysPlugin\Controllers\VacanciesDetailController', 'callback'],
                            ['POST', 'GET'],
                            $route['locale'],
                            'vacancy',
                            ['\Otys\OtysPlugin\Controllers\VacanciesDetailController', 'detailCallback']
                        );
                    }

                    if ($module === 'candidate_logout') {
                        static::add(
                            'candidate_logout',
                            [
                                'en' => '/'. $route['slug'] .'/',
                                'nl' => '/'. $route['slug'] .'/',
                                'de' => '/'. $route['slug'] .'/',
                                'fr' => '/'. $route['slug'] .'/',
                                'es' => '/'. $route['slug'] .'/'
                            ],
                            ['\Otys\OtysPlugin\Controllers\AuthController', 'logout'],
                            ['GET'],
                            $route['locale']
                        );
                    }

                    if ($module === 'candidate_portal') {
                        static::add(
                            'candidate_portal',
                            [
                                'en' => '/'. $route['slug'] .'/',
                                'nl' => '/'. $route['slug'] .'/',
                                'de' => '/'. $route['slug'] .'/',
                                'fr' => '/'. $route['slug'] .'/',
                                'es' => '/'. $route['slug'] .'/'
                            ],
                            ['\Otys\OtysPlugin\Controllers\AuthController', 'candidatePortal'],
                            ['GET'],
                            $route['locale']
                        );
                    }
                }
            }
        }

        /**
         * Webhook routes
         */
        // Webhook entry point
        static::add(
            'webhooks',
            ['/webhooks/'],
            ['\Otys\OtysPlugin\Controllers\WebhooksController', 'index'],
            ['POST', 'GET']
        );

        /**
         * Sitemap
         */
        static::add(
            'sitemap',
            ['/otys-sitemap/'],
            ['\Otys\OtysPlugin\Controllers\SitemapController', 'callback'],
            ['POST', 'GET']
        );
    }
}
