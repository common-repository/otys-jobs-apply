<?php

namespace Otys\OtysPlugin\Controllers\Shortcodes;

use Otys\OtysPlugin\Helpers\SettingHelper;
use Otys\OtysPlugin\Includes\OtysApi;
use Otys\OtysPlugin\Models\DocumentsModel;
use Otys\OtysPlugin\Models\MailModel;
use Otys\OtysPlugin\Models\Shortcodes\JobAlertModel;

/**
 * Callback controller for the jobalert shortcode
 *
 * @since 2.0.34
 */
final class JobAlertController extends AbstractInteractionsController
{
  /**
   * Model to use
   * @var JobAlertModel
   */
    public static $modelClass = JobAlertModel::class;

    /**
     * Model
     * @var JobAlertModel
     */
    public $model;

    /**
     * Summary of __construct
     * @param array $atts
     * @param string $content
     * @param string $tag
     */
    public function __construct(array $atts = [], string $content = '', string $tag = '')
    {
        parent::__construct($atts, $content, $tag);

        if ($this->model === null) {
            return;
        }
    
        // Set the id of the interaction form programmatically
        $this->setAtt('id', $this->model->getId());
    }

    /**
     * Summary of beforeInit
     * @return void
     */
    public function beforeInit(): void
    {
        
    }

    /**
     * Do something before displaying the shortcode
     *
     * @since 2.0.x
     * @return void
     */
    public function beforeDisplay(): void
    {
        // Check if the jobalert is enabled
        $jobAlertEnabled = $this->model->isEnabled();

        // Jobalert options
        $filters = get_option('otys_option_jobalert_filters', []);

        if (empty($filters)) {
            $this->setArgs('pages', []);
            $this->setArgs('system_error', __('Please configure the jobalert settings. Please navigate to the Job Alert tab under OTYS settings in your WordPress admin panel and select which match criteria to show.', 'otys-jobs-apply'));
            $this->loadTemplate('interactions/interaction-system-error.php');
            return;
        }

        // Check if the confirmation email is set
        $confirmEmail = (int) get_option('otys_option_document_template_confirm_jobalert', 0) > 0;
        
        if (!$confirmEmail) {
            $this->setArgs('pages', []);
            $this->setArgs('system_error', __('Please configure the jobalert settings. Please navigate to the Job Alert tab under OTYS settings in your WordPress admin panel and select which document to use for the confirmation email.', 'otys-jobs-apply'));
            $this->loadTemplate('interactions/interaction-system-error.php');
            return;
        }

        $filterOrder = array_keys($filters);

        $filtersToShow = array_filter($filters, function($filter) {
            $show = $filter['show'] ?? false;

            return filter_var($show, FILTER_VALIDATE_BOOLEAN) === true;
        });
        
        $filtersToShow = array_keys($filtersToShow);

        // Get the pages from the model
        $pages = $this->getArg('pages');

        foreach ($pages as $pageKey => $page) {
            $fields = $page['fields'];

            $fields = array_filter($fields, function($field) use ($filtersToShow) {
                return in_array($field['wsField'], $filtersToShow);
            });

            // Sort fields based on order of wsField in $filters
            uksort($fields, function($key1, $key2) use ($filterOrder, $fields) {
                $key1FieldName = $fields[$key1]['wsField'];
                $key2FieldName = $fields[$key2]['wsField'];
                
                $key1pos = array_search($key1FieldName, $filterOrder);
                $key2pos = array_search($key2FieldName, $filterOrder);

                return ($key1pos > $key2pos) ? 1 : -1;
            });

            $pages[$pageKey]['fields'] = $fields;
        }

        // Overwrite the pages with the pages that have filtered fields
        $this->setArgs('pages', $pages);

        // Set the success message
        $this->setArgs('success_message', __('Thank you for creating a job alert. An email has been send to your email address with a confirmation link.', 'otys-jobs-apply'));

        // Set the submit button text
        $this->setArgs('submit_button_text', __('Subscribe', 'otys-jobs-apply'));
    }

    /**
     * We'll send the confirmation email after the jobalert has been created
     *
     * @param array $commitResponse
     * @return void
     */
    public static function successCallback(array $commitResponse): void
    {
        $jobAlertUid = $commitResponse['addedRecordUid'] ?? false;

        if (!$jobAlertUid) {
            throw new \Exception(__('Something went wrong while creating the jobalert. Please contact OTYS support.', 'otys-jobs-apply'));
        }

        // Get job alert detail
        $jobAlert = OtysApi::post([
            'method' => 'Otys.Services.JobSearchAgentService.getDetail',
            'params' => [
                $jobAlertUid
            ]
        ], true, false);

        if (!isset($jobAlert['email'])) {
            throw new \Exception(__('No email address found for the jobalert. Please contact OTYS support.', 'otys-jobs-apply'));
        }

        if (is_wp_error($jobAlert) || !isset($jobAlert['code'])) {
            throw new \Exception(__('Something went wrong while getting the jobalert details. Please contact OTYS support.', 'otys-jobs-apply'));
        }

        $documentUid = get_option('otys_option_document_template_confirm_jobalert', false);

        // If the document is not set, we throw an exception
        if (!$documentUid) {
            throw new \Exception(__('No document set for the jobalert confirmation email, please configure this in your WordPress admin panel.', 'otys-jobs-apply'));
        }

        $websites = OtysApi::getWebsiteUrls();

        $siteId = SettingHelper::getSiteId();

        $websiteUrl = false;
    
        foreach ($websites as $website) {
            if ($siteId === $website['siteId']) {
                $websiteUrl = $website['website'];
                break;
            }
        }

        if (!$websiteUrl) {
            throw new \Exception(__('No OTYS website found for your website, please contact OTYS support.', 'otys-jobs-apply'));
        }

        
        // Get the document
        $document = DocumentsModel::get($documentUid, [
            'answers' => [
                'activation_link' => $websiteUrl . '/index.php/page/advsearchvacs/bb/1/command/activatesq/uid/' . $jobAlert['code'],
                'delete_link' => $websiteUrl . '/index.php/page/advsearchvacs/bb/1/command/deletesq/uid/' . $jobAlert['code'],
                'website_search_link' => site_url('/jobalert/'. $jobAlert['code'] . '/'),
                'website_link' => site_url('/')
            ]
        ]);

        if (is_wp_error($document)) {
            throw new \Exception(sprintf(
                __('Something went wrong while getting the mail confirmation configuration. Please contact %s.','otys-jobs-apply'), 
                get_bloginfo('admin_email')
            ));
        }

        // Send confirmation email
        MailModel::send([
            'subject' => $document['subject'],
            'htmlMessage' => $document['htmlBody'],
            'To' => $jobAlert['email']
        ]);
    }
}