<?php

namespace Otys\OtysPlugin\Helpers;

class DeviceHelpers
{
    /**
     * Check if the user is on a mobile device
     *
     * @return bool|int
     */
    public static function isMobile(): bool|int
    {
        return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
    }
}