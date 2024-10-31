<?php

namespace Otys\OtysPlugin\Controllers\Admin;

use Otys\OtysPlugin\Includes\Core\Webhooks;
use Otys\OtysPlugin\Includes\Core\Hooks;

use Otys\OtysPlugin\Includes\OtysApi\OtysApi;
use Otys\OtysPlugin\Models\Admin\AdminWebhooksModel;

class AdminWebhooksController extends AdminBaseController
{
    /**
     * The pagename
     * Pagename gets used in every setting registration
     * @since 1.0.0
     */
    private const PAGE_NAME = 'otys_webhooks';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Run functions before class initialisation.
     * function gets called when registering controller at routes.
     *
     * @return void
     * @since 1.0.0
     */
    public static function beforeInit(): void
    {
        // Initialise the settings on page load
        Hooks::addAction('admin_init', __class__, 'initSettings', 10, 0);
    }

     /**
     * Init settings for admin settings page
     *
     * @return void
     * @since 1.0.0
     */
    public static function initSettings(): void
    {
        /*
            API Section
        */
        // Add API Section to this page
        add_settings_section(
            'otys_webhooks_section',
            __('Api settings', 'otys-jobs-apply'),
            function () {
            },
            self::PAGE_NAME
        );

        // register a new field in the "otys_webhooks_section" section, inside the "otys_settings" page
        register_setting(self::PAGE_NAME, 'otys_option_webhook_url');
        add_settings_field(
            'otys_option_webhook_url',
            __('Webhook url', 'otys-jobs-apply'),
            [__class__, 'textField'],
            self::PAGE_NAME,
            'otys_webhooks_section',
            [
                'option_name' => 'otys_option_webhook_url',
                'option_section' => 'otys_webhooks_section',
                'description' => sprintf(
                    __('The webhook url should on live websites always point at %1$s. 
                    It is not recommended to change this url. This setting is here solely for development purposes. 
                    For safety this setting only gets used when debug mode is on. 
                    Remember to click the Register hooks button to register hooks with OTYS, otherwise changes will not have any effect. 
                    To make sure the correct webhooks are registered, see the list of registed webhooks below.', 'otys-jobs-apply'),
                    OTYS_PLUGIN_WEBHOOK_URL
                )
            ]
        );
    }

    /**
     * Admin webhooks page
     *
     * @return void
     * @since 1.0.0
     */
    public function index()
    {
        global $wp;

        AdminWebhooksModel::checkWebhooks();

        if (isset($_GET['register_hooks']) && $_GET['register_hooks'] == 1) {
            AdminWebhooksModel::deleteWebhooks();
            AdminWebhooksModel::registerWebhooks();
        }

        $webhooksList = AdminWebhooksModel::getList();

        foreach ($webhooksList as $key => $webhook) {
            $webhooksList[$key]['registered'] =  AdminWebhooksModel::getServiceCallback($webhook['serviceCall']);
        }

        $this->parseArgs('webhooks_list', $webhooksList);
        $this->parseArgs('webhooks_logs', AdminWebhooksModel::getLogs());
    }
}
