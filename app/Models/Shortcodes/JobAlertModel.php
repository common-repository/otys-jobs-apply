<?php
namespace Otys\OtysPlugin\Models\Shortcodes;

use Otys\OtysPlugin\Helpers\SettingHelper;
use Otys\OtysPlugin\Includes\Core\Cache;
use Otys\OtysPlugin\Includes\OtysApi;
use Otys\OtysPlugin\Models\Shortcodes\InteractionsModel;

final class JobAlertModel extends InteractionsModel
{
    protected $formName;

    public function __construct(int $id = 0, $session = null, $validationRules = [])
    {
        $this->formName = 'WordPress JobAlert (Do not edit or remove)';

        $id = $this->getJobAlertInteractionFormId();

        if (is_wp_error($id)) {
            $this->errors = $id;
            return;
        }

        parent::__construct($id, $session, $validationRules);
    }

    /**
     * Extend the validation of the post data
     *
     * @param array $postData
     * @return array
     */
    public function validatePost(array $postData = []): array
    {
        // Get the match criteria fields
        $matchCriteriaFields = array_filter($this->getFields(), function($field) {
            return $field['typeUi'] === 'Criteria';
        });

        // Get the field names of the match criteria fields
        $matchCriteriaFieldNames = array_map(function($field) {
            return $field['name'];
        }, $matchCriteriaFields);
       
        // Filter out the filled in match criteria fields
        $filledInCriteriaFields = array_filter($postData, function($value, $key) use ($matchCriteriaFieldNames) {
            return in_array($key, $matchCriteriaFieldNames);
        }, ARRAY_FILTER_USE_BOTH);

        // Use default validation of the parent class aswell
        $return = parent::validatePost($postData);
 
        // If no match criteria are filled in, add an error
        if (empty($filledInCriteriaFields)) {
            $return['system'] = [
                'errors' => [__('Please choose at least one match criteria', 'otys-jobs-apply')]
            ];
        }
        
        return $return;
    }

    /**
     * Get the ID of the JobAlert interaction form
     *
     * @return int|\WP_Error
     */
    public function getJobAlertInteractionFormId()
    {
        $interactionForms = OtysApi::post([
            'method' => 'Otys.Services.FreeInteractionService.getListEx',
            'params' => [
                [
                    'what' => [
                        'uid' => 1,
                        'name' => 1,
                        'actionId' => 1
                    ],
                    'limit' => 999,
                    'sort' => [
                        'entryDateTime' => 'DESC'
                    ],
                    'condition' => [
                        'type' => 'AND',
                        'items' => [
                            // TODO Uncomment this when task #527217 is done
                            // [
                            //     'type' => 'COND',
                            //     'field' => 'name',
                            //     'op' => 'EQ',
                            //     'param' => $this->formName
                            // ],
                            [
                                'type' => 'COND',
                                'field' => 'deleted',
                                'op' => 'EQ',
                                'param' => false
                            ]
                        ]
                    ]
                ]
            ]
        ], true);


        if (is_wp_error($interactionForms) || !isset($interactionForms['listOutput'])) {
            return new \WP_Error('otys_api_error', 'Error while fetching the interaction form');
        }

        $interactionForms = array_filter($interactionForms['listOutput'], function($interactionForm) {
            return $interactionForm['name'] === $this->formName;
        });

        $interactionForms = array_values($interactionForms);


        if (empty($interactionForms)) {
            // Create form if it doesn't exist
            $id = $this->createInteractionForm();
           
            if (!is_wp_error($id)) {
                return (int) $id;
            }

            return new \WP_Error('otys_api_error', 'Error while fetching the interaction form');
        }

        // Get the first form from the list
        $interactionForm = $interactionForms[0];

        // If the form is not the JobAlert form, delete it and create a new one
        if ($interactionForm['actionId'] !== 'InsertJobSearchAgent') {
            $delete = $this->delete($interactionForm['uid']);

            if (is_wp_error($delete)) {
                return new \WP_Error('otys_api_error', 'Error while fetching the interaction form');
            }

            $id = $this->createInteractionForm();

            if (!is_wp_error($id)) {
                return (int) $id;
            }

            return new \WP_Error('otys_api_error', 'Error while fetching the interaction form');
        }

        return (int) $interactionForm['uid'];
    }

    /**
     * Create the JobAlert interaction form for Job Alert
     *
     * @return int|\WP_Error
     */
    public function createInteractionForm()
    {
        $formId = OtysApi::post([
            'method' => 'Otys.Services.FreeInteractionService.add',
            'params' => [
                [
                    'name' => $this->formName
                ]
            ]
        ], false, false);

        if (is_wp_error($formId)) {
            return new \WP_Error('otys_api_error', 'Error while creating the interaction form');
        }

        $options = $this->getJobAlertInteractionFieldOptions();

        $fieldsToAdd = array_map(function($option) {
            return [
                'name' => $option['name'],
                'typeUi' => $option['typeUi'],
                'dbFieldId' => $option['uid'],
                'useInPdf' => false,
                'Validation' => $option['Validation']
            ];
        }, $options);

        $update = OtysApi::post([
            'method' => 'Otys.Services.FreeInteractionService.updateWithVersioning',
            'params' => [
                $formId,
                [
                    'actionId' => 'InsertJobSearchAgent',
                    'Fields' => [
                        'ADD' => $fieldsToAdd
                    ]
                ]
            ]
        ], false, false);

        $this->clearCache();

        if (is_wp_error($update)) {
            return new \WP_Error('otys_api_error', 'Error while creating the interaction form');
        }

        return $formId;
    }

