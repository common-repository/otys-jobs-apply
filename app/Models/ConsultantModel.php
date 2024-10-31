<?php

namespace Otys\OtysPlugin\Models;

use Otys\OtysPlugin\Includes\OtysApi;
use WP_Error;

class ConsultantModel extends BaseModel
{
    /**
     * Get consultant by email
     *
     * @param string $email
     * @return array|WP_error
     */
    public static function getByEmail(string $email)
    {
        if (!is_email($email)) {
            return new WP_Error('no_email', 'Given string is not a valid email.');
        }

        $userList = OtysApi::post([
            'method' => 'Otys.Services.UserService.getList',
            'params' => [
                [
                    'what' => [
                        'Person' => [
                            'firstName' => 1,
                            'infix' => 1,
                            'lastName' => 1,
                            'emailPrimary' => 1
                        ],
                        'uid' => 1
                    ],
                    'limit' => 1,
                    'condition' => [
                        'type' => 'AND',
                        'items' => [
                            [
                                'type' => 'COND',
                                'field' => 'Person.emailPrimary',
                                'op' => 'GT',
                                'param' => ''
                            ],
                            [
                                'type' => 'COND',
                                'field' => 'blocked',
                                'op' => 'EQ',
                                'param' => '0'
                            ],
                            [
                                'type' => 'OR',
                                'items' => [
                                    [
                                        'type' => 'COND',
                                        'field' => 'Person.emailPrimary',
                                        'op' => 'LIKE',
                                        'param' => $email
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'order' => [
                        'Person.lastName' => 'ASC',
                        'Person.firstName' => 'ASC'
                    ]
                ]
            ]
        ], true);

        // Return error if response is error
        if (is_wp_error($userList)) {
            return $userList;
        }

        // Return first user if a user was found
        if (is_array($userList) && isset($userList[0]) && isset($userList[0]['Person'])) {
            return $userList[0];
        }

        return new WP_Error('not_found', 'No user was found');
    }

    /**
     * Get consultant
     *
     * @param integer $userId
     * @return array|WP_Error
     */
    public static function get(int $userId)
    {
        $user = OtysApi::post([
            'method' => 'Otys.Services.UserService.getDetail',
            'params' => [
                $userId,
                [
                    'uid' => 1,
                    'functionId' => 1,
                    'Person' => [
                        'title' => 1,
                        'firstName' => 1,
                        'infix' => 1,
                        'lastName' => 1,
                        'emailPrimary' => 1,
                        'skype' => 1,
                        'photoFileName' => 1,
                        'gender' => 1,
                        'nationality' => 1,
                        'languageCode' => 1,
                        'AddressPrimary' => 1,
                        'bankAccountName' => 1,
                        'bankAccountNumber' => 1,
                        'driversLicence' => 1,
                        'driversLicenceCopy' => 1,
                        'identityCardCopy' => 1,
                        'identityCardExpirationDate' => 1,
                        'relationshipStatusRemark' => 1,
                        'married' => 1,
                        'numberOfKids' => 1,
                        'passPortCopy' => 1,
                        'passPortExpirationDate' => 1,
                        'passPortNumber' => 1,
                        'placeOfBirth' => 1,
                        'religion' => 1,
                        'startdateFirstWorkExperience' => 1,
                        'Emails' => 1,
                        'PhoneNumbers' => 1
                    ],
                    'emailSound' => 0,
                    'chatSound' => 0,
                    'videochatSound' => 0,
                    'chatOutgoingSound' => 0
                ]
            ]
        ], true);

        if (!is_wp_error($user) && isset($user['Person']['photoFileName']) && $user['Person']['photoFileName'] !== null) {
            $user['Person']['photoFileName'] = OtysApi::getImageUploadUrl() . $user['Person']['photoFileName'];
        } else {
            $user['Person']['photoFileName'] = null;
        }

        return $user;
    }
}