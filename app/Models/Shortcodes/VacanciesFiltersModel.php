<?php

namespace Otys\OtysPlugin\Models\Shortcodes;

use Otys\OtysPlugin\Includes\OtysApi;
use Otys\OtysPlugin\Models\Shortcodes\ShortcodeBaseModel;
use Otys\OtysPlugin\Models\Shortcodes\VacanciesListModel;

/**
 * This is the model that is used by the otys-vacancies-filters shortcode.
 *
 * @since 2.0.0
 */
final class VacanciesFiltersModel extends ShortcodeBaseModel
{
    /**
     * Get filters
     *
     * @since 2.0.0
     * @param array $activeFilters  Current active filters as slug
     * @param array $atts           Shortcode attributes
     * @return array
     */
    public static function get(array $urlParams = [], array $atts = []): array
    {
        // Get base result with attributes
        $baseResult = VacanciesListModel::get([], $atts);

        // Get specific filters to show
        $show = isset($atts['show']) && is_string($atts['show']) ? explode(',', $atts['show']) : [];

        if (is_wp_error($baseResult)) {
            return [];
        }

        // Get facets from base result
        $facets = $baseResult['searchExtras']['ACTONOMY']['facets'];

        // Get filters as slug
        $filterSlugs = static::getSlugs();

        // Loop through all filters to manipulate data
        foreach ($filterSlugs as $filterSlug => $filterSlugData) {
            if (!empty($show)) {
                if (array_search($filterSlug, $show) === false) {
                    unset($filterSlugs[$filterSlug]);
                    continue;
                }
            }

            $filterId = $filterSlugData['id'];

            $filterSlugs[$filterSlug]['active'] = array_key_exists($filterSlug, $urlParams);

            $newUrl = $urlParams;

            foreach ($filterSlugData['options'] as $optionSlug => $optionData) {
                // Store new options in here
                $newUrlOptions = isset($urlParams[$filterSlug]) ? static::optionsAsArray($urlParams[$filterSlug]) : [];

                /**
                 * Add active status to option
                 */     
                $filterSlugs[$filterSlug]['options'][$optionSlug]['active'] = ($filterSlugs[$filterSlug]['active'] && array_search($optionSlug, $newUrlOptions) !== false);

                /**
                 * Generate url for option
                 */

                // Remove current option from new url if it's active
                // This way we can make it so if you click a active option it gets deselected
                if (
                    $filterSlugs[$filterSlug]['options'][$optionSlug]['active'] &&
                    ($newUrlOptionKey = array_search($optionSlug, $newUrlOptions)) !== false
                ) {
                    unset($newUrlOptions[$newUrlOptionKey]);
                } else {
                    $newUrlOptions[] = $optionSlug;
                }

                // Place search parameter at the end of the url
                if (!empty($newUrl['search'])) {
                    $searchValue = $newUrl['search'];
                    unset($newUrl['search']);
                    $newUrl['search'] = $searchValue;
                }

                // Decide wheter to keep the current matchcriteria in the new url
                if (empty($newUrlOptions)) {
                    // If the new option list will be empty remove the entire matchcriteria
                    unset($newUrl[$filterSlug]);
                } else {
                    // If there are still options create the new options list
                    $newUrl[$filterSlug] = implode(" ", $newUrlOptions);
                }

                // Remove page from url
                unset($newUrl['page-number']);

                // Generate url for option
                $url = http_build_query($newUrl);

                // Add url to filter option
                $filterSlugs[$filterSlug]['options'][$optionSlug]['url'] = $url;

                /**
                 * Remove options which have a frequency of 0
                 */
                // Find option in facet base result
                $facetOptionKey = isset($facets[$filterId]) ? array_search($optionData['id'], array_column($facets[$filterId], 'id')) : false;

                // If option is not present in facet remove it from the slugs
                if ($facetOptionKey === false) {
                    unset($filterSlugs[$filterSlug]['options'][$optionSlug]);
                    continue;
                }
                
                $facetOptionData = $facets[$filterId][$facetOptionKey];
                
                // If the frequency of the base result is 0 remove the option from the filters
                if ($facetOptionData['frequency'] === 0) {
                    unset($filterSlugs[$filterSlug]['options'][$optionSlug]);
                }

                $filterSlugs[$filterSlug]['options'][$optionSlug]['frequency'] = $facetOptionData['frequency'];
            }

            if (empty($filterSlugs[$filterSlug]['options'])) {
                unset($filterSlugs[$filterSlug]);
            }
        }

        // Sort base on base frequency
        $optionsSorting = get_option('otys_option_vacancies_options_sorting');

        if ($optionsSorting === 'frequency') {
            foreach ($filterSlugs as $slug => $value) {
                uasort($value['options'], function($value1, $value2) {
                    return ($value1['frequency'] > $value2['frequency']) ?  -1 : 1;
                });
                
                $filterSlugs[$slug]['options'] = $value['options'];
            }
        }


        return $filterSlugs;
    }

