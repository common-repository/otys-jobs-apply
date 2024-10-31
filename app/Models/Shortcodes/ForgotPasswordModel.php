<?php

namespace Otys\OtysPlugin\Models\Shortcodes;

use Exception;
use Otys\OtysPlugin\Includes\Core\Recaptcha;
use Otys\OtysPlugin\Includes\OtysApi;
use Otys\OtysPlugin\Models\DocumentsModel;
use Otys\OtysPlugin\Models\MailModel;
use Otys\OtysPlugin\Models\Shortcodes\ShortcodeBaseModel;

class ForgotPasswordModel extends ShortcodeBaseModel
{
    /**
     * Validate shortcode attributes
     *
     * @param $params           Params to be validated
     * @param $filtersAsArray   Wheter to return the filter options as array
     * @return array
     */
    public function validateAtts(array $params = []): array
    {
        // Add default validation
        $validationRules =
        [
            'redirect' => [
                'filter' => FILTER_VALIDATE_URL
            ]
        ];

        return static::doSanitiziation($params, $validationRules);
    }

    /**
     * Validate POST
     *
     * @param array $postData
     * @param integer $questionsetId
     * @return array
     */
    public function validatePost(array $postData = []): array
    {
        $questionsetValidation = [
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
            'username' => function ($value) {
                if (!is_string($value)) {
                    return false;
                }

                return [
                    'value' => $value,
                    'errors' => []
                ];
            }
        ];

        $postData = static::doValidation($postData, $questionsetValidation);

        return $postData;
    }

    /**
     * Reset candidate password
     *
     * @param string $candidateUid
     * @param string $password
     * @throws \Exception
     * @return void
     */
    public function resetCandidatePassword(string $candidateUid, string $password): void
    {
        try {
            $response = OtysApi::post([
                'method' => 'Otys.Services.WebusersService.setNewPassword',
                'params' => [
                    $candidateUid,
                    $password
                ]
            ]);

            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }

            if ($response !== true) {
                throw new Exception(__('An error occurred while resetting the password.', 'otys-jobs-apply'));
            }
        } catch (Exception $e) {
            throw new $e;
        }
    }

    /**
     * Get web user by username
     *
     * @param string $username
     * @throws \Exception
     * @return array
     */
    public function getWebUserByUsername(string $username): array
    {
        try {
            $response = OtysApi::post([
                'method' => 'Otys.Services.WebusersService.getListEx',
                'params' => [
                    [
                        'what' => [
                            'uid' => 1,
                            'roles' => [
                                'uid' => 1,
                                'role' => 1,
                                'targetName' => 1,
                                'uidTarget' => 1,
                                'entryDateTime' => 1,
                                'active' => 1
                            ]
                        ],
                        'limit' => 1,
                        'offset' => 0,
                        'getTotalCount' => true,
                        'sort' => [
                            'uid' => 'DESC'
                        ],
                        'condition' => [
                            'type' => 'AND',
                            'items' => [
                                [
                                    'type' => 'COND',
                                    'field' => 'username',
                                    'op' => 'LIKE',
                                    'param' => "%{$username}%"
                                ]
                            ]
                        ]
                    ]
                ]
            ], false, false, [], false);

            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }

            $webuser = $response['listOutput'][0] ?? null;

            if ($webuser === null || !is_array($webuser)) {
                throw new Exception(__('User not found.', 'otys-jobs-apply'));
            }
        } catch (Exception $e) {
            throw new $e;
        }

        return $webuser;
    }
    
    /**
     * Get candidate by uid
     *
     * @param string $uid
     * @throws \Exception
     * @return array
     */
    public function getCandidateByUid(string $uid): array
    {
        try {
            $candidate = OtysApi::post([
                'method' => 'Otys.Services.CandidateService.getDetail',
                'params' => [
                    $uid,
                    [
            
                        'uid' => 1,
                        'Person' => [
                            'emailPrimary' => 1,
                        ]
                    
                    ]
                ]
            ], false);

            if (is_wp_error($candidate) || !isset($candidate['Person']['emailPrimary'])) {
                throw new Exception(__('Candidate not found.', 'otys-jobs-apply'));
            }
        } catch (Exception $e) {
            throw new $e;
        }

        return $candidate;
    }

    /**
     * New candidate password email
     *
     * @since 2.0.45
     * @return void
     */
    public function sendNewCandidatePasswordMail(string $candidateUid, array $args = [], array $docArgs = []): void
    {
        $documentUid = intval(get_option('otys_option_document_template_forgot_password', 0));

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

        MailModel::send($args, 0, false);
    }
}