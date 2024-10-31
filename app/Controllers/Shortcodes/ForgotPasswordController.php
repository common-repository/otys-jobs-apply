<?php

namespace Otys\OtysPlugin\Controllers\Shortcodes;

use Exception;
use Otys\OtysPlugin\Includes\Core\Routes;
use Otys\OtysPlugin\Models\CandidateModel;
use Otys\OtysPlugin\Models\Shortcodes\AuthModel;
use Otys\OtysPlugin\Models\Shortcodes\ForgotPasswordModel;
use WP_REST_Request;
use WP_REST_Response;


/**
 * [otys-login]
 *
 * @since 2.0.43
 */
final class ForgotPasswordController extends ShortcodeBaseController
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

        $loggedInUser = AuthModel::getUser();

        $templateDir = '/include-parts/rest-forms/';

        $identifier = sha1(uniqid('otys-forgot-password'));

        // Get the recaptcha key
        $recaptchaKey = get_option('otys_option_recaptcha_site_key', '');

        // If the recaptcha key is not set, don't display the shortcode and show a message to the admin
        if ($recaptchaKey === '') {
            if (current_user_can('administrator')) {
                echo __('Please setup the recaptcha key correctly, otherwise the interaction form will not work. If you need help please read the article <a target="_blank" href="https://wordpress.otys.com/kb/guide/en/QIkQJMUsa2/">How do I setup Google Recaptcha?</a> on our knowledge base.', 'otys-jobs-apply');
            }
            return;
        }

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

        $attParams = wp_parse_args($this->model->validateAtts($this->getAtts()), [
            'redirect' => isset($_GET['redirect']) ? esc_url($_GET['redirect']) : false
        ]);

        $this->setArgs('action', get_home_url() . '/wp-json/otys/v1/forgotpassword/');
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
            ]
        ]);

        $this->setArgs('success_template', Routes::locateTemplate('/forgot-password/forgot-password-success.php'));
        $this->setArgs('redirect', $attParams['redirect'] ?? '');
        $this->setArgs('uid', $identifier);
        $this->setArgs('recaptcha-key', $recaptchaKey);
        $this->setArgs('submit_button_text', __('Send new password', 'otys-jobs-apply'));
        $this->setArgs('success_message', __('A new password has been send to your e-mail address.', 'otys-jobs-apply'));
        $this->setArgs('user', $user);
        $this->setArgs('candidate_portal_url', Routes::get('candidate_portal'));

        $this->loadTemplate('forgot-password/forgot-password.php');
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
        $model = new ForgotPasswordModel();

        // Validate the post data
        $validated = $model->validatePost($postData);

        // Default error code
        $code = 400;

        // Get all fields with errors by OWS fieldname
        $fieldsWithErrors = array_filter($validated, function ($value) {
            return !empty($value['errors']);
        });

        // If there are fields with errors, return the response
        if (!empty($fieldsWithErrors)) {
            return new WP_REST_Response($validated, $code);
        }

        $username = $validated['username']['value'];

        try {
            // Get the webuser uid
            $webuser = $model->getWebUserByUsername($username);
        } catch (Exception $e) {
            // User not found, but we don't want to give that information to the user
            return new WP_REST_Response($validated, 200);
        }

        $candidateUid = null;

        foreach ($webuser['roles'] as $role) {
            if ($role['role'] === 'candidate') {
                $candidateUid = $role['uidTarget'];
                break;
            }
        }

        // If the candidate uid is not found, we don't want to give that information to the user
        if ($candidateUid === null) {
            return new WP_REST_Response($validated, 200);
        }

        try {
            $candidate = $model->getCandidateByUid($candidateUid);
        } catch (Exception $e) {
            // If the candidate is not found, we don't want to give that information to the user
            return new WP_REST_Response($validated, 200);
        }

        try {
            $password = CandidateModel::createPassword();
        } catch (Exception $e) {
            $validated['errors'][] = $e->getMessage();
            return new WP_REST_Response($validated, 400);
        }

        $model->resetCandidatePassword($webuser['uid'], $password);

        $model->sendNewCandidatePasswordMail(
            $candidateUid,
            [
                'To' => $candidate['Person']['emailPrimary']
            ],
            [
                'answers' => [
                    'password' => !is_wp_error($password) ? $password : ''
                ]
            ]
        );

        return new WP_REST_Response($validated, 200);
    }
}