<?php

namespace Otys\OtysPlugin\Models\Admin;

use Otys\OtysPlugin\Includes\Core\Webhooks;
use Otys\OtysPlugin\Includes\OtysApi;

/**
 * Manage OWS webhooks
 *
 * @since 2.0.0
 */
class AdminWebhooksModel extends AdminBaseModel
{
    /**
     * Get list of callbacks for all services
     * This list is used to register webhooks for each service. 
     *
     * @since 2.0.0
     * @return array    Returns service and callbacks as key value pairs.
     */
    public static function getServicesCallbacks(): array
    {
        $webhooks = new Webhooks();

        $webhooksList = $webhooks->getList();

        $return = [];

        foreach ($webhooksList as $webhook) {
            $return[$webhook['serviceCall']] = $webhook['callback'];
        }

        return $return;
    }

    /**
     * Returns callback for service
     *
     * @param string $service   Name of the OWS service.
     * @return string|bool      Returns callback for service or false if not found.
     */
    public static function getServiceCallback(string $service)
    {
        $callbacks = static::getServicesCallbacks();

        return array_key_exists($service, $callbacks) ? $callbacks[$service] : false;
    }

    /**
     * Get webhook url
     *
     * @since 2.0.0
     * @return string   Returns webhook url for current website.
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
     * Get list of registered webhooks
     *
     * @since 2.0.0
     * @return array    Returns list of webhooks registered at OTYS.
     */
    public static function getList(): array
    {
        $webhooks = OtysApi::post([
            'method' => 'Otys.Services.WebhooksService.getList',
            'params' => []
        ]);

        if (is_wp_error($webhooks)) {
            return [];
        }

        $webhooksUrl = static::getWebhookUrl();

        $webhooks = array_filter($webhooks, function($value) use ($webhooksUrl) {
            if(strpos($webhooksUrl, $value['webhookUrl']) === false) {
                return false;
            }

            return true;
        });

        return $webhooks;
    }

    /**
     * Registers all webhooks with OTYS
     *
     * @since 2.0.0
     * @return void
     */
    public static function registerWebhooks($session = false)
    {
        $webhooks = static::getServicesCallbacks();

        if (is_wp_error($webhooks) || !is_array($webhooks)) {
            return;
        }

        $params = [];

        $webhooksUrl = static::getWebhookUrl();

        foreach ($webhooks as $service => $callback) {
            $params[] = [
                'method' => 'Otys.Services.WebhooksService.add',
                'args' => [
                    [
                        'serviceCall' => $service,
                        'webhookUrl' => $webhooksUrl,
                        'klantGlobal' => true
                    ]
                ]
            ];
        }

        OtysApi::post([
            'method' => 'Otys.Services.MultiService.execute',
            'params' => [
                $params
            ]
        ], false);
    }

    /**
     * Get logs.
     *
     * @since 2.0.0
     * @return array    Returns webhooks logs including requests and responses.
     */
    public static function getLogs(): array
    {
        $webhooks = OtysApi::post([
            'method' => 'Otys.Services.WebhooksService.getLogs',
            'params' => [
                1000
            ]
        ], false, false);

        if (is_wp_error($webhooks) || !is_array($webhooks) || !isset($webhooks[0]) || $webhooks[0] === null) {
            return [];
        }

        $webhooksUrl = static::getWebhookUrl();        
        $webhooks = array_filter($webhooks[0], function($value) use ($webhooksUrl) {
            if (!isset($value['webhook_url'])) {
                return false;
            }

            if(strpos($webhooksUrl, $value['webhook_url']) === false) {
                return false;
            }

            return true;
        });

        return $webhooks;
    }

    /**
     * Delete webhooks
     *
     * @since 2.0.0
     * @return void
     */
    public static function deleteWebhooks(): void
    {
        $webhooks = static::getList();

        if (is_wp_error($webhooks) || !is_array($webhooks)) {
            return;
        }

        $params = [];

        foreach ($webhooks as $webhook) {
            $params[] = [
                'method' => 'Otys.Services.WebhooksService.delete',
                'args' => [
                    $webhook['uid']
                ]
            ];
        }

        OtysApi::post([
            'method' => 'Otys.Services.MultiService.execute',
            'params' => [
                $params
            ]
        ], false);
    }

    /**
     * Check if the webhooks are added correctly
     *
     * @since 2.0.0
     * @return boolean
     */
    public static function checkWebhooks(): void
    {
        $webhooks = static::getList();
        $webhookCallbacks = static::getServicesCallbacks();

        if (is_wp_error($webhooks) || !is_array($webhooks)) {
            return;
        }

        if (count($webhooks) !== count($webhookCallbacks)) {
            static::deleteWebhooks();
            static::registerWebhooks();
        }
    }
}