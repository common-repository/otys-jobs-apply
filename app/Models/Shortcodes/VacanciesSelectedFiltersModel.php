<?php

namespace Otys\OtysPlugin\Models\Shortcodes;

use Otys\OtysPlugin\Includes\OtysApi;
use Otys\OtysPlugin\Models\Shortcodes\ShortcodeBaseModel;
use Otys\OtysPlugin\Models\Shortcodes\VacanciesFiltersModel;

/**
 * This is the model that is used by the otys-vacancies-selected-filters shortcode.
 */
final class VacanciesSelectedFiltersModel extends ShortcodeBaseModel
{
    /**
     * Get filters
     *
     * @param array $activeFilters  Current active filters as slug
     * @param array $atts           Shortcode attributes
     * @return array
     */
    public static function get(array $urlParams = []): array
    {
        global $wp;
        $currentUrl = home_url($wp->request);

        $filters = [];

        // Get filter slugs
        $slugs = VacanciesFiltersModel::getSlugs();

        // Add geo to filters
        $geo = [];

        foreach ($urlParams as $urlParamKey => $urlParam) {
            if ($urlParamKey === 'pc' || $urlParamKey === 'plo') {
                $geo[] = $urlParam;
            }

            if ($urlParamKey === 'pcm') {
                $geo[] = $urlParam . __('km', 'otys-jobs-apply');
            }
        }

        if (!empty($geo)) {
            $urlParams['geo'] = implode('&nbsp;', $geo);
        }

        // Build selected filters params array
        foreach ($urlParams as $paramSlug => $paramValue) {
            if (empty($paramValue)) {
                continue;
            }

            if ($paramSlug === 'search') {
                $values = [$paramValue];
            } else {
                $values = !is_array($paramValue) ? explode(' ', (string) $paramValue) : $paramValue;
            }

            $params = $urlParams;

            // Remove current slug from new url params
            unset($params[$paramSlug]);

            // Remove geo params if filter is geo
            if ($paramSlug === 'geo') {
                unset($params['pc']);
                unset($params['pcm']);
                unset($params['plo']);
            }

            // Generate new url
            $url = !empty($params) ? $currentUrl . '?' . http_build_query($params) : $currentUrl;

            // Map filter values with real filter names
            $filters[$paramSlug] = [
                'values' => array_map(function ($optionSlug) use ($slugs, $paramSlug) {
                    // Find real filter name
                    if (
                        array_key_exists($paramSlug, $slugs) &&
                        array_key_exists($optionSlug, $slugs[$paramSlug]['options'])
                    ) {
                        return $slugs[$paramSlug]['options'][$optionSlug]['name'];
                    }

                    // Fallback when name not found use option slug (should be impossible)
                    return $optionSlug;
                }, $values),
                'url' => $url
            ];
        }

        // Combine pc pcm and plo
        unset($filters['pc']);
        unset($filters['pcm']);
        unset($filters['plo']);

        return $filters;
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

                        return stripslashes($value);
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
}