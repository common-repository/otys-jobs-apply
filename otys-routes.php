<?php

/**
 * Routes file
 *
 * Routes registers all public and admin pages
 */

use Otys\OtysPlugin\Includes\Core\Routes;
use Otys\OtysPlugin\Includes\Core\AdminPages;

/**
 * Add routes
 */
Routes::addRoutes();

/**
 *
 * Admin pages
 *
 */
AdminPages::add(
    'OTYS',
    'OTYS',
    'manage_options',
    'otys',
    ['Otys\OtysPlugin\Controllers\Admin\AdminSettingsController', 'init'],
    OTYS_PLUGIN_ASSETS_URL . '/images/admin/menu-icon.svg'
);
AdminPages::addSub(
    'otys',
    __('Settings', 'otys-jobs-apply'),
    __('Settings', 'otys-jobs-apply'), 
    'manage_options',
    'otys_settings',
    [
        'Otys\OtysPlugin\Controllers\Admin\AdminSettingsController',
        'init'
    ]
);

AdminPages::addSub(
    'otys',
    __('Cache', 'otys-jobs-apply'),
    __('Cache', 'otys-jobs-apply'),
    'manage_options',
    'otys_cache',
    [
        'Otys\OtysPlugin\Controllers\Admin\AdminCacheController',
        'init'
    ]
);

AdminPages::addSub(
    'otys',
    __('Jobalert', 'otys-jobs-apply'),
    __('Job alert', 'otys-jobs-apply'),
    'not_yet_used',
    'otys_jobalert',
    [
        'Otys\OtysPlugin\Controllers\Admin\AdminJobAlertController',
        'init'
    ]
);

AdminPages::addSub(
    'otys',
    __('Webhooks', 'otys-jobs-apply'),
    __('Webhooks', 'otys-jobs-apply'),
    'manage_options',
    'otys_webhooks',
    ['Otys\OtysPlugin\Controllers\Admin\AdminWebhooksController', 'init'],
    null,
    true
);

AdminPages::addSub(
    'otys',
    __('API Logs', 'otys-jobs-apply'),
    __('API Logs', 'otys-jobs-apply'),
    'manage_options',
    'otys_logs',
    ['Otys\OtysPlugin\Controllers\Admin\AdminLogsController', 'init']
);

AdminPages::addSub(
    'otys',
    __('Instructions', 'otys-jobs-apply'),
    __('Instructions', 'otys-jobs-apply'), 
    'manage_options',
    'otys_instructions',
    ['Otys\OtysPlugin\Controllers\Admin\AdminInstructionsController', 'init']
);