    /**
     * Get filters by slug
     *
     * @since 2.0.0
     *
     * @param bool $all Wheter to get all filters or only the ones that the user can select 
     * @return array
     */
    public static function getSlugs(bool $all = false): array
    {
        $data = static::getData('getSlugs-' . $all);

        if (!empty($data)) {
            return $data;
        }

        // Get filters to which should be shown
        $filtersToShow = static::getFilterSetting('otys_option_vacancies_filters_match_criteria');

        // Get filters from API
        $filters = static::getFilters();

        $sluggedFilters = [];

        // Loop through all filters and slugify them and add them to the sluggedFilters array
        foreach ($filters as $filterKey => $filter) {
            // Check if the filter is used on the website to filter if not don't include it in the list
            // If all is set to true don't filter
            if (!$all && !in_array($filterKey, $filtersToShow)) {
                continue;
            }

            // Generate slug
            $filterSlug = sanitize_title($filter['name']);

            // Add data to slug in sluggedFilters
            $sluggedFilters[$filterSlug]['id'] = $filterKey;
            $sluggedFilters[$filterSlug]['name'] = $filter['name'];
            $sluggedFilters[$filterSlug]['options'] = [];

            // Loop through each filter option and add them slugged to sluggedFilters
            // foreach ($sluggedFilters as $filterSlug => $sluggedFilter) {
            $options = $filters[$filterKey]['valueOptions'];

            foreach ($options as $optionKey => $option) {
                // Generate slug
                $optionSlug = sanitize_title($option);

                // Add data to slug in sluggedFilters
                $sluggedFilters[$filterSlug]['options'][$optionSlug]['name'] = $option;
                $sluggedFilters[$filterSlug]['options'][$optionSlug]['id'] = $optionKey;
            }
        }

        // Sort filters based on the filters option
        if (is_array($filtersToShow) && !empty($filtersToShow) && !empty($filtersToShow)) {
            uasort($sluggedFilters, function ($value1, $value2) use ($filtersToShow) {
                return ((array_search($value1['id'], $filtersToShow) > array_search($value2['id'], $filtersToShow)) ? 1 : -1);
            });
        }

        uasort($sluggedFilters, function ($value1, $value2) use ($filtersToShow) {
            return ((array_search($value1['id'], $filtersToShow) > array_search($value2['id'], $filtersToShow)) ? 1 : -1);
        });

        static::storeData('getSlugs', $sluggedFilters);

        return $sluggedFilters;
    }


    /**
     * Get filters from OTYS
     *
     * @since 2.0.0
     * @return array
     */
    public static function getFilters(): array
    {
        // Retrieve filters via API
        $response = OtysApi::post([
            'method' => 'Otys.Services.VacancyService.getSearchFacilityInfoNoCache',
            'params' => ['ACTONOMY']
        ], true);

        $filters = [];

        // Get match criteria and add them to filters
        if (is_array($response) && array_key_exists('matchcriteria', $response)) {
            $filters = $response['matchcriteria'];
        }

        // Get criteria and add them to filters
        if (is_array($response) && array_key_exists('category', $response)) {
            $filters['category'] = $response['category'];
        }

        $filters = array_filter($filters, function ($filter) {
            return !empty($filter['name']);
        });

        return $filters;
    }

