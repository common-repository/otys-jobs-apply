<?php

namespace Otys\OtysPlugin\Controllers;

use Otys\OtysPlugin\Controllers\BaseController;
use Otys\OtysPlugin\Models\Shortcodes\VacanciesListModel;

class SitemapController extends BaseController
{
    public function callback(): void
    {
        header('Content-Type: application/xml');

        $listOutput = [];

        // Get first page of vacancies
        $vacancies = $this->getVacancies(1);

        if (!is_wp_error($vacancies) && !empty($vacancies) && isset($vacancies['listOutput']) && isset($vacancies['searchExtras'])) {
            // Set the list output
            $listOutput = $vacancies['listOutput'];
            
            // Get the total vacancies
            $totalVacancies = $vacancies['searchExtras']['ACTONOMY']['totalCount'];

            // Calculate the total pages
            $totalPages = ceil($totalVacancies / $vacancies['totalCount']);

            // Get the rest of the vacancies if there is more than 1 page
            if ($totalPages > 1) {   
                for ($i = 2; $i <= $totalPages; $i++) {
                    $vacanciesLoopResult = $this->getVacancies($i);

                    if (!is_wp_error($vacanciesLoopResult) && !empty($vacanciesLoopResult) && isset($vacanciesLoopResult['listOutput'])) {
                        $listOutput = array_merge($vacanciesLoopResult['listOutput'], $listOutput);
                    }
                }
            }
        }

        $xml = [];

        // Loop through the list output and create the xml
        foreach ($listOutput as $vacancy) {
            $xml[] = [
                'loc' => trailingslashit(home_url($vacancy['url'])),
                'lastmod' => date('Y-m-d', strtotime($vacancy['lastModified']))
            ];
        }

        // Parse the xml to the view
        $this->parseArgs('xml', $this->arrayToXml($xml, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>'));

        $this->setTemplate('xml/sitemap.php');
    }

    /**
     * Get vacancies for the sitemap
     *
     * @param integer $page
     * @return array
     */
    private function getVacancies($page = 1): array
    {
        $vacancies = VacanciesListModel::get([
            'page-number' => $page,
            'perpage' => 200,
            'limit' => 200,
            'cache' => false
        ], [], [
            'what' => [
                'uid' => 1,
                'internalId' => 0,
                'title' => 1,
                'location' => 0,
                'relation' => 0,
                'category' => 0,
                'status' => 0,
                'userEmail' => 0,
                'photoFileName' => 0,
                'textField_description' => 0,
                'textField_companyProfile' => 0,
                'textField_requirements' => 0,
                'textField_salary' => 0,
                'textField_companyCulture' => 0,
                'textField_summary' => 0,
                'textField_extra1' => 0,
                'textField_extra2' => 0,
                'textField_extra3' => 0,
                'textField_extra4' => 0,
                'textField_extra5' => 0,
                'textField_extra6' => 0,
                'textField_extra7' => 0,
                'textField_extra8' => 0,
                'textField_extra9' => 0,
                'textField_extra10' => 0,
                'applyCount' => 0,
                'PhotoGallery' => 0,
                'referenceNr' => 0,
                'externalReferenceNr' => 0,
                'entryDateTime' => 0,
                'lastModified' => 1,
                'salaryCurrency' => 0,
                'salaryValue' => 0,
                'salaryUnit' => 0,
                'salaryMin' => 0,
                'salaryMax' => 0,
                'publicationStartDate' => 0,
                'publicationEndDate' => 0,
                'publicationFirstDate' => 0,
                'publicationStatus' => 0,
                'publicationStatusId' => 0,
                'showEmployer' => 0,
                'blockScanners' => 0,
                'removeApplyButton' => 0,
                'categoryId' => 0,
                'alternativeCategories' => 0,
                'statusId' => 0,
                'user' => 0,
                'userInitials' => 0,
                'userId' => 0,
                'userPhoneMobile' => 0,
                'createdByUser' => 0,
                'locationAddress' => 0,
                'locationCity' => 0,
                'locationCityId' => 0,
                'locationPostcode' => 0,
                'locationState' => 0,
                'locationCountry' => 0,
                'locationCountryCode' => 0,
                'applyCountToday' => 0,
                'matchCriteriaNames' => 0,
                'matchCriteria_1' => 0,
                'matchCriteria_2' => 0,
                'matchCriteria_3' => 0,
                'matchCriteria_4' => 0,
                'matchCriteria_5' => 0,
                'matchCriteria_6' => 0,
                'matchCriteria_7' => 0,
                'matchCriteria_8' => 0,
                'matchCriteria_9' => 0,
                'matchCriteria_10' => 0,
                'matchCriteria_11' => 0,
                'matchCriteria_12' => 0,
                'matchCriteria_13' => 0,
                'matchCriteria_14' => 0,
                'matchCriteria_15' => 0,
                'matchCriteria_16' => 0,
                'matchCriteria_17' => 0,
                'matchCriteria_18' => 0,
                'locationLatitude' => 0,
                'locationLongitude' => 0,
                'applyUrls' => 0,
                'customApplyUrl' => 0,
                'customDetailUrl' => 0,
                'hoursType' => 0,
                'hoursUnit' => 0,
                'hours' => 0,
                'housTotal' => 0,
                'tariffUnit' => 0,
                'tariff' => 0,
                'currencyId' => 0,
                'assignmentType' => 0,
                'responsibleCustomerOfficeContactPersonName' => 0,
                'responsibleCustomerOfficeName' => 0,
                'responsibleWorkplaceName' => 0,
                'responsibleWorkplaceContactPersonName' => 0,
                'responsibleContact' => 0,
                'workPlaceAddress' => 0,
                'workPlacePostalCode' => 0,
                'workPlaceCity' => 0,
                'workPlacePhone' => 0,
                'workPlaceFax' => 0,
                'slug' => 1
            ] 
        ]);

        if (!is_wp_error($vacancies)) {
            return $vacancies;
        }

        return [];
    }

    /**
     * Convert array to xml
     *
     * @param array $array
     * @param mixed $rootElement
     * @param mixed $xml
     * @return string
     */
    private function arrayToXml(array $array, $rootElement = null, $xml = null): string
    {
        $_xml = $xml;

        // If there is no Root Element then insert root
        if ($_xml === null) {
            $_xml = new \SimpleXMLElement($rootElement !== null ? $rootElement : '<root/>');
        }

        // Visit all key value pair
        foreach ($array as $k => $v) {
            $xmlKey = is_int($k) ? 'url' : $k;

            // If there is nested array then
            if (is_array($v)) {
                // Call function for nested array
                $this->arrayToXml($v, $xmlKey, $_xml->addChild($xmlKey));
            } else {

                // Simply add child element. 
                $_xml->addChild($xmlKey, $v);
            }
        }

        
        $result = $_xml->asXML();

        $result = str_replace('<?xml version="1.0"?>', '<?xml version="1.0" encoding="UTF-8"?>', $result);

        return $result;
    }
}