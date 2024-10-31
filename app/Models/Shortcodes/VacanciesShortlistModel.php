<?php

namespace Otys\OtysPlugin\Models\Shortcodes;

use Otys\OtysPlugin\Helpers\SettingHelper;
use Otys\OtysPlugin\Models\Shortcodes\ShortcodeBaseModel;
use Otys\OtysPlugin\Models\Shortcodes\VacanciesFiltersModel;

/**
 * This model is used by the otys-vacancies-list shortcode
 */
class VacanciesShortlistModel extends ShortcodeBaseModel
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
        $filterValidation = VacanciesFiltersModel::getFiltersValidation(true);

        // Add default validation
        $validationRules = array_merge_recursive(
            $filterValidation,
            [
                'premium' => [
                    'filter' => FILTER_CALLBACK,
                    'options' => function ($value) {
                        $value = filter_Var($value, FILTER_SANITIZE_STRING);

                        return $value;
                    }
                ],
                'limit' => FILTER_VALIDATE_INT,
                'relation' => FILTER_SANITIZE_ENCODED,
                'owner' => FILTER_SANITIZE_EMAIL,
                'mode' => [
                    'filter' => FILTER_CALLBACK,
                    'options' => function ($value) {
                        return array_search($value, [
                            'all',
                            'shortlist',
                        ]) !== false ? $value : false;
                    }
                ],
                'exclude' => FILTER_SANITIZE_ENCODED,
                'search' => [
                    'filter' => FILTER_CALLBACK,
                    'options' => function ($value) {
                        if (!is_string($value) || $value === '') {
                            return false;
                        }
 
                        return urldecode($value);
                    }
                ]
            ]
        );

        return static::doSanitiziation($params, $validationRules);
    }

    /**
     * Get vacancies shortlist
     *
     * @param array $args
     * @param array $owsArgs
     * @return array|\WP_Error
     */
    public static function get(array $args = [], array $owsArgs = [])
    {
        $args = wp_parse_args($args, [
            'limit' => 3,
            'mode' => 'shortlist'
        ]);

        // Add shortlist argument
        $websiteId = SettingHelper::getSiteId();

        // Add requirement of vacancies being marked as published in shortlist for current website
        if ($args['mode'] === 'shortlist') {
            $owsArgs['search']['ACTONOMY']['DATA']['publiceer_shortlist'] = [
                'options' => [
                    'required' => true,
                    'persistent' => false
                ],
                'value' => '1'
            ];

        if ((is_int($websiteId) && $websiteId > 0))
            $owsArgs['search']['ACTONOMY']['DATA']['p_shortlist_multi'] = [
                'options' => [
                    'required' => true
                ],
                'value' => [
                    'required' => ["{$websiteId}"]
                ]
            ];
        }

        return VacanciesListModel::get([], $args, $owsArgs);
    }
}