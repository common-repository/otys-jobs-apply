<?php

namespace Otys\OtysPlugin\Controllers;

use Otys\OtysPlugin\Controllers\BaseController;
use Otys\OtysPlugin\Helpers\SettingHelper;
use Otys\OtysPlugin\Includes\Core\Enqueue;
use Otys\OtysPlugin\Includes\Core\Hooks;
use Otys\OtysPlugin\Includes\Core\Routes;
use Otys\OtysPlugin\Includes\OtysApi;
use Otys\OtysPlugin\Models\ProceduresModel;
use Otys\OtysPlugin\Models\Shortcodes\AuthModel;
use Otys\OtysPlugin\Models\VacanciesDetailModel;
use WP_REST_Request;
use WP_REST_Response;

class VacanciesDetailController extends BaseController
{
    public function callback(): void
    {
        global $post;

        $website = SettingHelper::getSiteId();

        $customSlugIsActive = Routes::customSlugIsActive();

        $identifier = $customSlugIsActive ? Routes::getRouteParameter('slug') : static::getVacancyUidFromUrl();

        $vacancy = VacanciesDetailModel::get($identifier, [], $customSlugIsActive);

        $isPreviewMode = $post->post_status === 'draft';

        // If something is wrong with the vacancy result
        if (is_wp_error($vacancy) || !$vacancy) {
            Routes::throwError(404);
        }

        // Redirect to new url if old slug is used
        if ($customSlugIsActive && $vacancy['slug'][$website] !== $identifier) {
            $newUrl = Routes::get('vacancy-detail', [
                'slug' => $vacancy['slug'][$website]
            ]);

            wp_redirect(trailingslashit(home_url($newUrl)), 301);

            exit();
        }

        // Check if vacancy is found is published for the current website
        $siteId = SettingHelper::getSiteId();
        
        if (!$vacancy["published"] && !$isPreviewMode) {
            Routes::throwError(404);
        }
        
        // Check if the vacancy is published on the current website if there are multiple websites
        $websites = OtysApi::getwebsites();

        if (count($websites) > 1 && !isset($vacancy["publishedWebsites"][$siteId]) && !$isPreviewMode) {
            Routes::throwError(404);
        }

        // Set post data
        $post->post_title = sprintf(
            _x('%1$s', 'Vacancy detail title', 'otys-jobs-apply'),
            $vacancy['metaTitle'] ? $vacancy['metaTitle'] : $vacancy['title']
        );

        // Set robots
        if ($isPreviewMode || (isset($vacancy['blockScanners']) && $vacancy['blockScanners'] === true)) {
            $post->robots = [
                'noindex' => true,
                'nofollow' => true
            ];
        }

        $post->meta_description = $vacancy['metaDescription'] ?? '';

        // Check publication date
        if (!$isPreviewMode) {
            $today = new \DateTime();
            $publicationStartDate = new \DateTime($vacancy['publicationStartDate']);
            $publicationEndDate = new \DateTime($vacancy['publicationEndDate'] . "23:59:59");

            if ($vacancy['publicationStartDate'] !== NULL && $vacancy['publicationEndDate'] !== NULL && ($publicationStartDate > $today || $publicationEndDate < $today)) {
                Routes::throwError(404);
            }
        }

        // Check if the user has already applied for this vacancy if logged in
        $alreadyApplied = false;

        if ($user = AuthModel::getUser()) {
            // Check if the user aready applied for this vacancy
            if (ProceduresModel::procedureExists($user->getCandidateUid(), $vacancy['uid'])) {
                $alreadyApplied = true;
                $vacancy['removeApplyButton'] = true;
            }
        }

        // Enqueue scripts
        Enqueue::addScript(['handle' => 'glide-js', 'src' => OTYS_PLUGIN_ASSETS_URL . '/js/vendors/glide.min.js']);
        Enqueue::addScript(['handle' => 'otys-vacancy-detail-js', 'src' => OTYS_PLUGIN_ASSETS_URL . '/js/vacancy-detail.min.js']);

        // Parse arguments to the template
        $this->parseArgs('uid', $vacancy['uid']);
        $this->parseArgs('vacancy', $vacancy);
        $this->parseArgs('already_applied', $alreadyApplied);

        $this->setTemplate('vacancies/vacancies-detail.php');
    }

