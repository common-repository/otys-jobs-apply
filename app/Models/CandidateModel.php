<?php

namespace Otys\OtysPlugin\Models;

use Otys\OtysPlugin\Helpers\SettingHelper;
use WP_Error;
use Otys\OtysPlugin\Includes\OtysApi;

class CandidateModel extends BaseModel
{
    /**
     * Create webuser
     *
     * @param string    $candidateUID Candidate UID
     * @param string    $email Will be used as login name
     * @param string    Password needs to be string and more than 9 characters long otherwise a
     *                  auto password will be generated. Password will be included in the response
     * @return WP_Error | string Returns password of the newly created webuser
     * @since 1.0.0
     */
    public static function createWebuser(string $candidateUID, string $email)
    {
        // Sanatize email if email is given
        $email = $email !== '' ? sanitize_email($email) : $email;
        $password = static::createPassword();

        $webuser = OtysApi::post([
            'method' => 'Otys.Services.OCDService.createWebuserAndBind',
            'params' => [
                [
                    'role' => 'candidate',
                    'uid' => $candidateUID,
                    'username' => $email,
                    'allowLogin' => true
                ]
            ]
        ]);

        if (!is_wp_error($webuser)) {
            OtysApi::post([
                'method' => 'Otys.Services.WebusersService.setNewPassword',
                'params' => [
                    $webuser,
                    $password
                ]
            ]);
        }

        return $password;
    }

    /**
     * Generate random password
     *
     * @return string
     */
    public static function createPassword(): string
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = []; //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return (string) implode($pass); //turn the array into a string
    }

    /**
     * Create candidate
     *
     * @param array $owsFields
     * @return string | WP_Error
     */
    public static function createCandidate(array $owsFields)
    {
        // Build OWS based candidate data
        $candidateData = QuestionsetModel::buildCandidateData($owsFields);

        $language = OtysApi::getLanguage();

        // OWS for the add function a type supplied
        if(isset($candidateData['Person']['PhoneNumbers'])) {
            foreach ($candidateData['Person']['PhoneNumbers'] as $cpKey => $cpValue) {
                switch ($cpKey) {
                    case "1":
                        $candidateData['Person']['PhoneNumbers'][$cpKey]['type'] = 'primary';
                        break;

                    case "2":
                        $candidateData['Person']['PhoneNumbers'][$cpKey]['type'] = 'mobile';
                        break;

                    case "3":
                        $candidateData['Person']['PhoneNumbers'][$cpKey]['type'] = 'work';
                        break;

                    case "4":
                        $candidateData['Person']['PhoneNumbers'][$cpKey]['type'] = 'other';
                        break;

                    case "5":
                        $candidateData['Person']['PhoneNumbers'][$cpKey]['type'] = 'fax';
                        break;
                }
            }
        }

        // Build default OWS data for creating the user
        $owsData = [
            'pointOfEntry' => 'web',
            'varCopy' => null,
            'vcaCopy' => null,
            'currencyId' => 1,
            'statusId' => -1,
            'originSiteId' => SettingHelper::getSiteId(),
            'source' => 'website',
            'Person' => [
                'languageCode' => $language
            ]
        ];

        // Add utm tags
        $utmTags = static::getUTM();

        if (!is_wp_error($utmTags)) {
            $owsData['UtmTags'] = $utmTags;
        }

        // Referrer portal
        $referrer = ProceduresModel::getRefererPortal();

        if (!is_wp_error($referrer)) {
            $owsData['portalId'] = $referrer;
        }

        // Add some extra data if terms has been accepted
        if (isset($candidateData) && isset($candidateData['acceptTerms']) && $candidateData['acceptTerms'] == "true") {
            $owsData['acceptTermsTs'] = date('Y/m/d h:i:s', time());
            $owsData['acceptTermsIp'] = is_string($_SERVER['REMOTE_ADDR']) ? preg_replace("/[^.:()a-zA-Z0-9\/]/", "", $_SERVER['REMOTE_ADDR']) : '';
        }

        // Add user input data to ows data
        $owsData = array_replace_recursive($owsData, $candidateData);

        $result = OtysApi::post([
            'method' => 'Otys.Services.CandidateService.add',
            'params' => [
                $owsData
            ]
        ]);

        // If a error occured let's try to dd the candidate without statusId
        if (is_wp_error($result)) {
            unset($owsData['statusId']);
            $result = OtysApi::post([
                'method' => 'Otys.Services.CandidateService.add',
                'params' => [
                    $owsData
                ]
            ]);
        }

        if (is_wp_error($result) || !is_string ($result)) {
            return new WP_Error('create_candidate', __('Could not create candidate.', 'otys-jobs-apply'));
        }

        return $result;
    }

    /**
     * Update candidate
     *
     * @param string $candidateUid
     * @param array $owsFields
     * @return string | WP_Error
     */
    public static function updateCandidate(string $candidateUid, array $owsFields)
    {
        // Build OWS based candidate data
        $candidateData = QuestionsetModel::buildCandidateData($owsFields);

        unset($candidateData['userId']);
        unset($candidateData['Person']['emailPrimary']);

        // Add some extra data if terms has been accepted
        if (isset($candidateData) && isset($candidateData['acceptTerms']) && $candidateData['acceptTerms'] == "true") {
            $candidateData['acceptTermsTs'] = date('Y/m/d h:i:s', time());
            $candidateData['acceptTermsIp'] = is_string($_SERVER['REMOTE_ADDR']) ? preg_replace("/[^.:()a-zA-Z0-9\/]/", "", $_SERVER['REMOTE_ADDR']) : '';
        }

        // OWS needs the DELETE property defined when updating a candidates phonenumber
        if(isset($candidateData['Person']['PhoneNumbers'])) {
            $candidateData['Person']['PhoneNumbers']['DELETE'] = [];
        }

        $result = OtysApi::post([
            'method' => 'Otys.Services.CandidateService.updateEx',
            'params' => [
                $candidateUid,
                $candidateData
            ]
        ]);

        if (is_wp_error($result) || !is_string ($result)) {
            return new WP_Error('create_candidate', __('Could not create candidate.', 'otys-jobs-apply'));
        }

        return $result;
    }
}