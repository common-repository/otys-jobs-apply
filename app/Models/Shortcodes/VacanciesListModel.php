<?php

namespace Otys\OtysPlugin\Models\Shortcodes;

use Otys\OtysPlugin\Helpers\SettingHelper;
use Otys\OtysPlugin\Includes\Core\Routes;
use Otys\OtysPlugin\Includes\OtysApi;
use Otys\OtysPlugin\Models\ConsultantModel;
use Otys\OtysPlugin\Models\Shortcodes\ShortcodeBaseModel;
use Otys\OtysPlugin\Models\Shortcodes\VacanciesFiltersModel;
use Otys\OtysPlugin\Models\Shortcodes\VacanciesGeoSearchModel;
use Otys\OtysPlugin\Models\RelationModel;

use Otys\OtysPlugin\Models\VacanciesDetailModel;
use WP_Error;

/**
 * This model is used by the otys-vacancies-list shortcode
 */
class VacanciesListModel extends ShortcodeBaseModel
{
    /**
     * Summary of list
     * @var 
     */
    public array $list = [];

    /**
     * The limit for the amount of pagenumbers
     *
     * @var integer
     */
    private static $maxPageNumbers = 500;

    /**
     * Get vacancies list
     *
     * @param array $args       Arguments
     * @param array $owsArgs    directly modify the OWS call
     * @return array|WP_Error
     */
    public static function get(array $arguments = [], array $atts = [], array $owsArgs = [])
    {
        $argsDefault = [
            'page-number' => 1,
            'perpage' => 10,
            'cache' => true,
            'search' => '',
            'premium' => null,
            'pc' => '',
            'pcm' => 30,
            'plo' => 'EN',
            'limit' => false,
            'owner' => '',
            'exclude' => ''
        ];

        // Get website id
        $website = SettingHelper::getSiteId();

        // Get filters
        $userFilterSlugs = VacanciesFiltersModel::getSlugs(false);
        
        $allFilterSlugs = VacanciesFiltersModel::getSlugs(true);

        // Merge arguments & atts
        $args = array_replace_recursive($arguments, $atts);

        // Merge defaults in args
        $args = array_replace_recursive($argsDefault, $args);

        // Get filters that are parsed via arguments
        $argumentFilters = VacanciesFiltersModel::getFiltersArgs($arguments);

        // Count amount of argumnet filters
        $argumentFiltersCount = 0;

        foreach ($argumentFilters as $filter) {
            $argumentFiltersCount += count($filter);
        }

        // Disable cache for more than 1 filter;
        if ($argumentFiltersCount > 1) {
            $args['cache'] = false;
        }

        // Disable cache when page number is higher than 3
        if (($args['page-number'] ?? 0) > 3) {
            $args['cache'] = false;
        }

        // Disable cache when page number is higher than 1 and there are filters
        if (($args['page-number'] ?? 0) > 1 && !empty($argumentFiltersCount)) {
            $args['cache'] = false;
        }
   
        // Vacancy list based on owner
        if (is_email($args['owner'])) {
            // Check if shortlist should consider the owner of a vacancy as filter
            $owner = ConsultantModel::getByEmail($args['owner']);

            if (!is_wp_error($owner)) {
                $owsArgs['search']['ACTONOMY']['DATA']['owner']['value']['required'] = [$owner['uid']];
            }
        }

        // Exclude vacancy from result
        if ($args['exclude'] !== '') {
            $owsArgs['condition'] = [
                'type' => 'AND',
                'items' => [
                    [
                        'type' => 'COND',
                        'field' => 'uid',
                        'op' => 'NE',
                        'param' => $args['exclude'],
                    ]
                ],
            ];
        }

        // Proximity search
        if (
            $args['pc'] !== ''
        ) {
            $owsArgs["search"]["ACTONOMY"]["DATA"]['proximity'] = [
                'options' => [
                    'required' => true
                ],
                'value' => [
                    'countryCode' => $args['plo'],
                    'zipCode' => $args['pc'],
                    'preferredDistance' => 0,
                    'maxDistance' => $args['pcm']
                ]
            ];
        }

        // Filters
        $userFilters = array_filter($arguments, function ($key) use ($userFilterSlugs) {
            return isset($userFilterSlugs[$key]);
        }, ARRAY_FILTER_USE_KEY);

        $attributeFilters = array_filter($atts, function ($key) use ($allFilterSlugs) {
            return isset($allFilterSlugs[$key]);
        }, ARRAY_FILTER_USE_KEY);

        $usedFilters = array_merge($userFilters, $attributeFilters);

        if (!empty($args)) {
            if (!is_wp_error($userFilterSlugs)) {
                foreach ($usedFilters as $usedFilterSlug => $usedFilterOptions) {
                    if (array_key_exists($usedFilterSlug, $allFilterSlugs)) {
                        $filterValues = $allFilterSlugs[$usedFilterSlug];

                        $owsArgsOptions = [];

                        $usedFilterOptions = VacanciesFiltersModel::optionsAsArray($usedFilterOptions);

                        // Find arg filter option slug in filter value options if found add to actonomy ows args
                        foreach ($usedFilterOptions as $usedFilterOptionSlug) {
                            if (array_key_exists($usedFilterOptionSlug, $filterValues['options'])) {
                                $owsArgsOptions = $filterValues['options'][$usedFilterOptionSlug]['id'];
                            }

                            if (strpos($filterValues['id'], 'C_') !== false) {
                                $owsArgs["search"]["ACTONOMY"]["DATA"]["matchcriteria"][$filterValues['id']]['options']['required'] = true;
                                $owsArgs["search"]["ACTONOMY"]["DATA"]["matchcriteria"][$filterValues['id']]['value']['required'][] = $owsArgsOptions;
                            } elseif (strpos($filterValues['id'], 'category') !== false) {
                                $owsArgs["search"]["ACTONOMY"]["DATA"]["category"]['options']['required'] = true;
                                $owsArgs["search"]["ACTONOMY"]["DATA"]["category"]['value']['required'][] = $owsArgsOptions;
                            }
                        }
                    }
                }
            }
        }

        // Keyword search
        if ($args['search'] !== '' && is_string($args['search'])) {
            // Add keyword search to our vacancies args
            $owsArgs['search']['ACTONOMY']['DATA']['keywords'] = [
                'options' => [
                    'enableExpansion' => false,
                    'required' => true,
                    'searchList' => [
                        'title',
                        'chapo',
                        'description',
                        'external_reference',
                        'profile_culture',
                        'refs',
                        'salary',
                        'all_extra_fields'
                    ],
                    'searchMode' => 'one'
                ],
                'value' => "{$args['search']}"
            ];

            $args['cache'] = false;
        }

        // Premium vacancies
        if ($args['premium'] !== null) {
            $owsArgs['search']['ACTONOMY']['DATA']['premium'] = [
                'options' => [
                    'required' => true,
                    'persistent' => false
                ],
                'value' => $args['premium'] == "true" ? '1' : '0'
            ];
            
        }

        // Init vars
        $offset = is_int($args['perpage']) ? ($args['page-number'] - 1) * $args['perpage'] : 0;

        // Date vars
        $publishStartTo = new \DateTime();
        $publishStartToFormatted = $publishStartTo->format('Y-m-d\T22:00:00.000\Z');

        $publishEndFrom = new \DateTime();
        $publishEndFrom->modify("-1 day");
        $publishEndToFormatted = $publishEndFrom->format('Y-m-d\T22:00:00.000\Z');

        // Merge arguments with default arguments
        $options = array_replace_recursive([
            'search' => [
                'ACTONOMY' => [
                    'DATA' => [
                        'publiceer' => [
                            'options' => [
                                'required' => true,
                                'persistent' => false
                            ],
                            'value' => '1'
                        ],
                        'public_publish_date' => [
                            'class' => 'CustomizedDateRangeField',
                            'value' => [
                                'from' => null,
                                'to' => $publishStartToFormatted,
                                'periodType' => 'custom',
                                'periodUnit' => 'month',
                                'period' => 1,
                                'operation' => 'GE',
                                'fromDp' => null,
                                'toDp' => $publishStartToFormatted
                            ]
                        ],
                        'public_end_date' => [
                            'class' => 'CustomizedDateRangeField',
                            'value' => [
                                'from' => $publishEndToFormatted,
                                'to' => null,
                                'periodType' => 'custom',
                                'periodUnit' => 'month',
                                'period' => 1,
                                'operation' => 'GE',
                                'fromDp' => $publishEndToFormatted,
                                'toDp' => null
                            ]
                        ]
                    ],
                    'OPTIONS' => [
                        'getTotalCount' => 1,
                        'limit' => $args['perpage'],
                        'offset' => $offset,
                        'sort' => [
                            'public_publish_date' => 'DESC'
                        ],
                        'matchCriteriaNames' => 1,
                        'facetsToUse' => [
                            'p_multi',
                            'category',
                            'owner',
                            'pubstatus',
                            'procstatus',
                            'relation',
                            'vacancy_version',
                            'C_1',
                            'C_2',
                            'C_3',
                            'C_4',
                            'C_5',
                            'C_6',
                            'C_7',
                            'C_8',
                            'C_9',
                            'C_10',
                            'C_11',
                            'C_12',
                            'C_13',
                            'C_14',
                            'C_15',
                            'C_16',
                            'C_17',
                            'C_18'
                        ]
                    ],
                    'VERSION' => 2,
                    'SUB_VERSION' => 0
                ]
            ],
            'limit' => 1000,
            'totalCount' => 1,
            'getTotalCount' => true,
            'what' => [
                'uid' => 1,
                'internalId' => 1,
                'title' => 1,
                'location' => 1,
                'relation' => 1,
                'category' => 1,
                'status' => 1,
                'userEmail' => 1,
                'photoFileName' => 1,
                'textField_description' => 1,
                'textField_companyProfile' => 1,
                'textField_requirements' => 1,
                'textField_salary' => 1,
                'textField_companyCulture' => 1,
                'textField_summary' => 1,
                'textField_extra1' => 1,
                'textField_extra2' => 1,
                'textField_extra3' => 1,
                'textField_extra4' => 1,
                'textField_extra5' => 1,
                'textField_extra6' => 1,
                'textField_extra7' => 1,
                'textField_extra8' => 1,
                'textField_extra9' => 1,
                'textField_extra10' => 1,
                'applyCount' => 1,
                'PhotoGallery' => 1,
                'referenceNr' => 1,
                'externalReferenceNr' => 1,
                'entryDateTime' => 1,
                'lastModified' => 1,
                'salaryCurrency' => 1,
                'salaryValue' => 1,
                'salaryUnit' => 1,
                'salaryMin' => 1,
                'salaryMax' => 1,
                'publicationStartDate' => 1,
                'publicationEndDate' => 1,
                'publicationFirstDate' => 1,
                'publicationStatus' => 1,
                'publicationStatusId' => 1,
                'showEmployer' => 1,
                'blockScanners' => 1,
                'removeApplyButton' => 1,
                'categoryId' => 1,
                'alternativeCategories' => 1,
                'statusId' => 1,
                'user' => 1,
                'userInitials' => 1,
                'userId' => 1,
                'userPhoneMobile' => 1,
                'createdByUser' => 1,
                'locationAddress' => 1,
                'locationCity' => 1,
                'locationCityId' => 1,
                'locationPostcode' => 1,
                'locationState' => 1,
                'locationCountry' => 1,
                'locationCountryCode' => 1,
                'applyCountToday' => 1,
                'matchCriteriaNames' => 1,
                'matchCriteria_1' => 1,
                'matchCriteria_2' => 1,
                'matchCriteria_3' => 1,
                'matchCriteria_4' => 1,
                'matchCriteria_5' => 1,
                'matchCriteria_6' => 1,
                'matchCriteria_7' => 1,
                'matchCriteria_8' => 1,
                'matchCriteria_9' => 1,
                'matchCriteria_10' => 1,
                'matchCriteria_11' => 1,
                'matchCriteria_12' => 1,
                'matchCriteria_13' => 1,
                'matchCriteria_14' => 1,
                'matchCriteria_15' => 1,
                'matchCriteria_16' => 1,
                'matchCriteria_17' => 1,
                'matchCriteria_18' => 1,
                'locationLatitude' => 1,
                'locationLongitude' => 1,
                'applyUrls' => 1,
                'customApplyUrl' => 1,
                'customDetailUrl' => 1,
                'hoursType' => 1,
                'hoursUnit' => 1,
                'hours' => 1,
                'housTotal' => 1,
                'tariffUnit' => 1,
                'tariff' => 1,
                'currencyId' => 1,
                'assignmentType' => 1,
                'responsibleCustomerOfficeContactPersonName' => 1,
                'responsibleCustomerOfficeName' => 1,
                'responsibleWorkplaceName' => 1,
                'responsibleWorkplaceContactPersonName' => 1,
                'responsibleContact' => 1,
                'workPlaceAddress' => 1,
                'workPlacePostalCode' => 1,
                'workPlaceCity' => 1,
                'workPlacePhone' => 1,
                'workPlaceFax' => 1,
                'slug' => 1
            ]
        ], $owsArgs);

        // If a website has been selected we should only retrieve vacancies of that website
        if ($website) {
            $options['search']['ACTONOMY']['DATA']['p_multi'] = [
                'options' => [
                    'required' => true
                ],
                'value' => [
                    'required' => [
                        "{$website}"
                    ]
                ]
            ];
        }

        // Get extra fields 
        $extraFields = static::getExtraFields();

        if (!is_wp_error($extraFields)) {
            // Extra textfields to query
            foreach ($extraFields as $extraTextField) {
                $options['what']["extraInfo{$extraTextField['fieldnum']}"] = 1;
            }
        }

        // Get vacancy labels to show
        $vacancyLabels = static::getLabelsToShow();

        // Add labels to show to the what function
        if (is_array($vacancyLabels)) {
            foreach ($vacancyLabels as $vacancyLabel) {
                if (strpos($vacancyLabel, 'C_') !== false) {
                    $criteriaId = str_replace("C_", "", $vacancyLabel);
                    $options['what']["matchCriteria_{$criteriaId}"] = true;
                }
            }
        }

        // Relation
        if (isset($args['relation']) && is_string($args['relation'])) {
            if (strpos($args['relation'], '.') !== false) {
                $relation = RelationModel::getById($args['relation']);
                $relationUid = $relation['uid'] ?? null;
            } else {
                $relationUid = $args['relation'];
            }

            if ($relationUid !== null) {
                $options['search']['ACTONOMY']['DATA']['relation']['value']['required'] = [$relationUid];
            }
        }

        // Changing the sort to also consider the score of the match
        if ($args['search'] !== '' && is_string($args['search'])) {
            $options['search']['ACTONOMY']['OPTIONS']['sort'] = [
                'SCORE' => 'DESC',
                'public_publish_date' => 'DESC'
            ];
        }
        

        // Create OWS request
        $vacanciesRequest = [
            "method" => "Otys.Services.VacancyService.getListEx",
            "params" => [
                $options
            ]
        ];

        // Do OWS API Call
        $vacanciesListResult = OtysApi::post($vacanciesRequest, $args['cache']);

        if (is_wp_error($vacanciesListResult)) {
            return $vacanciesListResult;
        }

        // If something went wrong and list output is not available, return a empty result
        if (!$vacanciesListResult || !isset($vacanciesListResult['listOutput'])) {
            return new WP_Error('vacancy_error', __('Could not retrieve vacancy list', 'otys-jobs-apply'));
        }

        // Get vacancy textfields
        $textFields = VacanciesDetailModel::getTextFields();

        $customSlugIsActive = Routes::customSlugIsActive();

        /*
         * Manipulate result
         */
        foreach ($vacanciesListResult['listOutput'] as $key => $vacancy) {
            // Add textfields
            $vacanciesListResult['listOutput'][$key]['textFields'] = [];

            if (!is_wp_error($textFields) && !empty($textFields) && is_array($textFields)) {
                // Add textfields to result
                foreach ($textFields as $textField) {
                    if (!isset($vacancy[$textField['owsName']])) {
                        continue;
                    }

                    if ($textField['published']) {
                        $vacanciesListResult['listOutput'][$key]['textFields'][$textField['owsName']] = [
                            'title' => $vacancy[$textField['owsName']]['title'],
                            'text' => ($vacancy[$textField['owsName']]['text']) === null ? '' : $vacancy[$textField['owsName']]['text']
                        ];
                    }

                    if ($vacancy[$textField['owsName']]['text'] == null) {
                        $vacanciesListResult['listOutput'][$key][$textField['owsName']]['text'] = '';
                    }
                }
            }

            // Add apply url's
            $vacanciesListResult['listOutput'][$key]['url'] = Routes::get('vacancy-detail', [
                'slug' => $customSlugIsActive ? $vacancy['slug'][$website] : sanitize_title($vacancy['title']) . '-' . $vacancy['uid']
            ]);

            $vacanciesListResult['listOutput'][$key]['applyUrl'] = Routes::get('vacancy-apply', [
                'slug' => $customSlugIsActive ? $vacancy['slug'][$website] : sanitize_title($vacancy['title']) . '-' . $vacancy['uid']
            ]);

            // Check if there is a photo present and add a new variable including the entire url
            $vacanciesListResult['listOutput'][$key]['photoUrl'] =
                (isset($vacancy['photoFileName']) && $vacancy['photoFileName']) ?
                OtysApi::getImageUploadUrl() . $vacancy['photoFileName']
                : null;

            // Add extra textfields to result
            $extraTextFieldsResult = [];

            foreach ($extraFields as $extraTextField) {
                $extraTextFieldsResult['extraInfo' . $extraTextField['fieldnum']] = $extraTextField;
                $extraTextFieldsResult['extraInfo' . $extraTextField['fieldnum']]['value'] = (string) $vacancy['extraInfo' . $extraTextField['fieldnum']];
            }

            $vacanciesListResult['listOutput'][$key]['extraTextFields'] = $extraTextFieldsResult;

            // Add labels
            $vacanciesListResult['listOutput'][$key]['labels'] = [];
            if (isset($vacancy['matchCriteriaNames']) && is_array($vacancyLabels) && !empty($vacancyLabels)) {
                foreach ($vacancyLabels as $vacancyLabel) {
                    // Add criteria
                    if (strpos($vacancyLabel, 'C_') !== false) {
                        $vacancyNameKey = str_replace('C_', 'matchCriteria_', $vacancyLabel);
                        $criteriaName = array_key_exists($vacancyNameKey, $vacancy['matchCriteriaNames']) ? $vacancy['matchCriteriaNames'][$vacancyNameKey] : $vacancyLabel;

                        $vacanciesListResult['listOutput'][$key]['labels'][$vacancyLabel] = [
                            'name' => $criteriaName,
                            'values' => $vacancy[$vacancyNameKey]
                        ];
                    }

                    // Add categorie
                    if ($vacancyLabel === 'category' && isset($vacancy['category']) && $vacancy['category'] !== NULL) {
                        $vacanciesListResult['listOutput'][$key]['labels'][$vacancyLabel] = [
                            'name' => __('Category', 'otys-jobs-apply'),
                            'values' => [$vacancy['category']]
                        ];
                    }
                }
            }

            // Add image url's to gallery items
            if (isset($vacancy['PhotoGallery']) && !empty($vacancy['PhotoGallery'])) {
                foreach ($vacancy['PhotoGallery'] as $galleryKey => $galleryItem) {
                    $vacanciesListResult['listOutput'][$key]['PhotoGallery'][$galleryKey]['photoUrl'] = OtysApi::getImageUploadUrl() . $galleryItem['photo'];
                    $vacanciesListResult['listOutput'][$key]['PhotoGallery'][$galleryKey]['thumbnailUrl'] = OtysApi::getImageUploadUrl() . $galleryItem['thumbnail'];
                }
            }
        }

        return $vacanciesListResult;
    }

