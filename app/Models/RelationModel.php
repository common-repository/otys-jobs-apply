<?php

namespace Otys\OtysPlugin\Models;

use Otys\OtysPlugin\Includes\OtysApi;
use Otys\OtysPlugin\Models\Shortcodes\ShortcodeBaseModel;

class RelationModel extends ShortcodeBaseModel
{
    /**
     * Get relation by Customer ID
     *
     * @param string    $customerId     | Expected format xxxx.xx.xxxx. Customer ID can be found
     *                                    in the OTYS Go CRM.
     * @return array
     */
    public static function getById(string $customerId): array
    {
        // Retrieve filters via API
        $response = OtysApi::post([
            'method' => 'Otys.Services.RelationService.getList',
            'params' => [[
                "what" => [
                    "uid" => 1,
                    "relation" => 1,
                    "city" => 1
                ],
                "condition" => [
                    "type" => "COND",
                    "field" => "customerNr",
                    "op" => "EQ",
                    "param" => "{$customerId}"
                ],
                "limit" => 1
            ]
        ]], true, false);

        if (!empty($response) && is_array($response) && isset($response[0])) {
            return $response[0];
        }

        return [];
    }
}