<?php

namespace Otys\OtysPlugin\Models\Admin;

use Otys\OtysPlugin\Includes\OtysApi;

class AdminJobAlertModel extends AdminBaseModel
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Check if job alert is enabled
     *
     * @return boolean
     */
    public function isEnabled(): bool
    {
        $response = OtysApi::post([
            'method' => 'Otys.Services.JobSearchAgentService.getListEx',
            'params' => [
                [
                    "Person.languageCode"
                ]
            ]
        ], true, false);

        if (is_wp_error($response)) {
            return false;
        }

        return true;
    }
}
