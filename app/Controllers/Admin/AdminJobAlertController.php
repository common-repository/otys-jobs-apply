<?php

namespace Otys\OtysPlugin\Controllers\Admin;

use Otys\OtysPlugin\Includes\Core\Cache;
use Otys\OtysPlugin\Includes\Core\Hooks;
use Otys\OtysPlugin\Models\Shortcodes\JobAlertModel;

class AdminJobAlertController extends AdminBaseController
{
    /**
     * The pagename
     * Pagename gets used in every setting registration
     * @since 1.0.0
     */
    private const PAGE_NAME = 'otys_jobalert';

    /**
     * The locked fields which will always be shown
     *
     * @var array
     */
    private static $lockedFields = [
        'email',
        'period'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Run functions before class initialisation.
     * function gets called when registering controller at routes.
     *
     * @return void
     * @since 1.0.0
     */
    public static function beforeInit(): void
    {
        // Initialise the settings on page load
        Hooks::addAction('admin_init', __CLASS__, 'initSettings', 10, 0);
        Hooks::addAction('pre_update_option_otys_option_jobalert_filters', __CLASS__, 'beforeSaveFilters', 10, 2);
        Hooks::addAction('pre_update_option_otys_option_jobalert_filters', __CLASS__, 'updateInteractionForm', 11, 2);
    }

    /**
     * Init settings for admin settings page
     *
     * @return void
     * @since 1.0.0
     */
    public static function initSettings(): void
    {
        // Add API Section to this page
        add_settings_section(
            'otys_option_jobalert_section',
            __('Job alert management', 'otys-jobs-apply'),
            function () {
                echo '<hr size="1">';
                echo __('Below you can configure the JobAlert. You able to choose which questions a person has to fill in when creating a job alert.', 'otys-jobs-apply');
            },
            self::PAGE_NAME
        );
        
        register_setting(self::PAGE_NAME, 'otys_option_document_template_confirm_jobalert');

        add_settings_field(
            'otys_option_document_template_confirm_jobalert',
            __('Document to use for the Job Alert confirmation email', 'otys-jobs-apply'),
            [__CLASS__, 'documentSelect'],
            self::PAGE_NAME,
            'otys_option_jobalert_section',
            [
                'option_name' => 'otys_option_document_template_confirm_jobalert',
                'class' => 'small-text',
                'option_section' => 'otys_option_jobalert_section',
                'description' => __('Choose which document you want to use for the Job Alert confirmation email. By default we created a corresponding template automaticly which you can use, this document is called WordPress | JobAlert (default).', 'otys-jobs-apply')
            ]
        );


        // Register new setting for otys_settings page
        register_setting(self::PAGE_NAME, 'otys_option_jobalert_filters');

        add_settings_field(
            'otys_option_jobalert_filters',
            __('Questions which the candidate has to fill in', 'otys-jobs-apply'),
            [__CLASS__, 'jobAlertFieldSelect'],
            self::PAGE_NAME,
            'otys_option_jobalert_section',
            [
                'option_name' => 'otys_option_jobalert_filters',
                'class' => 'small-text otys-sortable-list',
                'option_section' => 'otys_option_jobalert_section',
                'description' => __('Choose which questions get asked in which order for the job alert.', 'otys-jobs-apply')
            ]
        );
    }

    /**
     * Before save filters (prio 10)
     *
     * @param array $new
     * @param array $old
     * @return array
     */
    public static function beforeSaveFilters($new, $old): array
    {
        // Make sure that the locked fields are always set to true
        foreach (static::$lockedFields as $fieldName) {
            $new[$fieldName]['show'] = true;
        }

        return $new;
    }

    /**
     * Update the interaction form based on the new settings (prio 11)
     *
     * @param array $new
     * @param array $old
     * @return void
     */
    public static function updateInteractionForm($new, $old): array
    {
        $updatedInfo = [];

        foreach ($new as $key => $value) {
            if (!isset($old[$key]) || $value['question'] !== $old[$key]['question']) {
                $updatedInfo[$key] = $value;
            }
        }

        // If no fields are updated, return the new settings
        if (empty($updatedInfo)) {
            return $new;
        }

        // If there is updated info we need to update the interaction form used by the job alert
        $jobAlertModel = new JobAlertModel();

        $jobAlertFieldsData = $jobAlertModel->getFields();

        $updateData = [];

        foreach ($jobAlertFieldsData as $field) {
            if (isset($updatedInfo[$field['wsField']])) {
                if (isset($updatedInfo[$field['wsField']]['question'])) {
                    $updateData[$field['uid']]['name'] = $updatedInfo[$field['wsField']]['question'];
                }
            }
        }

        $jobAlertModel->updateInteractionForm($updateData);

        Cache::delete('freeinteractionservice', false);

        return $new;
    }

    /**
     * Admin webhooks page
     *
     * @return void
     * @since 1.0.0
     */
    public function index()
    {
        global $wp;

        $this->parseArgs('is_enabled', $this->model->isEnabled());
    }

    /**
     * Matchcriteria select callback
     * @since 1.2.11
     */
    public static function jobAlertFieldSelect($args)
    {
        $settingValue = get_option($args['option_name'], []);
        $settingOrder = array_keys($settingValue);

        // Default values
        $args = wp_parse_args(
            $args,
            array(
                'class' => 'regular-text'
            )
        );

        $jobAlertModel = new JobAlertModel();

        $jobAlertFieldsData = $jobAlertModel->getFields();

        $jobAlertFields = [];

        foreach ($jobAlertFieldsData as $field) {
            $jobAlertFields[$field['wsField']] = $field;
        }

        $fieldsToFilter = [
            'cmsLanguage',
            'class',
            'siteId',
            'name'
        ];

        $jobAlertFields = array_map(function($field) {
            // Default return field
            $returnField = [
                'id' => $field['uid'],
                'wsField' => $field['wsField'],
                'question' => $field['question'],
                'locked' => false,
                'title' => '',
                'default' => false
            ];

            // Check if the field is locked
            if (in_array($field['wsField'], static::$lockedFields)) {
                $returnField['locked'] = true;
                $returnField['default'] = true;
                $returnField['title'] = __('This field is required and can therefore not be disabled', 'otys-jobs-apply');
            }

            if (isset($field['answers']) && empty($field['answers']) && $field['typeUi'] === 'Criteria') {
                $returnField['locked'] = true;
                $returnField['default'] = false;
                $returnField['title'] = __('This criteria does not have any options and can therefore not be enabled', 'otys-jobs-apply');   
            }

            return $returnField;
        }, $jobAlertFields);

        // We'll remove the fields that we don't want to be filled by the user
        $jobAlertFields = array_filter($jobAlertFields, function($field) use ($fieldsToFilter) {
            return !in_array($field['wsField'], $fieldsToFilter);
        });

        // Sort Job Alert fields based on the order
        $jobAlertFieldOrder = array_keys($jobAlertFields);

        // Sort the fields based on the order
        uksort($jobAlertFields, function($key1, $key2) use ($settingOrder, $jobAlertFieldOrder) {
            $index1 = array_search($key1, $settingOrder);
            $index2 = array_search($key2, $settingOrder);

            if ($index1 === false) {
                $index1 = array_search($key1, $jobAlertFieldOrder);
            }

            if ($index2 === false) {
                $index2 = array_search($key2, $jobAlertFieldOrder);
            }

            return $index1 - $index2;
        });

        // Assign the fields to the options
        $args['options'] = $jobAlertFields;

        $templatePath = OTYS_PLUGIN_ADMIN_TEMPLATE_URL . '/wp-settings-api/cb-jobalert-fields-select.php';

        load_template($templatePath, false, $args);
    }
}
