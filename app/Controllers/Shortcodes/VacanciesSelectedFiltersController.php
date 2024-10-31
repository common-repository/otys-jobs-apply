<?php

namespace Otys\OtysPlugin\Controllers\Shortcodes;

use Otys\OtysPlugin\Models\Shortcodes\VacanciesSelectedFiltersModel;

/**
 * [otys-vacancies-selected-filters]
 *
 * @since 2.0.0
 */
final class VacanciesSelectedFiltersController extends ShortcodeBaseController
{
    /**
     * Displays the shortcode
     *
     * @return void
     */
    public function display(): void
    {
        $urlParams = VacanciesSelectedFiltersModel::validateUrlParams($_GET);

        $filters = VacanciesSelectedFiltersModel::get($urlParams);

        $this->setArgs('filters', $filters);
        $this->loadTemplate('vacancies/vacancies-selected-filtes.php');
    }
}