    /**
     * Update the JobAlert interaction form for Job Alert
     *
     * @param array $fieldData
     * @return bool|\WP_Error
     */
    public function updateInteractionForm(array $fieldData)
    {
        $update = OtysApi::post([
            'method' => 'Otys.Services.FreeInteractionService.updateWithVersioning',
            'params' => [
                $this->id,
                [
                    'Fields' => $fieldData
                ]
            ]
        ], true);

        $this->clearCache();

        if (is_wp_error($update)) {
            return new \WP_Error('otys_api_error', 'Error while updating the interaction form');
        }

        return true;
    }

    /**
     * Get the JobAlert interaction field options
     *
     * @return array|\WP_Error
     */
    private function getJobAlertInteractionFieldOptions()
    {
        $options = OtysApi::post([
            'method' => 'Otys.Services.FreeInteractionService.getOptionListsEx',
            'params' => [
                [
                    "Fields.dbFieldId"
                ]
            ]
        ]);

        $fieldUids = [
            'class' => 486,
            'email' => 463,
            'name' => 460,
            'website' => 461,
            'language' => 462,
            'period' => 464,
        ];

        $jobsearchoptions = array_filter($options['Fields.dbFieldId'], function($option) use ($fieldUids) {        
            if (
                $option['category'] === 'JobSearchAgent' &&
                (
                    $option['typeUi'] === 'Criteria' ||
                    in_array($option['uid'], $fieldUids)
                )
            ) {
                return true;
            }
            
            return false;
        });

        // Define the fields for the JobAlert form
        $jobsearchoptions = array_map(function($option) {
            // Set default name to value
            $option['name'] = $option['value'];

            // Make every field mandatory except for Criteria
            if ($option['typeUi'] !== 'Criteria') {
                $option['Validation']['mandatory'] = true;
            }
            
            // Translate required field names and add validation rules
            switch ($option['uid']) {
                case 462:
                    $option['name'] = __('Language', 'otys-jobs-apply');
                    break;
                case 463:
                    $option['name'] = __('E-mail', 'otys-jobs-apply');
                    $option['Validation']['email'] = true;
                    break;
                case 464:
                    $option['name'] = __('Period', 'otys-jobs-apply');
                    break;
            }
            
            return $option;
        }, $jobsearchoptions);

        return $jobsearchoptions;
    }

    /**
     * Filter the data before sending it to the model
     *
     * @param array $values
     * @return array
     */
    public function filterData(array $values): array
    {   
        $fields = $this->getFields();

        $systemFields = array_filter($fields, function($field) use ($values) {
            return !isset($values[$field['name']]);
        });

        // Set values that are not set by the user
        foreach ($systemFields as $systemField) {
            $fieldName = $systemField['name'];

            switch ($systemField['wsField']) {
                case 'class':
                    $values[$fieldName] = 'vacancyAdvancedSearchRequest';
                    break;
                
                case 'siteId':
                    $values[$fieldName] = SettingHelper::getSiteId();
                    break;

                case 'cmsLanguage':
                    $language = strtoupper(OtysApi::getLanguage(get_locale()));
                    foreach ($systemField['answers'] as $fieldAnswer) {
                        if ($fieldAnswer['answer'] === $language) {
                            $values[$fieldName] = $fieldAnswer['uid'];
                            break;
                        }
                    }
                    break;
                
                case 'name':
                    $values[$fieldName] = 'Jobalert';
                    break;
            }
        }
        
        return $values;
    }

    /**
     * Delete the JobAlert interaction form
     *
     * @return boolean
     */
    protected function delete(int $id): bool
    {
        $delete = OtysApi::post([
            'method' => 'Otys.Services.FreeInteractionService.update',
            'params' => [
                $id,
                [
                    'deleted' => true
                ]
            ]
        ]);

        if (!is_wp_error($delete)) {
            return true;
        }

        return false;
    }

    /**
     * Clear the cache
     *
     * @return void
     */
    protected function clearCache()
    {
        // TODO Make the cache key more specific
        Cache::delete('FreeInteractionService');
    }

    /**
     * Check if job alert is enabled
     *
     * @return boolean
     */
    public function isEnabled(): bool
    {
        $response = OtysApi::post([
            'method' => 'Otys.Services.JobSearchAgentService.getListEx',
            'params' => [
                [
                    "Person.languageCode"
                ]
            ]
        ], true, false);

        if (is_wp_error($response)) {
            return false;
        }

        return true;
    }
}