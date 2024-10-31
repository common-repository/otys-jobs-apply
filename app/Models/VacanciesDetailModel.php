<?php

namespace Otys\OtysPlugin\Models;

use Otys\OtysPlugin\Helpers\SettingHelper;
use Otys\OtysPlugin\Includes\Core\Routes;
use Otys\OtysPlugin\Includes\OtysApi;
use WP_Error;

class VacanciesDetailModel extends BaseModel
{
    /**
     * Get a vacancy by uid
     *
     * @param $identifier
     * @return array|WP_Error
     */
    public static function get(string $identifier, $owsArgs = [], $getDetailBySlug = false)
    {
        if ($identifier === '') {
            return [];
        }

        // Get website id
        $website = SettingHelper::getSiteId();

        // Checkl if custom slugs is active
        $customSlugIsActive = Routes::customSlugIsActive();

        // Determine method to use
        $method = $getDetailBySlug ? 'Otys.Services.VacancyService.getDetailBySlug' : 'Otys.Services.VacancyService.getDetail';

        $uploadUrl = OtysApi::getImageUploadUrl();

        // Default get arguments
        $default = [
            'uid' => 1,
            'textFieldTitles' => 1,
            'internalId' => 1,
            'entryDateTime' => 1,
            'stopDate' => 1,
            'lastModified' => 1,
            'createdByUser' => 1,
            'modifiedByUser' => 1,
            'title' => 1,
            'copyTitle' => 1,
            'referenceNr' => 1,
            'extraTitle' => 1,
            'indeedMetaData' => 1,
            'salaryCurrency' => 1,
            'salaryValue' => 1,
            'salaryUnit' => 1,
            'salaryMin' => 1,
            'salaryMax' => 1,
            'searchFunnel' => 1,
            'passive' => 1,
            'planningFilled' => 1,
            'externalReferenceNr' => 1,
            'categoryId' => 1,
            'category' => 1,
            'alternativeCategories' => 1,
            'jobType' => 1,
            'user' => 1,
            'userId' => 1,
            'userId2' => 1,
            'userId3' => 1,
            'defaultConsultant' => 1,
            'userEmail' => 1,
            'listAllEmails' => 1,
            'assignmentDuration' => 1,
            'assignmentTariff' => 1,
            'location' => 1,
            'locationAddress' => 1,
            'applyUrls' => 1,
            'customApplyUrl' => 1,
            'locationPostcode' => 1,
            'locationCity' => 1,
            'locationState' => 1,
            'locationCountryCode' => 1,
            'locationLatitude' => 1,
            'locationLongitude' => 1,
            'relation' => 1,
            'relationId' => 1,
            'relationContact' => 1,
            'relationContact2' => 1,
            'relationContactId' => 1,
            'relationContactId2' => 1,
            'viewCount' => 1,
            'applyCount' => 1,
            'emailCount' => 1,
            'candidatesNeeded' => 1,
            'candidatesPlaced' => 1,
            'remarks' => 1,
            'statusId' => 1,
            'published' => 1,
            'otysFrontOfficePreviewUrl' => 1,
            'questionSetId' => 1,
            'relationLogoFileName' => 1,
            'photoFileName' => 1,
            'photoThumbFileName' => 1,
            'PhotoGallery' => [
                'photoId' => 1,
                'filename' => 1,
                'thumbFilename' => 1,
            ],
            'Video' => [
                'videoEmbed' => 1,
                'videoPosition' => 1,
            ],
            'ConfigurableOrganisationUnits' => [
                'unitId' => 1,
                'unitItemId' => 1,
            ],
            'textField_description' => 1,
            'textField_companyProfile' => 1,
            'textField_requirements' => 1,
            'textField_salary' => 1,
            'textField_companyCulture' => 1,
            'textField_summary' => 1,
            'textField_extra1' => 1,
            'textField_extra2' => 1,
            'textField_extra3' => 1,
            'textField_extra4' => 1,
            'textField_extra5' => 1,
            'textField_extra6' => 1,
            'textField_extra7' => 1,
            'textField_extra8' => 1,
            'textField_extra9' => 1,
            'textField_extra10' => 1,
            'assignmentUseEndTime' => 1,
            'assignmentEndTime' => 1,
            'assignmentUseStartTime' => 1,
            'assignmentStartTime' => 1,
            'endDateUnknown' => 1,
            'assignmentEndDate' => 1,
            'assignmentStartDate' => 1,
            'contractSteady' => 1,
            'orgunitCode' => 1,
            'locationCode' => 1,
            'costCentre' => 1,
            'declPeriod' => 1,
            'declPublicTransport' => 1,
            'onlineTimesheets' => 1,
            'hoursType' => 1,
            'hoursUnit' => 1,
            'hours' => 1,
            'housTotal' => 1,
            'tariffUnit' => 1,
            'tariff' => 1,
            'currencyId' => 1,
            'assignmentType' => 1,
            'responsibleCustomerOfficeContactPersonName' => 1,
            'responsibleCustomerOfficeName' => 1,
            'responsibleWorkplaceName' => 1,
            'responsibleWorkplaceContactPersonName' => 1,
            'responsibleContact' => 1,
            'workPlaceAddress' => 1,
            'workPlacePostalCode' => 1,
            'workPlaceCity' => 1,
            'workPlacePhone' => 1,
            'workPlaceFax' => 1,
            'workPlaceDescription' => 1,
            'applicationEmailRecipientRelationContact' => 1,
            'applicationEmailRecipientVacancyOwner' => 1,
            'applicationEmailRecipientVacancyOwnerUsePrintTemplate' => 1,
            'applicationEmailRecipientVacancyOwnerUsePlainTemplate' => 1,
            'applicationEmailRecipientCustom' => 1,
            'applicationEmailSenderCandidate' => 1,
            'applicationEmailPrintTemplateId' => 1,
            'applicationEmailAttachOriginalCv' => 1,
            'applicationEmailUploadedDocs' => 1,
            'applicationEmailNotificationToOrganisationUnit' => 1,
            'applicationEmailRecipientCustomEmail' => 1,
            'vacancyVersion' => 1,
            'VbFtpHtmlSettings' => [
                'ftpFolder' => 1,
                'htmlFile' => 1,
                'active' => 1,
            ],
            'showEmployer' => 1,
            'publishedInShortlist' => 1,
            'blockScanners' => 1,
            'removeApplyButton' => 1,
            'supplierPortal' => 1,
            'publicationStatusId' => 1,
            'publicationStatus' => 1,
            'publicationStartDate' => 1,
            'publicationEndDate' => 1,
            'publicationFirstDate' => 1,
            'publishedWebsites' => 1,
            'PublishedWebsitesEmails' => [
                'siteId' => 1,
                'email' => 1,
            ],
            'publishedInShortlistWebsites' => 1,
            'showLogo' => 1,
            'publishOnJobCent' => 1,
            'publishLanguages' => 1,
            'isMultiSite' => 1,
            'defaultWebsiteId' => 1,
            'searchableInActonomy' => 1,
            'premiumPublish' => 1,
            'VbRefreshPublications' => [
                'refreshAfterDays' => 1,
                'emailAfterRenewal' => 1,
            ],
            'premiumPostMultiWebsites' => 1,
            'jobBanner' => 1,
            'jobBannerWebsites' => 1,
            'invoiceCustomerId' => 1,
            'invoiceCustomer' => 1,
            'invoiceCustomerContactId' => 1,
            'extraCategory' => 1,
            'extraLabel' => 1,
            'jobSense' => 1,
            'lmViewers' => 1,
            'jobSpecificEmailSender' => 1,
            'projectsId' => 1,
            'canonicalUrl' => 1,
            'advancedXmlFeeds' => 1,
            'metaTitle' => 1,
            'foReferral' => 1,
            'matchCriteriaNames' => 1,
            'matchCriteria_1' => 1,
            'matchCriteria_2' => 1,
            'matchCriteria_3' => 1,
            'matchCriteria_4' => 1,
            'matchCriteria_5' => 1,
            'matchCriteria_6' => 1,
            'matchCriteria_7' => 1,
            'matchCriteria_8' => 1,
            'matchCriteria_9' => 1,
            'matchCriteria_10' => 1,
            'matchCriteria_11' => 1,
            'matchCriteria_12' => 1,
            'matchCriteria_13' => 1,
            'matchCriteria_14' => 1,
            'matchCriteria_15' => 1,
            'matchCriteria_16' => 1,
            'matchCriteria_17' => 1,
            'matchCriteria_18' => 1,
            'slug' => 1
        ];

        // Get extra fields
        $extraFields = static::getExtraFields();

        // Extra textfields to query
        $queryExtraFields = [];

        if (!is_wp_error($extraFields)) {
            foreach ($extraFields as $extraTextField) {
                $queryExtraFields['extraInfo' . $extraTextField['fieldnum']] = 1;
            }

            // Add extra textfields to default query
            $default = array_merge($default, $queryExtraFields);
        }

        // Merge arguments with default arguments
        $args = array_replace_recursive($default, $owsArgs);

        // Create OWS Request
        $vacancy = OtysApi::post([
            'method' => $method,
            'params' => [
                $identifier,
                $args
            ]
        ], true);

        // If request returns errror return the error
        if (is_wp_error($vacancy)) {
            return $vacancy;
        }

        if ($vacancy) {
            /**
             * Add textfields
             */
            $textFields = static::getTextFields();

            $vacancy['textFields'] = [];

            $textFieldMetaId = (int) get_option('otys_option_meta_description_textfield', 0);

            if (!is_wp_error($textFields) && !empty($textFields) && is_array($textFields)) {
                // Add textfields to result
                foreach ($textFields as $textField) {
                    if ($textField['published']) {
                        $title = $vacancy[$textField['owsName']]['title'];

                        $title = str_replace('[functie_o]', $vacancy['title'], $title);

                        $vacancy['textFields'][$textField['owsName']] = [
                            'uid' => $textField['uid'],
                            'title' => $title,
                            'text' => $vacancy[$textField['owsName']]['text']
                        ];
                    }

                    if ($textField['uid'] == $textFieldMetaId) {
                        $metaDescription = str_replace(["\\","\\r"], '', strip_tags($vacancy[$textField['owsName']]['text']));
                        
                        if (strlen($metaDescription) > 160) {
                            $metaDescription = substr($metaDescription, 0, 160);
                            $metaDescription = substr($metaDescription, 0, strrpos($metaDescription, ' ')) . '...';
                        }
                        
                        $vacancy['metaDescription'] = $metaDescription;
                    }
                }
            }

            $vacancy['metaDescription'] ??= '';

            // Add extra textfields to result
            $extraTextFieldsResult = [];

            if (!is_wp_error($extraFields)) {
                foreach ($extraFields as $extraTextField) {
                    $extraTextFieldsResult['extraInfo' . $extraTextField['fieldnum']] = $extraTextField;
                    $extraTextFieldsResult['extraInfo' . $extraTextField['fieldnum']]['value'] = $vacancy['extraInfo' . $extraTextField['fieldnum']];
                }
            }

            $vacancy['extraTextFields'] = $extraTextFieldsResult;

            /**
             * Add labels
             */
            $vacancyLabels = static::getLabelsToShow();

            /**
             * Ordering Criteria labels
             */
            if (is_array($vacancyLabels) && !empty($vacancyLabels) && isset($vacancy['matchCriteriaNames'])) {
                // Remove matchcriteria names that should not be shown
                $vacancy['matchCriteriaNames'] = array_filter($vacancy['matchCriteriaNames'], function ($key) use ($vacancyLabels) {
                    $keyNamed = str_replace('matchCriteria_', 'C_', $key);
                    
                    return (in_array($keyNamed, $vacancyLabels));
                }, ARRAY_FILTER_USE_KEY);

                // Sort criteria names based on the criteria to show
                uksort($vacancy['matchCriteriaNames'], function($key1, $key2) use ($vacancyLabels) {
                    $key1Named = str_replace('matchCriteria_', 'C_', $key1);
                    $key2Named = str_replace('matchCriteria_', 'C_', $key2);
                    
                    return ((array_search($key1Named, $vacancyLabels) > array_search($key2Named, $vacancyLabels)) ? 1 : -1);
                });
            }

            // Get vacancy labels to show
            $vacancy['labels'] = [];
            if (is_array($vacancyLabels) && !empty($vacancyLabels)) {
                foreach ($vacancyLabels as $vacancyLabel) {
                    // Add criteria
                    if (strpos($vacancyLabel, 'C_') !== false) {
                        $vacancyNameKey = str_replace('C_', 'matchCriteria_', $vacancyLabel);
                        $criteriaName = array_key_exists($vacancyNameKey, $vacancy['matchCriteriaNames']) ? $vacancy['matchCriteriaNames'][$vacancyNameKey] : $vacancyLabel;

                        $vacancy['labels'][$vacancyLabel] = [
                            'name' => $criteriaName,
                            'values' => $vacancy[$vacancyNameKey]
                        ];
                    }

                    // Add categorie
                    if ($vacancyLabel === 'category' && isset($vacancy['category']) && $vacancy['category'] !== NULL) {
                        $vacancy['labels'][$vacancyLabel] = [
                            'name' => __('Category', 'otys-jobs-apply'),
                            'values' => [$vacancy['category']]
                        ];
                    }
                }
            }

            /**
             * Add consultant info
             */
            $vacancy['consultantInfo'] = null;

            if (isset($vacancy['userId']) && is_int($vacancy['userId']) && $vacancy['userId'] !== 0) {
                $userInfo = ConsultantModel::get($vacancy['userId']);
                
                if (!is_wp_error($userInfo)) {
                    $vacancy['consultantInfo'] = $userInfo;
                }
            }
            
            /**
             * Add vacancy url
             */
            $vacancy['applyUrl'] = $vacancy['customApplyUrl'] ?? Routes::get('vacancy-apply', [
                'slug' => $customSlugIsActive ? $vacancy['slug'][$website] : sanitize_title($vacancy['title']) . '-' . $vacancy['uid']
            ]);

            /**
             * Add vacancy fallback image
             */
            $fallbackIamgeId = get_option('otys_option_vacancies_header_falllback', '');
            $vacancy['vacancyFallbackImage'] = ($fallbackIamgeId !== '') ?  wp_get_attachment_url($fallbackIamgeId) : esc_url(OTYS_PLUGIN_ASSETS_URL) . '/images/vacancies/vacancy-header.jpg';

            /**
             * Add image url's
             */

            // Set gallery url's
            if (is_array($vacancy['PhotoGallery'])) {
                foreach ($vacancy['PhotoGallery'] as $galleryKey => $galleryItem) {
                    $vacancy['PhotoGallery'][$galleryKey]['photoUrl'] = $uploadUrl. $galleryItem['photo'];
                    $vacancy['PhotoGallery'][$galleryKey]['thumbnailUrl'] = $uploadUrl. $galleryItem['thumbnail'];
                }
            }

            // Check if there is a relation logo is present and add a new variable including the entire url
            $vacancy['relationLogoUrl'] = (isset($vacancy['relationLogoFileName']) && $vacancy['relationLogoFileName']) ? 
            $uploadUrl . $vacancy['relationLogoFileName'] : null;
            
            // Check if there is a photo present and add a new variable including the entire url
            $vacancy['photoUrl'] = (isset($vacancy['photoFileName']) && $vacancy['photoFileName']) ? 
            $uploadUrl . $vacancy['photoFileName'] : null;
            
            // Check if there is a photo thumbnail present and add a new variable including the entire url
            $vacancy['photoThumbUrl'] = (isset($vacancy['photoThumbFileName']) && $vacancy['photoThumbFileName']) ? 
            $uploadUrl . $vacancy['photoThumbFileName'] : null;
        }

        return $vacancy;
    }