    /**
     * Get filter setting
     *
     * @since 2.0.0
     * @param string $settingName
     * @return array
     */
    public static function getFilterSetting(string $settingName = ''): array
    {
        if (($matchCriteriaToShow = get_option($settingName, false)) === false || !is_array($matchCriteriaToShow)) {
            return [];
        }

        $filteredValues = array_filter($matchCriteriaToShow, function ($val) {
            return $val === 'true';
        });

        return is_array($filteredValues) ? array_keys($filteredValues) : [];
    }

    /**
     * Get filters from shortcode attributes
     *
     * @since 2.0.0
     * @param array $atts
     * @return array
     */
    public static function getFiltersAtts(array $atts): array
    {
        $filterSlugs = static::getSlugs();

        $filters = [];

        // Loop over the attributes of the vacancy list
        foreach ($atts as $attribute => $value) {
            // Check if the attribute exists as filter
            if (array_key_exists($attribute, $filterSlugs)) {
                // Add the attribute to the list of filters
                $filters[$attribute] = explode(',', $value);
            }
        }

        return $filters;
    }

    /**
     * Get filters from shortcode attributes
     *
     * @since 2.0.17
     * @param array $atts
     * @return array
     */
    public static function getFiltersArgs(array $args): array
    {
        $filterSlugs = static::getSlugs();

        $filters = [];

        // Loop over the attributes of the vacancy list
        foreach ($args as $attribute => $value) {
            // Check if the attribute exists as filter
            if (array_key_exists($attribute, $filterSlugs)) {
                // Add the attribute to the list of filters
                $filters[$attribute] = explode(' ', $value);
            }
        }

        return $filters;
    }

    /**
     * Get all currently active filters
     *
     * @since 2.0.0
     * @param array $atts
     * @return array
     */
    public static function getActiveFilters(array $atts = []): array
    {
        // Get filters specified within the attributes of the shortcode
        $filterAtts = static::getFiltersAtts($atts);

        return $filterAtts;
    }

    /**
     * Get filter validation array
     * Can be used with filter_var_array()
     * @since 2.0.0
     *
     * @param bool $all Wheter to get all filters or only the ones that the user can select 
     * @return array
     */
    public static function getFiltersValidation(bool $all = false): array
    {
        $validation = [];

        $slugs = static::getSlugs($all);

        // Loop through all available slugs and create validation
        foreach ($slugs as $filterSlug => $filterValues) {
            // Get list of allowed options
            $allowedOptions = array_keys($filterValues['options']);

            // Create validation for this slug
            $validation[$filterSlug] = [
                'filter' => FILTER_CALLBACK,
                'options' => function ($options) use ($allowedOptions) {
                    if (is_string($options)) {
                        $optionsCommaseperated = (array) explode(',', $options);
                        $optionsSpaceSeperated = (array) explode(' ', $options);

                        $options = array_unique(array_merge_recursive($optionsCommaseperated, $optionsSpaceSeperated));
                    }

                    foreach ($options as $optionKey => $option) {
                        if (array_search($option, $allowedOptions) === false) {
                            unset($options[$optionKey]);
                        }
                    }

                    return empty($options) || !is_array($options) ? '' : implode(' ', $options);
                }
            ];
        }

        return $validation;
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
                'premium' => FILTER_VALIDATE_BOOLEAN,
                'show' =>  [
                    'filter' => FILTER_SANITIZE_SPECIAL_CHARS,
                    'flags' => FILTER_REQUIRE_SCALAR
                ],
                'relation' => FILTER_SANITIZE_ENCODED
            ]
        );

        return static::doSanitiziation($params, $validationRules);
    }

    /**
     * Returns options as array
     *
     * @since 2.0.0
     * @param mixed $options        The options
     * @param string $seperator     The seperator to use, by default a space.
     * @return array
     */
    public static function optionsAsArray($options, string $seperator = ' '): array
    {
        if (is_array($options)) {
            return $options;
        }

        return is_string($options) ? explode($seperator, $options) : [];
    }
}