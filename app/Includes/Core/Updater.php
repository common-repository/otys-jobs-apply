<?php

namespace Otys\OtysPlugin\Includes\Core;

use Otys\OtysPlugin\Helpers\SettingHelper;
use Otys\OtysPlugin\Includes\Core\Logs;
use Otys\OtysPlugin\Includes\OtysApi;
use Otys\OtysPlugin\Models\Admin\AdminSettingsModel;
use Otys\OtysPlugin\Models\Admin\AdminWebhooksModel;
use Otys\OtysPlugin\Includes\Core\Cache;
use Otys\OtysPlugin\Includes\Core\Routes;
use Otys\OtysPlugin\Includes\Core\AdminMessages;

/**
 * Manages plugin updates
 *
 * @since 1.2.6
 */
class Updater extends Base
{
    /**
     * Holds update path
     *
     * @since 1.2.6
     * @var array
     */
    protected array $updates = [];

    /**
     * Holds the current version of the plugin
     *
     * @since 1.2.6
     * @var string
     */
    protected string $oldVersion;

    /**
     * Holds the new version to update to
     *
     * @since 1.2.6
     * @var string
     */
    protected string $newVersion;

    /**
     * Holds the end point for the update tracker
     *
     * @since 1.2.6
     * @var string
     */
    static protected $entryHandler = 'https://wpi.otys.nl/api/';

    /**
     * Constructor
     *
     * @since 1.2.6
     */
    public function __construct()
    {
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        $pluginData = get_plugin_data(OTYS_PLUGIN_FILE_URL);

        $this->oldVersion = get_option('otys_plugin_version', false);
        $this->newVersion = $pluginData['Version'];

        $this->updates = [
            '1.2.6' => function () {
                /**
                 * Update match criteria filter data. Changes are made so
                 * the data saves supports ordering of the filters in the
                 * admin panel.
                 */
                $options = [
                    'otys_option_vacancies_filters_match_criteria',
                    'otys_option_vacancies_list_match_criteria_labels',
                    'otys_option_vacancies_detail_match_criteria_labels'
                ];

                // Loop through the option that should be updated
                foreach ($options as $optionName => $option) {
                    if ($optionValues = get_option($option, false)) {
                        $firstKey = array_key_first($optionValues);

                        // If key is an integer the data is old and should be up\dated
                        if (is_int($firstKey)) {
                            // Build a new updated array with the value as key and value true
                            $updatedOptionValue = [];
                            foreach ($optionValues as $optionValueKey => $optionValue) {
                                $updatedOptionValue[$optionValue] = "true";
                            }

                            // Update option
                            if (!update_option($option, $updatedOptionValue)) {
                                return false;
                            }
                        }
                    }
                }

                return true;
            },
            '1.2.11' => function () {
                /**
                 * Register new webhooks
                 */
                AdminWebhooksModel::deleteWebhooks();
                AdminWebhooksModel::registerWebhooks();

                /**
                 * Update match criteria filter data. Changes are made so
                 * the data saves supports ordering of the filters in the
                 * admin panel.
                 */
                $options = [
                    'otys_option_vacancies_filters_match_criteria',
                    'otys_option_vacancies_list_match_criteria_labels',
                    'otys_option_vacancies_detail_match_criteria_labels'
                ];

                // Loop through the option that should be updated
                foreach ($options as $optionName => $option) {
                    if ($optionValues = get_option($option, false)) {
                        $firstKey = array_key_first($optionValues);

                        // If key is an integer the data is old and should be up\dated
                        if (is_int($firstKey)) {
                            // Build a new updated array with the value as key and value true
                            $updatedOptionValue = [];
                            foreach ($optionValues as $optionValueKey => $optionValue) {
                                $updatedOptionValue[$optionValue] = true;
                            }

                            // Update option
                            if (!update_option($option, $updatedOptionValue)) {
                                return false;
                            }
                        }
                    }
                }

                return true;
            },
            '1.2.13' => function () {
                /**
                 * Register new webhooks
                 */
                AdminWebhooksModel::deleteWebhooks();
                AdminWebhooksModel::registerWebhooks();
            },
            '1.3.0' => function () {
                /**
                 * Updating variable if otys-vacancies-search.php is custom in the theme folder
                 */
                $themeFolder = get_template_directory() . DIRECTORY_SEPARATOR  . 'otys-jobs-apply';

                $searchFile = $themeFolder . DIRECTORY_SEPARATOR . 'vacancies' . DIRECTORY_SEPARATOR . 'vacancies-search.php';
                $searchIsCustom = file_exists($searchFile);

                // If the search file is custom 
                if ($searchIsCustom) {
                    $str=file_get_contents($searchFile);

                    $str=str_replace('matchCriteriaList', 'selectedParameters', $str);

                    //write the entire string
                    file_put_contents($searchFile, $str);
                }
            },
            '1.3.2' => function () {
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
            },
            '1.3.13' => function () {
                /**
                 * Remove all cache since a bug with caching has been fixed and we need to make
                 * sure all cache is the most recent again.
                 */
                Cache::deleteAll();
            },
            '1.3.14' => function() {
                AdminSettingsModel::createDefaultDocuments();
                
                Cache::deleteAll();
            },
            '1.3.16' => function () {
                Logs::deleteLogs();
            },
            '2.0.14' => function() {
                // Set default filter sorting option
                if (get_option('otys_option_vacancies_options_sorting') === false) {
                    update_option('otys_option_vacancies_options_sorting', 'alphabetic');
                }

                Cache::deleteAll();
            },
            '2.0.22' => function() {
                Cache::deleteAll();
            },
            '2.0.29' => function() {
                Cache::deleteAll();

                Routes::customSlugIsActive();
            },
            '2.0.33' => function() {
                Cache::deleteAll();
            },
            '2.0.35' => function () {
                Cache::deleteAll();
            },
            '2.0.36' => function () {
                Cache::deleteAll();
            },
            '2.0.37' => function () {
                Cache::deleteAll();
            },
            '2.0.42' => function () {
                Cache::deleteAll();
                AdminWebhooksModel::deleteWebhooks();
                AdminWebhooksModel::registerWebhooks();
            },
            '2.0.43' => function () {
                Cache::deleteAll();
            },
            '2.0.44' => function () {
                Cache::deleteAll();
            },
            '2.0.45' => function () {
                Cache::deleteAll();
            },
            '2.0.46' => function () {
                Cache::deleteAll();
            },
            '2.0.47' => function () {
                if (get_option('otys_option_website', 0) == 0) {
                    update_option('otys_option_website', 1);
                }

                AdminSettingsModel::createDefaultDocuments();
                Cache::deleteAll();
            }
        ];
    }

