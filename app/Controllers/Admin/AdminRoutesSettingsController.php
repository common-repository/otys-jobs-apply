<?php

namespace Otys\OtysPlugin\Controllers\Admin;

use Otys\OtysPlugin\Includes\Core\Hooks;

class AdminRoutesSettingsController extends AdminBaseController
{
    /**
     * The pagename
     * Pagename gets used in every setting registration
     */
    private const PAGE_NAME = 'otys_routes_settings';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Run functions before class initialisation.
     * function gets called when registering controller at routes.
     *
     * @return void
     */
    public static function beforeInit(): void
    {
        // Initialise the settings on page load
        Hooks::addAction('admin_init', __CLASS__, 'initSettings', 10, 0);

        Hooks::addFilter("pre_update_option_otys_option_language_routes", __CLASS__, 'beforeSave', 10, 3);

        // Set option default values on plugin activation
        register_activation_hook(OTYS_PLUGIN, [__CLASS__, 'defaultValues']);
    }

    /**
     * Pre update option callback
     *
     * @param mixed $newValue
     * @param string $oldValue
     * @param mixed $optionName
     * @return void
     */
    public static function beforeSave($newValue, $oldValue, $optionName)
    {
        /**
         * For otys_option_language_routes save the slug of the page aswell
         */
        if ($oldValue !== false && $optionName === 'otys_option_language_routes' && is_array($newValue)) {
            foreach ($newValue as $moduleName => $moduleValues) {
                foreach ($moduleValues as $key => $moduleValue) {
                  
                    $page = get_post($moduleValue['page']);

                    $newValue[$moduleName][$key]['slug'] = $page->post_name;
                }
            }
        }

        return $newValue;
    }

    /**
     * Set Default values for settings
     *
     * @return void
     */
    public static function defaultValues(): void
    {
        $defaultValues =  [];

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

        $moduleList = [
            'vacancies' => __('Vacancies overview', 'otys_ows'),
            'vacancy' => __('Vacancy detail', 'otys_ows')
        ];

        $args = wp_parse_args(
            $args,
            array(
                'class' => 'regular-text',
                'module_list' => $moduleList,
                'value' => $value
            )
        );

        $templatePath = OTYS_PLUGIN_ADMIN_TEMPLATE_URL . '/admin-routes-settings/admin-routes-input.php';

        load_template($templatePath, true, $args);
    }
}
