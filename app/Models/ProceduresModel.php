<?php

namespace Otys\OtysPlugin\Models;

use Otys\OtysPlugin\Models\BaseModel;
use Otys\OtysPlugin\Includes\OtysApi;
use WP_Error;

class ProceduresModel extends BaseModel
{
    /**
     * Get procedure status list of 1,2 & 3
     *
     * @param integer $statusType
     * @return array | WP_Error
     */
    public static function getList()
    {
        $procedures = OtysApi::post([
            'method' => 'Otys.Services.MultiService.execute',
            'params' => [
                [
                    [
                        'method' => 'Otys.Services.ProcedureStatus1Service.getList',
                        'args' => [
                            [
                                'limit' => 500,
                                'condition' => null,
                                'excludeLimitCheck' => true,
                                'what' => [
                                    'uid' => true,
                                    'code' => true,
                                    'automaticallySelectEvent' => true
                                ]
                            ]
                        ]
                    ],
                    [
                        'method' => 'Otys.Services.ProcedureStatus2Service.getList',
                        'args' => [
                            [
                                'limit' => 500,
                                'condition' => null,
                                'excludeLimitCheck' => true,
                                'what' => [
                                    'uid' => true,
                                    'code' => true,
                                    'automaticallySelectEvent' => true
                                ]
                            ]
                        ]
                    ],
                    [
                        'method' => 'Otys.Services.ProcedureStatus3Service.getList',
                        'args' => [
                            [
                                'limit' => 500,
                                'condition' => null,
                                'excludeLimitCheck' => true,
                                'what' => [
                                    'uid' => true,
                                    'code' => true,
                                    'automaticallySelectEvent' => true
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ], true);

        return $procedures;
    }

    /**
     * Get Status auto select
     * Gets which status to autoselect. There are 4 options which autoselect
     *
     * "1": "Joined via website",
     * "2": "Joined via match",
     * "3": "Joined via vendor management portal",
     * "4": "Use for introduce batch action"
     * 
     * @param integer $autoSelectId Can be status 1, 2, 3 or 4
     * @return array | WP_Error
     */
    public static function getStatusAutoSelect(int $autoSelectId)
    {
        $procedureLists = static::getList();

        // If failed to retrieve list return error
        if (is_wp_error($procedureLists)) {
            return $procedureLists;
        }

        $return = [];

        // Loop through procedureStatus 1,2,3
        foreach ($procedureLists as $procedureListKey => $procedureList) {
            // Assign procedure service number which can be 1,2 or 3. Need to add 1 because array starts with 0
            $procedureServiceNumber = $procedureListKey + 1;

            /** 
             * Loop through the procedure options and check if the auto select event equals the autoselet id
             * and add the current option to the resulting array and continue with the next procedurestatus since
             * only one option can be marked as auto select each procedurestatus
             */
            foreach ($procedureList["data"] as $procedureOption) {
                if ($procedureOption["automaticallySelectEvent"] == $autoSelectId) {
                    $return[$procedureServiceNumber] = $procedureOption;

                    continue;
                }
            }
        }

        return $return;
    }

    /**
     * Check if procedure exists
     *
     * @param string $candidateUid
     * @param string $vacancyUid
     * @return bool
     */
    public static function procedureExists(string $candidateUid, string $vacancyUid): bool
    {
        $procedures = OtysApi::post([
            'method' => 'Otys.Services.ProcedureService.getListEx',
            'params' => [
                [
                    'what' => [
                        'uid' => 1
                    ],
                    'limit' => 1,
                    'offset' => 0,
                    'getTotalCount' => true,
                    'sort' => [
                        'entryDateTime' => 'DESC'
                    ],
                    'excludeLimitCheck' => true,
                    'condition' => [
                        'type' => 'AND',
                        'items' => [
                            [
                                'type' => 'COND',
                                'field' => 'candidateUid',
                                'op' => 'EQ',
                                'param' => $candidateUid
                            ],
                            [
                                'type' => 'COND',
                                'field' => 'vacancyUid',
                                'op' => 'EQ',
                                'param' => $vacancyUid
                            ]
                        ]
                    ]
                ]
            ]
        ], false);

        if (is_wp_error($procedures)) {
            return false;
        }

        if (
            array_key_exists('listOutput', $procedures) &&
            !empty($procedures['listOutput'])
        ) {
            return true;
        }

        return false;
    }

    /**
     * Add procedure
     * Create a procedure linked to a candidate and vacancy.
     *
     * @param string $candidateUid
     * @param string $vacancyUid
     * @return string | \WP_Error
     * @since 1.0.0
     */
    public static function add(string $candidateUid, string $vacancyUid, array $args = [])
    {
        $defaultArgs = [
            'candidateUid' => $candidateUid,
            'vacancyUid' => $vacancyUid
        ];

        // Set procedure status
        $autoSelect = static::getStatusAutoSelect(1);

        if (!is_wp_error($autoSelect)) {
            if (isset($autoSelect[1])) {
                $defaultArgs['ProcedureStatus1']['uid'] = "1_{$autoSelect[1]['uid']}";
            }

            if (isset($autoSelect[2])) {
                $defaultArgs['ProcedureStatus2']['uid'] = "2_{$autoSelect[2]['uid']}";
            }

            if (isset($autoSelect[3])) {
                $defaultArgs['ProcedureStatus3']['uid'] = "3_{$autoSelect[3]['uid']}";
            }
        }

        // UTM Tags
        $utmTags = static::getUTM();

        if (!is_wp_error($utmTags)) {
            $defaultArgs['UtmTags'] = $utmTags;
        }

        $requestArgs = array_merge($defaultArgs, $args);

        return OtysApi::post([
            'method' => 'Otys.Services.ProcedureService.add',
            'params' => [
                $requestArgs
            ]
        ]);
    }

     /**
     * Get referer portal
     *
     * @param string $referer
     * @return string | WP_Error
     */
    public static function getRefererPortal(string $referer = '')
    {
        if ($referer === '') {
            if (isset($_SESSION['otys_referer'])) {
                $referer = $_SESSION['otys_referer'];
            } else if (isset($_COOKIE['otys_referer'])) {
                $referer = $_COOKIE['otys_referer'];
            } else {
                $referer = '';
            }
        }

        // Get referrer portals from Otys
        $referrerPortals = OtysApi::post([
            'method' => 'Otys.Services.ReferrerPortalService.getListEx',
            'params' => [
                [
                    'what' => [
                        'uid' => 1,
                        'portal' => 1,
                        'domain' => 1,
                        'utmTags' => 1,
                        'alias1' => 1,
                        'alias2' => 1,
                        'alias3' => 1,
                        'originalId' => 1,
                        'CustomUtmTags' => 1
                    ],
                    'condition' => null,
                    'limit' => 500,
                    'offset' => 0,
                    'getTotalCount' => 1
                ]
            ]
        ]);

        // If failed to retrieve list return error
        if (is_wp_error($referrerPortals) || !isset($referrerPortals['listOutput']) || empty($referrerPortals['listOutput'])) {
            return new WP_Error('reference_portal', 'Failed to retrieve referrer portal.');
        }

        // Get utm tags from the current user
        $utmTags = static::getUTM();

        // Map utm tags to mimic the structure of the referrer portal utm tags
        $utmTagsMapped = [];

        // Loop through utm tags and map them to mimic the structure of the referrer portal utm tags
        foreach ($utmTags as $utmTag => $utmTagValue) {
            $utmTagsMapped['utm_' . $utmTag] = $utmTagValue;
        }

        // Set default values for utm tags
        $utmTagsMapped = wp_parse_args($utmTagsMapped, [
            'utm_source' => '',
            'utm_medium' => '',
            'utm_campaign' => '',
            'utm_term' => '',
            'utm_content' => ''
        ]);

    

        // Loop through referrer portals and check if referer or utm matches any of the portals
        if (isset($referrerPortals['listOutput']) && !empty($referrerPortals['listOutput'])) {
            foreach ($referrerPortals['listOutput'] as $values) {
                // Check if domain or alias matches referer and set portal based on that
                if (
                    ($referer !== '') && (
                        (isset($values['domain']) && $referer === $values['domain']) ||
                        (isset($values['alias1']) && $referer === $values['alias1']) ||
                        (isset($values['alias2']) && $referer === $values['alias2']) ||
                        (isset($values['alias3']) && $referer === $values['alias3'])
                    )
                ) {
                    return $values['uid'];
                }

                // Check if utm tags are used and if they match set portal based on utm tags
                if (
                    isset($values['utmTags']) && ($values['utmTags'] == $utmTagsMapped)
                ) {
                    return $values['uid'];
                }

                // Check if custom utm tags are used and if they match set portal based on utm tags
                if ($values['CustomUtmTags']['useCustom'] === true) {
                    $customUtmTags = $values['CustomUtmTags']['utmTags'];

                    // Check if all custom utm tags match in a non specific order
                    if ($customUtmTags == $utmTagsMapped) {
                        return $values['uid'];
                    }
                }
            }
        }
        

        return new WP_Error('reference_portal', 'The referer portal does not match any of the available portals.');
    }
}