    /**
     * Get labels which should be shown
     *
     * @return array
     */
    private static function getLabelsToShow(): array
    {
        $settingName = 'otys_option_vacancies_list_match_criteria_labels';

        if (($matchCriteriaToShow = get_option($settingName, false)) === false || !is_array($matchCriteriaToShow)) {
            return [];
        }

        $filteredValues = array_filter($matchCriteriaToShow, function ($val) {
            return $val === 'true';
        });

        return is_array($filteredValues) ? array_keys($filteredValues) : [];
    }

    /**
     * Get extra fields for vacancies
     *
     * @return array|WP_Error
     */
    public static function getExtraFields()
    {
        // Get current WordPress language
        $language = OtysApi::getLanguage();

        // Define request
        $request = [
            "method" => "Otys.Services.VacancyService.getExtraFieldsList",
            "params" => [
                $language
            ]
        ];

        // Do request
        $response = OtysApi::post($request, true, false);

        return $response;
    }

    /**
     * Summary of getListFilterAtts
     * @param mixed $atts
     * @return void
     */
    public function getListFilterAtts(array $atts)
    {
    }

    /**
     * Validate shortcode attributes
     *
     * @param $params           Params to be validated
     * @param $filtersAsArray   Wheter to return the filter options as array
     * @return array
     * @since 1.0.0
     */
    public static function validateAtts(array $params = []): array
    {
        // Get validation for filters
        $filterValidation = VacanciesFiltersModel::getFiltersValidation(true);

        // Add default validation
        $validationRules = array_merge_recursive(
            $filterValidation,
            [
                'search' => [
                    'filter' => FILTER_CALLBACK,
                    'options' => function ($value) {
                        if (!is_string($value) || $value === '') {
                            return false;
                        }
 
                        return urldecode($value);
                    }
                ],
                'pc' => [
                    'filter' => FILTER_CALLBACK,
                    'options' => function ($value) {
                        if (!is_string($value) || $value === '') {
                            return false;
                        }
 
                        return stripslashes($value);
                    }
                ],
                'pcm' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => ['min_range' => 1, 'max_range' => 1000]
                ],
                'premium' => [
                    'filter' => FILTER_CALLBACK,
                    'options' => function ($value) {
                        $value = filter_Var($value, FILTER_SANITIZE_STRING);

                        return $value;
                    }
                ],
                'perpage' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => ['min_range' => 1, 'max_range' => 250]
                ],
                'relation' => FILTER_SANITIZE_ENCODED,
            ]
        );

        return static::doSanitiziation($params, $validationRules);
    }

    /**
     * Validate url parameters
     *
     * @param $params           Params to be validated
     * @param $filtersAsArray   Wheter to return the filter options as array
     * @return array
     * @since 1.0.0
     */
    public static function validateUrlParams(array $params = []): array
    {
        // Get validation for filters
        $filterValidation = VacanciesFiltersModel::getFiltersValidation();

        // Add default validation
        $validationRules = array_merge_recursive(
            $filterValidation,
            [
                'search' => [
                    'filter' => FILTER_CALLBACK,
                    'options' => function ($value) {
                        if (!is_string($value) || $value === '') {
                            return false;
                        }
 
                        return stripslashes(urldecode($value));
                    }
                ],
                'pc' => [
                    'filter' => FILTER_CALLBACK,
                    'options' => function ($value) {
                        if (!is_string($value) || $value === '') {
                            return false;
                        }
 
                        return stripslashes(urldecode($value));
                    },
                ],
                'pcm' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => ['min_range' => 1, 'max_range' => 1000]
                ],
                'plo' => [
                    'filter' => FILTER_CALLBACK,
                    'options' => function ($option) {
                        if (!is_string($option)) {
                            return false;
                        }

                        return (array_key_exists($option, VacanciesGeoSearchModel::getCountries())) ? $option : false;
                    }
                ],
                'page-number' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => ['min_range' => 1, 'max_range' => static::$maxPageNumbers]
                ]
            ]
        );

        return static::doSanitiziation($params, $validationRules);
    }


    /**
     * Get attributes from [otys-vacancies-list] on the current global $post
     *
     * @return array Returns array of attributes if given
     */
    public static function getAtts(): array
    {
        $atts = static::getShortcodeAttributes('otys-vacancies-list');

        return $atts;
    }
}