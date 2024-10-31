<?php

namespace Otys\OtysPlugin\Controllers\Shortcodes;

use Otys\OtysPlugin\Helpers\SettingHelper;
use Otys\OtysPlugin\Includes\Core\Routes;
use Otys\OtysPlugin\Models\CandidateModel;
use Otys\OtysPlugin\Models\DocumentsModel;
use Otys\OtysPlugin\Models\FilesModel;
use Otys\OtysPlugin\Models\MailModel;
use Otys\OtysPlugin\Models\ProceduresModel;
use Otys\OtysPlugin\Models\Shortcodes\AuthModel;
use Otys\OtysPlugin\Models\Shortcodes\VacanciesApplyModel;
use Otys\OtysPlugin\Includes\Core\ApplicationSessions;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * [otys-vacancies-apply]
 *
 * @since 2.0.0
 */
final class VacanciesApplyController extends ShortcodeBaseController
{
    /**
     * Holds instance that belongs to this controller
     *
     * @var VacanciesApplyModel
     */
    private $model;

    /**
     * Construct
     *
     * @since 2.0.0
     * @param array $atts
     * @param string $content
     * @param string $tag
     */
    public function __construct(array $atts = [], string $content = '', string $tag = '')
    {
        parent::__construct($atts, $content, $tag);

        $request = new WP_REST_Request('GET');

        $request->set_param('vacancy_uid', $this->getAtt('vacancy-uid'));
        $request->set_param('auth_user', AuthModel::getUser());

        $this->model = new VacanciesApplyModel($request);
    }

