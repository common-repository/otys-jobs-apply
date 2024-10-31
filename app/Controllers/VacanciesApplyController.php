<?php

namespace Otys\OtysPlugin\Controllers;

use Otys\OtysPlugin\Controllers\BaseController;
use Otys\OtysPlugin\Helpers\SettingHelper;
use Otys\OtysPlugin\Includes\Core\Routes;
use Otys\OtysPlugin\Includes\OtysApi;
use Otys\OtysPlugin\Models\VacanciesDetailModel;

/**
 * Vacancies application page controller
 *
 * @since 2.0.0
 */
class VacanciesApplyController extends BaseController
{
    public function callback(): void
    {
        global $post;

        $customSlugIsActive = Routes::customSlugIsActive();

        $identifier = $customSlugIsActive ? Routes::getRouteParameter('slug') : static::getVacancyUidFromUrl();

        $vacancy = VacanciesDetailModel::get($identifier, [], $customSlugIsActive);

        // If something is wrong with the vacancy result
        if (is_wp_error($vacancy) || !$vacancy) {
            Routes::throwError(404);
        }

       // Check if vacancy is found is published for the current website
       $siteId = SettingHelper::getSiteId();
       if (!$vacancy["published"]) {
           Routes::throwError(404);
       }

       $websites = OtysApi::getwebsites();
       // Check if this is a multisite environment
       if (count($websites) > 1) {
           if(!isset($vacancy["publishedWebsites"][$siteId])){
               Routes::throwError(404);
           }
       }

        // Set post data
        $post->post_title = sprintf(
            __('Apply for %1$s', 'otys-jobs-apply'),
            $vacancy['title']
        );

        $post->robots = [
            'noindex' => true,
            'nofollow' => true
        ];

        // Check publication date
        $today = new \DateTime();
        $publicationStartDate = new \DateTime($vacancy['publicationStartDate']);
        $publicationEndDate = new \DateTime($vacancy['publicationEndDate'] . "23:59:59");

        if ($vacancy['publicationStartDate'] !== NULL && $vacancy['publicationEndDate'] !== NULL && ($publicationStartDate > $today || $publicationEndDate < $today)) {
            Routes::throwError(404);
        }

        if (isset($vacancy['removeApplyButton']) && $vacancy['removeApplyButton'] === true) {
            Routes::throwError(404);
        }

        $this->parseArgs('uid', $vacancy['uid']);
        $this->parseArgs('vacancy', $vacancy);

        $this->setTemplate('vacancies/apply/vacancies-apply.php');
    }
}