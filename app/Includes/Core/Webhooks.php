<?php

namespace Otys\OtysPlugin\Includes\Core;

use Otys\OtysPlugin\Includes\OtysApi;
use WP_Error;

class Webhooks extends Base
{
    /**
     * Stores list of all webhooks to be registered at OTYS
     */
    protected $webhooks = array();

    public function __construct()
    {
        $this->add('VacancyService.delete', ['\Otys\OtysPlugin\Controllers\WebhooksController', 'webhookVacancyUpdate']);
        $this->add('VacancyService.add', ['\Otys\OtysPlugin\Controllers\WebhooksController', 'webhookVacancyUpdate']);
        $this->add('FreeInteractionService.sessionCommit', ['\Otys\OtysPlugin\Controllers\WebhooksController', 'webhookInteractions']);
        $this->add('VacancyService.update', ['\Otys\OtysPlugin\Controllers\WebhooksController', 'webhookVacancyUpdate']);
        $this->add('VacancyService.update', ['\Otys\OtysPlugin\Controllers\WebhooksController', 'webhookVacancyUpdate']);
        $this->add('VacancyService.updateEx', ['\Otys\OtysPlugin\Controllers\WebhooksController', 'webhookVacancyUpdate']);
        $this->add('MatchCriteriaService.update', ['\Otys\OtysPlugin\Controllers\WebhooksController', 'webhookMatchCriteriaUpdate']);
        $this->add('MatchCriteriaSettingsService.update', ['\Otys\OtysPlugin\Controllers\WebhooksController', 'webhookMatchCriteriaUpdate']);
        $this->add('CandidateQuestionSetService.update', ['\Otys\OtysPlugin\Controllers\WebhooksController', 'webhookQuestionsetUpdate']);
        $this->add('CandidateQuestionSetService.delete', ['\Otys\OtysPlugin\Controllers\WebhooksController', 'webhookQuestionsetUpdate']);
        $this->add('CsmService.setValue', ['\Otys\OtysPlugin\Controllers\WebhooksController', 'webhookCsmUpdate']);
        $this->add('VacancyService.setExtraFieldValue', ['\Otys\OtysPlugin\Controllers\WebhooksController', 'webhookVacancyUpdate']);
        $this->add('VacancyService.assignQuestionSet', ['\Otys\OtysPlugin\Controllers\WebhooksController', 'webhookVacancyUpdate']);
        $this->add('VacancyService.saveCustomSlug', ['\Otys\OtysPlugin\Controllers\WebhooksController', 'webhookVacancyUpdate']);
        $this->add('VacancyService.resetVacancySlug', ['\Otys\OtysPlugin\Controllers\WebhooksController', 'webhookVacancyUpdate']);
    }

    /**
     * Check if the webhooks are added correctly
     *
     * @return boolean
     */
    public function checkWebhooks()
    {
        $webhooks = $this->getList();

        $registeredWebhooks = OtysApi::post([
            'method' => 'Otys.Services.WebhooksService.getList'
        ], false, false);

        $filteredHooks = array_filter($registeredWebhooks, function($webhook) {
            $webhookUrl = static::getWebhookUrl();

            // Filter webhooks registered for other websites
            if (strpos($webhook['webhookUrl'], $webhookUrl) === false) {
                return false;
            }

            return true;
        });

        if (!is_array($filteredHooks) && !is_array($webhooks)) {
            return false;
        }

        // If the same amount of webhooks are registered for the current webhook url then return true
        if (count($filteredHooks) === count($webhooks)) {
            return true;
        }

        return false;
    }

    /**
     * Add webhook to the list of to be registered webhooks
     *
     * @param  string $serviceCall  The OWS service call that should trigger the webhook
     * @param  string $webHookUrl The webhook URL that is being called if the OWS service call is executed
     * @param  bool $global Wheter the webhook gets trigged by all clients or only the webhook client where
     * true triggers the webhook for all users and false only triggers for the webhook user
     * @return void
     */
    public function add(string $serviceCall, $callback, bool $klantGlobal = true): void
    {
        $slug = sanitize_title($serviceCall);

        $this->webhooks[$slug] = [
            'serviceCall' => $serviceCall,
            'webhookUrl' => static::getWebhookUrl(),
            'callback' => $callback,
            'klantGlobal' => $klantGlobal
        ];
    }

