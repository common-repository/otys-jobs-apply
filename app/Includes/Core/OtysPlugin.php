<?php

/**
 * The core Plugin class
 * @since 1.0.0
 * @package otys-jobs-apply
 */

namespace Otys\OtysPlugin\Includes\Core;

use Otys\OtysPlugin\Controllers\Shortcodes\VacanciesListController;
use Otys\OtysPlugin\Controllers\Shortcodes\VacanciesSearchController;
use Otys\OtysPlugin\Includes\Core\AdminMessages;
use Otys\OtysPlugin\Includes\OtysApi;
use Otys\OtysPlugin\Models\Admin\AdminWebhooksModel;
use Otys\OtysPlugin\Models\ProceduresModel;

class OtysPlugin extends Base
{
    /**
     * Instances
     */
    public $hooks;
    public $shortcodes;
    public $language;
    public $routes;
    public $adminPages;
    public $enqueue;
    public $AdminMessages;
    public $logs;
    public $cache;

    /**
     * Load the core functionality of the plugin
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->versionCheck();
        $this->registerBaseHooks();

        Logs::checkLogs();

        /**
         * Init plugin modules
         */

        /**
         * @var \Otys\OtysPlugin\Includes\Core\Hooks
         */
        $this->hooks = Hooks::getInstance();

        /**
         * @var \Otys\OtysPlugin\Includes\Core\Hooks
         */
        $this->AdminMessages = AdminMessages::getInstance();

        /**
         * @var \Otys\OtysPlugin\Includes\Core\Logs
         */
        $this->logs = Logs::getInstance();

        /**
         * @var \Otys\OtysPlugin\Includes\Core\Logs
         */
        $this->cache = Cache::getInstance();

        /**
         * @var \Otys\OtysPlugin\Includes\Core\Language
         */
        $this->language = Language::getInstance();

        /**
         * @var \Otys\OtysPlugin\Includes\Core\Routes
         */
        $this->routes = Routes::getInstance();

        /**
         * @var \Otys\OtysPlugin\Includes\Core\AdminPAges
         */
        $this->adminPages = AdminPages::getInstance();

        /**
         * @var \Otys\OtysPlugin\Includes\Core\Enqueue
         */
        $this->enqueue = Enqueue::getInstance();

        $this->initCustomAjaxActions();

        // Start session
        Hooks::addAction('init', $this, 'initSession', 1, 0);

        // Add admin enqueue
        Hooks::addAction('admin_enqueue_scripts', $this, 'enqueueAdmin', 10, 0);

        // Filter admin menu
        Hooks::addAction('wp_before_admin_bar_render', $this, 'customizeAdminBar', 10, 0);

        // Add filter to retrieve applications from current session
        Hooks::addFilter(
            'otys_get_applications',
            ApplicationSessions::class,
            'filterCallback'
        );

        // Display what pages are used for in the pages overview
        Hooks::addFilter('display_post_states', $this, 'filterPostStates', 10, 2);

        /**
         * Init shortcode classes
         */

        // Add shortcodes
        $this->initCustomAjaxActions();

        // Run the before init function
        $this->routes->runInitCallback();

        // Add rest end points
        static::registerRest();

        // Add shortcodes
        static::registerShortcodes();

