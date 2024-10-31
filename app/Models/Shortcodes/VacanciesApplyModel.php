<?php

namespace Otys\OtysPlugin\Models\Shortcodes;

use Otys\OtysPlugin\Includes\Core\ApplicationSessions;
use Otys\OtysPlugin\Includes\Core\Recaptcha;
use Otys\OtysPlugin\Includes\OtysApi;
use Otys\OtysPlugin\Models\QuestionsetModel;
use Otys\OtysPlugin\Models\Shortcodes\ShortcodeBaseModel;
use Otys\OtysPlugin\Models\Shortcodes\VacanciesFiltersModel;
use Otys\OtysPlugin\Models\VacanciesDetailModel;

use WP_REST_Request;

/**
 * This is the model that is used by the otys-vacancy-apply shortcode.
 */
final class VacanciesApplyModel extends ShortcodeBaseModel
{
    /**
     * Instance of questionsetModel
     *
     * @var QuestionsetModel
     */
    private $questionsetModel;

    /**
     * Store questionset for current instance
     *
     * @var string array
     */
    private $questionset;

    /**
     * Vacancy uid
     *
     * @var string
     */
    private string $vacancyUid;

    /**
     * Auth user
     */
    private $authUser;

    /**
     * Vacancy information current instance
     *
     * @var array
     */
    private array $vacancy;

    /**
     * Post data from current instance
     *
     * @var array
     */
    private array $postData;

    /**
     * File data from post
     *
     * @var array
     */
    private array $uploadedFiles;

    /**
     * Current request
     *
     * @var WP_REST_Request
     */
    private $request;

    /**
     * Constructor
     *
     * Creates instance of the questionset
     *
     * @param WP_REST_Request $request
     */
    public function __construct($request)
    {
        parent::__construct();

        $this->vacancyUid = $request->get_param('vacancy_uid') === null ? '' : $request->get_param('vacancy_uid');

        // $this->authUser = $request->get_param('auth_user') === null ? false : $request->get_param('auth_user');
        $this->authUser = AuthModel::getUser();

        $this->questionsetModel = new QuestionsetModel($this->vacancyUid);

        $this->request = $request;

        /**
         * Set post data
         */
        $uploadedFiles = (array) $this->request->get_file_params();
        $this->uploadedFiles = static::reArrayFiles($uploadedFiles);

        // Get all post data together in one variable
        $postData = array_merge(
            $this->request->get_body_params(),
            $this->uploadedFiles
        );

        // Reset confirmationcode if no confirmationcode is send with the request
        if (!isset($postData['confirmationcode'])) {
            ApplicationSessions::resetConfirmationcode($this->vacancyUid);
        }

        // Validate postdata
        if (!$this->questionsetModel->hasErrors()) {
            $this->postData = $this->validatePost($postData);
        }

        // Pass through any errors of the questionset model
        if ($this->questionsetModel->hasErrors()) {
            $this->errors->add(
                $this->questionsetModel->getErrors()->get_error_code(),
                $this->questionsetModel->getErrors()->get_error_message(),
                $this->questionsetModel->getErrors()->get_error_data()
            );
        }

        // Set questionset data
        $this->setQuestionset();

        // Set vacancy data
        $this->setVacancy();
    }

    /**
     * Set candidate data
     *
     * @return array
     */
    public function getCandidateData(): array
    {
        $validatedPostData = $this->getPostData();
        $candidateData = [];

        // Get validated POST data with OWS names
        $owsFieldNames = $this->getQuestionsetModel()->getOwsFieldNames();

        // Fill candidate data
        array_walk($owsFieldNames, function ($questionName, $owsFieldName) use (&$candidateData, $validatedPostData) {
            $value = $validatedPostData[$questionName]['value'];

            // Motivation field needs to have questionId supplied..
            if ($owsFieldName === 'motivation') {
                $value = [
                    'answer' => $validatedPostData[$questionName]['value'],
                    'questionId' => str_replace('qs_', '', $questionName)
                ];
            }

            $candidateData[$owsFieldName] = $value;
        });

        if ($this->authUser) {
            $candidateData['Person.emailPrimary'] = [
                'answer' => $this->authUser->getEmail(),
                'questionId' => 'system_field'
            ];
        }

        return $candidateData;
    }

