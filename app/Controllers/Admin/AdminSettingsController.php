<?php

namespace Otys\OtysPlugin\Controllers\Admin;

use Otys\OtysPlugin\Includes\Core\Hooks;
use Otys\OtysPlugin\Includes\Core\Routes;
use Otys\OtysPlugin\Includes\OtysApi;
use Otys\OtysPlugin\Includes\Core\Cache;
use Otys\OtysPlugin\Includes\Core\Updater;
use Otys\OtysPlugin\Models\Admin\AdminSettingsModel;
use Otys\OtysPlugin\Models\Admin\AdminWebhooksModel;
use Otys\OtysPlugin\Models\VacanciesDetailModel;

class AdminSettingsController extends AdminBaseController
{
    /**
     * The pagename
     * Pagename gets used in every setting registration
     * @var string
     * @since 1.0.0
     */
    protected const PAGE_NAME = 'otys_settings';

    /**
     * List of tabs available on the settings page
     *
     * @var array
     * @since 1.2.11
     */
    protected static $tabs = [];

    /**
     * List of modules available in the plugin
     *
     * @var array
     * @since 1.2.11
     */
    protected static $moduleList = [];

    /**
     * Constructor
     * @since 1.2.11
     */
    public function __construct()
    {
        parent::__construct();

        $this->parseArgs('tabs', static::$tabs);
    }