    /**
     * Get labels which should be shown
     *
     * @return array
     */
    private static function getLabelsToShow(): array
    {
        $settingName = 'otys_option_vacancies_detail_match_criteria_labels';

        if (($matchCriteriaToShow = get_option($settingName, false)) === false || !is_array($matchCriteriaToShow)) {
            return [];
        }

        $filteredValues = array_filter($matchCriteriaToShow, function ($val) {
            return $val === 'true';
        });

        return is_array($filteredValues) ? array_keys($filteredValues) : [];
    }

    /**
     * Get extra fields for vacancies
     *
     * @return array | \WP_error
     */
    public static function getExtraFields()
    {
        // Get current WordPress language
        $language = OtysApi::getLanguage();

        $extraFields = OtysApi::post([
            'method' => 'Otys.Services.VacancyService.getExtraFieldsList',
            'params' => [$language]
        ], true);

        return $extraFields;
    }

    /**
     * getFields returns a list of the textfields in OTYS and wheter the textFields are published or not
     * Textfields are based on setting GE127
     *
     * @return array | \WP_Error
     * @since 1.0.0
     */
    public static function getTextFields()
    {
        $textFields = OtysApi::post([
            'method' => 'Otys.Services.VacancyTextFieldService.getList',
            'params' => []
        ], true);

        if (is_wp_error($textFields) || !is_array($textFields)) {
            return $textFields;
        }

        // Assign the OWS Fieldname to every textField
        foreach ($textFields as $keyTexTfield => $textField) {
            $textFields[$keyTexTfield]['owsName'] = static::getTextFieldsOwsName($textField['field']);
        }

        return $textFields;
    }