    /**
     * Check function
     *
     * @since 1.2.6
     * @return void
     */
    public function check()
    {
        // Check if we should update the current version
        if (version_compare($this->oldVersion, $this->newVersion, '<')) {
            $updaterResponse = $this->doUpdates();

            // Check if webhooks are up to date
            AdminWebhooksModel::checkWebhooks();

            Updater::entryActivate(get_option('otys_option_api_key'));
            
            if (is_wp_error($updaterResponse)) {
                AdminMessages::add($updaterResponse->get_error_message(), 'error');
            } else {
                update_option('otys_plugin_version', $this->newVersion, true);
            }
        }
        
        if (!get_transient('otys_updater')) {
            set_transient('otys_updater', date('Y-m-d h:i:s'), 86400);

            Updater::entryActivate(get_option('otys_option_api_key'));
        }
    }

    /**
     * doUpdates
     * Runs all updates accordingly in the correct order
     *
     * @since 1.2.6
     * @param string $oldVersion
     * @param string $newVersion
     * @return mixed
     */
    public function doUpdates()
    {
        // Loop through all version updates
        foreach ($this->updates as $version => $update) {
            // Check if the current update should run
            if (version_compare($version, $this->oldVersion, '>') && version_compare($version, $this->newVersion, '<=')) {
                // Run update
                if (is_callable($update)) {
                    $updateResponse = call_user_func($update);

                    // Check if a error occured while updating
                    if ($updateResponse === false) {
                        $supportLink = '<a href="https://www.otys.nl/support" target="_blank">' . __('How to contact support?', 'otys-jobs-apply') . '</a>';
                        return new \WP_Error('update_failed', sprintf(__('Failed updating version %s to version %s. The updater failed at version %s. Please create a OTYS support ticket and provide this information in the ticket. %s', "otys-jobs-apply"), $this->oldVersion, $this->newVersion, $version, $supportLink));
                    }

                    update_option('otys_plugin_version', $version, true);
                }
            }
        }

        return true;
    }