    /**
     * On init
     *
     * @return void
     * 1.2.11
     */
    public static function onInit()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'remove-api-key') {
            Updater::entryDeactivate(get_option('otys_option_api_key', ''));

            update_option('otys_option_api_key', '');
            Cache::deleteAll();
            add_settings_error('otys_messages', 'otys_message', __('The API key has been removed.', 'otys-jobs-apply'), 'updated');
            wp_redirect(admin_url('admin.php?page=' . static::PAGE_NAME));
            exit;
        }
    }

    /**
     * Run functions before class initialisation.
     * function gets called when registering controller at routes.
     *
     * @return void
     * @since 1.2.11
     */
    public static function beforeInit(): void
    {
        $check = OtysApi::check();
        $apiUser = OtysApi::getApiUser() ?? [];

        static::$tabs = [
            'api' => [
                'slug' => 'otys-settings-api',
                'name' => __('API', 'otys-jobs-apply'),
                'disabled' => false,
                'actions' => 0
            ],
            'vacancies-overview' => [
                'slug' => 'otys_settings_api_vacancies_overview',
                'name' => __('Vacancies overview', 'otys-jobs-apply'),
                'disabled' => !$check,
                'actions' => 0
            ],
            'vacancies-detail' => [
                'slug' => 'otys_settings_api_vacancies_detail',
                'name' => __('Vacancies detail', 'otys-jobs-apply'),
                'disabled' => !$check,
                'actions' => 0
            ],
            'vacancies-apply' => [
                'slug' => 'otys_settings_api_vacancies_apply',
                'name' => __('Vacancies apply', 'otys-jobs-apply'),
                'disabled' => !$check,
                'actions' => 0
            ],
            'candidate_auth' => [
                'slug' => 'otys_settings_candidate_login',
                'name' => __('Candidate authentication', 'otys-jobs-apply'),
                'disabled' => !$check,
                'actions' => 0
            ]
        ];

        if (!isset($apiUser['partnerId']) || $apiUser['partnerId'] != '78') {
            static::$tabs['api']['actions']++;
        }

        if (!get_option('otys_option_recaptcha_site_key')) {
            static::$tabs['vacancies-apply']['actions']++;
        }

        if (!get_option('otys_option_recaptcha_secret_key')) {
            static::$tabs['vacancies-apply']['actions']++;
        }

        $routes = get_option('otys_option_language_routes', []);

        if (is_array($routes) && count($routes) < 2) {
            static::$tabs['vacancies-overview']['actions'] = 2 - count($routes);
        } else if ($routes === '') {
            static::$tabs['vacancies-overview']['actions'] = 2;
        }

        if ($check === false) {
            static::$tabs['api']['actions']++;
        }

        // Initialise the settings on page load
        Hooks::addAction('admin_init', __CLASS__, 'initSettings', 10, 0);

        // Option validation on update
        Hooks::addAction('pre_update_option_otys_option_api_key', __CLASS__, 'checkApiKEy', 10, 2);

        // Option validation on update
        Hooks::addAction('pre_update_option_otys_option_vacancies_per_page', __CLASS__, 'checkPageLimit', 10, 2);

        // Option validation on update
        Hooks::addAction('pre_update_option_otys_option_is_production_website', __CLASS__, 'checkProduction', 10, 2);

        // Option validation on update
        Hooks::addAction('pre_update_option_otys_option_website', __CLASS__, 'checkWebsite', 10, 2);

        // Before save for routes
        Hooks::addFilter('pre_update_option_otys_option_language_routes', __CLASS__, 'beforeSaveRoutes', 10, 3);

        Hooks::addAction('update_option_otys_option_language_routes', __CLASS__, 'afterSaveRoutes', 10, 3);
    }

    /**
     * Callback after saving new routes
     *
     * @param mixed $newValue
     * @param mixed $oldValue
     * @param mixed $optionName
     * @return void
     */
    public static function afterSaveRoutes($newValue, $oldValue, $optionName)
    {
        // Add new routes
        Routes::addRoutes();

        // Register new routes
        Routes::initRewriteRules();

        // Communicate new routes to OTYSfd
        Routes::setOtysUrlFormat();

        return $newValue;
    }

    /**
     * Set default values
     *
     * @return void
     * @since 1.2.11
     */
    public static function defaultValues(): void
    {
        $defaultValues = [];

        // Load default values
        if (get_option('otys_option_language_routes') === false) {
            update_option('otys_option_language_routes', $defaultValues);
        }
    }

    /**
     * Textfield callback
     */
    public static function languageSettingHTML($args)
    {
        self::defaultValues();

        // Get current value
        $value = get_option($args['option_name']);

        $args = wp_parse_args(
            $args,
            array(
                'class' => 'regular-text',
                'value' => $value
            )
        );

        $templatePath = OTYS_PLUGIN_ADMIN_TEMPLATE_URL . '/admin-routes-settings/admin-routes-input.php';

        load_template($templatePath, false, $args);
    }

    /**
     * Before save routes
     *
     * @param mixed $newValue
     * @param string $oldValue
     * @param mixed $optionName
     * @return void
     */
    public static function beforeSaveRoutes($newValue, $oldValue, $optionName)
    {
        /**
         * For otys_option_language_routes save the slug of the page aswell
         */
        if ($oldValue !== false && $optionName === 'otys_option_language_routes' && is_array($newValue)) {
            foreach ($newValue as $moduleName => $moduleValues) {
                foreach ($moduleValues as $key => $moduleValue) {
                    $slug = $newValue[$moduleName][$key]['slug'];

                    $slug = ltrim($slug, '/');
                    $slug = rtrim($slug, '/');

                    $newValue[$moduleName][$key]['slug'] = $slug;
                }
            }
        }

        return $newValue;
    }

    /**
     * Filter menu title
     *
     * @param string $menuTitle
     * @return string
     * @since 1.2.11
     */
    public static function filterMenuTitle(string $menuTitle): string
    {
        $actions = 0;

        foreach (static::$tabs as $tab) {
            if (isset($tab['actions']) && is_int($tab['actions']) && isset($tab['disabled']) && $tab['disabled'] !== true) {
                $actions = $actions + $tab['actions'];
            }
        }

        if ($actions > 0) {
            $menuTitle = sprintf("%s <span class=\"awaiting-mod\">%s</span>", $menuTitle, $actions);
        }

        return $menuTitle;
    }

    /**
     * Check new website
     *
     * @param string $new
     * @param string $old
     * @return string
     */
    public static function checkWebsite($new, $old): string
    {
        if ($new !== $old) {
            static::resetCache();

            if ($new === null || !is_string($new)) {
                $new = 0;
            }

            Updater::entryUpdate(get_option('otys_option_api_key', ''), [
                "otys_site_id" => $new
            ]);
        }

        return $new;
    }

    /**
     * Check new website
     *
     * @param string $new
     * @param string $old
     * @return string
     */
    public static function checkPageLimit(string $new, string $old): string
    {
        $new = intval($new);

        if (is_int($new) && $new > 0) {
            return $new;
        }

        return $old;
    }

    /**
     * Check new website
     *
     * @param string $new
     * @param string $old
     * @return string
     */
    public static function checkProduction(string $new, string $old): string
    {
        if ($new !== $old) {
            static::resetCache();

            Updater::entryUpdate(get_option('otys_option_api_key', ''), [
                "production" => $new
            ]);
        }

        if ($new) {
            // Communicate new routes to OTYS if website is live
            Routes::setOtysUrlFormat(true);
        }

        return $new;
    }

    /**
     * Reset cache callback
     *
     * @return void
     * @since 1.0.0
     */
    public static function resetCache(): void
    {
        Cache::deleteAll();
        add_settings_error('otys_messages', 'otys_message', __('All plugin related cache has been removed', 'otys-jobs-apply'), 'updated');
    }

    /**
     * Callback when API KEY changes
     *
     * @param string $new
     * @param string $old
     * @return string
     * @since 1.0.0
     */
    public static function checkApiKEy($new, $old)
    {
        if ($new !== $old) {
            Cache::deleteAll();

            // Clear clients related options
            update_option('otys_option_website', '1');

            update_option('otys_option_is_production_website', 0);

            update_option('otys_option_vacancies_filters_match_criteria', []);
            update_option('otys_option_vacancies_list_match_criteria_labels', []);
            update_option('otys_option_vacancies_detail_match_criteria_labels', []);

            update_option('otys_option_document_template_apply_notify_candidate', 0);
            update_option('otys_option_document_template_apply_notify_new_candidate', 0);
            update_option('otys_option_document_template_open_apply_notify_candidate', 0);
            update_option('otys_option_document_template_apply_confirm_email', 0);
            update_option('otys_option_document_template_apply_notify_consultant', 0);
            update_option('otys_option_document_template_open_apply_notify_consultant', 0);

            $session = OtysApi::login($new);

            // Try to login with new api key and return error if api key is wrong
            if (is_wp_error($session)) {
                add_settings_error('otys_messages', 'otys_message', __('Invalid API key given', 'otys-jobs-apply'), 'error');
                return '';
            }

            AdminWebhooksModel::deleteWebhooks();
            AdminWebhooksModel::registerWebhooks();
            Cache::deleteAll();
            add_settings_error('otys_messages', 'otys_message', __('All plugin related cache has been removed', 'otys-jobs-apply'), 'updated');

            /**
             * Create standard documents
             */
            AdminSettingsModel::createDefaultDocuments();
        }

        return $new;
    }

    /**
     * Init settings for admin settings page
     *
     * @return void
     * @since 1.0.0
     */
    public static function initSettings(): void
    {
        $otysApi = new OtysApi();
        $check = $otysApi->check();

        /*
            API Section
        */
        // Add API Section to this page
        add_settings_section(
            'otys_api_section',
            __('Api settings', 'otys-jobs-apply'),
            function () {
                echo '<hr size="1">';
            },
            static::$tabs['api']['slug']
        );

        // register a new field in the "otys_api_section" section, inside the "otys_settings" page
        register_setting(self::PAGE_NAME, 'otys_option_api_key');
        add_settings_field(
            'otys_option_api_key',
            __('API key', 'otys-jobs-apply'),
            [__CLASS__, 'apiKeyField'],
            static::$tabs['api']['slug'],
            'otys_api_section',
            [
                'option_name' => 'otys_option_api_key',
                'option_section' => 'otys_api_section',
                'description' => __('Enter the API key provided by OTYS.', 'otys-jobs-apply')
            ]
        );

        /**
         * If current SESSION ID is valid show the rest of the settings
         */
        if ($check) {
            // Register new setting for otys_settings page
            register_setting(self::PAGE_NAME, 'otys_option_website');
            add_settings_field(
                'otys_option_website',
                __('Brand', 'otys-jobs-apply'),
                [__CLASS__, 'websiteRadio'],
                static::$tabs['api']['slug'],
                'otys_api_section',
                [
                    'option_name' => 'otys_option_website',
                    'option_section' => 'otys_api_section',
                    'description' => __('Choose which brand to use.', 'otys-jobs-apply')
                ]
            );

            register_setting(self::PAGE_NAME, 'otys_option_is_production_website');
            add_settings_field(
                'otys_option_is_production_website',
                __('Website status', 'otys-jobs-apply'),
                function ($args) {
                    $args['options'] = [
                        __('No, this website is not available to the public. (i.e. development environment)', 'otys-jobs-apply'),
                        __('Yes, this website is available to the public.', 'otys-jobs-apply')
                    ];
                    self::radio($args);
                },
                static::$tabs['api']['slug'],
                'otys_api_section',
                [
                    'option_name' => 'otys_option_is_production_website',
                    'option_section' => 'otys_api_section',
                    'description' => __('Production means that this website is used by the end-user and the website is live. <br><b>Note</b>: Only one website can be used as production website per brand, if you mark more than one website as production issues will occur.', 'otys-jobs-apply')
                ]
            );


            /*
                Vacancies overview section
            */
            add_settings_section(
                'otys_vacancies_section',
                __('Vacancy Overview', 'otys-jobs-apply'),
                function () {
                    echo '<hr size="1">';
                },
                static::$tabs['vacancies-overview']['slug']
            );

            // Register new setting for otys_settings page
            register_setting(self::PAGE_NAME, 'otys_option_vacancies_per_page');

            // register a new field in the "otys_vacancies_section" section, inside the "otys_settings" page
            add_settings_field(
                'otys_option_vacancies_per_page',
                __('Vacancies per page', 'otys-jobs-apply'),
                [__CLASS__, 'textField'],
                static::$tabs['vacancies-overview']['slug'],
                'otys_vacancies_section',
                [
                    'option_name' => 'otys_option_vacancies_per_page',
                    'class' => 'small-text',
                    'option_section' => 'otys_vacancies_section',
                    'description' => __('The amount of vacancies displayed per page on the vacancy overview.', 'otys-jobs-apply')
                ]
            );

            // Register new setting for otys_settings page
            register_setting(self::PAGE_NAME, 'otys_option_vacancies_options_sorting');

            add_settings_field(
                'otys_option_vacancies_options_sorting',
                __('Sorting of filter options', 'otys-jobs-apply'),
                [__CLASS__, 'select'],
                static::$tabs['vacancies-overview']['slug'],
                'otys_vacancies_section',
                [
                    'option_name' => 'otys_option_vacancies_options_sorting',
                    'option_section' => 'otys_vacancies_section',
                    'description' => __('The sorting of the options of each filter', 'otys-jobs-apply'),
                    'options' => ['alphabetic' => __('Alphabetic', 'otys'), 'frequency' => __('Frequency', 'otys')]
                ]
            );

            // Register new setting for otys_settings page
            register_setting(self::PAGE_NAME, 'otys_option_vacancies_filters_match_criteria');

            // register a new field in the "otys_vacancies_section" section, inside the "otys_settings" page
            add_settings_field(
                'otys_option_vacancies_filters_match_criteria',
                __('Vacancy filters', 'otys-jobs-apply'),
                [__CLASS__, 'sortableCheckboxes'],
                static::$tabs['vacancies-overview']['slug'],
                'otys_vacancies_section',
                [
                    'option_name' => 'otys_option_vacancies_filters_match_criteria',
                    'class' => 'small-text otys-sortable-list',
                    'option_section' => 'otys_vacancies_section',
                    'description' => __('The filters displayed when using the shortcode [otys-vacancies-filters].', 'otys-jobs-apply')
                ]
            );

            // Register new setting for otys_settings page
            register_setting(self::PAGE_NAME, 'otys_option_vacancies_list_match_criteria_labels');

            // register a new field in the "otys_vacancies_section" section, inside the "otys_settings" page
            add_settings_field(
                'otys_option_vacancies_list_match_criteria_labels',
                __('Vacancy labels', 'otys-jobs-apply'),
                [__CLASS__, 'sortableCheckboxes'],
                static::$tabs['vacancies-overview']['slug'],
                'otys_vacancies_section',
                [
                    'option_name' => 'otys_option_vacancies_list_match_criteria_labels',
                    'class' => 'small-text otys-sortable-list',
                    'option_section' => 'otys_vacancies_section',
                    'description' => __('Labels displayed at the vacancies displayed with the shortcodes [otys-vacancies-list] and [otys-vacancies-shortlist].', 'otys-jobs-apply')
                ]
            );

            // Register new setting and create field for the setting in the routes section of this settings page
            $languageRouteSettingName = 'otys_option_language_routes';
            register_setting(self::PAGE_NAME, $languageRouteSettingName);
            add_settings_field(
                $languageRouteSettingName,
                __('Vacancy overview url', 'otys-jobs-apply'),
                [__CLASS__, 'languageSettingHTML'],
                static::$tabs['vacancies-overview']['slug'],
                'otys_vacancies_section',
                [
                    'option_name' => $languageRouteSettingName,
                    'option_section' => 'otys_vacancy_routes_section',
                    'description' => '',
                    'module_list' => [
                        'vacancies' => [
                            'name' => __('Vacancies overview', 'otys-jobs-apply'),
                            'description' => __('Choose what url you want to use for the vacancies overview. This url should correspond with the page containing atleast a [otys-vacancies-list] shortcode. Add a vacancies overview url for each language. If your language is not available in the dropdown, make sure to install the language in your WordPress installation.', 'otys-jobs-apply')
                        ]
                    ]
                ]
            );

            /*
                Vacancies section
            */
            add_settings_section(
                'otys_vacancies_apply_section',
                __('Vacancy apply', 'otys-jobs-apply'),
                function () {
                    echo '<hr size="1">';
                },
                static::$tabs['vacancies-apply']['slug']
            );

            // Register new setting for otys_settings page
            register_setting(self::PAGE_NAME, 'otys_option_use_mobile_questionset');

            add_settings_field(
                'otys_option_show_candidate_login_link_at_form',
                __('Use mobile questionset', 'otys-jobs-apply'),
                [__CLASS__, 'select'],
                static::$tabs['vacancies-apply']['slug'],
                'otys_vacancies_apply_section',
                [
                    'option_name' => 'otys_option_use_mobile_questionset',
                    'option_section' => 'otys_vacancies_apply_section',
                    'description' => __('Wheter to use the mobile questionset for mobile devices. Mobile devices get automaticly recognized based on user agent. Make sure to mark a qusetionset as mobile questionset in OTYS (Setting nr. GE31). Using mobile questionset you can ask different questions to mobile users or have different validation rules.', 'otys-jobs-apply'),
                    'options' => ['false' => __('No', 'otys'), 'true' => __('Yes', 'otys')]
                ]
            );

            // Register new setting and create field for the setting in the routes section of this settings page
            $languageRouteSettingName = 'otys_option_language_routes';
            register_setting(self::PAGE_NAME, $languageRouteSettingName);
            add_settings_field(
                $languageRouteSettingName,
                __('Vacancy thank you url', 'otys-jobs-apply'),
                [__CLASS__, 'languageSettingHTML'],
                static::$tabs['vacancies-apply']['slug'],
                'otys_vacancies_apply_section',
                [
                    'option_name' => $languageRouteSettingName,
                    'option_section' => 'otys_vacancy_routes_section',
                    'description' => '',
                    'module_list' => [
                        'vacancy-apply-thank-you' => [
                            'name' => __('Vacancy application thank you', 'otys-jobs-apply'),
                            'description' => __('Optionally you can choose what url you want to use for the thank you page after a candidate has applied for a vacancy. You can add a different thank you url for each language. If your language is not available in the dropdown, make sure to install the language in your WordPress installation.', 'otys-jobs-apply')
                        ]
                    ]
                ]
            );

            /*
               Recaptcha
           */
            // Add API Section to this page
            add_settings_section(
                'otys_vacancies_apply_recaptcha_section',
                __('Google reCAPTCHA v3', 'otys-jobs-apply'),
                function () {
                    echo '<hr size="1">';
                    if (static::$tabs['vacancies-apply']['actions'] > 0) {
                        echo '<div class="otys-error-message">';
                        echo '<p>' . sprintf(__('Setting up recaptcha is required. Please use your Google account to generate the needed API keys. Please make sure to choose reCAPTCHA v3 as type and add <u>%s</u> to the list of domains.', 'otys-jobs-apply'), home_url()) . '</p>';
                        echo '<p><a class="button" target="_blank" href="https://www.google.com/recaptcha/admin/create"/>' . __('Create API Key', 'otys-jobs-apply') . '</a></p>';
                        echo '</div>';
                    }
                },
                static::$tabs['vacancies-apply']['slug']
            );

            // register a new field in the "otys_option_recaptcha_section" section, inside the "otys_settings" page
            register_setting(self::PAGE_NAME, 'otys_option_recaptcha_site_key');
            add_settings_field(
                'otys_option_recaptcha_site_key',
                __('Site key', 'otys-jobs-apply'),
                [__CLASS__, 'textField'],
                static::$tabs['vacancies-apply']['slug'],
                'otys_vacancies_apply_recaptcha_section',
                [
                    'option_name' => 'otys_option_recaptcha_site_key',
                    'option_section' => 'otys_recaptcha_section',
                    'description' => __('This site key is used in the HTML code your site serves to users.', 'otys-jobs-apply')
                ]
            );

            // register a new field in the "otys_option_recaptcha_section" section, inside the "otys_settings" page
            register_setting(self::PAGE_NAME, 'otys_option_recaptcha_secret_key');
            add_settings_field(
                'otys_option_recaptcha_secret_key',
                __('Secret Key', 'otys-jobs-apply'),
                [__CLASS__, 'textField'],
                static::$tabs['vacancies-apply']['slug'],
                'otys_vacancies_apply_recaptcha_section',
                [
                    'option_name' => 'otys_option_recaptcha_secret_key',
                    'option_section' => 'otys_recaptcha_section',
                    'description' => __('This secret key is used for communication between your site and reCAPTCHA.', 'otys-jobs-apply')
                ]
            );

            // register a new field in the "otys_option_recaptcha_section" section, inside the "otys_settings" page
            register_setting(self::PAGE_NAME, 'otys_option_recaptcha_threshold');
            add_settings_field(
                'otys_option_recaptcha_threshold',
                __('Threshold', 'otys-jobs-apply'),
                [__CLASS__, 'selectScoreRecaptcha'],
                static::$tabs['vacancies-apply']['slug'],
                'otys_vacancies_apply_recaptcha_section',
                [
                    'option_name' => 'otys_option_recaptcha_threshold',
                    'option_section' => 'otys_recaptcha_section',
                    'description' => __('reCAPTCHA learns by seeing real traffic on your site. For this reason, scores in a staging environment or soon after implementing may differ from production. As reCAPTCHA v3 doesn\'t ever interrupt the user flow, you can first run reCAPTCHA without taking action and then decide on thresholds by looking at your traffic in the admin console. By default, you can use a threshold of 0.5.', 'otys-jobs-apply')
                ]
            );

            /*
              Mails
          */
            add_settings_section(
                'otys_mail_section',
                __('Mails', 'otys-jobs-apply'),
                function () {
                    echo '<hr size="1">';
                    $templatePath = OTYS_PLUGIN_ADMIN_TEMPLATE_URL . '/admin-settings/admin-settings-mails.php';

                    load_template($templatePath, false);

                    echo '<br /><br />';
                },
                static::$tabs['vacancies-apply']['slug']
            );

            /**
             * Application section
             */
            add_settings_section(
                'otys_mail_section_application',
                __('Application', 'otys-jobs-apply'),
                function () {
                    echo '<hr size="1">';
                },
                static::$tabs['vacancies-apply']['slug']
            );


            // Register new setting for otys_settings page
            register_setting(self::PAGE_NAME, 'otys_option_document_template_apply_notify_candidate');

            // register a new field in the "otys_vacancy_detail_section" section, inside the "otys_settings" page
            add_settings_field(
                'otys_option_document_template_apply_notify_candidate',
                __('Candidate applied (existing)', 'otys-jobs-apply'),
                [__CLASS__, 'documentSelect'],
                static::$tabs['vacancies-apply']['slug'],
                'otys_mail_section_application',
                [
                    'option_name' => 'otys_option_document_template_apply_notify_candidate',
                    'class' => 'small-text',
                    'option_section' => 'otys_mail_section_application',
                    'description' => __('Email when an existing candidate applied for a job', 'otys-jobs-apply')
                ]
            );

            // Register new setting for otys_settings page
            register_setting(self::PAGE_NAME, 'otys_option_document_template_apply_notify_new_candidate');

            // register a new field in the "otys_vacancy_detail_section" section, inside the "otys_settings" page
            add_settings_field(
                'otys_option_document_template_apply_notify_new_candidate',
                __('Candidate applied (new)', 'otys-jobs-apply'),
                [__CLASS__, 'documentSelect'],
                static::$tabs['vacancies-apply']['slug'],
                'otys_mail_section_application',
                [
                    'option_name' => 'otys_option_document_template_apply_notify_new_candidate',
                    'class' => 'small-text',
                    'option_section' => 'otys_mail_section_application',
                    'description' => __('Email when a new candidate applied for a job.', 'otys-jobs-apply')
                ]
            );

            // Register new setting for otys_settings page
            register_setting(self::PAGE_NAME, 'otys_option_document_template_apply_confirm_email');

            // register a new field in the "otys_vacancy_detail_section" section, inside the "otys_settings" page
            add_settings_field(
                'otys_option_document_template_apply_confirm_email',
                __('Candidate confirm email', 'otys-jobs-apply'),
                [__CLASS__, 'documentSelect'],
                static::$tabs['vacancies-apply']['slug'],
                'otys_mail_section_application',
                [
                    'option_name' => 'otys_option_document_template_apply_confirm_email',
                    'class' => 'small-text',
                    'option_section' => 'otys_mail_section_application',
                    'description' => __('When a candidate applies with a known email adres, the candidate will receive an email with a code, the code needs to be filled in at the last step of the application proces to continue the application.', 'otys-jobs-apply')
                ]
            );

            // Register new setting for otys_settings page
            register_setting(self::PAGE_NAME, 'otys_option_document_template_apply_notify_consultant');

            // register a new field in the "otys_vacancy_detail_section" section, inside the "otys_settings" page
            add_settings_field(
                'otys_option_document_template_apply_notify_consultant',
                __('Vacancy owner application notification', 'otys-jobs-apply'),
                [__CLASS__, 'documentSelect'],
                static::$tabs['vacancies-apply']['slug'],
                'otys_mail_section_application',
                [
                    'option_name' => 'otys_option_document_template_apply_notify_consultant',
                    'class' => 'small-text',
                    'option_section' => 'otys_mail_section_application',
                    'description' => __('Notification to vacancy owner when a candidate applied on a vacancy.', 'otys-jobs-apply')
                ]
            );

            /**
             * Open application section
             */
            add_settings_section(
                'otys_mail_section_open_application',
                __('Open application', 'otys-jobs-apply'),
                function () {
                    echo '<hr size="1">';
                },
                static::$tabs['vacancies-apply']['slug']
            );

            // Register new setting for otys_settings page
            register_setting(self::PAGE_NAME, 'otys_option_document_template_open_apply_notify_candidate');

            // register a new field in the "otys_vacancy_detail_section" section, inside the "otys_settings" page
            add_settings_field(
                'otys_option_document_template_open_apply_notify_candidate',
                __('Candidate open application', 'otys-jobs-apply'),
                [__CLASS__, 'documentSelect'],
                static::$tabs['vacancies-apply']['slug'],
                'otys_mail_section_open_application',
                [
                    'option_name' => 'otys_option_document_template_open_apply_notify_candidate',
                    'class' => 'small-text',
                    'option_section' => 'otys_mail_section_open_application',
                    'description' => __('Email to candidate when candidate did an open application.', 'otys-jobs-apply')
                ]
            );

            // Register new setting for otys_settings page
            register_setting(self::PAGE_NAME, 'otys_option_document_template_open_apply_notify_consultant');

            // register a new field in the "otys_vacancy_detail_section" section, inside the "otys_settings" page
            add_settings_field(
                'otys_option_document_template_open_apply_notify_consultant',
                __('Candidate open application notification consultant', 'otys-jobs-apply'),
                [__CLASS__, 'documentSelect'],
                static::$tabs['vacancies-apply']['slug'],
                'otys_mail_section_open_application',
                [
                    'option_name' => 'otys_option_document_template_open_apply_notify_consultant',
                    'class' => 'small-text',
                    'option_section' => 'otys_mail_section_open_application',
                    'description' => __('Email to chosen recipient after a candidate did a open application.', 'otys-jobs-apply')
                ]
            );

            // Register new setting for otys_settings page
            register_setting(self::PAGE_NAME, 'otys_option_email_open_apply_consultant_email');

            // register a new field in the "otys_vacancy_detail_section" section, inside the "otys_settings" page
            add_settings_field(
                'otys_option_email_open_apply_consultant_email',
                __('Open application email recipient', 'otys-jobs-apply'),
                [__CLASS__, 'textField'],
                static::$tabs['vacancies-apply']['slug'],
                'otys_mail_section_open_application',
                [
                    'option_name' => 'otys_option_email_open_apply_consultant_email',
                    'class' => 'regular-text',
                    'placeholder' => sprintf(__('i.e. name@%s', 'otys-jobs-apply'), preg_replace('#^https?://#i', '', home_url())),
                    'option_section' => 'otys_mail_section_open_application',
                    'description' => __('The e-mail adres a open application notifcation gets send to.', 'otys-jobs-apply')
                ]
            );


            /*
                Vacancies section
            */
            add_settings_section(
                'otys_vacancies_detail_section',
                __('Vacancy detail', 'otys-jobs-apply'),
                function () {
                    echo '<hr size="1">';
                },
                static::$tabs['vacancies-detail']['slug']
            );

            // Register new setting and create field for the setting in the routes section of this settings page
            $languageRouteSettingName = 'otys_option_language_routes';
            register_setting(self::PAGE_NAME, $languageRouteSettingName);
            add_settings_field(
                $languageRouteSettingName,
                __('Vacancy detail url', 'otys-jobs-apply'),
                [__CLASS__, 'languageSettingHTML'],
                static::$tabs['vacancies-detail']['slug'],
                'otys_vacancies_detail_section',
                [
                    'option_name' => $languageRouteSettingName,
                    'option_section' => 'otys_vacancy_routes_section',
                    'description' => '',
                    'module_list' => [
                        'vacancy' => [
                            'name' => __('Vacancy detail', 'otys-jobs-apply'),
                            'description' => __('Choose what url you want to use for the vacancies detail page. The vacancy detail page can be the same url as the vacancy overview url. Add a vacancies detail url for each language. If your language is not available in the dropdown, make sure to install the language in your WordPress installation.', 'otys-jobs-apply')
                        ]
                    ]
                ]
            );

            // Fallback header image 
            register_setting(self::PAGE_NAME, 'otys_option_vacancies_header_falllback');
            add_settings_field(
                'otys_option_vacancies_header_falllback',
                __('Fallback header image', 'otys-jobs-apply'),
                [__CLASS__, 'image'],
                static::$tabs['vacancies-detail']['slug'],
                'otys_vacancies_detail_section',
                [
                    'option_name' => 'otys_option_vacancies_header_falllback',
                    'option_section' => 'otys_vacancy_detail_section',
                    'description' => __('The header to show when no vacancy header is set.', 'otys-jobs-apply'),
                    'wp_media' => [
                        'title' => __('Choose fallback header image', 'otys-jobs-apply'),
                        'button_text' => __('Use image', 'otys-jobs-apply'),
                        'preview_size' => 372
                    ]
                ]
            );

            // Fallback header image 
            register_setting(self::PAGE_NAME, 'otys_option_meta_description_textfield');
            add_settings_field(
                'otys_option_meta_description_textfield',
                __('Use textfield as meta description', 'otys-jobs-apply'),
                [__CLASS__, 'select'],
                static::$tabs['vacancies-detail']['slug'],
                'otys_vacancies_detail_section',
                [
                    'option_name' => 'otys_option_meta_description_textfield',
                    'option_section' => 'otys_vacancy_detail_section',
                    'description' => __('You can select a textfield from OTYS to use as meta description for your vacancy. Recommended is to either use the short description or specify a specific field in OTYS to use as meta description.', 'otys-jobs-apply'),
                    'options' => VacanciesDetailModel::getTextFieldOptions()
                ]
            );


            // Register new setting for otys_settings page
            register_setting(self::PAGE_NAME, 'otys_option_vacancies_detail_match_criteria_labels');

            // register a new field in the "otys_vacancy_detail_section" section, inside the "otys_settings" page
            add_settings_field(
                'otys_option_vacancies_detail_match_criteria_labels',
                __('Vacancy labels', 'otys-jobs-apply'),
                [__CLASS__, 'sortableCheckboxes'],
                static::$tabs['vacancies-detail']['slug'],
                'otys_vacancies_detail_section',
                [
                    'option_name' => 'otys_option_vacancies_detail_match_criteria_labels',
                    'class' => 'small-text otys-sortable-list',
                    'option_section' => 'otys_vacancy_detail_section',
                    'description' => __('Vacancy labels displayed on the vacancy detail.', 'otys-jobs-apply')
                ]
            );

            /**
             * System mails
             */
            add_settings_section(
                'otys_mail_section_sender',
                __('Mail sender', 'otys-jobs-apply'),
                function () {
                    echo '<hr size="1">';
                },
                static::$tabs['vacancies-apply']['slug']
            );

            // Register new setting for otys_settings page
            register_setting(self::PAGE_NAME, 'otys_option_system_mails_from_owner');

            // register a new field in the "otys_vacancy_detail_section" section, inside the "otys_settings" page
            add_settings_field(
                'otys_option_system_mails_from_owner',
                __('Force sending all mails from one user', 'otys-jobs-apply'),
                [__CLASS__, 'radio'],
                static::$tabs['vacancies-apply']['slug'],
                'otys_mail_section_sender',
                [
                    'option_name' => 'otys_option_system_mails_from_owner',
                    'class' => 'small-text',
                    'option_section' => 'otys_mail_section_sender',
                    'description' => __('When this setting is enabled mails will never be send from the consultant. Instead mails will be send from the mail address assigned to the API.', 'otys-jobs-apply'),
                    'options' => [0 => 'disabled', 1 => 'enabled']
                ]
            );

            /*
                Recaptcha
            */
            // Add API Section to this page
            add_settings_section(
                'otys_option_pagination_section',
                __('Pagination', 'otys-jobs-apply'),
                function () {
                    echo '<hr size="1">';
                },
                static::$tabs['vacancies-overview']['slug']
            );

            // Maximum amount of pages setting
            register_setting(self::PAGE_NAME, 'otys_option_pagination_max_pages');
            add_settings_field(
                'otys_option_pagination_max_pages',
                __('Maximum amount of pages', 'otys-jobs-apply'),
                [__CLASS__, 'select'],
                static::$tabs['vacancies-overview']['slug'],
                'otys_option_pagination_section',
                [
                    'option_name' => 'otys_option_pagination_max_pages',
                    'option_section' => 'otys_option_pagination_section',
                    'description' => __('The maximum amount of pages shown in the pagination.', 'otys-jobs-apply'),
                    'options' => [3 => 3, 5 => 5, 7 => 7, 9 => 9, 11 => 11, 13 => 13, 15 => 15]
                ]
            );

            // Prev next button behaviour setting
            register_setting(self::PAGE_NAME, 'otys_option_pagination_buttons_prev_next');
            add_settings_field(
                'otys_option_pagination_buttons_prev_next',
                __('Previous and next buttons', 'otys-jobs-apply'),
                [__CLASS__, 'radio'],
                static::$tabs['vacancies-overview']['slug'],
                'otys_option_pagination_section',
                [
                    'option_name' => 'otys_option_pagination_buttons_prev_next',
                    'option_section' => 'otys_option_pagination_section',
                    'description' => __('How to show the previous and next buttons.', 'otys-jobs-apply'),
                    'options' => [
                        __('Hide previous and next button'),
                        __('Show previous and next button as text'),
                        __('Show previous and next button as icon')
                    ]
                ]
            );

            // Next button icon 
            register_setting(self::PAGE_NAME, 'otys_option_pagination_buttons_prev_icon');
            add_settings_field(
                'otys_option_pagination_buttons_prev_icon',
                __('Previous page button', 'otys-jobs-apply'),
                [__CLASS__, 'image'],
                static::$tabs['vacancies-overview']['slug'],
                'otys_option_pagination_section',
                [
                    'option_name' => 'otys_option_pagination_buttons_prev_icon',
                    'option_section' => 'otys_option_pagination_section',
                    'description' => __('How to show the first and last buttons.', 'otys-jobs-apply'),
                    'wp_media' => [
                        'title' => __('Choose the icon to be used for the previous page button', 'otys-jobs-apply'),
                        'button_text' => __('Use as icon', 'otys-jobs-apply')
                    ]
                ]
            );

            // Prev button icon 
            register_setting(self::PAGE_NAME, 'otys_option_pagination_buttons_next_icon');
            add_settings_field(
                'otys_option_pagination_buttons_next_icon',
                __('Next page button', 'otys-jobs-apply'),
                [__CLASS__, 'image'],
                static::$tabs['vacancies-overview']['slug'],
                'otys_option_pagination_section',
                [
                    'option_name' => 'otys_option_pagination_buttons_next_icon',
                    'option_section' => 'otys_option_pagination_section',
                    'description' => __('Choose the icon to be used for the next page button.', 'otys-jobs-apply'),
                    'wp_media' => [
                        'title' => __('Choose icon to be used for the next page button', 'otys-jobs-apply'),
                        'button_text' => __('Use as icon', 'otys-jobs-apply')
                    ]
                ]
            );

            // First last button behaviour setting
            register_setting(self::PAGE_NAME, 'otys_option_pagination_buttons_first_last');
            add_settings_field(
                'otys_option_pagination_buttons_first_last',
                __('First and last buttons', 'otys-jobs-apply'),
                [__CLASS__, 'radio'],
                static::$tabs['vacancies-overview']['slug'],
                'otys_option_pagination_section',
                [
                    'option_name' => 'otys_option_pagination_buttons_first_last',
                    'option_section' => 'otys_option_pagination_section',
                    'description' => __('How to show the first and last buttons.', 'otys-jobs-apply'),
                    'options' => [
                        __('Hide first and last button button'),
                        __('Show first and last button as text'),
                        __('Show first and last button as icon')
                    ]
                ]
            );

            // First button icon
            register_setting(self::PAGE_NAME, 'otys_option_pagination_buttons_first_icon');
            add_settings_field(
                'otys_option_pagination_buttons_first_icon',
                __('First page button', 'otys-jobs-apply'),
                [__CLASS__, 'image'],
                static::$tabs['vacancies-overview']['slug'],
                'otys_option_pagination_section',
                [
                    'option_name' => 'otys_option_pagination_buttons_first_icon',
                    'option_section' => 'otys_option_pagination_section',
                    'description' => __('How to show the first and last buttons.', 'otys-jobs-apply'),
                    'wp_media' => [
                        'title' => __('Choose icon to be used for the first page button', 'otys-jobs-apply'),
                        'button_text' => __('Use as icon', 'otys-jobs-apply')
                    ]
                ]
            );

            // Last button icon 
            register_setting(self::PAGE_NAME, 'otys_option_pagination_buttons_last_icon');
            add_settings_field(
                'otys_option_pagination_buttons_last_icon',
                __('Last page button', 'otys-jobs-apply'),
                [__CLASS__, 'image'],
                static::$tabs['vacancies-overview']['slug'],
                'otys_option_pagination_section',
                [
                    'option_name' => 'otys_option_pagination_buttons_last_icon',
                    'option_section' => 'otys_option_pagination_section',
                    'description' => __('How to show the first and last buttons.', 'otys-jobs-apply'),
                    'wp_media' => [
                        'title' => __('Choose icon to be used for the last page button', 'otys-jobs-apply'),
                        'button_text' => __('Use as icon', 'otys-jobs-apply')
                    ]
                ]
            );

            /**
             * Candidate login TAB
             */
            add_settings_section(
                'otys_candidate_authentication_section',
                __('Candidate authentication settings', 'otys-jobs-apply'),
                function () {
                    echo '<hr size="1">';
                },
                static::$tabs['candidate_auth']['slug']
            );


            // Register new setting for otys_settings page
            register_setting(self::PAGE_NAME, 'otys_option_show_candidate_login_link_at_form');

            add_settings_field(
                'otys_option_show_candidate_login_link_at_form',
                __('Show candidate login link at application forms', 'otys-jobs-apply'),
                [__CLASS__, 'select'],
                static::$tabs['candidate_auth']['slug'],
                'otys_candidate_authentication_section',
                [
                    'option_name' => 'otys_option_show_candidate_login_link_at_form',
                    'option_section' => 'otys_candidate_authentication_section',
                    'description' => __('A login link will be shown at the application form.', 'otys-jobs-apply'),
                    'options' => ['false' => __('No', 'otys'), 'true' => __('Yes', 'otys')]
                ]
            );

            // Register new setting for otys_settings page
            register_setting(self::PAGE_NAME, 'otys_option_use_known_candidate_questionset');

            add_settings_field(
                'otys_option_use_known_candidate_questionset',
                __('Use known candidate questionset for logged in candidates', 'otys-jobs-apply'),
                [__CLASS__, 'select'],
                static::$tabs['candidate_auth']['slug'],
                'otys_candidate_authentication_section',
                [
                    'option_name' => 'otys_option_use_known_candidate_questionset',
                    'option_section' => 'otys_candidate_authentication_section',
                    'description' => __('Use the known candidate questionset for logged in users', 'otys-jobs-apply'),
                    'options' => ['false' => __('No', 'otys'), 'true' => __('Yes', 'otys')]
                ]
            );

            /**
             * Forgot password mail
             */

            // Register new setting for otys_settings page
            register_setting(self::PAGE_NAME, 'otys_option_document_template_forgot_password');

            // register a new field in the "otys_vacancy_detail_section" section, inside the "otys_settings" page
            add_settings_field(
                'otys_option_document_template_forgot_password',
                __('Forgot password e-mail', 'otys-jobs-apply'),
                [__CLASS__, 'documentSelect'],
                static::$tabs['candidate_auth']['slug'],
                'otys_candidate_authentication_section',
                [
                    'option_name' => 'otys_option_document_template_forgot_password',
                    'class' => 'small-text',
                    'option_section' => 'otys_mail_section_candidate_authentication',
                    'description' => __('To enable forgot password choose the document which is used as e-mail to send the newly generated password to the candidate. Also make sure to do the url settings and provide a forgot password url.', 'otys-jobs-apply')
                ]
            );

            // Register new setting and create field for the setting in the candidate login section of this settings page
            $candidateLoginRouteSettingName = 'otys_option_candidate_authentication_routes';
            register_setting(self::PAGE_NAME, $candidateLoginRouteSettingName);
            add_settings_field(
                $candidateLoginRouteSettingName,
                __('URL Settings', 'otys-jobs-apply'),
                [__CLASS__, 'languageSettingHTML'],
                static::$tabs['candidate_auth']['slug'],
                'otys_candidate_authentication_section',
                [
                    'option_name' => $candidateLoginRouteSettingName,
                    'option_section' => 'otys_candidate_authentication_section',
                    'description' => '',
                    'module_list' => [
                        'candidate_login' => [
                            'name' => __('Candidate login', 'otys-jobs-apply'),
                            'description' => __('Choose what url you want to use for the candidate login. Note that you\'ll have to create page by yourself and that the [otys-candidate-login] shortcode should be present on this page.', 'otys-jobs-apply')
                        ],
                        'candidate_logout' => [
                            'name' => __('Candidate logout', 'otys-jobs-apply'),
                            'description' => __('Choose what url you want to use for the candidate logout. Logout will only be a link, the candidate will be automaticly redirected.', 'otys-jobs-apply')
                        ],
                        'candidate_portal' => [
                            'name' => __('Candidate portal', 'otys-jobs-apply'),
                            'description' => __('Choose what url you want to use for the candidate portal. The candidate will be redirected to the otys candidate portal using this url.', 'otys-jobs-apply')
                        ],
                        'candidate_forgot_password' => [
                            'name' => __('Candidate forgot password', 'otys-jobs-apply'),
                            'description' => __('Choose what url you want to for the forgot password page. Note that you\'ll have to create the page by yourself and that the [otys-candidate-forgot-password] shotcode should be present on this page and that you need to specify which document you want to use for the forgot password mail. By default a forgot password document has been made which can be used.', 'otys-jobs-apply')
                        ]
                    ]
                ]
            );
        }
    }

    /**
     * Radio callback
     * @return void
     * @since 1.0.0
     */
    public static function websiteRadio($args)
    {
        // Default values
        $args = wp_parse_args(
            $args,
            array(
                'class' => 'regular-text'
            )
        );

        $args['options'] = OtysApi::getWebsites();

        ksort($args['options']);

        $templatePath = OTYS_PLUGIN_ADMIN_TEMPLATE_URL . '/wp-settings-api/cb-radio.php';

        load_template($templatePath, false, $args);
    }

    /**
     * Recaptcha callback
     */
    public static function selectScoreRecaptcha($args)
    {
        $option = get_option($args['option_name']);

        $args = wp_parse_args(
            $args,
            [
                'class' => 'regular-text'
            ]
        );

        echo '<select name="' . esc_attr($args['option_name']) . '">';
        for ($i = 0.1; $i < 1; $i = $i + 0.1) {
            echo ' <option value="' . esc_attr($i) . '" ' . (isset($option) && $option == $i ? esc_attr('selected') : '') . '>' . esc_html($i) . '</option>';
        }

        echo '</select>';

        if (isset($args['description'])) {
            echo '<p class="description">' . wp_kses_post($args['description']) . '</p>';
        }
    }

    /**
     * Api key callback
     * @return void
     * @since 1.2.11
     */
    public static function apiKeyField($args)
    {
        $option = get_option($args['option_name']);

        $args = wp_parse_args(
            $args,
            array(
                'class' => 'regular-text'
            )
        );

        $checkApi = OtysApi::check();
        $apiUser = OtysApi::getApiUser();

        if ($checkApi) {
            echo '<a onclick="return confirm(\'' . __('Are you sure you want to remove the API Key? You will loose plugin related settings and all plugin related cache will be removed.', 'otys-jobs-apply') . '\')" class="button" href="' . admin_url('admin.php?page=' . static::PAGE_NAME) . '&action=remove-api-key">' . __('Remove API key', 'otys-jobs-apply') . '</a>';

            if (($apiUser = otysApi::getApiUser()) && isset($apiUser['client'])) {
                echo '<p class="otys-correct-api-key">' . sprintf(__('Connected to %s', 'otys-jobs-apply'), $apiUser['client']) . '</p>';
            }
        }

        echo '
        <input
            class="' . esc_attr($args['class']) . '"
            name="' . esc_attr($args['option_name']) . '"
            type="' . (($checkApi) ? 'hidden' : 'text') . '"
            ' . ((isset($option)) ? 'value="' . esc_attr($option) . '"' : '') . '
        />';

        if ($checkApi) {
            $apiUser = OtysApi::getApiUser();

            if (!isset($apiUser['partnerId']) || $apiUser['partnerId'] != '78') {
                echo '<div class="error">' . __('You are using an incorrect API key for the OTYS WordPress plugin. Please contact OTYS to resolve this to avoid future issues.', 'otys-jobs-apply') . '</div>';
            }
        }

        if (!$checkApi) {
            if (isset($args['description'])) {
                echo '<p class="description">' . wp_kses_post($args['description']) . '</p>';
            }
        }
    }
}