        // Hooks & Shortcodes must be initialized last
        $this->hooks->init(); 
    }

    /**
     * Customize WordPress admin bar
     *
     * @return void
     * @since 1.0.0
     */
    public function customizeAdminBar(): void
    {
        global $wp_admin_bar;
        global $post;
        if ($post !== NULL && property_exists($post, 'ID') && $post->ID === -99) {
            $wp_admin_bar->remove_node('edit');
            $wp_admin_bar->add_menu([
                'id' => '5', // an unique id (required)
                'parent' => '', // false for a top level menu
                'title' => '<span class="ab-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="21" viewBox="0 0 50.266 37.295"> <g id="Group_313" data-name="Group 313" transform="translate(-307.701 -249)"> <g id="Group_240" data-name="Group 240" transform="translate(189.16 249)"> <g id="Group_181" data-name="Group 181" transform="translate(0 0.725)"> <path id="Path_83" data-name="Path 83" d="M757.539,256.906a4.913,4.913,0,0,0-1.861-3.97,4.606,4.606,0,0,0-3.581-.659,4.424,4.424,0,0,0-2.951,1.969c-2.531,4.239-9.229,14.1-16.722,14.1-7.519,0-14.22-9.861-16.751-14.1a4.414,4.414,0,0,0-2.941-1.969,4.615,4.615,0,0,0-3.592.659,5.128,5.128,0,0,0-1.186,6.507c6.508,10.912,17.251,14.784,24.472,14.784s17.932-3.871,24.444-14.78a4.972,4.972,0,0,0,.669-2.541Z" transform="translate(-588.733 -252.17)" fill="#FFF"/> </g> <circle id="Ellipse_13" data-name="Ellipse 13" cx="5.606" cy="5.606" r="5.606" transform="translate(138.069 0)" fill="#FFF"/> <circle id="Ellipse_14" data-name="Ellipse 14" cx="5.606" cy="5.606" r="5.606" transform="translate(138.069 26.083)" fill="#FFF"/> </g> </g> </svg></span><span class="ab-label">' . __('Edit settings', 'otys-jobs-apply') . "</span>", // title/menu text to display
                'href' => admin_url('admin.php?page=otys_settings'), // target url of this menu item
                // optional meta array 
                'meta' => array(
                    'onclick' => '',
                    'html' => '',
                    'class' => '',
                    'target' => '',
                    'title' => ''
                )
            ]);
        }
    }

    /**
     * Filters post states in the admin panel
     *
     * @param mixed $states
     * @param mixed $post
     * @return void
     * @since 1.0.0
     */
    public function filterPostStates($states, $post)
    {
        if (is_admin()) {
            $moduleList = [
                'vacancies' => __('Vacancies overview', 'otys-jobs-apply'),
                'vacancy' => __('Vacancy detail', 'otys-jobs-apply')
            ];

            /**
             * For otys_option_language_routes save the slug of the page aswell
             */
            $routes = get_option('otys_option_language_routes', "");
            if ($routes !== "") {
                foreach ($routes as $moduleName => $moduleValues) {
                    foreach ($moduleValues as $key => $moduleValue) {
                        if (isset($moduleValue['slug']) && $moduleValue['slug'] == $post->post_name) {
                            $states['otys_route_' . $moduleName] = $moduleList[$moduleName];
                        }
                    }
                }
            }
        } 

        return $states;
    }

    /**
     * enqueue Admin scripts
     *
     * @return void
     * @since 1.0.0
     */
    public function enqueueAdmin()
    {
        $pluginData = get_plugin_data(OTYS_PLUGIN_FILE_URL);

        wp_enqueue_editor();
        wp_enqueue_script('otys-admin', OTYS_PLUGIN_ASSETS_URL . '/js/admin/admin.min.js', array('jquery'), OTYS_PLUGIN_VERSION, true);
        wp_enqueue_script('otys-jobalert', OTYS_PLUGIN_ASSETS_URL . '/js/admin/admin-jobalert.min.js', array('jquery'), OTYS_PLUGIN_VERSION, true);
        wp_enqueue_script('otys-swiper', OTYS_PLUGIN_URL . '/node_modules/swiper/swiper-bundle.min.js', array('jquery'), OTYS_PLUGIN_VERSION, false);
        wp_enqueue_style('otys-admin', OTYS_PLUGIN_ASSETS_URL . '/css/admin/admin.css', [], OTYS_PLUGIN_VERSION);
        wp_enqueue_style('otys-swiper', OTYS_PLUGIN_URL . '/node_modules/swiper/swiper.min.css', [], OTYS_PLUGIN_VERSION);

        if (isset($_GET['page']) && strpos($_GET['page'],'otys_') !== false) {
            wp_enqueue_media();
            wp_enqueue_script('sortable-js', OTYS_PLUGIN_URL . '/node_modules/sortablejs/Sortable.min.js', [],  OTYS_PLUGIN_VERSION, true);
            wp_enqueue_script('otys-admin-settings-js', OTYS_PLUGIN_ASSETS_URL . '/js/admin/admin-settings.min.js', [], OTYS_PLUGIN_VERSION, true);
        }
    }

    /**
     * Register base hooks needed for the plugin to work
     *
     * @return void
     * @since 1.0.0
     */
    public function registerBaseHooks(): void
    {
        register_activation_hook(OTYS_PLUGIN_FILE_URL, [$this, 'activate']);
        register_deactivation_hook(OTYS_PLUGIN_FILE_URL, [$this, 'deactivate']);
        register_uninstall_hook(OTYS_PLUGIN_FILE_URL, [__CLASS__, 'uninstall']);
    }

    /**
     * Plugin activation
     *
     * @return void
     * since 1.0.0
     */
    public function activate(): void
    {
        Logs::createTable();

        // Set default value of settings
        if (get_option('otys_option_recaptcha_threshold') === false) {
            update_option('otys_option_recaptcha_threshold', 0.5);
        }

        // Set default value of created standard documents
        if (get_option('otys_option_created_standard_documents') === false) {
            update_option('otys_option_created_standard_documents', false);
        }

        // Set default vacancies per page number
        if (get_option('otys_option_vacancies_per_page') === false) {
            update_option('otys_option_vacancies_per_page', 10);
        }

        // Set default vacancies per page number
        if (get_option('otys_option_is_production_website') === false) {
            update_option('otys_option_is_production_website', 1);
        }

        // Set default website option
        if (get_option('otys_option_website') === false) {
            update_option('otys_option_website', 0);
        }
        
        // Set default filter sorting option
        if (get_option('otys_option_vacancies_options_sorting') === false) {
            update_option('otys_option_vacancies_options_sorting', 'alphabetic');
        }

        /**
         * Pagination default options
         */
        if (get_option('otys_option_pagination_max_pages') === false) {
            update_option('otys_option_pagination_max_pages', 5);
        }

        if (get_option('otys_option_pagination_buttons_prev_next') === false) {
            update_option('otys_option_pagination_buttons_prev_next', 1);
        }

        if (get_option('otys_option_pagination_buttons_first_last') === false) {
            update_option('otys_option_pagination_buttons_first_last', 0);
        }

        // Check if we have a valid session and
        if (OtysApi::check()) {
            AdminWebhooksModel::deleteWebhooks();
            AdminWebhooksModel::registerWebhooks();
        }
    }

    /**
     * Plugin deactivation
     *
     * @return void
     * @since 1.0.0
     */
    public function deactivate(): void
    {
        Cache::deleteAll();
        AdminWebhooksModel::deleteWebhooks();
    }

    /**
     * Plugin uninstall
     *
     * @return void
     * @since 1.0.0
     */
    public static function uninstall(): void
    {
        global $wpdb;

        Logs::deleteTable();

        AdminWebhooksModel::deleteWebhooks();

        // Delete all OTYS Options
        $sql = "DELETE FROM $wpdb->options WHERE option_name LIKE '%otys_option%'";
        $wpdb->get_results($wpdb->prepare($sql));

        Cache::deleteAll();
    }

    /**
     * Plugin initialisation
     *
     * @return void
     * @since 1.0.0
     */
    public function initSession(): void
    {
        /**
         * Start session if there's no session yet
         */
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_lifetime' => 86400,
            ]);
        }

        // If any utm tags are set as GET parameters, remove them from the session and cookies so they can be set again
        $utmNames = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

        foreach ($utmNames as $utmName) {
            if (isset($_GET[$utmName])) {
                unset($_SESSION['utm_tags'][$utmName]);
                setcookie($utmName, '', strtotime('-30 days'), '/');
            }
        }

        /**
         * Add UTM tags to session
         */
        // $_SESSION['utm_tags'] = isset($_SESSION['utm_tags']) ? filter_var($_SESSION['utm_tags'], FILTER_CALLBACK, array('options' => [$this, 'sanitizeUTM'])) : [];

        /**
         * Search get parameters for utm information and add to the session and cookies
         */
        if (isset($_GET['utm_source'])) {
            $utmSource = filter_var($_GET['utm_source'], FILTER_CALLBACK, array('options' => [$this, 'sanitizeUTM']));

            if ($utmSource !== '') {
                $_SESSION['utm_tags']['source'] = $utmSource;
                setcookie("utm_source", $utmSource, strtotime('+30 days'), '/');
            }
        }

        if (isset($_GET['utm_medium'])) {
            $utmMedium = filter_var($_GET['utm_medium'], FILTER_CALLBACK, array('options' => [$this, 'sanitizeUTM']));

            if ($utmMedium !== '') {
                $_SESSION['utm_tags']['medium'] = $utmMedium;
                setcookie("utm_medium", $utmMedium, strtotime('+30 days'), '/');
            }
        }

        if (isset($_GET['utm_campaign'])) {
            $utmCampaign = filter_var($_GET['utm_campaign'], FILTER_CALLBACK, array('options' => [$this, 'sanitizeUTM']));

            if ($utmCampaign !== '') {
                $_SESSION['utm_tags']['campaign'] = $utmCampaign;
                setcookie("utm_campaign", $utmCampaign, strtotime('+30 days'), '/');
            }
        }

        if (isset($_GET['utm_term'])) {
            $utmTerm =  filter_var($_GET['utm_term'], FILTER_CALLBACK, array('options' => [$this, 'sanitizeUTM']));

            if ($utmTerm !== '') {
                $_SESSION['utm_tags']['term'] = $utmTerm;
                setcookie("utm_term", $utmTerm, strtotime('+30 days'), '/');
            }
        }

        if (isset($_GET['utm_content'])) {
            $utmContent = filter_var($_GET['utm_content'], FILTER_CALLBACK, array('options' => [$this, 'sanitizeUTM']));

            if ($utmContent !== '') {
                $_SESSION['utm_tags']['content'] = $utmContent;
                setcookie("utm_content", $utmContent, strtotime('+30 days'), '/');
            }
        }
        
        /**
         * Save referer in session
         */
        if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] !== NULL) {
            $refererUrl = filter_var($_SERVER['HTTP_REFERER'], FILTER_SANITIZE_URL);
            $siteUrl = trailingslashit(home_url());

            // Check if refer is not itself
            if (strpos($refererUrl, $siteUrl) === false) {
                $refererParsed = parse_url($refererUrl);
                
                // Save in session
                $_SESSION['otys_referer'] = isset($refererParsed['host']) ?  str_replace(['https://', 'http://', 'www.'], '', $refererParsed['host']) : NULL;

                // Save in cookie
                setcookie("otys_referer", $_SESSION['otys_referer'], strtotime('+30 days'), '/');
            }
        }
    }

    /**
     * Create custom ajax Actions
     *
     * @return void
     * @since 1.2.0
     */
    public function initCustomAjaxActions(): void
    {
        Hooks::addAction('wp_ajax_otys_vvc', '\Otys\OtysPlugin\Controllers\VacanciesController', 'trackView', 10, 1);
        Hooks::addAction('wp_ajax_nopriv_otys_vvc', '\Otys\OtysPlugin\Controllers\VacanciesController', 'trackView', 10, 1);
    }

     /**
     * Version checker.
     * This checker is responsible for breaking 
     *
     * @return void
     * @since 1.2.6
     */
    public function versionCheck(): void
    {
        if (is_admin()) {
            $updater = new \Otys\OtysPlugin\Includes\Core\Updater();

            $updater->check();
        }
    }
    
    /**
     * Register rest calls
     */
    public static function registerRest()
    {
        add_action('rest_api_init', function () {
            register_rest_route('otys/v1', '/apply', [
                'methods' => 'POST',
                'callback' => ['\Otys\OtysPlugin\Controllers\Shortcodes\VacanciesApplyController', 'restPost'],
                'permission_callback' => '__return_true'
            ]);

            register_rest_route('otys/v1', '/vacancy/analytics', [
                'methods' => 'POST',
                'callback' => ['\Otys\OtysPlugin\Controllers\VacanciesDetailController', 'track'],
                'permission_callback' => '__return_true'
            ]);

            register_rest_route('otys/v1', '/login', [
                'methods' => 'POST',
                'callback' => ['\Otys\OtysPlugin\Controllers\Shortcodes\AuthController', 'restPost'],
                'permission_callback' => '__return_true'
            ]);

            register_rest_route('otys/v1', '/forgotpassword', [
                'methods' => 'POST',
                'callback' => ['\Otys\OtysPlugin\Controllers\Shortcodes\ForgotPasswordController', 'restPost'],
                'permission_callback' => '__return_true'
            ]);

            register_rest_route('otys/v1', '/interactions', array(
                'methods' => 'POST',
                'callback' => ['\Otys\OtysPlugin\Controllers\Shortcodes\JobAlertController', 'restPost'],
                'permission_callback' => '__return_true'
            ));
        });
    }

    /**
     * Register shortcodes
     *
     * @since 2.0.0
     * @return void
     */
    public static function registerShortcodes()
    {
        add_shortcode(
            'otys-vacancies-list',
            ['\Otys\OtysPlugin\Controllers\Shortcodes\VacanciesListController', 'callback']
        );

        Hooks::addAction('template_redirect', VacanciesListController::class, 'filterStatusCode', 10, 0);

        add_shortcode(
            'otys-vacancies-filters',
            ['\Otys\OtysPlugin\Controllers\Shortcodes\VacanciesFiltersController', 'callback']
        );
        
        add_shortcode(
            'otys-vacancies-shortlist',
            ['\Otys\OtysPlugin\Controllers\Shortcodes\VacanciesShortlistController', 'callback']
        );

        add_shortcode(
            'otys-vacancies-search',
            ['\Otys\OtysPlugin\Controllers\Shortcodes\VacanciesSearchController', 'callback']
        );

        Hooks::addAction('template_redirect', VacanciesSearchController::class, 'filterPost', 10, 0);

        add_shortcode(
            'otys-vacancies-geo-search',
            ['\Otys\OtysPlugin\Controllers\Shortcodes\VacanciesGeoSearchController', 'callback']
        );

        add_shortcode(
            'otys-vacancies-selected-filters',
            ['\Otys\OtysPlugin\Controllers\Shortcodes\VacanciesSelectedFiltersController', 'callback']
        );

        add_shortcode(
            'otys-vacancies-apply',
            ['\Otys\OtysPlugin\Controllers\Shortcodes\VacanciesApplyController', 'callback']
        );

        add_shortcode(
            'otys-candidate-login',
            ['\Otys\OtysPlugin\Controllers\Shortcodes\AuthController', 'callback']
        );

        add_shortcode(
            'otys-candidate-forgot-password',
            ['\Otys\OtysPlugin\Controllers\Shortcodes\ForgotPasswordController', 'callback']
        );

        add_shortcode(
            'otys-jobalert',
            ['\Otys\OtysPlugin\Controllers\Shortcodes\JobAlertController', 'callback']
        );
    }
}
