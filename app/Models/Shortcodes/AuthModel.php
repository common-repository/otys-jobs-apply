<?php

namespace Otys\OtysPlugin\Models\Shortcodes;

use Exception;
use Otys\OtysPlugin\Entity\AuthUser;
use Otys\OtysPlugin\Includes\Core\Recaptcha;
use Otys\OtysPlugin\Includes\OtysApi;
use Otys\OtysPlugin\Models\Shortcodes\ShortcodeBaseModel;

/**
 * This model is used by the otys-login shortcode
 */
class AuthModel extends ShortcodeBaseModel
{
    /**
     * Login OCD user
     *
     * @param string $username
     * @param string $password
     * @return AuthUser
     */
    public function login(string $username, string $password)
    {
        try {
            $candidate = OtysApi::post([
                'method' => 'Otys.Services.OCDService.loginUser',
                'params' => [
                    $username,
                    $password,
                    'candidate'
                ]
            ], false);

            if (is_wp_error($candidate)) {
                throw new Exception(__('Something went wrong while trying to login.', 'otys-jobs-apply'), 500);
            }

            if (!isset($candidate['candidate_ouid']) || $candidate['candidate_ouid'] === false) {
                throw new Exception(__('User and password combination is wrong.', 'otys-jobs-apply'), 400);
            }

            if (!$candidate['allowLogin']) {
                $this->logout();

                throw new Exception(__('User and password combination is wrong.', 'otys-jobs-apply'), 400);
            }

            // Get candidate data based on the candidate_ouid
            $candidateData = OtysApi::post([
                'method' => 'Otys.Services.CandidateService.getDetail',
                'params' => [
                    $candidate['candidate_ouid'],
                    [
                        'uid' => 1,
                        'internalId' => 1,
                        'Person' => [
                            'firstName' => 1,
                            'lastName' => 1
                        ]
                    ]
                ]
            ], false);

            $authUser = new AuthUser();

            $authUser->setFirstname($candidate['firstname'] ?? null);
            $authUser->setMiddleName($candidate['middleName'] ?? null);
            $authUser->setLastname($candidate['lastname'] ?? null);
            $authUser->setEmail($candidate['email'] ?? null);
            $authUser->setGender($candidate['gender'] ?? null);
            $authUser->setWebuserUid($candidate['webuser_ouid']);
            $authUser->setCandidateUid($candidate['candidate_ouid']);
            $authUser->setSesion($candidate['session_id']);
            $authUser->setCandidateId($candidateData['internalId'] ?? 0);

            $this->setUserSession($authUser);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }

        return $authUser;
    }

    /**
     * Validate shortcode attributes
     *
     * @param $params           Params to be validated
     * @param $filtersAsArray   Wheter to return the filter options as array
     * @return array
     * @since 1.0.0
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
     * @since 2.0.0
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
            },
            'password' => function ($value) {
                if (!is_string($value)) {
                    return false;
                }

                return [
                    'value' => $value,
                    'errors' => []
                ];
            },
            'redirect' => function ($value) {
                $value = filter_var($value, FILTER_SANITIZE_URL);

                if (!$value) {
                    return false;
                }

                $validated = [
                    'value' => $value,
                    'errors' => []
                ];

                return $validated;
            }
        ];

        $postData = static::doValidation($postData, $questionsetValidation);

        return $postData;
    }

    /**
     * Create user session
     *
     * @param AuthUser $user
     *
     * @return void
     */
    protected function setUserSession($user)
    {
        $_SESSION['otys_user'] = $user;
    }

    /**
     * Logout user
     *
     * @return void
     */
    public static function logout(): void
    {
        unset($_SESSION['otys_user']);
    }

    /**
     * Get logged in user
     *
     * @return AuthUser|bool
     */
    public static function getUser()
    {
        return $_SESSION['otys_user'] ?? false;
    }

    /**
     * Generate login token for user
     *
     * @param AuthUser $user
     * @return string
     */
    public function getUserLoginLink($user): string
    {
        try {
            $loginLink = OtysApi::post([
                'method' => 'Otys.Services.CandidateService.generateFoLoginToken',
                'params' => [
                    $user->getCandidateUid(),
                    1
                ]
            ], false);

            if (is_wp_error($loginLink)) {
                throw new Exception(__('Unable to generate the login link', 'otys-jobs-apply'), 500);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }

        return $loginLink;
    }
}