<?php

namespace Otys\OtysPlugin\Helpers;
use Otys\OtysPlugin\Includes\OtysApi;

class SettingHelper
{
    /**
     * Get OTYS site ID
     *
     * @return integer
     */
    public static function getSiteId(): int
    {
        $websiteOption = intval(get_option('otys_option_website', 1));
        
        // If website option is 1 we can return 1 because it's the default value
        if ($websiteOption === 1) {
            return 1;
        }

        // Get websites from API from current client
        $websites = OtysApi::getwebsites();

        // If website option is 0 or not set we can return 1 because it's the default value
        if ($websiteOption === 0 || !isset($websites[$websiteOption])) {
            return 1;
        }

        return $websiteOption;
    }
}