    /**
     * Get custom files
     *
     * @since 1.3.0
     * @param string $dir
     * @param array $results
     * @return array
     */
    public static function getCustomfiles(string $dir = '', &$results = []): array
    {
        $themeFolder = get_template_directory() . DIRECTORY_SEPARATOR  . 'otys-jobs-apply';
        $dir = ($dir === '') ? $themeFolder : $dir;

        if (!is_dir($dir)) {
            return [];
        }

        $files = scandir($dir);

        foreach ($files as $key => $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);

            if (!is_dir($path)) {
                $results[] = substr($path, strpos($path, 'otys-jobs-apply') + 16);
            } else if ($value != "." && $value != "..") {
                self::getCustomfiles($path, $results);
                //$results[] = substr($path, strpos($path, 'otys-jobs-apply') + 16);
            }
        }
    
        return $results;
    }

    /**
     * Register Instance with OTYS
     *
     * @since 1.3.0
     * @param string $apikey
     * @return void
     */
    public static function entryActivate(string $apiKey)
    {
        global $wp_version;
        $pluginData = get_plugin_data(OTYS_PLUGIN_FILE_URL);

        if (!is_wp_error(OtysApi::login($apiKey))) {
            $apiUser = OtysApi::getApiUser();
           
            if (!empty($apiUser)) {
                $post = wp_remote_post(
                    static::$entryHandler . 'entries/activate',
                    [
                        'method' => 'POST',
                        'headers' => [
                            'Accept' => 'application/json'
                        ],
                        'body' => [
                            'otys_client_name' => $apiUser['client'],
                            'otys_client_id' => $apiUser['clientId'],
                            'otys_site_id' => (string) SettingHelper::getSiteId(),
                            'site_url' => home_url(),
                            'plugin_version' => (string) $pluginData['Version'],
                            'php_version' => (string) phpversion(),
                            'wp_version' => (string) $wp_version,
                            'last_digits_api_key' => substr($apiKey, -3),
                            'custom_view_files' => self::getCustomfiles(),
                            'production' => get_option('otys_option_is_production_website', 0)
                        ]
                    ]
                );
            }
        }
    }

    /**
     * Update a insights entry
     *
     * @since 1.3.0
     * @param string $apiKey
     * @param array $data
     * @return void
     */
    public static function entryUpdate(string $apiKey, array $data): void
    {
        if (!is_wp_error(OtysApi::login($apiKey))) {
            $apiUser = OtysApi::getApiUser();

            if (!empty($apiUser)) {
                $options = [
                    'method' => 'PATCH',
                    'headers' => [
                        'Accept' => 'application/json'
                    ],
                    'body' => [
                        'otys_client_id' => $apiUser['clientId'],
                        'site_url' => home_url(),
                        'instance' => $data
                    ],
                    'blocking' => false
                ];

                wp_remote_post(
                    static::$entryHandler . 'entries/update-instance',
                    $options
                );
            }
        }
    }

    /**
     * Deactivate insights instance
     *
     * @since 2.0.0
     * @return void
     */
    public static function entryDeactivate($apiKey)
    {

        if (!is_wp_error(OtysApi::login($apiKey))) {
            $apiUser = OtysApi::getApiUser();

            if (!empty($apiUser)) {
                wp_remote_post(
                    static::$entryHandler . 'entries/deactivate',
                    [
                        'method' => 'PATCH',
                        'headers' => [
                            'Accept' => 'application/json'
                        ],
                        'body' => [
                            'otys_client_id' => $apiUser['clientId'],
                            'site_url' => home_url()
                        ]
                    ]
                );
            }
        }
    }
}
