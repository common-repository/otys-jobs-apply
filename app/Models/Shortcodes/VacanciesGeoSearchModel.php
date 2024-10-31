<?php

namespace Otys\OtysPlugin\Models\Shortcodes;

use Otys\OtysPlugin\Includes\OtysApi;
use Otys\OtysPlugin\Models\Shortcodes\ShortcodeBaseModel;
use Otys\OtysPlugin\Models\Shortcodes\VacanciesFiltersModel;

/**
 * This model is used by the [otys-vacancies-search] shortcode
 */
class VacanciesGeoSearchModel extends ShortcodeBaseModel
{
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
                'keyword' => FILTER_VALIDATE_BOOLEAN,
                'filters' => [
                    'filter' => FILTER_VALIDATE_REGEXP,
                    'options' => ['regexp' => '/^[A-Za-z\-\,\0-9]+$/']
                ],
                'min' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => ['min_range' => 1, 'max_range' => 1000]
                ],
                'max' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => ['min_range' => 1, 'max_range' => 1000]
                ],
                'default' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => ['min_range' => 1, 'max_range' => 500]
                ],
                'steps' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => ['min_range' => 1, 'max_range' => 1000]
                ],
                'countryselect' => FILTER_VALIDATE_BOOLEAN
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
    public static function validateUrlParams(array $params = [], array $args = []): array
    {
        $args = wp_parse_args($args, [
            'min_range' => 1,
            'max_range' => 1000
        ]);

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
                    }
                ],
                'pcm' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => ['min_range' => $args['min_range'], 'max_range' => $args['max_range']],
                    'flags' => FILTER_REQUIRE_SCALAR
                ],
                'plo' => [
                    'filter' => FILTER_CALLBACK,
                    'options' => function ($option) {
                        if (!is_string($option)) {
                            return false;
                        }

                        return (array_key_exists($option, VacanciesGeoSearchModel::getCountries())) ? $option : false;             
                    }
                ]
            ]
        );

        return static::doSanitiziation($params, $validationRules);
    }

    /**
     * Validate filters
     *
     * @param $params           Params to be validated
     * @param $filtersAsArray   Wheter to return the filter options as array
     * @return array
     * @since 1.0.0
     */
    public static function validateFilters(array $params = []): array
    {
        // Get validation for filters
        $validationRules = VacanciesFiltersModel::getFiltersValidation();

        return static::doSanitiziation($params, $validationRules);
    }

    /**
     * Get countries
     *
     * @return void
     */
    public static function getCountries(): array
    {
        // Retrieve filters via API
        $response = OtysApi::post([
            'method' => 'Otys.Services.VacancyService.getSearchFacilityInfoNoCache',
            'params' => ['ACTONOMY']
        ], true);

        if (is_wp_error($response)) {
            return [];
        }

        // Get proximity
        if (
            is_array($response) &&
            isset($response['proximity']) &&
            isset($response['proximity']['countryCodesObject']) &&
            is_array($response['proximity']['countryCodesObject'])
        ) {
            return $response['proximity']['countryCodesObject'];
        }

        return [];
    }
}