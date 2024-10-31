<?php

namespace Otys\OtysPlugin\Models;

use Otys\OtysPlugin\Entity\AuthUser;
use Otys\OtysPlugin\Includes\Core\Routes;
use Otys\OtysPlugin\Includes\OtysApi;
use Otys\OtysPlugin\Includes\Core\Cache;
use Otys\OtysPlugin\Models\BaseModel;
use Otys\OtysPlugin\Models\Shortcodes\AuthModel;
use Otys\OtysPlugin\Helpers\ArrayHelper;

use WP_Error;
/**
 * This is the model that is used by the otys-vacancies-selected-filters shortcode.
 */
final class QuestionsetModel extends BaseModel 
{
    /**
     * Stores questionset
     *
     * @var array|WP_Error
     */
    private $questionset;

    /**
     * Questionset UID
     *
     * @var integer
     */
    private int $questionsetUid;

    /**
     * Vacancy UID
     *
     * @var string
     */
    private string $vacancyUid;

    /**
     * Auth user
     *
     * @var AuthUser|bool
     */
    private $authUser;

    /**
     * User data of logged in user
     *
     * @var array
     */
    private array $authUserData = [];

    /**
     * List of allowed document types
     * This list is based on the default allowed document types in OTYS
     *
     * @var array
     */
    private static $allowedDocumentTypes = [
        'application/msword',
        'application/encrypted',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/x-pkcs12',
        'application/pdf',
        'application/rtf,text/rtf,text/plain'
    ];

    /**
     * Apply type
     *
     * @var 
     */
    private $applyType;

    /**
     * List of allowed candidate fields
     *
     * @var array
     */
    private static $allowedCandidateFields = [
        'Person.title',
        'Person.firstName',
        'Person.lastName',
        'Person.infix',
        'Person.initials',
        'Person.suffix',
        'Person.emailPrimary',
        'Person.birthdate',
        'Person.age',
        'Person.gender',
        'Person.nationalityCode',
        'Person.nationalityCodes',
        'Person.nationality',
        'Person.languageCode',
        'Person.skype',
        'Person.phoneMobile',
        'Person.AddressPrimary',
        'Person.AddressPrimary.address',
        'Person.AddressPrimary.city',
        'Person.AddressPrimary.postcode',
        'Person.AddressPrimary.stateCode',
        'Person.AddressPrimary.countryCode',
        'Person.AddressPrimary.country',
        'Person.PhoneNumbers.0.type',
        'Person.PhoneNumbers.0.phoneNumber',
        'Person.PhoneNumbers.1.type',
        'Person.PhoneNumbers.1.phoneNumber',
        'Person.PhoneNumbers.2.type',
        'Person.PhoneNumbers.2.phoneNumber',
        'Person.PhoneNumbers.3.type',
        'Person.PhoneNumbers.3.phoneNumber',
        'Person.PhoneNumbers.4.type',
        'Person.PhoneNumbers.4.phoneNumber',
        'Person.PhoneNumbers.5.type',
        'Person.PhoneNumbers.5.phoneNumber',
        'Person.married',
        'Person.passPortNumber',
        'Person.passPortExpirationDate',
        'Person.bankAccountNumber',
        'Person.religion',
        'Person.placeOfBirth', //TODO ows returns PlaceOfBirth which is wrong needs to be fixed
        'phoneMobile',
        'availableAt',
        'Nationality',
        'promoText',
        'IdentityCardNumber',
        'SocialSecurityTaxNumber',
        'main_bank_iban_number',
        'IdentityCardExpirationDate',
        'website',
        'personalNr',
        'vatNumber',
        'greenCardExpirationDate',
        'greenCardNumber',
        'militaryHistory',
        'ownsCar',
        'workPermitExpirationDate',
        'workPermitNumber',
        'graduationDate',
        'age',
        'acceptTerms',
        'companyAddressCity',
        'companyWebsite',
        'imId',
        'handicapped',
        'willRelocate',
        'specificWorkplaceRequirement',
        'Linkedin',
        'hasActiveCustomerInLastWorkExperience',
        'ExtraPhoneNumbers',
        'EducationHistory',
        'EmploymentHistory',
        'SoftSkills',
        'ComputerSkills',
        'LanguageSkills',
        'ConfigurableOrganisationUnits',
        'Hobbies',
        'HrmItems',
        'References',
        'UtmTags',
        'BankAccounts',
        'Addresses',
        'noticeTerm',
        'driversLicence',
        'driversLicenceCopy',
        'driversLicenceNumber',
        'driversLicenceExpirationDate',
        'vatNr',
        'varType',
        'varCopy',
        'varExpirationDate',
        'vcaCopy',
        'vcaExpirationDate',
        'vcaNumber',
        'workContractCopy',
        'workContractNumber',
        'workContractEndDate',
        'workBaseLocationCountryCode',
        'workBaseLocation',
        'workBaseType',
        'numberOfKids',
        'socialSecurityTaxNumber',
        'matchCriteria_1',
        'matchCriteria_2',
        'matchCriteria_3',
        'matchCriteria_4',
        'matchCriteria_5',
        'matchCriteria_6',
        'matchCriteria_7',
        'matchCriteria_8',
        'matchCriteria_9',
        'matchCriteria_10',
        'matchCriteria_11',
        'matchCriteria_12',
        'matchCriteria_13',
        'matchCriteria_14',
        'matchCriteria_15',
        'matchCriteria_16',
        'matchCriteria_17',
        'matchCriteria_18',
        'extraInfo1',
        'extraInfo2',
        'extraInfo3',
        'extraInfo4',
        'extraInfo5',
        'extraInfo6',
        'extraInfo7',
        'extraInfo8',
        'extraInfo9',
        'extraInfo10',
        'extraInfo11',
        'extraInfo12',
        'extraInfo13',
        'extraInfo14',
        'extraInfo15',
        'extraInfo16',
        'extraInfo17',
        'extraInfo18',
        'extraInfo19',
        'extraInfo20',
        'extraInfo21',
        'extraInfo22',
        'extraInfo23',
        'extraInfo24',
        'extraInfo25',
        'extraInfo26',
        'extraInfo27',
        'extraInfo28',
        'extraInfo29',
        'extraInfo30',
        'extraInfo31',
        'extraInfo32',
        'extraInfo33',
        'extraInfo34',
        'extraInfo35',
        'extraInfo36',
        'extraInfo37',
        'extraInfo38',
        'extraInfo39',
        'extraInfo40',
        'extraInfo41',
        'extraInfo42',
        'extraInfo43',
        'extraInfo44',
        'extraInfo45',
        'extraInfo46',
        'extraInfo47',
        'extraInfo48',
        'extraInfo49',
        'extraInfo50',
        'extraInfo51',
        'extraInfo52',
        'extraInfo53',
        'extraInfo54',
        'extraInfo55',
        'extraInfo56',
        'extraInfo57',
        'extraInfo58',
        'extraInfo59',
        'extraInfo60',
        'extraInfo61',
        'extraInfo62',
        'extraInfo63',
        'extraInfo64',
        'extraInfo65',
        'extraInfo66',
        'extraInfo67',
        'extraInfo68',
        'extraInfo69',
        'extraInfo70',
        'extraInfo71',
        'extraInfo72',
        'extraInfo73',
        'extraInfo74',
        'extraInfo75',
        'extraInfo76',
        'extraInfo77',
        'extraInfo78',
        'extraInfo79',
        'extraInfo80',
        'extraInfo81',
        'extraInfo82',
        'extraInfo83',
        'extraInfo84',
        'extraInfo85',
        'extraInfo86',
        'extraInfo87',
        'extraInfo88',
        'extraInfo89',
        'extraInfo90',
        'extraInfo91',
        'extraInfo92',
        'extraInfo93',
        'extraInfo94',
        'extraInfo95',
        'extraInfo96',
        'extraInfo97',
        'extraInfo98',
        'extraInfo99',
        'extraInfo100',
        'portalId',
        'userId'
    ];