    /**
     * Displays the shortcode
     *
     * @since 2.0.0
     * @return void
     */
    public function display(): void
    {
        global $wp;

        // Enqueue scripts
        wp_enqueue_script('otys-questionset', OTYS_PLUGIN_ASSETS_URL . '/js/questionset.min.js', [], OTYS_PLUGIN_VERSION, [
            'in_footer' => true
        ]);

        wp_enqueue_script('recaptcha', 'https://www.google.com/recaptcha/api.js?render='. get_option('otys_option_recaptcha_site_key'), [], OTYS_PLUGIN_VERSION, [
            'in_footer' => true
        ]);

        $recaptchaKey = get_option('otys_option_recaptcha_site_key', '');

        if ($recaptchaKey === '') {
            if (current_user_can('administrator')) {
                echo __('Please setup the recaptcha key correctly, otherwise the application form will not work. If you need help please read the article <a target="_blank" href="https://wordpress.otys.com/kb/guide/en/QIkQJMUsa2/">How do I setup Google Recaptcha?</a> on our knowledge base.', 'otys-jobs-apply');
            }
            return;
        }

        // If the model has errors don't display the shortcode
        if ($this->model->hasErrors()) {
            return;
        }

        // Get validated POST data with OWS names
        $owsFieldNames = $this->model->getQuestionsetModel()->getOwsFieldNames();

        // Get questionset
        $questionset = $this->model->getQuestionset();

        // If the questionset is not known, check if the email question is present in the questionset
        if ($questionset['applyType'] !== 'known_candidate') {
            // Get email question name
            $emailQuestionName = $owsFieldNames['Person.emailPrimary'] ?? null;

            // If email question is not present in the questionset throw error
            if ($emailQuestionName === null) {
                echo '<p>It is required for the e-mail field to be present in the questionset, also the e-mail field should be required to be filled in by a candidate.</p><p>Please <a target="_blank" href="https://faq.otys.com/?faq=Rrp5vClJX6">check our FAQ</a> to see how to make changes to the questionset or if you need help please create a support ticket.';
                return;
            }
        }

        $vacancyUid = $this->getAtt('vacancy-uid');

        // Reset confirmation code
        ApplicationSessions::resetConfirmationcode($vacancyUid);

        $vacancy = $vacancyUid !== '' ? $this->model->getVacancy() : [];

        // Don't show shortcode if there are error
        if (is_wp_error($vacancy)) {
            return;
        }

        // Don't show shortcode if the application is set to hide the application button
        if (isset($vacancy['removeApplyButton']) && $vacancy['removeApplyButton'] === true) {
            return;
        }

        $successTemplate = $vacancyUid !== '' ?
            Routes::locateTemplate('/vacancies/apply/vacancies-apply-success') :
            Routes::locateTemplate('/vacancies/apply/vacancies-open-apply-success');

        $showCandidateLoginLinkSetting = filter_var(get_option('otys_option_show_candidate_login_link_at_form', false), FILTER_VALIDATE_BOOLEAN);
        $candidateLoginRoute = Routes::get('candidate_login');
        $candidateLoginLink = $candidateLoginRoute . '/?redirect=' . urlencode(home_url($wp->request));
        $showCandidateLogin = ($showCandidateLoginLinkSetting && !AuthModel::getUser() && $candidateLoginRoute);

        $this->setArgs('redirect', $this->getAtt('redirect'));
        $this->setArgs('vacancy', $vacancy);
        $this->setArgs('identifier', sha1($vacancyUid . strtotime('now')));
        $this->setArgs('success_template', $successTemplate);
        $this->setArgs('confirm_email_template', Routes::locateTemplate('/vacancies/apply/vacancies-confirm-email'));
        $this->setArgs('recaptcha-key', $recaptchaKey);
        $this->setArgs('action', get_home_url() . '/wp-json/otys/v1/apply/');
        $this->setArgs('vacancy_uid', $vacancyUid);
        $this->setArgs('questionset', $questionset);
        $this->setArgs('auth_user', AuthModel::getUser());
        $this->setArgs('candidate_login_url', $candidateLoginLink);
        $this->setArgs('show_candidate_login', $showCandidateLogin);

        if ($user = AuthModel::getUser()) {
            // Check if the user is a known candidate
            if (ProceduresModel::procedureExists($user->getCandidateUid(), $vacancyUid)) {
                $this->loadTemplate('vacancies/apply/vacancies-apply-shortcode-known-candidate.php');

                return;
            }
        }

        $this->loadTemplate('vacancies/apply/vacancies-apply-shortcode.php');
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
        $model = new VacanciesApplyModel($request);
    
        $user = AuthModel::getUser();

        // If errors occured while instantiating the model return errors
        if ($model->getErrors()->has_errors()) {
            return $model->getErrors();
        }
    
        // Get vacancy
        $vacancy = $model->getVacancy();

        // Get questionset 
        $questionset = $model->getQuestionset();
        
        // Get candidate data
        $candidateData = $model->getCandidateData();

        // Make candidate owner the vacancy owner
        if (isset($vacancy['userId'])) {
            $candidateData['userId'] = $vacancy['userId'];
        }

        // Throw error when applications are disabled and some one still tries to apply
        if (isset($vacancy['removeApplyButton']) && $vacancy['removeApplyButton'] === true) {
            return new WP_Error('application_disabled', 'Vacancy is not open for applications');
        }

        // Get vacancy uid
        $vacancyUid = $model->getVacancyUid();

        // If the vacancy uid is empty it's not a vacancy application but a open application
        $isVacancyApplication = $vacancyUid === '' ? false : true;

        $questionsetFields = $model->getQuestionsetModel()->getQuestionsByFormName();

        // Validate postData
        $validated = $model->getPostData();

        // Set redirect url if it's set in the settings and not as a shortcode attribute
        if (!isset($validated['redirect']) || $validated['redirect'] === '') {
            $website = SettingHelper::getSiteId();
            $customSlugIsActive = Routes::customSlugIsActive();

            $redirect = Routes::get('vacancy-apply-thank-you', [
                'slug' => $customSlugIsActive ? $vacancy['slug'][$website] : sanitize_title($vacancy['title']) . '-' . $vacancy['uid']
            ], get_locale());

            if ($redirect !== '') {
                $validated['redirect']['value'] = $redirect;
            }
        }

        // If current candidate is not a known candidate
        if ($questionset['applyType'] !== 'known_candidate') {
            // Get validated POST data with OWS names
            $owsFieldNames = $model->getQuestionsetModel()->getOwsFieldNames();

            // Get email question name
            $emailQuestionName = isset($owsFieldNames['Person.emailPrimary']) ? $owsFieldNames['Person.emailPrimary'] : null;

            // If email question is not present in the questionest throw error
            if ($emailQuestionName === null) {
                return new WP_Error('no_email_field', '<p>It is required for the e-mail field to be present in the questionset, also the e-mail field should be required to be filled in by a candidate.</p><p>Please <a target="_blank" href="https://faq.otys.com/?faq=Rrp5vClJX6">check our FAQ</a> to see how to make changes to the questionset or if you need help please create a support ticket.');
            }

            $candidate = false;
            $isNewCandidate = false;
            $confirmEmailDocument = get_option('otys_option_document_template_apply_confirm_email', '0');

            // Check if email is unique
            if ($emailQuestionName !== null && is_email($validated[$emailQuestionName]['value'])) {
                $candidate = VacanciesApplyModel::getCandidateByEmail($validated[$emailQuestionName]['value']);

                $isNewCandidate = $candidate ? false : true;

                // If current application is an open application and a candidate with the set email already exists throw error.
                if ($candidate !== false && !$isVacancyApplication) {
                    $validated[$emailQuestionName]['errors'][] = __('Email adres is already used, please choose a different email adres.', 'otys-jobs-apply');
                }

                // Check if candidate already applied on this vacancy
                if ($isNewCandidate === false && ProceduresModel::procedureExists($candidate, $vacancyUid)) {
                    // Candidate already exists and has already applied on this vacancy
                    $validated[$emailQuestionName]['errors'][] = __('You\'ve already applied on this vacancy with this email adres.', 'otys-jobs-apply');
                }
            } else if(!is_email($validated[$emailQuestionName]['value'])) {
                $validated[$emailQuestionName]['errors'][] = __('Invalid email adres.', 'otys-jobs-apply');
            }
        }

        // If the questionset is known candidate, check if the candidate already exists
        if ($questionset['applyType'] === 'known_candidate') {
            $candidate = VacanciesApplyModel::getCandidateByEmail($user->getEmail());

            $isNewCandidate = false;

            // Check if candidate already applied on this vacancy
            if (ProceduresModel::procedureExists($candidate, $vacancyUid)) {
                // Candidate already exists and has already applied on this vacancy
                $validated['system_error']['errors'][] = __('You\'ve already applied on this vacancy with this email adres.', 'otys-jobs-apply');
            }
        }

        // Get all fields with errors by OWS fieldname
        $fieldsWithErrors = array_filter($validated, function ($value) {
            return !empty($value['errors']);
        });

        // If there are no ows fields with errors
        if (empty($fieldsWithErrors)) {
            /**
             * Check if we should send a new confirmation code
             */
            if (
                !$user &&
                !ApplicationSessions::getConfirmationcode($vacancyUid) &&
                $isNewCandidate === false &&
                $confirmEmailDocument != '0'
            ) {
                // Generate confirmation code
                $confirmationCode = rand(10000, 99999);

                ApplicationSessions::setConfirmationcode($vacancyUid, $confirmationCode, $candidateData['Person.emailPrimary']);

                static::sendConfirmationEmailToCandidate(
                    $candidate,
                    $vacancyUid,
                    [
                        'To' => $candidateData['Person.emailPrimary']
                    ],
                    [
                        'answers' => [
                            'code' => $confirmationCode
                        ]
                    ]
                );

                // Candidate already exists and needs to confirm his email
                return new WP_REST_Response(['message' => 'send confirmation email, awaiting response.'], 409);
            }

            // Create candidate if candidate does not yet exist
            $candidateUid = $candidate !== false ? $candidate : CandidateModel::createCandidate($candidateData);

            if (!is_wp_error($candidateUid)) {
                // Get if the GDPR question exists in the validated post data
                $acceptedGDPR = isset($validated['qs_-666']) && $validated['qs_-666']['value'] == "true" ? true : false;

                $model->getQuestionsetModel()->setGDPR($candidateUid, $acceptedGDPR);

                // Update candidate if candidate is preexisting
                if (!$isNewCandidate) {
                    CandidateModel::updateCandidate($candidateUid, $candidateData);
                }
               
                // Upload each file to upload to OTYS
                foreach ($model->getUploadedFiles() as $formName => $files) {
                    if (!array_key_exists($formName, $validated)) {
                        continue;
                    }

                    foreach ($files as $file) {
                        // Upload file to OTYS
                        $uploadedFile = FilesModel::upload($file);

                        // An error occured, error has been saved in API logs. We will not notify the candidate and continue.
                        if (!is_array($uploadedFile) || empty($uploadedFile)) {
                            continue;
                        }

                        if (!is_wp_error($uploadedFile)) {
                            // Search question that belongs to this upload
                            $question = array_key_exists($formName, $questionsetFields) ? $questionsetFields[$formName] : false;

                            // Get the field name from the question
                            $fieldName = $question !== false && isset($question['fieldName']) ? $question['fieldName'] : 'document';

                            $typeId = $question['data']['documentTypeId'] ?? null;
                        
                            $typeId = $typeId ? $typeId : (($fieldName == 'cv' || $fieldName == 'cvFileName') ? '1' : '201');

                            $fileArgs = [
                                'subject' => $uploadedFile['name'],
                                'typeId' => $typeId,
                                // type 1 = CV, type 201 = Other
                                'private' => 'false',
                                'customerRightsLevel' => $question['customerRights'] ?? 0,
                                'alwaysOnTop' => 'false',
                                'candidateUid' => $candidateUid,
                                'fileUid' => $uploadedFile['ouid']
                            ];

                            if (!empty($vacancy) && isset($vacancy['internalId'])) {
                                $fileArgs['applicationUid'] = $vacancy['internalId'];
                            }

                            FilesModel::attach($fileArgs);
                        }
                    }
                }

                // Do vacancy application stuff if current application is on a vacancy
                if ($isVacancyApplication) {
                    $procedureArgs = [];

                    if (array_key_exists('motivation', $candidateData)) {
                        $procedureArgs['KillerQuestions'] = [
                            $candidateData['motivation']
                        ];
                    }

                    $procedureUid = ProceduresModel::add($candidateUid, $vacancyUid, $procedureArgs);

                    // Fallback when procedure creation failed
                    if (is_wp_error($procedureUid)) {
                        return new WP_Error(
                            'failed_creating_procedure', 
                            sprintf(
                                __('Something went wrong. Please contact %s to make sure your application went through.','otys-jobs-apply'), 
                                get_bloginfo('admin_email')
                            )
                        );
                    }
                }

                // MAILS
                if (!empty($vacancy)) {
                    $vacancyMailArgs = [
                        'To' => $candidateData['Person.emailPrimary']
                    ];

                    if (
                        isset($vacancy['userEmail']) && $vacancy['userEmail'] !== null &&
                        isset($vacancy['user']) && $vacancy['user'] !== null
                    ) {
                        $vacancyMailArgs['From'] = [
                            'name' => $vacancy['user'],
                            'email' => $vacancy['userEmail']
                        ];
                    }

                    if ($isNewCandidate) {
                        // Send new candidate new application mail
                        $password = CandidateModel::createWebuser($candidateUid, $candidateData['Person.emailPrimary']);
                        static::sendNewCandidateApplicationMail($vacancy['userId'], $candidateUid, $vacancyUid, $vacancyMailArgs, [
                            'answers' => [
                                'password' => !is_wp_error($password) ? $password : ''
                            ]
                        ]);
                    } else {
                        // Send existing candidate
                        static::sendExistingCandidateApplicationMail($vacancy['userId'], $candidateUid, $vacancyUid, $vacancyMailArgs);
                    }

                    // Send consultant email
                    if ($vacancy['userEmail'] != '0') {
                        static::sendConsultantCandidateVacancyApplyMail($candidateUid, $vacancyUid, [
                            'To' => $vacancy['userEmail']
                        ]);
                    }
                } else {
                    // Send open application mail to candidate
                    $password = CandidateModel::createWebuser($candidateUid, $candidateData['Person.emailPrimary']);
                    static::sendCandidateOpenApplicationMail($candidateUid, [
                        'To' => $candidateData['Person.emailPrimary']
                    ], [
                        'answers' => [
                            'password' => !is_wp_error($password) ? $password : ''
                        ]
                    ]);

                    // Send open application mail to consultant
                    static::sendOpenApplicationMailToConsultant($candidateUid);
                }

                // Save application data in session
                $applyData = [
                    'vacancy_uid' => $vacancyUid,
                    'candidate_uid' => $candidateUid,
                    'procedure_uid' => isset($procedureUid) ? $procedureUid : false,
                    'completed' => true,
                    'form_data' => $validated
                ];

                ApplicationSessions::add($vacancyUid, $applyData);

                // Call custom action otys_webhook
                do_action('otys_application', ApplicationSessions::get($vacancyUid));

                // Reset confirmation code
                ApplicationSessions::resetConfirmationcode($vacancyUid);

                return new WP_REST_Response($validated, 200);
            } else {
                return new WP_Error('application_failed', __('Application failed, please contact us.', 'otys-jobs-apply'));
            }
        }

        // Save application data in session
        ApplicationSessions::add($vacancyUid,  [
            'completed' => false,
            'form_data' => $validated
        ]);

        // Call custom action otys_webhook
        do_action('otys_invalid_application', ApplicationSessions::get($vacancyUid));

        // Tried email confirmation
        if (ApplicationSessions::getConfirmationcode($vacancyUid)) {
            return new WP_REST_Response($validated, 409);
        }

        return new WP_REST_Response($validated, 400);
    }

