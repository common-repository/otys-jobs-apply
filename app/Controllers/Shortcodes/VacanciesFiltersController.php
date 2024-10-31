<?php

namespace Otys\OtysPlugin\Controllers\Shortcodes;

use Otys\OtysPlugin\Models\Shortcodes\VacanciesFiltersModel;
use Otys\OtysPlugin\Models\Shortcodes\VacanciesListModel;

/**
 * [otys-vacancies-filters]
 *
 * @since 2.0.0
 */
final class VacanciesFiltersController extends ShortcodeBaseController
{
    /**
     * Displays the shortcode
     *
     * @since 2.0.0
     * @param array $atts
     * @return void
     */
    public function display(): void
    {
        $listAttributes = VacanciesListModel::getAtts();


        $listAtts = VacanciesListModel::validateAtts($listAttributes);

        $atts = array_merge($listAtts, VacanciesFiltersModel::validateAtts($this->getAtts()));

        $urlParams = VacanciesListModel::validateUrlParams($_GET);
        $filters = VacanciesFiltersModel::get($urlParams, $atts);

        // If there are no filters don't show the shortcode
        if (empty($filters)) {
            return;
        }

        $this->setArgs('matchCriteriaList', $filters);
        $this->setArgs('preSelectedCriteria', $listAttributes);

        $this->loadTemplate('vacancies/vacancies-filters.php');
    }
}
