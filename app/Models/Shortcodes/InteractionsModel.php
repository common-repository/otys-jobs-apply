<?php

namespace Otys\OtysPlugin\Models\Shortcodes;

use Exception;
use Otys\OtysPlugin\Includes\Core\Recaptcha;
use Otys\OtysPlugin\Includes\Core\Routes;
use Otys\OtysPlugin\Includes\OtysApi;
use Otys\OtysPlugin\Models\FilesModel;
use Otys\OtysPlugin\Models\Shortcodes\ShortcodeBaseModel;
use WP_Error;

class InteractionsModel extends ShortcodeBaseModel
{
    /**
     * Interaction form id
     *
     * @var integer
     */
    protected int $id;

    /**
     * Interaction session id
     *
     * @var string
     */
    protected string $sessionId;

    /**
     * Data from the api
     *
     * @var array|null
     */
    protected $data = null;

    /**
     * Interactions Model
     *
     * @param int $id Interaction form id
     * @param string|null $session Interaction session id
     * @param array $validationRules Validation rules
     */
    public function __construct(int $id, $session = null, $validationRules = [])
    {
        parent::__construct();

        $this->id = (int) $id;
    }
    
    /**
     * Get interaction form id
     *
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get interaction form fields
     *
     * @return array
     */
    public function getFields(): array
    {
        $fields = $this->getInteractionData()['form']['Fields'];
        $templateDir = '/include-parts/rest-forms/';

        foreach ($fields as $key => $field) {
            $type = strtolower($field['typeUi']);

            // Template directory for forms used for field front end template
            switch ($type) {
                case 'textfield':
                    $template = Routes::locateTemplate($templateDir . 'field-text');

                    if ($field['Validation']['phone'] === true) {
                        $htmlType = 'tel';
                    } elseif ($field['Validation']['email'] === true) {
                        $htmlType = 'email';
                    }
                    break;

                case 'textarea':
                    $template = Routes::locateTemplate($templateDir . 'field-textarea');
                    $htmlType = 'textarea';
                    break;

                case 'motivation':
                    $template = Routes::locateTemplate($templateDir . 'field-textarea');
                    $htmlType = 'textarea';
                    break;

                case 'dateselect':
                    $template = Routes::locateTemplate($templateDir . 'field-date');
                    $htmlType = 'date';
                    break;

                case 'file':
                    $template = Routes::locateTemplate($templateDir . 'field-file');
                    $htmlType = 'file';
                    break;

                case 'multifile':
                    $template = Routes::locateTemplate($templateDir . 'field-multifile');
                    $htmlType = 'multifile';
                    break;

                case 'multiselect':
                    $template = Routes::locateTemplate($templateDir . 'field-select');
                    $htmlType = 'multiselect';
                    break;

                case 'select':
                    $template = Routes::locateTemplate($templateDir . 'field-select');
                    $htmlType = 'select';
                    break;

                case 'criteria':
                    $template = Routes::locateTemplate($templateDir . 'field-select');
                    $htmlType = 'multiselect';
                    break;

                case 'other':
                    $template = Routes::locateTemplate($templateDir . 'field-checkbox');
                    $htmlType = 'checkbox';
                    break;

                case 'checkbox':
                    $template = Routes::locateTemplate($templateDir . 'field-checkbox');
                    $htmlType = 'checkbox';
                    break;

                case 'multicheckbox':
                    $template = Routes::locateTemplate($templateDir . 'field-checkbox');
                    $htmlType = 'checkbox';
                    break;

                case 'radio':
                    $template = Routes::locateTemplate($templateDir . 'field-radio');
                    $htmlType = 'radio';
                    break;


                default:
                    $template = Routes::locateTemplate($templateDir . 'field-text');
                    $htmlType = 'text';
                    break;
            }

             // Add field data to tab
            $fields[$key]['validation'] = $field['Validation'];

            // Change some field data
            $fields[$key]['question'] = $field['name'];
            
            $fields[$key]['name'] = 'ia_' . $field['uid'];
            
            $fields[$key]['explanation'] = $field['description'];

            // TODO check if we can be more specific for the autocomplete on interactions
            $fields[$key]['autoCompleteName'] = 'on';
            
            $fields[$key]['answers'] = $field['Answers'];
            unset($fields[$key]['Answers']);

            // Format match criteria ui type
            if ($field['typeUi'] === 'Criteria') {
                foreach ($field['Answers'] as $answerKey => $answer) {
                    $fields[$key]['answers'][$answerKey] = [
                        'uid' => $answer['uid']['answer'],
                        'answer' => $answer['value']['answer'],
                        'rank' => $answer['rank']['answer']
                    ];
                }
            }

            $fields[$key]['type'] = $htmlType;
            $fields[$key]['template'] = $template;
        }

        return $fields;
    }