    /**
     * Return applications from current session
     *
     * @since 2.0.0
     * @return array
     */
    public static function getApplicationsFromSession(): array
    {
        if (isset($_SESSION['otys_applications'])) {
            return $_SESSION['otys_applications'];
        }

        return [];
    }

    /**
     * Send confirmation email to candidate
     *
     * @since 2.0.0
     * @param string $candidateUid
     * @param array $args
     * @return void
     */
    public static function sendConfirmationEmailToCandidate(string $candidateUid, string $vacancyUid, array $args = [], array $docArgs = []): void
    {
        $documentUid = intval(get_option('otys_option_document_template_apply_confirm_email', 0));

        // Skip mail if document equals 0
        if ($documentUid === 0) {
            return;
        }

        $docArgs = array_replace_recursive([
            'relatedEntities' => [
                [
                    /* vacancy */
                    "entityId" => 1,
                    "recordId" => "{$vacancyUid}"
                ],
                [
                    /* candidate */
                    "entityId" => 2,
                    "recordId" => $candidateUid
                ],
            ]
        ], $docArgs);

        $document = DocumentsModel::get($documentUid, $docArgs);

        if (is_wp_error($document)) {
            return;
        }

        $args = wp_parse_args([
            'subject' => $document['subject'],
            'htmlMessage' => $document['htmlBody']
        ], $args);

        MailModel::send($args);
    }