    /**
     * Get all textfields as options
     *
     * @return array
     */
    public static function getTextFieldOptions(): array
    {
        $textfields = static::getTextFields();

        $options = [
            '0' => __('Disabled', 'otys-jobs-apply')
        ];

        foreach ($textfields as $textfield) {
            $options[$textfield['uid']] = $textfield['name'];
        }

        return $options;
    }

    /**
     * getTextFieldsOwsName
     *
     * @param  mixed $fieldName
     * @return string
     * @since 1.0.0
     */
    public static function getTextFieldsOwsName(string $fieldName): string
    {
        $fieldNames = [
            'bedrprofiel' => 'textField_companyProfile',
            'functieo' => 'textField_description',
            'functiee' => 'textField_requirements',
            'sal_o' => 'textField_salary',
            'chapo' => 'textField_summary',
            'bedrcultuur' => 'textField_companyCulture',
            'extra_fld_01' => 'textField_extra1',
            'extra_fld_02' => 'textField_extra2',
            'extra_fld_03' => 'textField_extra3',
            'extra_fld_04' => 'textField_extra4',
            'extra_fld_05' => 'textField_extra5',
            'extra_fld_06' => 'textField_extra6',
            'extra_fld_07' => 'textField_extra7',
            'extra_fld_08' => 'textField_extra8',
            'extra_fld_09' => 'textField_extra9',
            'extra_fld_10' => 'textField_extra10'
        ];

        return array_key_exists($fieldName, $fieldNames) ? $fieldNames[$fieldName] : '';
    }
}