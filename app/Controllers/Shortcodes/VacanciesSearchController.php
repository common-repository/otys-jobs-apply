<?php

namespace Otys\OtysPlugin\Controllers\Shortcodes;

use Otys\OtysPlugin\Includes\Core\Routes;
use Otys\OtysPlugin\Models\Shortcodes\VacanciesFiltersModel;
use Otys\OtysPlugin\Models\Shortcodes\VacanciesSearchModel;

/**
 * [otys-vacancies-search]
 *
 * @since 2.0.0
 */
final class VacanciesSearchController extends ShortcodeBaseController
{
    /**
     * Displays the shortcode
     *
     * @return void
     */
    public function display(): void
    {
        // Get attribute parameters
        $attParams = wp_parse_args(VacanciesSearchModel::validateAtts($this->getAtts()), [
            'keyword' => true,
            'filters' => ''
        ]);

        // Get url parameters
        $urlParams =  wp_parse_args(VacanciesSearchModel::validateUrlParams($_GET), [
            'search' => ''
        ]);

        // Get filters set via params as array
        $attFilter = explode(',', $attParams['filters']);

        // Get filters to show
        $filters = array_filter(VacanciesFiltersModel::get($urlParams), function($filterSlug) use ($attFilter) {
            return array_search($filterSlug, $attFilter) !== false;
        }, ARRAY_FILTER_USE_KEY);
     
        // Get url active filters
        $selectedPameters = array_filter(VacanciesSearchModel::validateFilters($_GET), function($filterSlug) use ($filters) {
            return !array_key_exists($filterSlug, $filters);
        }, ARRAY_FILTER_USE_KEY);

        $this->setArgs('showKeywordSearch', $attParams['keyword']);
        $this->setArgs('search', $urlParams['search']);
        $this->setArgs('action', $this->getFormAction());
        $this->setArgs('filters', $filters);
        $this->setArgs('selectedParameters', $selectedPameters);

        $this->loadTemplate('vacancies/vacancies-search.php');
    }

    /**
     * Get form action
     *
     * @return string
     */
    public function getFormAction(): string
    {
        global $post;
        global $wp;

        if ($post !== null && property_exists($post, 'post_content')) {
            if (has_shortcode($post->post_content, 'otys-vacancies-list')) {
                return trailingslashit(home_url($wp->request));
            }
        }

        return Routes::get('vacancies');
    }

    
    /**
     * Callback when searched
     * Converts the POST request in proper GET parameters
     *
     * @return void
     */
    public static function filterPost()
    {
        if (isset($_POST) && isset($_POST['action']) && $_POST['action'] === "otys_search") {
            global $wp;

            $validated = VacanciesSearchModel::validateUrlParams($_POST);

            if ($validated === null) {
                return;
            }

            $validated = array_map(function($value) {
                if (is_array($value)) {
                    return implode(' ', $value);
                }

                return $value;
            }, $validated);

            $url = home_url($wp->request . '/?' . http_build_query($validated));

            wp_redirect($url);
        }
    }
}