    /**
     * Get form tabs
     *
     * @return array
     */
    public function getFormTabs(): array
    {
        $data = $this->getInteractionData();

        $tabs = $data['form']['Tabs'];

        $pageNumber = 1;

        if (empty($tabs)) {
            $tabs = [
                0 => [
                    'uid' => 0,
                    'name' => 'General',
                    'rank' => 1
                ]
            ];
        }
  
        foreach ($tabs as $tabKey => $tab) {
            $tabs[$tabKey]['pageNumber'] = $pageNumber;

            $pageNumber++;
        }

        foreach ($this->getFields() as $field) {
            $tabId = $field['tabId'] ?? array_key_first($tabs);

            $tabId = !isset($tabs[$tabId]) ? array_key_first($tabs) : $tabId;

            // Add field to tab
            $tabs[$tabId]['fields'][$field['uid']] = $field;
        }

        return $tabs;
    }

    /**
     * Get session id
     *
     * @return string|null
     */
    public function getSessionId()
    {
        if (isset($this->sessionId)) {
            return $this->sessionId;
        }

        // Create new session
        $response = OtysApi::post(
            [
                'method' => 'Otys.Services.FreeInteractionService.sessionStart',
                'params' => [
                    $this->id,
                    null,
                    null,
                    null
                ]
            ],
            false
        );

        if (is_wp_error($response)) {
            $this->errors->add($response->get_error_code(), $response->get_error_message());
            
            throw new Exception(__('Error while fetching the interaction form.', 'otys-jobs-apply'));
        }
        
        if (!is_array($response) || empty($response)) {        
            $this->errors->add('no_data', __('Interaction form data not found.', 'otys-jobs-apply'));

            throw new Exception(__('Could not find interaction form data.', 'otys-jobs-apply'), 404);
        }

        if (!isset($response['sessionId'])) {
            $this->errors->add('no_session_id', __('No session id found.', 'otys-jobs-apply'));

            throw new Exception(__('No session id found.', 'otys-jobs-apply'), 500);
        }

        $this->sessionId = $response['sessionId'];

        return $this->sessionId;
    }

    /**
     * Get interaction form from the API
     *
     * @param integer $id
     * @return array|WP_Error
     */
    private function getInteractionData(): array
    {
        if ($this->data) {
            return $this->data;
        }

        $response = OtysApi::post(
            [
                'method' => 'Otys.Services.FreeInteractionService.sessionStart',
                'params' => [
                    $this->id,
                    null,
                    null,
                    null
                ]
            ],
            true
        );

        if (is_wp_error($response)) {
            $this->errors->add($response->get_error_code(), $response->get_error_message());
            
            throw new Exception(__('Error while fetching the interaction form.', 'otys-jobs-apply'));
        }
        
        if (!is_array($response) || empty($response)) {        
            $this->errors->add('no_data', __('Interaction form data not found.', 'otys-jobs-apply'));

            throw new Exception(__('Could not find interaction form data.', 'otys-jobs-apply'), 404);
        }

        return $response;
    }

    /**
     * Submit interaction form
     *
     * @param array $requestData
     * @return array
     */
    public function submit(array $requestData): array
    {
        $response = OtysApi::post(
            [
                'method' => 'Otys.Services.FreeInteractionService.sessionSubmit',
                'params' => [
                    $this->getSessionId(),
                    $requestData
                ]
            ],
            false,
            false
        );

        if (is_wp_error($response)) {
            throw new Exception(__('Error while submitting the interaction form', 'otys-jobs-apply'), 500);
        }

        return $response;
    }

    /**
     * Commit
     *
     * @return array
     */
    public function commit(): array
    {
        if ($this->getSessionId() === null) {
            throw new Exception(__('No session id found.', 'otys-jobs-apply'), 500);
        }

        $response = OtysApi::post(
            [
                'method' => 'Otys.Services.FreeInteractionService.sessionCommit',
                'params' => [
                    $this->getSessionId()
                ]
            ],
            false,
            false
        );

        if (is_wp_error($response)) {
            throw new Exception(__('Errors occured while commiting the session', 'otys-jobs-apply'), 500);
        }

        return $response;
    }

    /**
     * Get allowed interaction values
     *
     * @param array $postData
     * @return array
     */
    public function getInteractionValues(array $postData): array
    {
        $fields = $this->getFields();

        $fieldNames = array_map(function($field) {
            return $field['name']; 
        }, $fields);

        $postData = array_filter($postData, function ($key) use ($fieldNames) {
            return in_array($key, $fieldNames);
        }, ARRAY_FILTER_USE_KEY);

        $interactionValues = [];

        foreach ($postData as $fieldKey => $fieldValue) {
            $fieldUid = array_search($fieldKey, $fieldNames);

            if ($fieldUid !== false) {
                $interactionValues[$fieldUid] = $fieldValue;
            }
        }

        return $interactionValues;
    }

    /**
     * Filter interaction data
     *
     * @param array $postData
     * @param InteractionsModel $model
     * @return array
     */
    public function filterData(array $values): array
    {
        return $values;
    }

