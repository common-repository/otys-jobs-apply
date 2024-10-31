<?php

namespace Otys\OtysPlugin\Controllers\Shortcodes;

use Otys\OtysPlugin\Includes\Core\Routes;
use Otys\OtysPlugin\Includes\OtysApi;
use Otys\OtysPlugin\Models\Shortcodes\VacanciesFiltersModel;
use Otys\OtysPlugin\Models\Shortcodes\VacanciesGeoSearchModel;
use Otys\OtysPlugin\Models\Shortcodes\VacanciesSearchModel;

/**
 * [otys-vacancies-geo-search]
 *
 * @since 2.0.0
 */
final class VacanciesGeoSearchController extends ShortcodeBaseController
{
    /**
     * Displays the shortcode
     *
     * @return void
     */
    public function display(): void
    {
        /**
         * Logic
         */

        // Get attribute parameters
        $attParams = wp_parse_args(VacanciesGeoSearchModel::validateAtts($this->getAtts()), [
            'keyword' => true,
            'filters' => '',
            'min' => 5,
            'max' => 50,
            'default' => 20,
            'country' => 'NL',
            'countryselect' => false,
            'steps' => 5
        ]);

        // Get url parameters
        $urlParams = wp_parse_args(VacanciesGeoSearchModel::validateUrlParams($_GET), [
            'search' => '',
            'pc' => '',
            'pcm' => $attParams['default'],
            'plo' => OtysApi::getLanguage()
        ]);

        // Create reset url
        $resetUrl = remove_query_arg(['pc', 'pcm', 'plo'], false);

        // Get filters set via params as array
        $attFilter = explode(',', $attParams['filters']);

        // Get filters to show
        $filters = array_filter(VacanciesFiltersModel::get($urlParams), function ($filterSlug) use ($attFilter) {
            return array_search($filterSlug, $attFilter) !== false;
        }, ARRAY_FILTER_USE_KEY);

        // Get url active filters
        $selectedPameters = array_filter(VacanciesSearchModel::validateFilters($_GET), function ($filterSlug) use ($filters) {
            return !array_key_exists($filterSlug, $filters);
        }, ARRAY_FILTER_USE_KEY);

        $this->setArgs('showKeywordSearch', $attParams['keyword']);
        $this->setArgs('search', $urlParams['search']);
        $this->setArgs('action', $this->getFormAction());
        $this->setArgs('filters', $filters);
        $this->setArgs('selectedParameters', $selectedPameters);
        $this->setArgs('resetUrl', $resetUrl);
        $this->setArgs('postalCode', $urlParams['pc']);
        $this->setArgs('distance', $urlParams['pcm']);
        $this->setArgs('country', $urlParams['plo']);
        $this->setArgs('minDistance', $attParams['min']);
        $this->setArgs('maxDistance', $attParams['max']);
        $this->setArgs('defaultDistance', $attParams['default']);
        $this->setArgs('steps', $attParams['steps']);
        $this->setArgs('countries', VacanciesGeoSearchModel::getCountries());
        $this->setArgs('useCountrySelect', $attParams['countryselect']);

        $this->loadTemplate('vacancies/vacancies-geo-search.php');
    }

    /**
     * Get form action
     *
     * @return string
     */
    public function getFormAction(): string
    {
        global $post;

        if (has_shortcode($post->post_content, 'otys-vacancies-list')) {
            return '';
        }

        return Routes::get('vacancies');
    }
}