<?php

namespace Otys\OtysPlugin\Models;

class Pagination extends BaseModel {
    /**
     * Generate pagination array
     *
     * @param   int     $maxItems
     * @param   int     $totalItems
     * @param   int     $activePage
     * @param   array   $allowedParams
     * @param   array   $options
     * @return array
     * @since 1.0.0
     */
    public static function get(int $maxItems, int $totalItems, int $activePage, array $params = [], array $options = []): array
    {
        // Default options
        $options = wp_parse_args($options, [
            'max_amount_of_pages' => 5,
            'previous_next_buttons' => 1, /** 0: hide, 1: text, 2: icons */
            'first_last_buttons' => 0, /** 0: hide, 1: text, 2: icons */
            'prev_icon' => '',
            'next_icon' => '',
            'first_icon' => '',
            'last_icon' => ''
        ]);

        // Set total page items
        $totalPages = intval((ceil($totalItems / $maxItems)));

        // Initialiase pagination array
        $pagination = [
            'pages' => [],
            'numeric_pages' => [],
            'total_pages' => (int) $totalPages,
            'current_page_number' => (int) $activePage
        ];

        // Get current url
        global $wp;
        $currentUrl = home_url(add_query_arg(array(), $wp->request)) . '/';

        $pageRangeStart = 0;
        $pageRangeEnd = 0;

        // Create pages array
        for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++) {
            $pageQuery = $params;

            // If the page is 1 remove the page-number parameter from the url
            if ($pageNumber !== 1) {
                $pageQuery['page-number'] = $pageNumber;
            } else {
                unset($pageQuery["page-number"]);
            }

            $httpPageQuery = http_build_query($pageQuery);

            $paginationOffset = intval(floor($options['max_amount_of_pages'] / 2));

            // Define from which page we start the pagination
            $pageRangeStart = $activePage - $paginationOffset;

            // Define at which page we end the pagination
            $pageRangeEnd= $activePage + $paginationOffset;

            // Check if the begin of the pagination is below page 1, if so save the amount of pages which are below 1
            $pageRangeStartTooLess = ($pageRangeStart < 1) ? (1 - $pageRangeStart) : 0;
            
            // Check if the end of the pagination is higher than the total pages, if so save the amount of pages that is higher than the total pages
            $pageRangeMaxTooMany =  ($pageRangeEnd > $totalPages) ? $pageRangeEnd - $totalPages : 0;
                        
            // When the pages at the end of the page range is above the total pages we will try to add it to the pagerange start
            if ($pageRangeMaxTooMany > 0) {
                $pageRangeStart = ($pageRangeStart + $pageRangeMaxTooMany > 1) ? $pageRangeStart - $pageRangeMaxTooMany : 1;
            }

            // When the pages at the start are lower than 1 we will try to add the amount of pages that are below one to the page range end
            if ($pageRangeStartTooLess > 0) {
                $pageRangeEnd = (($pageRangeEnd + $pageRangeStartTooLess) < $totalPages) ? $pageRangeEnd + $pageRangeStartTooLess : $totalPages;
            }

            $pageInfo = [
                'active' => ($activePage === $pageNumber) ? true : false,
                'url' => ($httpPageQuery !== "") ? $currentUrl . '?' . http_build_query($pageQuery) : $currentUrl,
                'page' => $pageNumber,
                'show' => ($pageNumber >= $pageRangeStart && $pageNumber <= $pageRangeEnd) // if the page is within the page range return true
            ];

            $pagination['pages'][] = $pageInfo;
        }

        // Get current get parameters which we will edit parameters after that build
        $prevPageQuery = $params;

        // If the page is 1 remove the page-number parameter from the url
        if (($prevPageNumber = ($activePage - 1)) !== 1) {
            $prevPageQuery['page-number'] = (int) $prevPageNumber;
        } else {
            unset($prevPageQuery["page-number"]);
        }

        /**
         * Create first link
         */
        $firstPageQuery = $params;
        $firstPageQuery['page-number'] = 1;
        $pagination['first'] = [
            "url" => sanitize_url($currentUrl . '?' . http_build_query($firstPageQuery)),
            "show" => ($pageRangeStart > 1) && $options['first_last_buttons'] != 0,
            "text" => __('First', 'otys-jobs-apply'),
            "icon" => false
        ];

        if ($options['first_last_buttons'] == 2) {
            $pagination['first']['icon'] = $options['first_icon'];
        }

        /**
         * Create prev link
         */
        $httpPrevQuery = http_build_query($prevPageQuery);
        $prevUrl = ($httpPrevQuery !== "") ? $currentUrl . '?' . $httpPrevQuery : $currentUrl;
        $pagination["prev"] = [
            "url" => sanitize_url($prevUrl),
            "show" => ($activePage !== 1 && $totalPages !== 1) && $options['previous_next_buttons'] != 0 ? true : false,
            "text" => __('Prev', 'otys-jobs-apply'),
            "icon" => false
        ];

        if ($options['previous_next_buttons'] == 2) {
            $pagination["prev"]["icon"] = $options['prev_icon'];
        }

        /**
         * Create next link
         */
        $nextPageQuery = $params;
        $nextPageQuery['page-number'] = intval($activePage) + 1;
        $pagination["next"] = [
            "url" => sanitize_url($currentUrl . '?' . http_build_query($nextPageQuery)),
            "show" => ($activePage < $totalPages && intval($activePage + 1) <= $totalPages) && $options['previous_next_buttons'] != 0 ? true : false,
            "text" =>  __('Next', 'otys-jobs-apply'),
            "icon" => false
        ];

        if ($options['previous_next_buttons'] == 2) {
            $pagination['next']['icon'] = $options['next_icon'];
        }

        /**
         * Create last link
         */
        $lastPageQuery = $params;
        $lastPageQuery['page-number'] = $totalPages;
        $pagination['last'] = [
            "url" => sanitize_url($currentUrl . '?' . http_build_query($lastPageQuery)),
            "show" => ($pageRangeEnd < $totalPages) && $options['first_last_buttons'] != 0,
            "icon" => false,
            "text" => __('Last', 'otys-jobs-apply')
        ];

        if ($options['first_last_buttons'] == 2) {
            $pagination['last']['icon'] = $options['last_icon'];
        }

        return (array) $pagination;
    }
}