    public const autoCompleteMapping = [
        'Person.firstName' => 'given-name',
        "Person.infix" => 'additional-name',
        'Person.lastName' => 'family-name',
        'Person.emailPrimary' => 'email',
        'Person.phoneMobile' => 'tel',
        'Person.AddressPrimary.address' => 'street-address',
        'Person.AddressPrimary.city' => 'locality',
        'Person.AddressPrimary.postcode' => 'postal-code',
        'Person.AddressPrimary.stateCode' => 'region',
        'Person.AddressPrimary.countryCode' => 'country-name',
        'Person.AddressPrimary.country' => 'country-name',
    ];

    /**
     * Constructor
     *
     * @param string $vacancyUid
     * @param AuthUser|bool $authUser
     */
    public function __construct(string $vacancyUid = '')
    {
        parent::__construct();

        $this->vacancyUid = $vacancyUid;
        $this->authUser = AuthModel::getUser();

        $this->init();
    }

    /**
     * Init model data
     *
     * @return void
     */
    private function init(): void
    {
        // Get questionset UID for vacancy
        $questionsetUid = $this->vacancyUid !== '' ? 
        QuestionsetModel::getVacancyQuestionsetId($this->vacancyUid) :
        QuestionsetModel::getOpenApplicationQuestionsetUid();

        $useKnownCandidateQuestionset = filter_var(get_option('otys_option_use_known_candidate_questionset', false), FILTER_VALIDATE_BOOLEAN);
        $useMobileQuestionset = filter_var(get_option('otys_option_use_mobile_questionset', false), FILTER_VALIDATE_BOOLEAN);

        $isMobile = wp_is_mobile();

        if (
            $useMobileQuestionset &&
            $isMobile && 
            ($uid = $this->getMobileQuestionsetUid()) && 
            is_wp_error($uid) === false &&
            $uid !== 0
        ) {
            $questionsetUid = $uid;
        } elseif (
            $this->vacancyUid !== '' && 
            $this->authUser !== false && 
            $useKnownCandidateQuestionset && 
            ($uid = QuestionsetModel::getKnownCandidateQuestionset()) &&
            is_wp_error($uid) === false &&
            $uid !== 0
        ) {
            // Get known candidate questionset if user is logged in
            $questionsetUid = $uid;

            $this->applyType = 'known_candidate';
        } elseif (
            $this->vacancyUid !== '' &&
            ($uid = QuestionsetModel::getVacancyQuestionsetId($this->vacancyUid)) &&
            is_wp_error($uid) === false &&
            $uid !== 0
        ) {
            // Get questionset UID for vacancy if user is not logged in
            $questionsetUid = $uid;

            $this->applyType = 'vacancy';
        } else {
            // Get open application questionset UID if user is not logged in and no vacancy is set
            $questionsetUid = QuestionsetModel::getOpenApplicationQuestionsetUid();

            $this->applyType = 'open_application';
        }

        if (is_wp_error($questionsetUid)) {
            $this->errors->add($questionsetUid->get_error_code(), $questionsetUid->get_error_message(), $questionsetUid->get_error_data());
            return;
        }

        $this->authUserData = $this->authUser !== false ? $this->getCandidateData($this->authUser->getCandidateUid()) : [];

        $this->questionsetUid = $questionsetUid;

        // Retrieve questionset
        $questionset = $this->getQuestionset($questionsetUid);

        if (is_wp_error($questionset)) {
            $this->errors->add($questionset->get_error_code(), $questionset->get_error_message(), $questionset->get_error_data());
        }

        $this->questionset = $questionset;
    }

