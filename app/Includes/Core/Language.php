<?php

namespace Otys\OtysPlugin\Includes\Core;

class Language extends Base
{
    public function __construct()
    {
        Hooks::addAction('init', $this, 'textDomainInit');
        Hooks::addFilter('locale', $this, 'setLocale', 1);
    }

    function textDomainInit()
    {
        $load = load_plugin_textdomain('otys-jobs-apply', false, OTYS_PLUGIN_NAME . '/languages');
    }

    /**
     * Filter locale so it returns en_US when no locale is
     * given
     *
     * @param string $locale
     * @return string
     * @since 1.0.0
     */
    public function setlocale(string $locale): string
    {
        if ($locale === '') {
            return 'en_US';
        }

        return $locale;
    }
}