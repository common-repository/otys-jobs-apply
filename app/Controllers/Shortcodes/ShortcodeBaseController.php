<?php

namespace Otys\OtysPlugin\Controllers\Shortcodes;

use Otys\OtysPlugin\Includes\Core\Routes;

/**
 * Shortcode base controller, should be extended by every shortcode controller
 *
 * @since 2.0.0
 */
abstract class ShortcodeBaseController
{
    /**
     * Stores arguments which will be provided to the template
     *
     * @var array
     */
    private array $args = [];

    /**
     * Stores instances
     *
     * @var array
     */
    private static array $instances = [];

    /**
     * Stores shortcode attributes
     *
     * @var array
     */
    protected array $atts = [];

    /**
     * Stores potential shortcode content
     *
     * @var string
     */
    protected string $content;

    /**
     * Stores shortcode
     *
     * @var string
     */
    protected string $tag;

    /**
     * Constructor
     *
     * @param array $atts       Shortcode attributes
     * @param string $content   Shortcode content
     * @param string $tag       Shortcode tag
     */
    public function __construct(array $atts = [], string $content = '', string $tag = '')
    {
        $this->atts = $atts + $this->atts;
        $this->content = $content;
        $this->tag = $tag;
    }

    /**
     * This callback method will be used as callback function for any new
     * shortcode instance.
     *
     * @param mixed $attributes Shortcode attributes, will be an empty string
     *                          when no attributes are defined.
     * @return string
     */
    public static function callback($atts, string $content, string $tag): string
    {
        ob_start();

        // Make sure attributes is always of type array
        if (!is_array($atts)) {
            $atts = [];
        }

        // Call the display function which will contain the output of the shortcode
        static::getInstance($atts, $content, $tag)->display();

        $output = ob_get_clean();

        // Make sure the output is string since WordPress expects a string
        if (is_string($output)) {
            return $output;
        }

        return '';
    }

    /**
     * Returns new instance of the called class.
     */
    public static function getInstance($atts, $content, $tag)
    {
        $className = get_called_class();

        return new $className($atts, $content, $tag);
    }

    /**
     * Display function is containing the output of the shortcode.
     * When creating a shortcode the callback function will automaticly
     * call the display function.
     *
     * @param array $att
     * @return void
     */
    public function display(): void
    {
    }

    /**
     * Set arguments which can be later
     *
     * @param string $name  Name of the argument
     * @param mixed $data   Data stored in the argument
     * @return void
     */
    public function setArgs(string $name, $data): void
    {
        $this->args[$name] = $data;
    }

    /**
     * Get the list of available arguments
     *
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * Get argument by name
     *
     * @param string $name
     * @return mixed
     */
    public function getArg(string $name)
    {
        if (isset($this->args[$name])) {
            return $this->args[$name];
        }

        return null;
    }

    /**
     * Get filtered shortcode attributes as array
     *
     * @return array
     */
    public function getAtts(): array
    {
        return $this->atts;
    }

    /**
     * Set shortcode attribute
     *
     * @param string $attName
     * @param mixed $attValue
     * @return void
     */
    public function setAtt(string $attName, $attValue): void
    {
        $this->atts[$attName] = $attValue;
    }

    /**
     * Get filtered shortcode attributes as array
     *
     * @return mixed
     */
    public function getAtt(string $attName)
    {
        if (isset($this->atts[$attName])) {
            return $this->atts[$attName];
        }

        return '';
    }

    /**
     * Load view template
     *
     * @param string $template
     * @return void
     */
    public function loadTemplate(string $template): void
    {
        if (($templatePath = Routes::locateTemplate($template)) !== '') {
            load_template($templatePath, false, $this->getArgs());
        }
    }
}