    /**
     * Get qusetionset UID
     *
     * @since 2.0.0
     * @return integer
     */
    public function getUid(): int
    {
        return $this->questionsetUid;
    }

    /**
     * Get questionset
     *
     * @since 2.0.0
     * @return array|WP_Error
     */
    public function get()
    {
        return $this->questionset;
    }

    /**
     * Get User data based on the logged in user
     *
     * @return array
     */
    private function getCandidateData(string $candidateUid): array
    {
        $candidateData = [];

        // Convert candidate data array to multidimensional array, since this is how OWS expect the data to be delivered
        foreach (static::$allowedCandidateFields as $owsName) {
            $explode = explode('.', $owsName);
            $object = OtysApi::owsFieldValuesToObject($explode, false);
            $candidateData = array_replace_recursive($candidateData, $object);
        }

        $candidateData = OtysApi::post([
            'method' => 'Otys.Services.CandidateService.getDetail',
            'params' => [
                $candidateUid,
                [
                    'uid' => 1,
                    'internalId' => 1,
                    'Person' => true,
                    'Linkedin' => 1,
                    'availableAt' => 1,
                    'promoText' => 1,
                    'IdentityCardNumber' => 1,
                    'SocialSecurityTaxNumber' => 1,
                    'main_bank_iban_number' => 1,
                    'IdentityCardExpirationDate' => 1,
                    'website' => 1,
                    'personalNr' => 1,
                    'vatNumber' => 1,
                    'greenCardExpirationDate' => 1,
                    'greenCardNumber' => 1,
                    'militaryHistory' => 1,
                    'ownsCar' => 1,
                    'workPermitExpirationDate' => 1,
                    'workPermitNumber' => 1,
                    'graduationDate' => 1,
                    'age' => 1,
                    'acceptTerms' => 1,
                    'companyAddressCity' => 1,
                    'companyWebsite' => 1,
                    'imId' => 1,
                    'handicapped' => 1,
                    'willRelocate' => 1,
                    'specificWorkplaceRequirement' => 1,
                    'Linkedin' => 1,
                    'hasActiveCustomerInLastWorkExperience' => 1,
                    'ExtraPhoneNumbers' => 1,
                    'EducationHistory' => 1,
                    'EmploymentHistory' => 1,
                    'SoftSkills' => 1,
                    'ComputerSkills' => 1,
                    'LanguageSkills' => 1,
                    'ConfigurableOrganisationUnits' => 1,
                    'Hobbies' => 1,
                    'HrmItems' => 1,
                    'References' => 1,
                    'UtmTags' => 1,
                    'BankAccounts' => 1,
                    'Addresses' => 1,
                    'noticeTerm' => 1,
                    'driversLicence' => 1,
                    'driversLicenceCopy' => 1,
                    'driversLicenceNumber' => 1,
                    'driversLicenceExpirationDate' => 1,
                    'vatNr' => 1,
                    'varType' => 1,
                    'varCopy' => 1,
                    'varExpirationDate' => 1,
                    'vcaCopy' => 1,
                    'vcaExpirationDate' => 1,
                    'vcaNumber' => 1,
                    'workContractCopy' => 1,
                    'workContractNumber' => 1,
                    'workContractEndDate' => 1,
                    'workBaseLocationCountryCode' => 1,
                    'workBaseLocation' => 1,
                    'workBaseType' => 1,
                    'numberOfKids' => 1,
                    'socialSecurityTaxNumber' => 1,
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
                    'extraInfo1' => 1,
                    'extraInfo2' => 1,
                    'extraInfo3' => 1,
                    'extraInfo4' => 1,
                    'extraInfo5' => 1,
                    'extraInfo6' => 1,
                    'extraInfo7' => 1,
                    'extraInfo8' => 1,
                    'extraInfo9' => 1,
                    'extraInfo10' => 1,
                    'extraInfo11' => 1,
                    'extraInfo12' => 1,
                    'extraInfo13' => 1,
                    'extraInfo14' => 1,
                    'extraInfo15' => 1,
                    'extraInfo16' => 1,
                    'extraInfo17' => 1,
                    'extraInfo18' => 1,
                    'extraInfo19' => 1,
                    'extraInfo20' => 1,
                    'extraInfo21' => 1,
                    'extraInfo22' => 1,
                    'extraInfo23' => 1,
                    'extraInfo24' => 1,
                    'extraInfo25' => 1,
                    'extraInfo26' => 1,
                    'extraInfo27' => 1,
                    'extraInfo28' => 1,
                    'extraInfo29' => 1,
                    'extraInfo30' => 1,
                    'extraInfo31' => 1,
                    'extraInfo32' => 1,
                    'extraInfo33' => 1,
                    'extraInfo34' => 1,
                    'extraInfo35' => 1,
                    'extraInfo36' => 1,
                    'extraInfo37' => 1,
                    'extraInfo38' => 1,
                    'extraInfo39' => 1,
                    'extraInfo40' => 1,
                    'extraInfo41' => 1,
                    'extraInfo42' => 1,
                    'extraInfo43' => 1,
                    'extraInfo44' => 1,
                    'extraInfo45' => 1,
                    'extraInfo46' => 1,
                    'extraInfo47' => 1,
                    'extraInfo48' => 1,
                    'extraInfo49' => 1,
                    'extraInfo50' => 1,
                    'extraInfo51' => 1,
                    'extraInfo52' => 1,
                    'extraInfo53' => 1,
                    'extraInfo54' => 1,
                    'extraInfo55' => 1,
                    'extraInfo56' => 1,
                    'extraInfo57' => 1,
                    'extraInfo58' => 1,
                    'extraInfo59' => 1,
                    'extraInfo60' => 1,
                    'extraInfo61' => 1,
                    'extraInfo62' => 1,
                    'extraInfo63' => 1,
                    'extraInfo64' => 1,
                    'extraInfo65' => 1,
                    'extraInfo66' => 1,
                    'extraInfo67' => 1,
                    'extraInfo68' => 1,
                    'extraInfo69' => 1,
                    'extraInfo70' => 1,
                    'extraInfo71' => 1,
                    'extraInfo72' => 1,
                    'extraInfo73' => 1,
                    'extraInfo74' => 1,
                    'extraInfo75' => 1,
                    'extraInfo76' => 1,
                    'extraInfo77' => 1,
                    'extraInfo78' => 1,
                    'extraInfo79' => 1,
                    'extraInfo80' => 1,
                    'extraInfo81' => 1,
                    'extraInfo82' => 1,
                    'extraInfo83' => 1,
                    'extraInfo84' => 1,
                    'extraInfo85' => 1,
                    'extraInfo86' => 1,
                    'extraInfo87' => 1,
                    'extraInfo88' => 1,
                    'extraInfo89' => 1,
                    'extraInfo90' => 1,
                    'extraInfo91' => 1,
                    'extraInfo92' => 1,
                    'extraInfo93' => 1,
                    'extraInfo94' => 1,
                    'extraInfo95' => 1,
                    'extraInfo96' => 1,
                    'extraInfo97' => 1,
                    'extraInfo98' => 1,
                    'extraInfo99' => 1,
                    'extraInfo100' => 1,
                ]
            ]
        ], false);

        if (!is_array($candidateData)) {
            return [];
        }

        return ArrayHelper::flatten($candidateData);
    }