    /**
     * Send open application email to consultant
     *
     * @since 2.0.0
     * @param string $candidateUid
     * @param array $args
     * @return void
     */
    public static function sendOpenApplicationMailToConsultant(string $candidateUid, array $args = []): void
    {
        $documentUid = intval(get_option('otys_option_document_template_open_apply_notify_consultant', 0));

        $notificationEmailRecipient = get_option('otys_option_email_open_apply_consultant_email', '');

        // Skip mail if document equals 0
        if ($documentUid === 0) {
            return;
        }

        $document = DocumentsModel::get($documentUid, [
            'relatedEntities' => [
                [
                    /* candidate */
                    "entityId" => 2,
                    "recordId" => $candidateUid
                ],
            ]
        ]);

        if (is_wp_error($document)) {
            return;
        }

        $args = wp_parse_args([
            'subject' => $document['subject'],
            'htmlMessage' => $document['htmlBody'],
            'To' => [
                $notificationEmailRecipient
            ],
            'Bindings' => [
                'entityId' => 2,
                'uid' => $candidateUid
            ]
        ], $args);

        MailModel::send($args);
    }

    /**
     * Send open application email to candidate
     *
     * @since 2.0.0
     * @param string $candidateUid
     * @param array $args
     * @return void
     */
    public static function sendCandidateOpenApplicationMail(string $candidateUid, array $args = [], array $docArgs = []): void
    {
        $documentUid = intval(get_option('otys_option_document_template_open_apply_notify_candidate', 0));

        // Skip mail if document equals 0
        if ($documentUid === 0) {
            return;
        }

        $docArgs = array_merge_recursive($docArgs, [
            'relatedEntities' => [
                [
                    /* candidate */
                    "entityId" => 2,
                    "recordId" => $candidateUid
                ],
            ]
        ]);

        $document = DocumentsModel::get($documentUid, $docArgs);

        if (is_wp_error($document)) {
            return;
        }

        $args = wp_parse_args([
            'subject' => $document['subject'],
            'htmlMessage' => $document['htmlBody'],
        ], $args);

        MailModel::send($args);
    }

