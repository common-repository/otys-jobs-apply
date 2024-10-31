<?php

namespace Otys\OtysPlugin\Controllers\Shortcodes;
use Exception;
use WP_REST_Request;
use Otys\OtysPlugin\Includes\Core\Routes;
use Otys\OtysPlugin\Models\Shortcodes\InteractionsModel;

use WP_REST_Response;
use function Otys\OtysPlugin\Helpers\reArrayFiles;

/**
 * Abstract Interaction Controller used to create shortcodes which use interaction forms
 *
 * @since 2.0.34
 */
abstract class AbstractInteractionsController extends ShortcodeBaseController
{
    /**
     * Instance of the InteractionsModel
     *
     * @param InteractionsModel
     */
    public $model;

    /**
     * @var InteractionsModel
     */
    public static $modelClass = InteractionsModel::class;

    public function __construct(array $atts = [], string $content = '', string $tag = '')
    {
        parent::__construct($atts, $content, $tag);

        try {
            $this->model = new static::$modelClass((int) $this->getAtt('id'));
        } catch (Exception $e) {
        }
    }

     /**
     * Displays the shortcode
     *
     * @since 2.0.x
     * @return void
     */
    public function display(): void
    {
        if ($this->model === null) {
            return;
        }

        // Enqueue scripts
        wp_enqueue_script('recaptcha', 'https://www.google.com/recaptcha/api.js?render='. get_option('otys_option_recaptcha_site_key'), [], OTYS_PLUGIN_VERSION, [
            'in_footer' => true
        ]);
        wp_enqueue_script('otys-questionset', OTYS_PLUGIN_ASSETS_URL . '/js/questionset.min.js', [], OTYS_PLUGIN_VERSION, [
            'in_footer' => true
        ]);

        // Get the interaction form id
        $interactionFormId = $this->getAtt('id');

        try {
            $formTabs = $this->model->getFormTabs();
        } catch (Exception $e) {
            return;
        }

        // If no id is set, don't display the shortcode
        if ($interactionFormId === '') {
            return;
        }

        // Get the recaptcha key
        $recaptchaKey = get_option('otys_option_recaptcha_site_key', '');

        // If the recaptcha key is not set, don't display the shortcode and show a message to the admin
        if ($recaptchaKey === '') {
            if (current_user_can('administrator')) {
                echo __('Please setup the recaptcha key correctly, otherwise the interaction form will not work. If you need help please read the article <a target="_blank" href="https://wordpress.otys.com/kb/guide/en/QIkQJMUsa2/">How do I setup Google Recaptcha?</a> on our knowledge base.', 'otys-jobs-apply');
            }
            return;
        }

        // If the model has errors don't display the shortcode
        if ($this->model->hasErrors()) {
            return;
        }

        $this->setArgs('uid',  $interactionFormId);
        $this->setArgs('identifier', sha1('interaction-ID' . strtotime('now')));
        $this->setArgs('recaptcha-key', $recaptchaKey);
        $this->setArgs('action', get_home_url() . '/wp-json/otys/v1/interactions/');
        $this->setArgs('pages', $formTabs);
        $this->setArgs('redirect', get_home_url() . '/bedankt/');
        $this->setArgs('success_template', Routes::locateTemplate('/interactions/interaction-success.php'));
        $this->setArgs('system_error', '');
        $this->setArgs('success_message', __('Thank you for filling in the form.', 'otys-jobs-apply'));
        $this->setArgs('submit_button_text', __('Send', 'otys-jobs-apply'));

        $this->beforeDisplay();

        $this->loadTemplate('interactions/interactions.php');
    }

    /**
     * Before init
     *
     * @return void
     */
    public function beforeInit(): void
    {
        // Do something before the init
    }

    /**
     * Before display
     *
     * @return void
     */
    public function beforeDisplay(): void
    {
    }
    
    /**
     * REST POST
     *
     * @since 2.0.0
     * @param WP_REST_Request $request
     * @return mixed
     */
    public static function restPost($request)
    {
        /**
         * Set post data
         */
        $uploadedFiles = (array) $request->get_file_params();
        $request->uploadedFiles = reArrayFiles($uploadedFiles);

        // Get all post data together in one variable
        $postData = $request->get_body_params() + $uploadedFiles;

        // New instance based on the model class
        $instance = new static::$modelClass(
            (int) $postData['uid'] ?? 0
        );
    
        $values = $instance->filterData($postData);

        $validated = $instance->validatePost($values);

        // Get all fields with errors by OWS fieldname
        $fieldsWithErrors = array_filter($validated, function ($value) {
            return !empty($value['errors']);
        });

        if (!empty($fieldsWithErrors)) {
            return new WP_REST_Response($validated, 400);
        }

        $interactionValues = $instance->getInteractionValues($values);

        try {
            $instance->submit($interactionValues);
            $commit = $instance->commit();

            // Call the success callback
            static::successCallback($commit);
        } catch (Exception $e) {
            $validated['system']['errors'][] = __($e->getMessage(), 'otys-jobs-apply');
            return new WP_REST_Response($validated, 400);
        }

        return new WP_REST_Response($validated, 200);
    }

    /**
     * Success Callback
     *
     * @param array $commitResponse
     * @return void|Exception
     */
    public static function successCallback(array $commitResponse): void
    {
    }

    /**
     * Filter data
     *
     * @param array $values
     * @return array
     */
    public function filterData(array $values): array
    {
        return $values;
    }
}