    /**
     * Callback function for Vacancy Detail
     *
     * @since 1.0.0
     * @return void
     */
    public static function detailCallback(): void
    {
        /**
         * When wp_head action gets fired when want to implement Google for jobs
         * within the head tags. Therefore we add a callback action.
         */
        Hooks::addAction('wp_head', static::class, 'googleForJobs', 10, 1);
        Hooks::addFilter('wp_head', static::class, 'metaData', 1, 1);
        // Hooks::addAction('wp_head', static::class, 'vacancyJsInfo', 10, 1);
        Hooks::addAction('wp_footer', static::class, 'previewMode', 10, 1);
    }

    /**
     * Google for jobs header callback
     * Function is called when wp_head action is triggered
     *
     * @since 1.0.0
     * @param mixed $headers
     * @return mixed
     */
    public static function googleForJobs($headers)
    {
        $customSlugIsActive = Routes::customSlugIsActive();

        $identifier = $customSlugIsActive ? Routes::getRouteParameter('slug') : static::getVacancyUidFromUrl();

        $vacancy = VacanciesDetailModel::get($identifier, [], $customSlugIsActive);

        if (is_wp_error($vacancy) || !isset($vacancy['uid'])) {
            return $headers;
        }

        $googleForJobs = OtysApi::post([
            'method' => 'Otys.Services.GoogleForJobsService.getMappingValues',
            'params' => [
                $vacancy['uid'],
                SettingHelper::getSiteId()
            ]
        ], true);

        if (is_wp_error($googleForJobs) || empty($googleForJobs)) {
            return $headers;
        }

        $googleForJobsObject = [
            "@context" => "https://schema.org/",
            "@type" => "JobPosting",
            "identifier" => [
                "@type" => "PropertyValue"
            ],
            "hiringOrganization" => [
                "@type" => "Organization"
            ],
            "jobLocation" => [
                "@type" => "Place",
                "address" => [
                    "@type" => "PostalAddress"
                ]
            ],
            "baseSalary" => [
                "@type" => "MonetaryAmount",
                "value" => [
                    "@type" => "QuantitativeValue"
                ]
            ]
        ];

        foreach ($googleForJobs as $name => $value) {
            $explode = explode('___', $name);
            $array = OtysApi::owsFieldValuesToObject($explode, $value);

            $googleForJobsObject = array_merge_recursive($googleForJobsObject, $array);
        }

        static::loadTemplate('/vacancies/google-for-jobs.php', $googleForJobsObject);

        return $headers;
    }

    /**
     * Load meta data
     *
     * @param mixed $headers
     * @return mixed
     */
    public static function metaData($headers)
    {
        global $post;

        $data = [
            'meta_description' => $post->meta_description ?? '',
        ];
        
        static::loadTemplate('/vacancies/vacancies-meta-data.php', $data);

        return $headers;
    }

    /**
     * Vacancy JS info
     *
     * @since 2.0.0
     * @param mixed $headers
     * @return void
     */
    public static function vacancyJsInfo($headers)
    {
        $jsonInfo = [
            'vacancy' => [
                'uid' => static::getVacancyUidFromUrl()
            ]
        ];

        echo '<script>window.otys_jobs_apply = ' . json_encode($jsonInfo) . ';</script>';

        return $headers;
    }

    /**
     * REST POST
     *
     * @param WP_REST_Request $request
     * @return mixed
     */
    public static function track($request)
    {
        $url = parse_url(filter_var( $request->get_param('vacancy_url'), FILTER_SANITIZE_URL), PHP_URL_PATH);

        if (!$url) {
            return new WP_REST_Response(false);
        }

        $customSlugIsActive = Routes::customSlugIsActive();

        $identifier = $customSlugIsActive ? Routes::getRouteParameter('slug', $url) : static::getVacancyUidFromUrl($url);

        if ($identifier === null) {
            return new WP_REST_Response(false);
        }

        $vacancy = VacanciesDetailModel::get($identifier, [], $customSlugIsActive);

        if (is_wp_error($vacancy) || !isset($vacancy['uid'])) {
            return new WP_REST_Response(false);
        }

        $response = OtysApi::post(
            [
                'method' => 'Otys.Services.VacancyService.incrementViewCount',
                'params' => [
                    $vacancy['uid']
                ]
            ],
            false,
            false
        );

        if (is_wp_error($response)) {
            return new WP_REST_Response(false);
        }

        return new WP_REST_Response(true);
    }

    /**
     * Preview mode addons
     *
     * @since 2.0.0
     * @param $headers
     * @return void
     */
    public static function previewMode($headers)
    {
        global $post;

        // Check post status
        if ($post->post_status === 'draft') {
            static::loadTemplate('/vacancies/preview-mode.php', $post);
        }

        return $headers;
    }
}