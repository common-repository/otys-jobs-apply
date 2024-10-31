<?php

namespace Otys\OtysPlugin\Controllers\Admin;

use Otys\OtysPlugin\Includes\Core\Base;
use Otys\OtysPlugin\Models\DocumentsModel;
use Otys\OtysPlugin\Models\Shortcodes\JobAlertModel;
use Otys\OtysPlugin\Models\Shortcodes\VacanciesFiltersModel;
use WP_Error;

abstract class AdminBaseController extends Base
{
    /**
     * Store all intances, used to keep track of all instances created
     *
     * @since 1.0.0
     */
    protected static $instances = array();

    /**
     * Store errors thrown using WP_ERROR
     *
     * @var bool
     *
     * @since 1.0.0
     */
    public $error = array();

    /**
     * Holds the args that are parsed to the template using the Wordpress load_template function
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected $args = array();

    /**
     * Store the instance of the ascociated model of the called class
     *
     * @var mixed
     */
    public $model;

    public function __construct()
    {
        $this->getModel();
    }

    /**
     * Filter menu title
     *
     * @param string $menuTitle
     * @return string
     */
    public static function filterMenuTitle(string $menuTitle): string {
        return $menuTitle;
    }

    /**
     * Get Model based on the controller name
     * i.e. when a controller is called MyController we expect a model called MyModel
     * Admin models are located within the \Otys\OtysPlugin\Model\Admin Workspace
     * @return mixed
     *
     * @since 1.0.0
     */
    protected function getModel()
    {
        // Get called classname
        $className = substr(strrchr(get_called_class(), "\\"), 1);

        // Generate expected Model class name
        $name = str_replace('Controller', 'Model', $className);

        // Expected class including namespace
        $class = '\Otys\OtysPlugin\Models\Admin\\' . $name;

        if (class_exists($class)) {
            $this->model = new $class();
        } else {
            $error = new WP_Error('model_not_found', 'Model not found', '<span class="error-message">Model file expected at: "' . $class . '"</span>');

            $this->throwError($error, true);
        }

        return $this->model;
    }

    /**
     * Function that allows to register i.e. hooks & shortcodes using the Includes\Hooks class
     *
     * Make sure to avoid creating instances in beforeInit since this gets always called within the Wordpress
     * Admin panel!
     * @return void
     *
     * @since 1.0.0
     */
    public static function beforeInit()
    {
    }

    /**
     * Function that allows to register i.e. hooks & shortcodes using the Includes\Hooks class
     *
     * Make sure to avoid creating instances in beforeInit since this gets always called within the Wordpress
     * Admin panel!
     * @return void
     *
     * @since 1.0.0
     */
    public static function onInit()
    {
    }

