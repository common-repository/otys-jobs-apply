<?php

namespace Otys\OtysPlugin\Models;
use Otys\OtysPlugin\Helpers\SettingHelper;
use Otys\OtysPlugin\Includes\OtysApi;
use Otys\OtysPlugin\Models\BaseModel;

use WP_Error;

class MailModel extends BaseModel
{
    /**
     * Send email
     *
     * @param array $args
     * @param boolean $useEmptyMail
     * @param int $userId
     * @return \WP_Error | array
     */
    public static function send($args = [], int $userId = 0, bool $log = true)
    {
        $forceMailsFromApi = (int) get_option('otys_option_system_mails_from_owner', 0);
        $sendingProfileUid = null;

        /**
         * If force mails from API 
         */
        if ($forceMailsFromApi) {
            // Reset user ID to 0
            $userId = 0;

            // Remove from key
            unset($args['From']);
        }

        // Check if html message is given otherwise throw error
        if (!isset($args['htmlMessage'])) {
            return new WP_Error('mail', __('No message set for email.', 'otys-jobs-apply'));
        }

        /**
         * Set sender
         */
        $setSendAs = static::setSendAs($userId);

        // If set send as failed, try to set send as back to the api user
        if (is_wp_error($setSendAs)) {
            // Reset set send as
            $setSendAs = static::setSendAs(0);

            if (is_wp_error($setSendAs)) {
                return $setSendAs;
            }
        }

        // Get the sending profile
        $sendingProfile = static::getSendingProfile();

        $sendingProfileUid = isset($sendingProfile['uid']) ? $sendingProfile['uid'] : $sendingProfileUid;

        // Set email address to email in sending profile
        if (
            $sendingProfileUid !== null && 
            isset($args['From']) &&
            isset($args['From']['email'])
        ) {
            $args['From']['email'] = $sendingProfile['email'];

            $args['profileUid'] = $sendingProfile['uid'];
        }

        $emptyMail = static::getUserSignature($userId, $sendingProfileUid);

        if (!is_wp_error($emptyMail)) {
            $args['htmlMessage'] = str_replace('<!--BODY-->', $args['htmlMessage'], $emptyMail['htmlMessage']);
        }

        $mail = OtysApi::post([
            'method' => 'Otys.Services.EmailService.send',
            'params' => [$args]
        ], false, true, [], $log);

        if (is_wp_error($mail)) {
            return $mail;
        }

        // Reset set send as
        static::setSendAs(0);

        return $mail;
    }

    /**
     * Set send as
     *
     * @param int $userId
     * @return bool|WP_error
     */
    public static function setSendAs(int $userId) {
        $request = OtysApi::post([
            'method' => 'Otys.Services.EmailService.setSendAs',
            'params' => [
                $userId
            ]  
        ]);

        if (
            is_wp_error($request) ||
            (is_array($request) && isset($request['status']) && $request['status'] == 'error')
        ) {
            return new WP_Error('send_as', 'Failed to set send as', $request);
        }

        return true;
    }

    /**
     * Get user sending profile
     *
     * @return array    Returns sending profile or empty array
     */
    public static function getSendingProfile(): array
    {
        $siteID = SettingHelper::getSiteId();

        $profiles = OtysApi::post([
            'method' => 'Otys.Services.SendingProfileService.getList',
            'params' => [
                'what' => [
                    'uid' => 1,
                    'name' => 1,
                    'email' => 1,
                    'siteId' => 1,
                    'signatureId' => 1
                ]
            ]
        ]);

        // Return empty array when error happens
        if (is_wp_error($profiles)) {
            return [];
        }

        if (isset($profiles['status']) && $profiles['status'] == 'error') {
            return [];
        }

        $selectedProfile = [];

        if (
            !is_wp_error($profiles) && 
            is_array($profiles) &&
            !empty($profiles)
        ) {
            foreach ($profiles as $profile) {
                if (!isset($profile['siteId'])) {
                    continue;
                }

                if (
                    $profile['siteId'] === $siteID &&
                    (
                        empty($selectedProfile) ||
                        (isset($selectedProfile['isStandardProfile']) && $selectedProfile['isStandardProfile'] === false)
                    )
                ) {
                    $selectedProfile = $profile;
                }
            }
        }

        return $selectedProfile;
    }

    /**
     * Get empty email including user email signature
     *
     * @param   int   $userId               User id to send the mail from
     * @param   int|null   $sendingProfileUid    UID from sending profile to use
     * @return  \WP_error | array
     */
    public static function getUserSignature(int $userId = 0, $sendingProfileUid = null)
    {
        if ($sendingProfileUid === null) {
            if ($userId === 0) {
                return OtysApi::post([
                    'method' => 'Otys.Services.EmailService.getEmptyMessage',
                    'params' => []
                ]);
            }

            $clientId = OtysApi::getClientId();
            
            return OtysApi::post([
                'method' => 'Otys.Services.EmailService.getEmptyMessageForUser',
                'params' => [
                    $clientId,
                    $userId
                ]
            ]);
        }

        $emptyMessage = OtysApi::post([
            'method' => 'Otys.Services.EmailService.getEmptyMessage',
            'params' => [
                $sendingProfileUid,
                true
            ]
        ]);

        return $emptyMessage;
    }
}