    /**
     * Get questionset
     *
     * @since 2.0.0
     * @param   array $activeFilters  Current active filters as slug
     * @param   array $atts           Shortcode attributes
     * @return  array|WP_Error
     */
    public function getQuestionset(int $questionsetId)
    {
        // Get questionset id based on vacancy id
        if ($questionsetId === 0) {
            return new WP_Error('questionset', __('Wrong questionset UID', 'otys-jobs-apply'));
        }

        // Check if GDPR is enabled
        $gdprSettings = static::getGDPRSettings();

        $gdprEnabled = (
            !is_wp_error($gdprSettings) &&
            (($this->vacancyUid !== '' && $gdprSettings['gdpr_enabled_application']) ||
            ($this->vacancyUid === '' && $gdprSettings['gdpr_enabled_open_application']))
        ) ? true : false;

        // If GDPR is enabled we want to use a different method which includes the GDPR question
        $questionsetMethod = $gdprEnabled ? 'getDetailWithGdprConsent' : 'getDetail';

        // Get questionset
        $candidateQuestionset = OtysApi::post([
            'method' => 'Otys.Services.QuestionSetService.' . $questionsetMethod,
            'params' => [$questionsetId]
        ], true);

        // Get candidate data
        if ($this->authUser) {
            $candidateData = OtysApi::post([
                'method' => 'Otys.Services.CandidateService.getDetail',
                'params' => [
                    $this->authUser->getCandidateUid(),
                    [
                        'uid' => 1,
                        'internalId' => 1,
                        'Person' => [
                            'firstName' => 1,
                            'lastName' => 1,
                            'emailPrimary' => 1
                        ]
                    ]
                ]
            ], true);
        }

        if (is_wp_error($candidateQuestionset) || empty($candidateQuestionset)) {
            return $candidateQuestionset;
        }

        $currentPageNumber = get_query_var('page-number', 1);
        $currentPageNumber = $currentPageNumber === 0 ? 1 : $currentPageNumber;

        /*
             Create array of questionset data for the current page
        */
        $questionResult = [];
     
        foreach ($candidateQuestionset['Pages'] as $questionsetPageNumber => $questionsetPage) {
            // Setup page base information
            $questionResult[$questionsetPageNumber] = [
                'name' => isset($questionsetPage['name']) ? $questionsetPage['name'] : '',
                'pageTitle' => isset($questionsetPage['pageTitle']) ? $questionsetPage['pageTitle'] : '',
                'pageIntroText' => array_key_exists('pageIntroText', $questionsetPage) ? $questionsetPage['pageIntroText'] : '',
                'number' => $questionsetPageNumber
            ];

            $hasEmailField = false;

            // If there are no questions for this page add empty array
            if (empty($questionsetPage['Questions'])) {
                $questionResult[$questionsetPageNumber]['questions'] = [];
            }

            // Build question data
            foreach ($questionsetPage['Questions'] as $questionid => $question) {
                // Check if we have existing candidate data from the logged in user
                $question['value'] = $this->authUserData[$question['fieldName']] ?? '';
            
                // Skip when there's no type set
                if (!is_array($question) || !array_key_exists('type', $question)) {
                    continue;
                }

                $question['showQuestion'] = true;

                $fieldName = $question['fieldName'] ?? false;

                // If fieldname is email set has email true so we know the questionset includes a email adres
                if ($question['fieldName'] === 'Person.emailPrimary') {
                    if ($this->applyType === 'known_candidate') {
                        continue;
                    }

                    $hasEmailField = true;

                    // Force the email field to be mandatory
                    $question['Validation']['mandatory'] = true;
                }
                
                // Force motivation field to be of subtype textarea
                if (!$fieldName && $question['type'] === "motivation") {
                    $fieldName = 'motivation';
                    $question['subtype'] = 'textarea';
                }
                
                // Replace GDPR date
                if ($fieldName === "candidate_gdpr_accept") {
                    $question['explanation'] = str_replace(
                        '{$gdprLongTermMonths}',
                        sprintf(
                            _x('%s months', 'GDPR', 'otys-jobs-apply'),
                            $gdprSettings['gdpr_accepted_months']
                        ),
                        $question['explanation']
                    );
                }

                // Force validation for documents
                if ($question['type'] === 'file' || $question['subtype'] === 'multiFile' || $question['subtype'] === 'file') {
                    $question['Validation']['documentType'] = true;

                } else {
                    $question['Validation']['documentType'] = false;
                }

                // Define type since the type can be either determined using the actual type or the subtype, when a subtype is defined we use a subtype else just the type
                $type = isset($question['subtype']) && !empty($question['subtype']) ? 
                strtolower($question['subtype']) :
                (!empty($question['type']) ? strtolower($question['type']) : '');

                $htmlType = 'text';
                $templateDir = '/include-parts/rest-forms/';

                // Template directory for forms used for field front end template
                switch ($type) {
                    case 'textfield':
                        $template = Routes::locateTemplate($templateDir . 'field-text');

                        if ($question['Validation']['phone'] === true || $question['Validation']['internationalPhone'] === true) {
                            $htmlType = 'tel';
                        } elseif ($question['Validation']['email'] === true) {
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
                        $htmlType = 'select';
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

                // Force GDPR template
                if ($question['fieldName'] === "candidate_gdpr_accept") {
                    $template = Routes::locateTemplate($templateDir . 'field-avg');
                    $question['showQuestion'] = false;
                }

                // Force GDPR template
                if ($question['fieldName'] === "acceptTerms") {
                    $template = Routes::locateTemplate($templateDir . 'field-terms');
                    $question['showQuestion'] = false;
                }

                $autoCompleteName = self::autoCompleteMapping[$fieldName] ?? 'on';

                $questionResult[$questionsetPageNumber]['questions'][$questionid] = [
                    'id' => $questionid,
                    'name' => 'qs_' . $questionid,
                    'question' => $question['question'],
                    'showQuestion' => $question['showQuestion'],
                    'fieldName' => $fieldName,
                    'fieldNameId' => static::camelCase2UnderScore($fieldName),
                    'template' => $template,
                    'type' => $htmlType,
                    'validation' => $question['Validation'],
                    'data' => $question,
                    'autoCompleteName' => $autoCompleteName,
                    'value' => $question['value'],
                ];

                if (array_key_exists('PossibleAnswersExt', $question)) {
                    $questionResult[$questionsetPageNumber]['questions'][$questionid]['answers'] = $question['PossibleAnswersExt'];
                }
            }
        }

        $questionsetTypes = [
            'openEntrySet',
            'myProfileSet',
            'basicSet',
            'phoenixSet',
            'otcSet',
            'knownCandidateSet',
            'relationSet',
            'linkedInSet',
            'textkernelSet',
            'supplierSet',
            'supplierEditSet',
            'eventSet',
            'indeedSet',
            'mobileLinkedInSet',
            'mobileApplyCvSet',
            'mobileBasicSet',
            'mobileIndeedSet',
            'useMinKillerQuestionPoints',
            'minKillerQuestionPoints',
        ];

        $questionsetType = array_filter($questionsetTypes, function ($type) use ($candidateQuestionset) {
            return $candidateQuestionset[$type] ?? false;
        });

        $returnData = [
            'uid' => $candidateQuestionset['uid'],
            'currentPage' => (int) $currentPageNumber,
            'name' => $candidateQuestionset['name'],
            'shortDescription' => $candidateQuestionset['shortDescription'],
            'pageTitle' => $candidateQuestionset['pageTitle'],
            'pageIntroText' => $candidateQuestionset['pageIntroText'],
            'pages' => $questionResult,
            'hasEmailField' => $hasEmailField,
            'errorTemplate' => Routes::locateTemplate($templateDir . 'field-error'),
            'applyType' => $this->applyType,
            'useFor' => $questionsetType,
        ];

        return $returnData;
    }

    /**
     * Get questionset for vacancy
     *
     * @since 2.0.0
     * @return string | \WP_error
     */
    public static function getVacancyQuestionsetId(string $vacancyUid)
    {
        $vacancyUid = OtysApi::post([
            'method' => 'Otys.Services.QuestionSetService.getVacancyQsIdByVacancyOuid',
            'params' => [
                $vacancyUid
            ]
        ], true);

        return $vacancyUid;
    }

    /**
     * Get post data by OWS keys
     *
     * @since 2.0.0
     * @param array $postData
     * @param integer $questionsetId
     * @return array
     */
    public function getOwsFieldNames(): array
    {
        $newPostData = [];

        foreach ($this->questionset['pages'] as $questionsetPage) {
            foreach ($questionsetPage['questions'] as $questionId => $question) {
                $postDataFieldName = 'qs_' . $questionId;

                $newPostData[$question['fieldName']] = $postDataFieldName;
            }
        }

        return $newPostData;
    }

    /**
     * Get open application questionset UID
     *
     * @since 2.0.0
     * @return integer | WP_Error
     */
    public static function getOpenApplicationQuestionsetUid()
    {
        $response = OtysApi::post([
            'method' => 'Otys.Services.CandidateQuestionSetService.getListEx',
            'params' => [
                [
                    "what" => null,
                    "limit" => 1,
                    "offset" => 0,
                    "getTotalCount" => 1,
                    "sort" => [
                        "uid" => "DESC"
                    ],
                    "excludeLimitCheck" => true,
                    "condition" => [
                        "type" => "AND",
                        "items" => [
                            [
                                "type" => "OR",
                                "items" => [
                                    [
                                        "type" => "COND",
                                        "field" => "openEntrySet",
                                        "op" => "EQ",
                                        "param" => true
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ], true);

        if (is_wp_error($response)) {
            return $response;
        }

        if (
            !is_array($response) ||
            !array_key_exists('listOutput', $response)
        ) {
            return new WP_Error('invalid_open_questionset', __('Response open questionset is invalid', 'otys-jobs-apply'));
        }

        $uid = $response['listOutput'][0]['uid'];

        return is_int($uid) ? $uid : 0;
    }

    /**
     * Get mobile questionset UID
     *
     * @since 2.0.0
     * @return integer | WP_Error
     */
    public static function getMobileQuestionsetUid()
    {
        $response = OtysApi::post([
            'method' => 'Otys.Services.CandidateQuestionSetService.getListEx',
            'params' => [
                [
                    "what" => null,
                    "limit" => 1,
                    "offset" => 0,
                    "getTotalCount" => 1,
                    "sort" => [
                        "uid" => "DESC"
                    ],
                    "excludeLimitCheck" => true,
                    "condition" => [
                        "type" => "AND",
                        "items" => [
                            [
                                "type" => "OR",
                                "items" => [
                                    [
                                        "type" => "COND",
                                        "field" => "mobile",
                                        "op" => "EQ",
                                        "param" => true
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ], true);

        if (is_wp_error($response)) {
            return $response;
        }

        if (
            !is_array($response) ||
            !array_key_exists('listOutput', $response)
        ) {
            return new WP_Error('invalid_open_questionset', __('Response open questionset is invalid', 'otys-jobs-apply'));
        }

        $uid = $response['listOutput'][0]['uid'] ?? 0;

        return is_int($uid) ? $uid : 0;
    }

    /**
     * Get known candidate questionset
     *
     * @since 2.0.43
     * @return integer | WP_Error
     */
    public static function getKnownCandidateQuestionset()
    {
        $response = OtysApi::post([
            'method' => 'Otys.Services.CandidateQuestionSetService.getListEx',
            'params' => [
                [
                    "what" => null,
                    "limit" => 1,
                    "offset" => 0,
                    "getTotalCount" => 1,
                    "sort" => [
                        "uid" => "DESC"
                    ],
                    "excludeLimitCheck" => true,
                    "condition" => [
                        "type" => "AND",
                        "items" => [
                            [
                                "type" => "OR",
                                "items" => [
                                    [
                                        "type" => "COND",
                                        "field" => "knownCandidateSet",
                                        "op" => "EQ",
                                        "param" => true
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ], true);

        if (is_wp_error($response)) {
            return $response;
        }

        if (
            !is_array($response) ||
            !array_key_exists('listOutput', $response)
        ) {
            return new WP_Error('invalid_open_questionset', __('Response open questionset is invalid', 'otys-jobs-apply'));
        }

        $uid = $response['listOutput'][0]['uid'];

        return is_int($uid) ? $uid : 0;
    }

    /**
     * Question validation based on OTYS validation rules
     *
     * @since 2.0.0
     * @param mixed $value
     * @param array $validations
     * @return WP_Error
     */
    public static function validate($value, array $validations)
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
                if (!filter_var($value, FILTER_VALIDATE_EMAIL) && !is_email($value)) {
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
     * Get filter validation array
     * Can be used with filter_var_array()
     *
     * @since 2.0.0
     * @return array
     */
    public function getQuestionsetValidation(): array
    {
        $validation = [];

        if (is_wp_error($this->questionset) || empty($this->questionset)) {
            return [];
        }

        foreach ($this->questionset['pages'] as $page) {
            foreach ($page['questions'] as $question) {
                $validation['qs_' . $question['id']] = function ($value) use ($question) {
                    $errors = static::validate($value, $question['validation']);

                    return [
                        'value' => $value,
                        'errors' => $errors->get_error_messages()
                    ];
                };
            }
        }

        return $validation;
    }

    /**
     * Get all questionset fields empty
     *
     * @since 2.0.0
     * @param integer $questionsetId
     * @return array
     */
    public function getQuestionsetFields(): array
    {
        $questionsetFields = [];

        foreach ($this->questionset['pages'] as $page) {
            foreach ($page['questions'] as $question) {
                $questionsetFields['qs_' . $question['id']] = $question;
            }
        }

        return $questionsetFields;
    }

    /**
     * Get questions by form names
     *
     * @since 2.0.0
     * @return array
     */
    public function getQuestionsByFormName(): array
    {
        $result = [];

        if (array_key_exists('pages', $this->questionset)) {
            foreach ($this->questionset['pages'] as $page) {
                if (array_key_exists('questions', $page)) {
                    foreach ($page['questions'] as $question) {
                        $result[$question['name']] = $question;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get allowed document types
     *
     * @since 2.0.0
     * @return array
     */
    public static function getAllowedDocumentTypes(): array
    {
        return static::$allowedDocumentTypes;
    }

    /**
     * Check if GDPR is enabled for the current client
     *
     * @since 2.0.0
     * @return boolean
     */
    public static function isGdprEnabled()
    {
        $clientId = OtysApi::getClientId();

        if ($clientId === 0) {
            return false;
        }

        $gdprSetting = OtysApi::post([
            'method' => 'Otys.Services.CsmService.getValue',
            'params' => [
                "SE2995",
                3210,
                0,
                0
            ]
        ], true);

        if (
            !empty($gdprSetting) &&
            array_key_exists('value', $gdprSetting) &&
            $gdprSetting['value'] == 1
        ) {
            return true;
        }

        return false;
    }

    /**
     * Build candidate data based on allowed OWS fields and returns candidateData
     * based on OWS names
     *
     * @since 1.0.0
     * @return mixed
     */
    public static function buildCandidateData(array $candidateData): array
    {
        $candidate = [];
        $candidateObject = [];

        // Assign candidate data to candidate array and make changes to the data where needed
        foreach ($candidateData as $key => $value) {
            if ($value === "" || $value === null) {
                continue;
            }

            // Extra fields have to be submitted as comma separated values instead of an array
            if (strpos($key, 'extraInfo') !== false && is_array($value)) {
                $candidate[$key] = implode(',', $value);
                continue;
            }

            if (strpos($key, 'matchCriteria_') !== false && is_array($value)) {
                $candidate[$key] = array_flip($value);
                continue;
            } else if (strpos($key, 'matchCriteria_') !== false && is_string($value)) {
                $candidate[$key] = [$value => 1];
                continue;
            }

            // Reformatting phonenumber because otherwise this doesn't work
            if ($key === 'Person.PhoneNumbers.primary') {
                $candidate['Person.PhoneNumbers.1.phoneNumber'] = $value;
                continue;
            }

            // Reformatting phonenumber because otherwise this doesn't work
            if ($key === 'Person.phoneMobile') {
                $candidate['Person.PhoneNumbers.2.phoneNumber'] = $value;
                continue;
            }

            $candidate[$key] = $value;
        }

        // After that we will make sure only allowed values are returned
        $candidate = array_filter($candidate, function ($key) {
            return in_array($key, static::$allowedCandidateFields);
        }, ARRAY_FILTER_USE_KEY);

        // Convert candidate data array to multidimensional array, since this is how OWS expect the data to be delivered
        foreach ($candidate as $owsName => $data) {
            $explode = explode('.', $owsName);
            $object = OtysApi::owsFieldValuesToObject($explode, $data);
            $candidateObject = array_replace_recursive($candidateObject, $object);
        }

        return $candidateObject;
    }

    /**
     * Set candidate GDPR status
     *
     * @since 2.0.0
     * @param string $candidateUid
     * @param boolean $accepted
     * @return void
     */
    public function setGDPR(string $candidateUid, bool $accepted = false): void
    {
        $gdprValue = $accepted ? 'otherYes' : 'cron';

        $settings = static::getGDPRSettings();

        // Stop if failed to retrieve settings
        if (is_wp_error($settings)) {
            return;
        }

        // Check if GDPR is enabled for current application situation
        if (
            ($this->vacancyUid !== '' && !$settings['gdpr_enabled_application']) ||
            ($this->vacancyUid === '' && !$settings['gdpr_enabled_open_application'])
        ) {
            // Return if gdpr is not enabled for current application
            return;
        }

        $date = ($accepted) ? $settings['gdpr_accepted'] : $settings['gdpr_declined'];

        OtysApi::post([
            'method' => 'Otys.Services.CandidateService.setGdprDueDatePartner',
            'params' => [
                $candidateUid,
                $date,
                $gdprValue,
                false,
                false
            ]
        ]);
    }

    /**
     * Get GDPR Settings
     *
     * @since 2.0.0
     * @return array|WP_Error
     */
    public static function getGDPRSettings()
    {
        if($gdprCache = Cache::get('gdpr', '')) {
            return $gdprCache['value'];
        }

        $clientId = OtysApi::getClientId();

        // GDPR Enabled
        $request['gdpr_enabled'] = [
            'method' => 'Otys.Services.CsmService.getValue',
            'args' => [
                ["SE2995"],
                $clientId
            ]
        ];
        
        // GDPR Enabled for application
        $request['gdpr_enabled_application'] = [
            'method' => 'Otys.Services.CsmService.getValue',
            'args' => [
                ["SE3004"],
                $clientId
            ]
        ];

        // GDPR Enabled for open application
        $request['gdpr_enabled_open_application'] = [
            'method' => 'Otys.Services.CsmService.getValue',
            'args' => [
                ["SE3348"],
                $clientId
            ]
        ];

        // GDPR Declined days till removal
        $request['gdpr_declined'] = [
            'method' => 'Otys.Services.CsmService.getValue',
            'args' => [
                ["SE2997"],
                $clientId
            ]
        ];

        // GPDR Accepted months till removal
        $request['gdpr_accepted'] = [
            'method' => 'Otys.Services.CsmService.getValue',
            'args' => [
                ["SE2999"],
                $clientId
            ]
        ];

        // GDPR Settings multiservice request
        $gdprSettings = OtysApi::post([
            'method' => 'Otys.Services.MultiService.execute',
            'params' => [
                $request
            ]
        ]);

        if (is_wp_error($gdprSettings)) {
            return $gdprSettings;
        }

        $settings = [];

        $settings['gdpr_enabled'] = ($gdprSettings['gdpr_enabled']['data']['SE2995']['value'] == 1);
        $settings['gdpr_enabled_application'] = ($gdprSettings['gdpr_enabled_application']['data']['SE3004']['value'] == 1);
        $settings['gdpr_enabled_open_application'] = ($gdprSettings['gdpr_enabled_open_application']['data']['SE3348']['value'] == 1);
        $settings['gdpr_declined'] = date('Y-m-d', strtotime('+'. intval($gdprSettings['gdpr_declined']['data']['SE2997']['value']) . 'day'));
        $settings['gdpr_accepted'] = date('Y-m-d', strtotime('+'. (intval($gdprSettings['gdpr_accepted']['data']['SE2999']['value'])) . 'month'));
        $settings['gdpr_accepted_months'] = intval($gdprSettings['gdpr_accepted']['data']['SE2999']['value']);

        Cache::add('gdpr', '', $settings);

        return $settings;
    }

    /**
     * Convert Camel Case String to underscore-separated
     *
     * @since 2.0.0
     * @param string $str The input string.
     * @param string $separator Separator, the default is underscore
     * @return string
     */
    private static function camelCase2UnderScore($str, $separator = "_")
    {
        if (empty($str)) {
            return $str;
        }
        $str = lcfirst($str);
        $str = preg_replace("/[A-Z]/", $separator . "$0", $str);
        $str = str_replace('.', '_', $str);
        return strtolower($str);
    }
}