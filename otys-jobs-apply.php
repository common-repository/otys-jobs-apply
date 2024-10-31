<?php

/**
 * @package OtysPlugin
 * @author              OTYS
 * @copyright           2024 OTYS Recruiting Technology
 *
 * @wordpress-plugin
 * Plugin Name:         OTYS Jobs & Apply
 * Description:         Make your Wordpress website a proper recruitment website using the OTYS API
 * Version:             2.0.56
 * Requires at least:   6.0
 * Requires PHP:        7.4
 * Author:              OTYS
 * Author URI:          https://otys.nl
 * License:             GPL v2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         otys-jobs-apply
 * Domain Path:         /languages
 */

defined('WPINC') || die('No access');

define('OTYS_PLUGIN_FILE_URL', __FILE__);
define('OTYS_PLUGIN', dirname(__FILE__));
define('OTYS_PLUGIN_URL', plugins_url('', __FILE__));
define('OTYS_PLUGIN_ASSETS_URL', plugins_url('assets', __FILE__));
define('OTYS_PLUGIN_DIR_PATH', plugin_dir_path(__FILE__) . 'app');
define('OTYS_PLUGIN_DIR_URL', plugin_dir_url(__FILE__) . 'app');
define('OTYS_PLUGIN_DIR_LANG', dirname(__FILE__) . '/languages');
define('OTYS_PLUGIN_TEMPLATE_URL', OTYS_PLUGIN_DIR_PATH . '/views');
define('OTYS_PLUGIN_ADMIN_TEMPLATE_URL', OTYS_PLUGIN_TEMPLATE_URL . '/admin');
define('OTYS_PLUGIN_WEBHOOK_URL', home_url() . '/webhooks');
define('OTYS_PLUGIN_NAME', dirname(plugin_basename(__FILE__)));
define('OTYS_PLUGIN_PREFIX', 'otys');
define('OTYS_THEME_DIR', wp_get_theme() . '/otys-jobs-apply');
define('OTYS_PLUGIN_VERSION', '2.0.56');

// Load autoload
if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require dirname(__FILE__) . '/vendor/autoload.php';
} else {
    return false;
}

require_once('otys-routes.php');

// Create a new instance of the plugin
$otysPlugin = Otys\OtysPlugin\Includes\Core\OtysPlugin::getInstance();