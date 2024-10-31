<?php

namespace Otys\OtysPlugin\Models\Shortcodes;

use Otys\OtysPlugin\Models\Shortcodes\ShortcodeBaseModel;
use Otys\OtysPlugin\Models\Shortcodes\VacanciesFiltersModel;

/**
 * This model is used by the [otys-vacancies-search] shortcode
 */
class VacanciesSearchModel extends ShortcodeBaseModel
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
        // Add default validation
        $validationRules = [
            'keyword' => FILTER_VALIDATE_BOOLEAN,
            'filters' => [
                'filter' => FILTER_VALIDATE_REGEXP,
                'options' => ['regexp' => '/^[0-9-A-Za-z,-]+$/']
            ]
        ];

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
                    }
                ],
                'pcm' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => ['min_range' => 0, 'max_range' => 1000],
                    'flags' => FILTER_REQUIRE_SCALAR
                ]
            ]
        );

        $result = filter_var_array($params, $validationRules, false);

        $result = array_filter($result, function ($v, $k) {
            return ($v !== null && $v !== false && !empty($v));
        }, ARRAY_FILTER_USE_BOTH);

        if (!is_array($result)) {
            return [];
        }

        return $result;
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
        $filterValidation = VacanciesFiltersModel::getFiltersValidation();

        $validationRules = array_merge_recursive(
            $filterValidation,
            [
                'pc' => FILTER_SANITIZE_SPECIAL_CHARS,
                'pcm' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'options' => ['min_range' => 1, 'max_range' => 1000]
                ]
            ]
        );

        return static::doSanitiziation($params, $validationRules);
    }
}