    /**
     * Get vacancy uid
     *
     * @return string
     */
    public function getVacancyUid(): string
    {
        return $this->vacancyUid;
    }

    /**
     * Get uploaded files data
     *
     * @return array
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * Returns validated post data
     *
     * @return array
     */
    public function getPostData(): array
    {
        return $this->postData;
    }

    /**
     * Get the current questionset model
     *
     * @return QuestionsetModel
     */
    public function getQuestionsetModel()
    {
        return $this->questionsetModel;
    }

    /**
     * Get questionset for current session
     *
     * @return void
     */
    private function setQuestionset(): void
    {
        $sessionData = ApplicationSessions::get($this->vacancyUid);

        $completed = empty($sessionData) || !isset($sessionData['completed']) || $sessionData['completed'] === false ? false : true;

        $questionset = $this->questionsetModel->get();

        if (is_wp_error($questionset) || empty($questionset)) {
            $this->questionset = [];
            
            return;
        }

        // Assign question answers from session to questionset
        foreach ($questionset['pages'] as $pageId => $page) {
            foreach ($page['questions'] as $questionId => $question) {
                $sessionQuestionData = ApplicationSessions::getQuestion($this->vacancyUid, $question['name']);

                $value =
                    $completed === false &&
                    isset($sessionQuestionData['value'])
                    ? $sessionQuestionData['value'] : '';

                if ($value !== '') {
                    $questionset['pages'][$pageId]['questions'][$questionId]['value'] = $value;
                }
            }
        }

        $this->questionset = $questionset;
    }

    /**
     * Get questionset for current instance
     *
     * @return array
     */
    public function getQuestionset(): array
    {
        return $this->questionset;
    }

    /**
     * Set vacancy
     *
     * @return void
     */
    private function setVacancy(): void
    {
        $vacancy = VacanciesDetailModel::get($this->vacancyUid, [], false);

        if (is_wp_error($vacancy)) {
            $this->errors->add('vacancy_error', __('Something went wrong while retrieving the vacancy.', 'otys-jobs-apply'));
            $this->vacancy = [];
        } else {
            $this->vacancy = $vacancy;
        }
    }

    /**
     * Get vacancy for current instance
     *
     * @since 2.0.0
     * @return array
     */
    public function getVacancy(): array
    {
        return $this->vacancy;
    }

    /**
     * Validate shortcode attributes
     *
     * @since 2.0.0
     * @param $params           Params to be validated
     * @param $filtersAsArray   Wheter to return the filter options as array
     * @return array
     */
    public static function validateAtts(array $params = []): array
    {
        // Add default validation
        $validationRules = [
            'vacancy-uid' => [
                'filter' => FILTER_SANITIZE_ENCODED
            ]
        ];

        return static::doSanitiziation($params, $validationRules);
    }