    /**
     * Get list of webhooks added
     *
     * @return array
     * @since 1.0.0
     */
    public function getList(): array
    {
        return $this->webhooks;
    }

    /**
     * Retrieve webhook from the list of webhooks
     *
     * @param  string $serviceCall  The OWS service call that should trigger the webhook
     * @param  string $webHookUrl The webhook URL that is being called if the OWS service call is executed
     * @param  bool $global Wheter the webhook gets trigged by all clients or only the webhook client where
     * true triggers the webhook for all users and false only triggers for the webhook user
     * @return array
     */
    public function get(string $serviceCall): array
    {
        $slug = sanitize_title($serviceCall);

        if (array_key_exists($slug, $this->webhooks)) {
            return $this->webhooks[$slug];
        }

        return [];
    }

    /**
     * Registers all webhooks with OTYS
     *
     * @return array|string|WP_Error
     */
    public function registerWebhooks($session = false)
    {
        $otysApi = new OtysApi();

        $responses = [];

        $session = $session ? $session : $otysApi->getSession();

        // Save requests to add to multiservice
        $requests = [];

        // Loop through webhooks and add them to the multiservice request
        foreach ($this->getList() as $slug => $webhook) {
            // Check if the webhookUrl not is localhost since we don't want to register webhooks which are local
            $requests[] = [
                "method" => "Otys.Services.WebhooksService.add",
                "args" => [
                    $session,
                    [
                        "serviceCall" => $webhook['serviceCall'],
                        "webhookUrl" => $webhook['webhookUrl'],
                        "klantGlobal" => $webhook['klantGlobal']
                    ]
                ]
            ];
        }

        $multiServiceRequest = [
            "jsonrpc" => "2.0",
            "method" => "Otys.Services.MultiService.execute",
            "params" => [
                $requests
            ],
            "id" => 1
        ];


        $responses = $otysApi->post($multiServiceRequest, false, false);

        return $responses;
    }

    /**
     * Get webhook url
     *
     * @return string
     */
    public static function getWebhookUrl(): string
    {
        $webhookCustomUrl = get_option('otys_option_webhook_url', OTYS_PLUGIN_WEBHOOK_URL);

        if (WP_DEBUG && filter_var($webhookCustomUrl, FILTER_VALIDATE_URL) !== FALSE) {
            return $webhookCustomUrl;
        }

        return OTYS_PLUGIN_WEBHOOK_URL;
    }

    /**
     * Deletes all webhooks registered at OTYS
     *
     * @return void
     */
    public static function deleteWebhooks($session = false): void
    {
        $otysApi = new OtysApi();

        $session = $session ? $session : $otysApi->getSession();

        // First get a list of all registered webhooks
        $webhooks = $otysApi->post([
            "jsonrpc" => "2.0",
            "method" => "Otys.Services.WebhooksService.getList",
            "params" => [
                $otysApi->getSession()
            ],
            "id" => 1
        ], false, false);

        
        // If there are webhooks
        if ($webhooks) {
            
            // Save requests to use in multiservice request
            $requests = [];

            // Get webhook url
            $webhookUrl = static::getWebhookUrl();

            // Remove all webhooks individually
            foreach ($webhooks as $webhook) {
                // Check if the webhook contains the plugin's webhook url if so we will delete it
                if (
                    strpos($webhook['webhookUrl'], OTYS_PLUGIN_WEBHOOK_URL) !== false ||
                    strpos($webhook['webhookUrl'], $webhookUrl) !== false ||
                    $webhook['webhookUrl'] == '' ||
                    filter_var($webhook['webhookUrl'], FILTER_VALIDATE_URL) === FALSE
                ) {
                    $requests[] = [
                        "method" => "Otys.Services.WebhooksService.delete",
                        "args" => [
                            $otysApi->getSession(),
                            $webhook['uid']
                        ]
                    ];
                }
            }
            
            // If requests is not empty build a multiservice
            if (!empty($requests)) {
                $multiServiceRequest = [
                    "jsonrpc" => "2.0",
                    "method" => "Otys.Services.MultiService.execute",
                    "params" => [
                        $requests
                    ],
                    "id" => 1
                ];

                $otysApi->post($multiServiceRequest, false, false);
            }
        }
    }
}
