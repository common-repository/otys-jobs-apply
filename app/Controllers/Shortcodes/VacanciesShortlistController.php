<?php

namespace Otys\OtysPlugin\Controllers\Shortcodes;

use Otys\OtysPlugin\Models\Shortcodes\VacanciesShortlistModel;

/**
 * [otys-vacancies-shortlist]
 *
 * @since 2.0.0
 */
final class VacanciesShortlistController extends ShortcodeBaseController
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

        // Get url parameters
        $attParams = VacanciesShortlistModel::validateAtts($this->getAtts());
   
        $attParams = wp_parse_args($attParams, [
            'limit' => 3,
            'mode' => 'shortlist'
        ]);

        // Fetch vacancies
        $vacanciesList = VacanciesShortlistModel::get($attParams, [
            'limit' => intval($attParams['limit'])
        ]);

        // Don't display the shortcode if there's an error
        if (is_wp_error($vacanciesList)) {
            return;
        }

        $totalVacancies = 
        !is_wp_error($vacanciesList) &&
        isset($vacanciesList['searchExtras']) &&
        isset($vacanciesList['searchExtras']['ACTONOMY']) &&
        isset($vacanciesList['searchExtras']['ACTONOMY']['totalCount']) ? 
            intval($vacanciesList['searchExtras']['ACTONOMY']['totalCount']) : 0;

        $vacancies = !is_wp_error($vacanciesList) && isset($vacanciesList['listOutput']) ?
            $vacanciesList['listOutput'] : [];

        $this->setArgs('vacancies', $vacancies);

        $this->setArgs('vacanciesTotal', $totalVacancies);

        $this->loadTemplate('vacancies/vacancies-shortlist.php');
    }
}