    /**
     * Send mail to candidate
     *
     * @since 2.0.0
     * @param string    $candidateUid
     * @param string    $vacancyUid
     * @param boolean   $newCandidate
     * @param array     $args
     * @return void
     */
    public static function sendNewCandidateApplicationMail(int $userId, string $candidateUid, string $vacancyUid, array $args = [], array $docArgs = []): void
    {
        $documentUid = intval(get_option('otys_option_document_template_apply_notify_new_candidate', 0));

        // Skip mail if document equals 0
        if ($documentUid === 0) {
            return;
        }

        $docArgs = array_merge_recursive($docArgs, [
            'relatedEntities' => [
                [
                    /* vacancy */
                    "entityId" => 1,
                    "recordId" => "{$vacancyUid}"
                ],
                [
                    /* candidate */
                    "entityId" => 2,
                    "recordId" => $candidateUid
                ],
            ]
        ]);

        $document = DocumentsModel::get($documentUid, $docArgs);

        if (is_wp_error($document)) {
            return;
        }

        $args = wp_parse_args([
            'subject' => $document['subject'],
            'htmlMessage' => $document['htmlBody'],
        ], $args);

        MailModel::send($args, $userId);
    }

    /**
     * Send mail to candidate
     *
     * @since 2.0.0
     * @param string    $candidateUid
     * @param string    $vacancyUid
     * @param boolean   $newCandidate
     * @param array     $args
     * @return void
     */
    public static function sendExistingCandidateApplicationMail(int $userId, string $candidateUid, string $vacancyUid, array $args = []): void
    {
        $documentUid = intval(get_option('otys_option_document_template_apply_notify_candidate', 0));

        // Skip mail if document equals 0
        if ($documentUid === 0) {
            return;
        }

        $document = DocumentsModel::get($documentUid, [
            'relatedEntities' => [
                [
                    /* vacancy */
                    "entityId" => 1,
                    "recordId" => "{$vacancyUid}"
                ],
                [
                    /* candidate */
                    "entityId" => 2,
                    "recordId" => $candidateUid
                ],
            ]
        ]);

        if (is_wp_error($document)) {
            return;
        }

        $args = wp_parse_args([
            'subject' => $document['subject'],
            'htmlMessage' => $document['htmlBody'],
        ], $args);

        MailModel::send($args, $userId);
    }

    /**
     * Mail to consultant when candidate applied on vacancy
     *
     * @since 2.0.0
     * @param string    $candidateUid
     * @param string    $vacancyUid
     * @param boolean   $newCandidate
     * @param array     $args
     * @return void
     */
    public static function sendConsultantCandidateVacancyApplyMail(string $candidateUid, string $vacancyUid, array $args = []): void
    {
        $documentUid = intval(get_option('otys_option_document_template_apply_notify_consultant', 0));

        // Skip mail if document equals 0
        if ($documentUid === 0) {
            return;
        }

        $document = DocumentsModel::get($documentUid, [
            'relatedEntities' => [
                [
                    /* vacancy */
                    "entityId" => 1,
                    "recordId" => "{$vacancyUid}"
                ],
                [
                    /* candidate */
                    "entityId" => 2,
                    "recordId" => $candidateUid
                ],
            ]
        ]);

        if (is_wp_error($document)) {
            return;
        }

        $args = wp_parse_args([
            'subject' => $document['subject'],
            'htmlMessage' => $document['htmlBody'],
            'Bindings' => [
                [
                    'entityId' => 2,
                    'uid' => $candidateUid
                ]
            ]
        ], $args);

        MailModel::send($args);
    }
}