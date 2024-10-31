<?php

namespace Otys\OtysPlugin\Controllers\Shortcodes;

use Exception;
use Otys\OtysPlugin\Entity\AuthUser;
use Otys\OtysPlugin\Includes\Core\Routes;
use Otys\OtysPlugin\Models\Shortcodes\AuthModel;
use WP_REST_Request;
use WP_REST_Response;


/**
 * [otys-login]
 *
 * @since 2.0.43
 */
final class AuthController extends ShortcodeBaseController
{
    /**
     * @var AuthModel
     */
    protected $model;

    public function __construct(array $atts = [], string $content = '', string $tag = '')
    {
        parent::__construct($atts, $content, $tag);

        $this->model = new AuthModel();
    }

    /**
     * Displays the shortcode
     *
     * @param array $atts
     * @return void
     */
    public function display(): void
    {
        global $wp;

        /**
         * Logic
         */
        // Enqueue scripts
        wp_enqueue_script('recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . get_option('otys_option_recaptcha_site_key'), [], OTYS_PLUGIN_VERSION, [
            'in_footer' => true
        ]);

        wp_enqueue_script('otys-questionset', OTYS_PLUGIN_ASSETS_URL . '/js/questionset.min.js', [], OTYS_PLUGIN_VERSION, [
            'in_footer' => true
        ]);

        $templateDir = '/include-parts/rest-forms/';

        $identifier = sha1(uniqid('otys-login'));

        // Get the recaptcha key
        $recaptchaKey = get_option('otys_option_recaptcha_site_key', '');

        // If the recaptcha key is not set, don't display the shortcode and show a message to the admin
        if ($recaptchaKey === '') {
            if (current_user_can('administrator')) {
                echo __('Please setup the recaptcha key correctly, otherwise the interaction form will not work. If you need help please read the article <a target="_blank" href="https://wordpress.otys.com/kb/guide/en/QIkQJMUsa2/">How do I setup Google Recaptcha?</a> on our knowledge base.', 'otys-jobs-apply');
            }
            return;
        }

        $attParams = wp_parse_args($this->model->validateAtts($this->getAtts()), [
            'redirect' => isset($_GET['redirect']) ? esc_url($_GET['redirect']) : false
        ]);

        $this->setArgs('action', get_home_url() . '/wp-json/otys/v1/login/');
        $this->setArgs('identifier', $identifier);

        // Set the questions
        $this->setArgs('questions', [
            'username' => [
                'uid' => 'username',
                'question' => 'Username',
                'name' => 'username',
                'template' => Routes::locateTemplate($templateDir . 'field-text'),
                'validation' => [
                    'mandatory' => true
                ],
                'type' => 'text',
                'typeUi' => 'Textfield',
                'autoCompleteName' => 'username'
            ],
            'password' => [
                'uid' => 'password',
                'question' => 'Password',
                'name' => 'password',
                'template' => Routes::locateTemplate($templateDir . 'field-text'),
                'validation' => [
                    'mandatory' => true
                ],
                'type' => 'password',
                'typeUi' => 'Textfield',
                'autoCompleteName' => 'current-password'
            ]
        ]);

        // Get the logged in user
        $loggedInUser = $this->model->getUser();

        $user = $loggedInUser ? [
            'first_name' => $loggedInUser->getFirstName(),
            'middle_name' => $loggedInUser->getMiddleName(),
            'last_name' => $loggedInUser->getLastName(),
            'full_name' => $loggedInUser->getFullName(),
            'email' => $loggedInUser->getEmail(),
            'candidate_uid' => $loggedInUser->getCandidateUid(),
            'webuser_uid' => $loggedInUser->getWebuserUid(),
            'session' => $loggedInUser->getSesion()
        ] : false;

        $logoutUrl = home_url(Routes::get('candidate_logout'));

        $this->setArgs('logout_url_with_redirect', $logoutUrl . '?redirect=' . home_url(add_query_arg([], $wp->request)));
        $this->setArgs('logout_url', $logoutUrl);
        $this->setArgs('forgot_password_url', get_option('otys_option_document_template_forgot_password', '') ? Routes::get('candidate_forgot_password') : '');
        $this->setArgs('candidate_portal_url', Routes::get('candidate_portal'));
        $this->setArgs('user', $user);
        $this->setArgs('success_template', Routes::locateTemplate('/login/login-success.php'));
        $this->setArgs('redirect', $attParams['redirect'] ?? '');
        $this->setArgs('uid', $identifier);
        $this->setArgs('recaptcha-key', $recaptchaKey);
        $this->setArgs('submit_button_text', __('Login', 'otys-jobs-apply'));
        $this->setArgs('success_message', __('You\'ve been sucessfully logged in and will be redirect to the portal..', 'otys-jobs-apply'));

        if ($user) {
            $this->loadTemplate('login/login-logged-in.php');
        } else {
            $this->loadTemplate('login/login.php');
        }
    }

    /**
     * REST POST
     *
     * @since 2.0.43
     * @param WP_REST_Request $request
     * @return mixed
     */
    public static function restPost($request)
    {
        // Get all post data together in one variable
        $postData = $request->get_body_params();

        // New instance based on the model class
        $model = new AuthModel();

        // Validate the post data
        $validated = $model->validatePost($postData);

        // Default error code
        $code = 400;

        // Try logging in the user
        try {
            $user = $model->login($postData['username'], $postData['password']);

            assert ($user instanceof AuthUser);
        } catch (Exception $e) {
            $validated['system']['errors'][] = $e->getMessage();

            $code = $e->getCode();
        }

        // Get all fields with errors by OWS fieldname
        $fieldsWithErrors = array_filter($validated, function ($value) {
            return !empty($value['errors']);
        });

        // If there are fields with errors, return the response
        if (!empty($fieldsWithErrors)) {
            return new WP_REST_Response($validated, $code);
        }

        // Get login token
        try {
            if (!isset($validated['redirect'])) {
                $loginLink = $model->getUserLoginLink($user);

                $validated['redirect'] = [
                    'value' => $loginLink,
                    'errors' => []
                ];
            }
        } catch (Exception $e) {
            // If there is an error we will not redirect the user
        }

        // try {
 
        // } catch (Exception $e) {
        //     $validated['system']['errors'][] = __($e->getMessage(), 'otys-jobs-apply');
        //     return new WP_REST_Response($validated, 400);
        // }

        return new WP_REST_Response($validated, 200);
    }

    public static function logout()
    {
        AuthModel::logout();
    }
}