    /**
     * Get template path
     *
     * @param string $fileName
     * @return string
     */
    public static function getTemplatePath(string $fileName = ''): string
    {
        $className = substr(strrchr(get_called_class(), "\\"), 1);
        $name = str_replace('Controller', '', $className);

        // Convert Camel case class name to dashed i.e. 'MyController' to 'my-controller'
        $classSlug = strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $name));
        
        // fileName
        $fileName = $fileName !== '' ? $fileName : $classSlug;
        
        // Generate filepath based on slug
        return (string) OTYS_PLUGIN_ADMIN_TEMPLATE_URL . '/' . $classSlug . '/'  . $fileName . '.php';
    }

    /**
     * Load template based on called class
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function loadTemplate()
    {
        $filepath = self::getTemplatePath();

        if (file_exists($filepath)) {
            load_template($filepath, true, $this->args);
        } else {
            $error = new WP_Error(
                'template_not_found',
                'Template not found',
                '<div class="error-message">Unable to locate template file.</div>
                <div class="error-message">Based on naming conventions the template is expected to be located at: <br/>&quot;<i>' . $filepath . '</i>&quot;</div>'
            );

            $this->throwError($error);
        }
    }

    /**
     * Loading error template for admin panel
     * Admin error template are located in app/views/admin/errors/..
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function throwError($error = null, $die = false, $template = null)
    {
        if (null !== $template && file_exists($template)) {
            load_template(OTYS_PLUGIN_ADMIN_TEMPLATE_URL . '/errors/' . $template, true, $error);
        } else {
            load_template(OTYS_PLUGIN_ADMIN_TEMPLATE_URL . '/errors/error-default.php', true, $error);
        }

        if ($die) {
            die();
        }
    }

    /**
     * checks Action and runs function based on the action provided
     *
     * By Default the index function gets called if it is callable if any other action is specified it tries to call the specified action instead
     *
     * @return object
     *
     * @since 1.0.0
     */
    public function checkAction()
    {
        $function = isset($_GET['action']) ? [self::getInstance(), sanitize_text_field($_GET['action'])] : [self::getInstance(), 'index'];
        if (is_callable($function)) {
            call_user_func($function);
        }

        return self::getInstance();
    }

    /**
     * Parse args to template using the Wordpress load_template function
     *
     * The loadTemplate function uses the wordpress load_template function which allows to to parse
     * args to the template to load. Using parseArgs() we are saving args to the $this->args array
     * which we can later on parse to the template.
     *
     * @param string $name Name of argument this is later on used as $args[$name] in the template
     * @param mixed $data The data you want to parse to the template
     * @return void
     *
     * @since 1.0.0
     */
    public function parseArgs(string $name, $data): void
    {
        $this->args[$name] = $data;
    }

    /**
     * Create instance of called class and load ascociated template based on classname
     * This function may only be called as a callback of the add_(sub)menu_page from Wordpress
     * @return void
     *
     * @since 1.0.0
     */
    public static function init()
    {
        self::getInstance()->checkAction()->loadTemplate();
    }

    /**
     * Textfield callback
     */
    public static function textField($args)
    {
        $option = get_option($args['option_name']);

        $args = wp_parse_args(
            $args,
            array(
                'class' => 'regular-text',
                'placeholder' => ''
            )
        );

        echo '
        <input
            class="' . esc_attr($args['class']) . '"
            name="' . esc_attr($args['option_name']) . '"
            type="text"' . 
            ((isset($option)) ? ' value="' . esc_attr($option) . '" ' : '') .
            ($args['placeholder'] !== '' ? ' placeholder="'. $args['placeholder'] .'" ' : '')
        .  '/>';

        if (isset($args['description'])) {
            echo '<p class="description">' . wp_kses_post($args['description']) . '</p>';
        }
    }

    /**
     * Checkbox callback
     */
    public static function checkbox($args)
    {
        $templatePath = OTYS_PLUGIN_ADMIN_TEMPLATE_URL . '/wp-settings-api/cb-checkbox.php';

        load_template($templatePath, false, $args);
    }

    /**
     * Select callback
     */
    public static function select($args)
    {
        $templatePath = OTYS_PLUGIN_ADMIN_TEMPLATE_URL . '/wp-settings-api/cb-select.php';

        load_template($templatePath, false, $args);
    }

    /**
     * Radio callback
     */
    public static function radio($args)
    {
        $templatePath = OTYS_PLUGIN_ADMIN_TEMPLATE_URL . '/wp-settings-api/cb-radio.php';

        load_template($templatePath, false, $args);
    }

    /**
     * Radio callback
     */
    public static function image($args)
    {
        $templatePath = OTYS_PLUGIN_ADMIN_TEMPLATE_URL . '/wp-settings-api/cb-image.php';

        load_template($templatePath, false, $args);
    }

    /**
     * Matchcriteria select callback
     * @since 1.2.11
     */
    public static function sortableCheckboxes($args)
    {
        // Default values
        $args = wp_parse_args(
            $args,
            array(
                'class' => 'regular-text'
            )
        );

        $args['options'] = VacanciesFiltersModel::getFilters();

        $templatePath = OTYS_PLUGIN_ADMIN_TEMPLATE_URL . '/wp-settings-api/cb-sortable-checkboxes.php';

        load_template($templatePath, false, $args);
    }

     /**
     * Document select
     *
     * @param mixed $args
     * @return void
     * @since 1.0.0
     */
    public static function documentSelect($args): void
    {
        // Default values
        $args = wp_parse_args(
            $args,
            array(
                'class' => 'regular-text'
            )
        );

        $args['documents'] = DocumentsModel::getList();

        $templatePath = OTYS_PLUGIN_ADMIN_TEMPLATE_URL . '/wp-settings-api/cb-select-document.php';

        load_template($templatePath, false, $args);
    }
}