    /**
     * Interaction validation based on OTYS validation rules
     *
     * @since 2.0.0
     * @param mixed $value
     * @param array $validations
     * @return WP_Error
     */
    public function validate($value, array $validations)
    {
        $errors = new WP_Error();

        foreach ($validations as $validator => $validation) {
            if (!$validation) {
                continue;
            }

            if ($validator === 'mandatory') {
                if ($value == '' || $value == false || $value == null) {
                    $errors->add($validator, __('This field is required.', 'otys-jobs-apply'));
                }

                if (isset($value['documents']) && empty($value['documents'])) {
                    $errors->add($validator, __('File upload is required.', 'otys-jobs-apply'));
                }
            }

            if ($validator === 'date') {
                if ($value !== null) {
                    if (\DateTime::createFromFormat('Y-m-d H:i:s', $value) === false) {
                        $errors->add($validator, __('Value should be a valid date.', 'otys-jobs-apply'));
                    }
                }
            }

            if ($validator === 'postCode') {
                if ($value === '') {
                    $errors->add($validator, __('Postal code is invalid.', 'otys-jobs-apply'));
                }
            }

            if ($validator === 'digitsMaxLen') {
                if (is_string($value)) {
                    if (preg_match_all("/[0-9]/", $value) > $validation) {
                        $errors->add($validator, __('Too many digits.', 'otys-jobs-apply'));
                    }
                }
            }

            if ($validator === 'email') {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors->add($validator, __('Invalid email adres.', 'otys-jobs-apply'));
                }
            }

            if ($validator === 'validCharacters') {
                if ($value === null || (!is_string($value) && !is_int($value))) {
                    $errors->add($validator, __('Only numbers & letters are allowed.', 'otys-jobs-apply'));
                } else {
                    if (!preg_match('/^[a-z0-9 .\-]+$/i', $value)) {
                        $errors->add($validator, __('Only numbers & letters are allowed.', 'otys-jobs-apply'));
                    }
                }
            }

            if ($validator === 'phone') {
                if (!$value) {
                    $errors->add($validator, __('Invalid phonenumber.', 'otys-jobs-apply'));
                } else {
                    $phoneNumber = str_replace([' ', '+', '(', ')'], '', $value);
                    
                    if (!is_numeric($phoneNumber) || strlen($phoneNumber) < 3 || strlen($phoneNumber) > 16) {
                        $errors->add($validator, __('Invalid phonenumber.', 'otys-jobs-apply'));
                    }
                }
            }

            if ($validator === 'internationalPhone') {
                if (!is_numeric($value) || strpos($value, '+') === false || strlen($value) < 14) {
                    $errors->add($validator, __('Please enter a international phonenumber.', 'otys-jobs-apply'));
                }
            }

            if ($validator === 'maxDigits') {
                if ($value !== null || !is_string($value)) {
                    if (preg_match_all("/[0-9]/", $value) > $validation) {
                        $errors->add($validator, __('Too many digits.', 'otys-jobs-apply'));
                    }
                }
            }

            if ($validator === 'validBirth') {
                if ($value === null) {
                    $errors->add($validator, __('Value should be a valid date of birth.', 'otys-jobs-apply'));
                } else {
                    if (\DateTime::createFromFormat('Y-m-d H:i:s', $value) === false || strtotime($value) > strtotime('now')) {
                        $errors->add($validator, __('Value should be a valid date of birth.', 'otys-jobs-apply'));
                    }
                }
            }

            if ($validator === 'documentType') {
                if (is_array($value)) {
                    foreach ($value as $file) {
                        $validatedUpload = FilesModel::validateDocumentUpload($file, $validations['mandatory']);

                        if (is_wp_error($validatedUpload) && $validatedUpload->has_errors()) {
                            $errors->add($validatedUpload->get_error_code(), $validatedUpload->get_error_messages());
                        }
                    }
                } else {
                    $errors->add($validator, __('Invalid document type.', 'otys-jobs-apply'));
                }
            }
        }

        return $errors;
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
        $questionsetValidation = array_merge_recursive([
            'id' => function ($value) {
                $value = filter_var($value, FILTER_SANITIZE_ENCODED);

                $validated = [
                    'value' => $value,
                    'errors' => []
                ];

                if (!is_string($value)) {
                    $validated['errors'] = [__('Invalid id.', 'otys-jobs-apply')];
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
        ], $this->getValidation());

        $postData = static::doValidation($postData, $questionsetValidation);

        return $postData;
    }

    /**
     * Get filter validation array
     * Can be used with filter_var_array()
     *
     * @since 2.0.0
     * @return array
     */
    public function getValidation(): array
    {
        $validation = [];

        $fields = $this->getFields();

        foreach ($fields as $field) {
            $validation[$field['name']] = function ($value) use ($field) {
                $errors = static::validate($value, $field['Validation']);

                return [
                    'value' => $value,
                    'errors' => $errors->get_error_messages()
                ];
            };
        }

        return $validation;
    }
}