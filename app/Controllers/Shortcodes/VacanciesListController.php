<?php

namespace Otys\OtysPlugin\Controllers\Shortcodes;

use Otys\OtysPlugin\Models\Shortcodes\VacanciesListModel;
use Otys\OtysPlugin\Models\Pagination;

/**
 * [otys-vacancies-list]
 *
 * @since 2.0.0
 */
final class VacanciesListController extends ShortcodeBaseController
{
    /**
     * Displays the shortcode
     *
     * @param array $atts
     * @return void
     */
    public function display(): void
    {
        /**
         * Logic
         */
        wp_enqueue_script('otys-vacancies-js', OTYS_PLUGIN_ASSETS_URL . '/js/otys-vacancies.min.js', [], OTYS_PLUGIN_VERSION, [
            'in_footer' => true
        ]);
         
        // Get url parameters
        $urlParams = wp_parse_args(VacanciesListModel::validateUrlParams($_GET), [
            'page-number' => 1
        ]);

        $attParams = VacanciesListModel::validateAtts($this->getAtts());

        $attParams = wp_parse_args($attParams, [
            'perpage' => (int) get_option("otys_option_vacancies_per_page", 10)
        ]);

        // Fetch vacancies
        $vacanciesList = VacanciesListModel::get($urlParams, $attParams);

        $vacancies = !is_wp_error($vacanciesList) && isset($vacanciesList['listOutput']) ? $vacanciesList['listOutput'] : [];

        if (is_wp_error($vacanciesList)) {
            return;
        }

        // Get total amount of vacancies
        $totalVacancies = 
        (array_key_exists('searchExtras', $vacanciesList) &&
        array_key_exists('ACTONOMY', $vacanciesList['searchExtras']) &&
        array_key_exists('totalCount', $vacanciesList['searchExtras']['ACTONOMY'])) ? intval($vacanciesList['searchExtras']['ACTONOMY']['totalCount']) : 0;

        /**
         * Arguments to parse to document
         */
        $this->setArgs('vacancies', $vacancies);

        $this->setArgs('pagination', Pagination::get(
            intval($attParams['perpage']),
            intval($totalVacancies),
            intval($urlParams['page-number']),
            $urlParams,
            [
                'max_amount_of_pages' => intval(get_option('otys_option_pagination_max_pages', 3)),
                'previous_next_buttons' => intval(get_option('otys_option_pagination_buttons_prev_next')),
                'first_last_buttons' => intval(get_option('otys_option_pagination_buttons_first_last')),

            ]
        ));

        $this->setArgs('vacanciesTotal', $totalVacancies);


        /**
         * Load template
         */
        $this->loadTemplate('vacancies/vacancies-list.php');
    }

    /**
     * Sets status to 404 if page has otys-vacancies-list with 0 results
     *
     * @return void
     */
    public static function filterStatusCode()
    {
        global $post;

        if ($post === null || !property_exists($post, 'post_content')) {
            return;
        }
        
        $content = $post->post_content;

        if (has_shortcode($content, 'otys-vacancies-list')) {
            // Vacancieslist att params
            $attParams = VacanciesListModel::getAtts();

            // Get url parameters
            $urlParams = wp_parse_args(VacanciesListModel::validateUrlParams($_GET), [
                'page-number' => 1
            ]);

            // Fetch vacancies
            $vacanciesList = VacanciesListModel::get($urlParams, $attParams);

            if (is_wp_error($vacanciesList) || !is_array($vacanciesList)) {
                return;
            }

            if (!isset($vacanciesList['listOutput']) || empty($vacanciesList['listOutput'])) {
                status_header(404, __('No vacancies found.', 'otys-jobs-apply'));
            }
        }
    }
}