    /**
     * Validate POST
     *
     * @since 2.0.0
     * @param array $postData
     * @param integer $questionsetId
     * @return array
     */
    public function validatePost(array $postData = []): array
    {
        $vacancyUid = $this->getVacancyUid();

        $questionsetValidation = array_merge_recursive([
            'vacancy_uid' => function ($value) {
                $value = filter_var($value, FILTER_SANITIZE_ENCODED);

                $validated = [
                    'value' => $value,
                    'errors' => []
                ];

                if (!is_string($value)) {
                    $validated['errors'] = [__('Invalid vacancy uid.', 'otys-jobs-apply')];
                }

                return $validated;
            },
            'g-recaptcha-response' => function ($value) {
                $value = filter_var($value, FILTER_SANITIZE_ENCODED);
                
                $recaptcha = new Recaptcha();

                $validated = [
                    'value' => $value,
                    'errors' => []
                ];

                if (!is_string($value) || !$recaptcha->verify($value)) {
                    $validated['errors'][] = __('reCAPTCHA is incorrect. Please try again.', 'otys-jobs-apply');
                }

                return $validated;
            },
            'action' => function ($value) {
                $value = filter_var($value, FILTER_SANITIZE_ENCODED);

                $validated = [
                    'value' => $value,
                    'errors' => []
                ];

                if (!is_string($value)) {
                    $validated['errors'] = [__('Invalid action.', 'otys-jobs-apply')];
                }

                return $validated;
            },
            'confirmationcode' => function ($code) use ($vacancyUid, $postData) {
                $code = (int) filter_var($code, FILTER_SANITIZE_ENCODED);

                // Get email question name
                $owsFieldNames = $this->getQuestionsetModel()->getOwsFieldNames();
                $emailQuestionName = isset($owsFieldNames['Person.emailPrimary']) ? $owsFieldNames['Person.emailPrimary'] : null;

                $email = $emailQuestionName !== null && isset($postData[$emailQuestionName]) ? $postData[$emailQuestionName] : '';

                $validated = [
                    'value' => $code,
                    'errors' => []
                ];

                $confirmationcodeHash = ApplicationSessions::getConfirmationcode($vacancyUid);

                $codeHash = ApplicationSessions::getConfirmationCodeHash($code, $email);

                // If a confirmationcode is set for the current session require confirmation
                if ($confirmationcodeHash && ($confirmationcodeHash != $codeHash)) {
                    $validated['errors'][] = __('Invalid confirmation code.', 'otys-jobs-apply');
                }

                return $validated;
            },
            'redirect' => function ($value) {
                if (!is_string($value)) {
                    return false;
                }

                $value = sanitize_title($value);

                if ($value === '') {
                    return false;
                }

                $validated = [
                    'value' => $value,
                    'errors' => []
                ];

                $redirectPage = get_page_by_path($value);

                $redirectLink = $redirectPage === null ? null : get_permalink($redirectPage->ID);

                if ($redirectLink !== null) {
                    $validated['value'] = $redirectLink;

                    return $validated;
                }

                return false;
            }
        ], $this->questionsetModel->getQuestionsetValidation());

        $postData = static::doValidation($postData, $questionsetValidation);

        return $postData;
    }

    /**
     * Validate url parameters
     *
     * @param $params           Params to be validated
     * @param $filtersAsArray   Wheter to return the filter options as array
     * @return array
     * @since 1.0.0
     */
    public static function validateUrlParams(array $params = []): array
    {
        // Get validation for filters
        $filterValidation = VacanciesFiltersModel::getFiltersValidation();

        return static::doSanitiziation($params, $filterValidation);
    }

    /**
     * Get candidate by email
     *
     * @param string $email
     * @return string | bool    Candidate UID | False
     */
    public static function getCandidateByEmail(string $email)
    {
        $candidate = OtysApi::post([
            'method' => 'Otys.Services.CandidateService.getListEx',
            'params' => [
                [
                    'search' => [
                        'ACTONOMY' => [
                            'DATA' => [
                                'hasEmail' => [
                                    'options' => [
                                        'required' => true,
                                        'persistent' => true
                                    ],
                                    'value' => "1"
                                ],
                                'keywords' => [
                                    'options' => [
                                        'enableExpansion' => false,
                                        'required' => true,
                                        'searchList' => [
                                            'emailAddress'
                                        ],
                                        'searchMode' => 'one'
                                    ],
                                    "value" => "\"$email\""
                                ]
                            ],
                            'OPTIONS' => [
                                'getTotalCount' => 1,
                                'limit' => 1,
                                'offset' => 0,
                                'sort' => [
                                    'SCORE' => 'DESC',
                                    'entryDateFull' => 'DESC'
                                ],

                            ],
                            "VERSION" => 2,
                            "SUB_VERSION" => 0
                        ]
                    ],
                    'limit' => 1,
                    'excludeLimitCheck' => 1,
                    'getTotalCount' => 1,
                    'what' => [
                        'uid' => 1
                    ]
                ]
            ]
        ], false);

        if (
            array_key_exists('listOutput', $candidate) &&
            is_array($candidate['listOutput']) &&
            !empty($candidate['listOutput'])
        ) {
            return $candidate['listOutput'][0]['uid'];
        }

        return false;
    }
}