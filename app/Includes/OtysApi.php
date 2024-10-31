<?php

/**
 * OTYS Api class to connect with OTYS Web Services
 *
 * @since 2.0.0
 */

namespace Otys\OtysPlugin\Includes;

use WP_Error;

use Otys\OtysPlugin\Includes\Core\Base;

use Otys\OtysPlugin\Includes\Core\Cache;
use Otys\OtysPlugin\Includes\Core\Logs;

final class OtysApi extends Base
{
    /**
     * API End point
     *
     * @since 2.0.0
     * @var string
     */
    // protected static $handler = "https://testing.otysapp.com/jservice.php";
    protected static $handler = "https://ows.otys.nl/jservice.php";

    /**
     * The max amount a API request can take in seconds
     *
     * @since 2.0.0
     * @var integer
     */
    protected static int $timeOut = 15;

    /**
     * Stores API session
     *
     * @since 2.0.0
     * @var string|WP_Error
     */
    protected static $session = '';

    /**
     * Stores client code
     *
     * @since 2.0.0
     * @var string
     */
    protected static string $clientCode = '';

    /**
     * Stores client code
     *
     * @since 2.0.0
     * @var string
     */
    protected static int $clientId = 0;

    /**
     * Save if session is valid
     *
     * @since 2.0.0
     * @var boolean
     */
    protected static bool $validSession;

    protected static array $exludedLogMethods = [
        'Otys.Services.WebusersService.setNewPassword'
    ];

    /**
     * Get API Session
     *
     * @since 2.0.0
     * @return string|WP_Error
     */
    public static function getSession()
    {
        if (static::$session === '') {
            $session = static::login();

            static::$session = $session;
        }

        return static::$session;
    }

    private static $retries = 0;

    /**
     * Get OTYS images upload url
     *
     * @since 2.0.0
     * @return string
     */
    public static function getImageUploadUrl(): string
    {
        $clientCode = static::getClientCode();

        if ($clientCode) {
            return 'https://www.yourit.nl/' . $clientCode . '/_images_upload/';
        }

        return '';
    }

    /**
     * Get the client code
     *
     * @since 2.0.0
     * @return string
     */
    public static function getClientCode(): string
    {
        if (static::$clientCode !== '') {
            return static::$clientCode;
        }

        if (!empty($apiUser = static::getApiUser()) && isset($apiUser['clientCode'])) {
            static::$clientCode = $apiUser['clientCode'];
        }

        return static::$clientCode;
    }

    /**
     * Get the client code
     *
     * @since 2.0.0
     * @return string
     */
    public static function getClientId(): string
    {
        if (static::$clientId !== 0) {
            return static::$clientId;
        }

        if (!empty($apiUser = static::getApiUser()) && isset($apiUser['clientId'])) {
            static::$clientId = $apiUser['clientId'];
        }

        return static::$clientId;
    }

    /**
     * Login and retrieve session using API key
     *
     * @since 2.0.0
     * @return string|WP_Error
     */
    public static function login(string $apiKey = '')
    {
        $apiKey = $apiKey === '' ? get_option('otys_option_api_key', '') : $apiKey;

        if ($apiKey === '') {
            return new WP_Error('invalid_api_key', __('API key provided is invalid.', 'otys-jobs-apply'));
        }

        // Get API Key using the clients API key
        $body = [
            'jsonrpc' => '2.0',
            'method' => "loginByUid",
            'params' => [$apiKey],
            'id' => 1
        ];

        if ($cacheData = Cache::get('loginByUid', $body)) {
            if (isset($cacheData['value'])) {
                return $cacheData['value'];
            } else {
                Cache::deleteAll();
            }
        }

        // Create OWS request
        $request = [
            'method' => 'POST',
            'body' => json_encode($body),
            'headers' => 'Content-Type: application/json\r\n' .
            'Accept: application/json\r\n',
            'timeout' => static::$timeOut,
            'httpversion' => '1.0'
        ];

        // Check there's cache for the current post
        $apiResponse = wp_remote_post(
            static::$handler,
            $request
        );

        Logs::add($request, $apiResponse);

        $result = static::getResult($apiResponse);

        if (is_wp_error($result) || !is_string($result)) {
            return new WP_Error('invalid_api_key', __('API key provided is invalid.', 'otys-jobs-apply'));
        }

        Cache::add('loginByUid', $body, $result, 28800);

        static::$session = $result;

        return $result;
    }

    /**
     * Get response
     *
     * @since 2.0.0
     * @param mixed $response
     * @return array|WP_Error
     */
    private static function getResult($response)
    {
        // Get the body of the response
        $body = static::extractResponseBody($response);

        // If session seems to be incorrect throw WP_Error
        if (
            (array_key_exists('error', $body) && array_key_exists('message', $body['error'])) &&
            ($body['error']['message'] == 'Access denied; please login again' || $body['error']['message'] == 'Access denied; please login first')
        ) {
            return new WP_Error('invalid_session', __('Session provided with the request is invalid.', 'otys-jobs-apply'), $response);
        } else if (array_key_exists('error', $body)) {
            $message = array_key_exists('message', $body['error']) ? $body['error']['message'] : __('No error message available.', 'otys-jobs-apply');

            return new WP_Error('error', $message);
        }

        // Get the result from the response
        $result = static::extractResult($response);

        return $result;
    }

    /**
     * Extract the body from the a API result
     *
     * @since 2.0.0
     * @return array
     */
    public static function extractResponseBody($apiResult = null): array
    {
        if ($apiResult !== null && is_array($apiResult) && array_key_exists('body', $apiResult)) {
            $jsonBody = json_decode($apiResult["body"], true);

            if ($jsonBody === null) {
                return [];
            }
            return $jsonBody;
        }

        return [];
    }

    /**
     * Extract the result from the a API result
     *
     * @since 2.0.0
     * @return array
     */
    public static function extractResult($apiResult = null)
    {
        if (!empty($body = self::extractResponseBody($apiResult))) {
            if (array_key_exists('result', $body)) {
                return $body['result'];
            }
        }

        return [];
    }

    /**
     * Post OWS request
     * 
     * @since 2.0.0
     * @param array $post                   ['method' => ''], ['params' => '']
     * @param boolean|int $cache            True, False or integer time to expire in seconds
     * @param boolean|string $languageWrap  True, False or string language code
     * @param array $requestArgs            Args supplied to wp_remote_post
     * @return array|string|WP_Error
     */
    public static function post(
        array $postData = [],
        $cache = false,
        $languageWrap = true,
        array $requestArgs = [],
        bool $log = true
    ) {
        // Return wp error if session is error
        if (is_wp_error(static::getSession())) {
            return static::getSession();
        }

        $post = array_replace_recursive([
            'params' => []
        ], $postData);

        $cacheTime = is_int($cache) && $cache > 1 ? $cache : 604800; // Default 7 days

        // Object used for caching
        $cacheObject = [
            'post' => $post,
            'cache' => $cache,
            'languageWrap' => $languageWrap
        ];

        // Used to store cache name
        $cacheName = '';

        if ($post['method'] === 'Otys.Services.MultiService.execute') {
            // Loop through multiple posts
            foreach ($post['params'] as $paramKey => $pr) {
                foreach ($pr as $pk => $p) {
                    // If post has params array add session
                    if (array_key_exists('args', $p) && is_array($p['args'])) {
                        array_unshift($post['params'][$paramKey][$pk]['args'], static::getSession());
                        
                        // Add method to cache name
                        if (array_key_exists('method', $p) && is_string($p['method'])) {
                            $cacheName .= strtolower(str_replace('.', '_', str_replace('Otys.Services.', '', $p['method'])));
                        }
                    }
                }
            }
        } else {
            // If post has params array add session
            if (array_key_exists('params', $post) && is_array($post['params'])) {
                array_unshift($post['params'], static::getSession());
            }

            // Add method to cache name
            if (array_key_exists('method', $post) && is_string($post['method'])) {
                $cacheName .= strtolower(str_replace('.', '_', str_replace('Otys.Services.', '', $post['method'])));
            }
        }

        // Try to get data from memory
        if (($memoryData = static::getData($cacheObject)) !== false) {
            return $memoryData;
        }

        if ($cache && $cacheData = Cache::get($cacheName, $cacheObject)) {
            if (array_key_exists('value', $cacheData)) {
                return $cacheData['value'];
            }
        }

        // Prepare request body
        $request = [
            "jsonrpc" => "2.0",
            "method" => $post['method'],
            "params" => $post['params'],
            "id" => 1
        ];

        // If language wrap should be used wrap request body in language wrapper
        if ($languageWrap && $post['method'] !== 'Otys.Services.MultiService.execute') {
            $request = [
                "jsonrpc" => "2.0",
                "method" => "Otys.Services.LanguageService.wrapRequest",
                "params" => [
                    OtysApi::getLanguage($languageWrap),
                    $request
                ],
                "id" => 1
            ];
        }

        // Create OWS request
        $mergedRequestArgs = array_replace_recursive($requestArgs, [
            'method' => 'POST',
            'body' => (is_array($request) ? json_encode($request) : $request),
            'headers' => 'Content-Type: application/json\r\n' .
            'Accept: application/json\r\n',
            'timeout' => 15,
            'httpversion' => '1.0'
        ]);

        $requestResult = wp_remote_post(
            static::$handler,
            $mergedRequestArgs
        );

        if ($log) {
            Logs::add($mergedRequestArgs, $requestResult);
        }

        if (is_wp_error($requestResult)) {
            return $requestResult;
        }

        $result = static::getResult($requestResult);

        // Check result error
        if (is_wp_error($result)) {
            if (is_wp_error(static::$session)) {
                return static::$session;
            }

            // Retry once to refresh session if the session is invalid
            if ($result->get_error_code() === 'invalid_session' && static::$retries <= 1) {
                static::$retries++;

                $session = static::resetSession();

                if (is_wp_error($session)) {
                    return $session;
                }

                $result = static::post($postData, $cache, $languageWrap, $requestArgs);

                if (!is_wp_error($result) && !is_wp_error($requestResult) && $cache) {
                    Cache::add($cacheName, $cacheObject, $result, $cacheTime);
                }

                return $result;
            }
            
            return $result;
        }

        if (!is_wp_error($result) && !is_wp_error($requestResult) && $cache) {
            Cache::add($cacheName, $cacheObject, $result, $cacheTime);
        }

        // Store data in memory
        static::storeData($cacheObject, $result);

        return $result;
    }

    /**
     * Resets session
     *
     * @return string|WP_Error
     */
    public static function resetSession()
    {
        static::$session = '';

        Cache::delete('loginByUid');

        return static::getSession();
    }

    /**
     * Get language based on current locale. Returns supported
     * language within OTYS
     *
     * @since 2.0.0
     * @return string
     */
    public static function getLanguage($lang = ''): string
    {
        if ($lang === '' || !is_string($lang)) {
            if (get_locale() === '') {
                $language = 'en';
            } else {
                $locale = explode('_', get_locale());
                $language = $locale[0];
            }
        } else {
            $language = str_contains($lang, '_') ? explode('_', $lang)[0] : $lang;
        }

        $supportedLanguages = static::getLanguages();
   
        if (isset($supportedLanguages[$language])) {
            return $language;
        }

        $apiUser = static::getApiUser();
        $defaultResponse = $apiUser['defaultContentLanguage'] ?? 'en';

        return $defaultResponse;
    }

    /**
     * Get language by OTYS internal language ID
     *
     * @param integer $languageId
     * @return string
     */
    public static function getLanguageByCodeByOtysLanguageId(int $languageId): string
    {
        $languageMapping = [
            1 => 'nl',
            2 => 'de',
            3 => 'en',
            4 => 'fr',
            5 => 'cs',
            6 => 'es',
            7 => 'en',
            8 => 'sl',
            9 => 'pl',
            10 => 'ro'
        ];

        return isset($languageMapping[$languageId]) ? static::getLanguage($languageMapping[$languageId]) : OtysApi::getLanguage();
    }

    /**
     * Get available languages from client
     *
     * @since 2.0.0
     * @return array
     */
    public static function getLanguages(): array
    {
        $response = static::post([
            'method' => 'Otys.Services.CandidateService.getOptionLists',
            'params' => [
                [
                    "Person.languageCode"
                ]
            ]
        ], true, false);

        if (is_wp_error($response) || !isset($response['cmsLanguages'])) {
            return [];
        }

        if (isset($response['cmsLanguages'])) {
            return $response['cmsLanguages'];
        }

        return [];
    }

    /**
     * Get list of websites
     *
     * @since 2.0.0
     * @return array
     */
    public static function getWebsites(): array
    {
        $result = static::post([
            'method' => 'Otys.Services.VacancyService.getOptionLists',
            'params' => [
                [
                    "publishedWebsites"
                ]
            ]
        ], true, false);

        if (!is_wp_error($result) && !empty($result) && isset($result['publishedWebsites'])) {
            return $result['publishedWebsites'];
        }

        return [];
    }

    /**
     * Get list of websites
     *
     * @return array
     */
    public static function getWebsiteUrls(): array
    {
        $result = static::post([
            'method' => 'Otys.Services.WebsiteService.getList',
            'params' => [
                "what" => [
                    "uid" => 1,
                    "siteId" => 1,
                    "website" => 1
                ]
            ]
        ], true, false);

        if (!is_wp_error($result) && !empty($result)) {
            return $result;
        }

        return [];
    }

    /**
     * Check if session is still valid
     * 
     * @since 2.0.0
     * @return boolean
     */
    public static function check(): bool
    {
        if (isset(static::$validSession)) {
            return static::$validSession;
        }

        if ($checkSession = get_transient('otys_check_session')) {
            return $checkSession;
        }
        
        $check = static::post([
            'method' => 'check'
        ], false, false);
        
        if (is_wp_error($check)) {
            static::$validSession = false;
            delete_transient('otys_check_session');
        } else {
            set_transient('otys_check_session', true, 600);
            static::$validSession = true;
        }

        return static::$validSession;
    }

    /**
     * Get the current logged in user. This returns the user which
     * is linked to the API Key. This user is used to do all the
     * OWS request.
     *
     * @since 2.0.0
     * @return array
     */
    public static function getApiUser(): array
    {
        if (static::getSession() === '') {
            return [];
        }

        $checkResponse = static::post([
            'method' => 'check'
        ], true, false);

        if (!is_wp_error($checkResponse) && isset($checkResponse['clientId'])) {
            // Get client code
            $clientCodeResponse = static::post([
                'method' => 'Otys.Services.CsmService.getValue',
                'params' => [
                    ["DB103"],
                    $checkResponse['clientId']
                ]
            ], true, false);

            if (!is_wp_error($clientCodeResponse)) {
                if (is_array($clientCodeResponse) && array_key_exists('DB103', $clientCodeResponse) && array_key_exists('value', $clientCodeResponse['DB103'])) {
                    $checkResponse['clientCode'] = $clientCodeResponse['DB103']['value'];
                }
            }

            return $checkResponse;
        }

        return [];
    }

    /**
     * Create OWS object
     *
     * @since 2.0.0
     * @param  mixed $array
     * @param  mixed $lastKeyValue
     * @return array
     */
    public static function owsFieldValuesToObject(array $array, $lastKeyValue = []): array
    {
        $lastKeyValue = json_encode($lastKeyValue);

        /**
         * We will be using JSON to build the new array since we can easily build
         * a string based object. Later on we will use this JSON to convert it
         * to an array and return a error
         */
        $output = '{';

        // Loop through the array
        foreach ($array as $arrayKey => $value) {
            /**
             * Check if the current key is the last key since when the current
             * key is the last key we want to assign the user given value to
             * the last property
             */
            if (array_key_last($array) === $arrayKey) {
                // Assign the user given value to the last property
                $output .= '"' . $value . '" : ' . $lastKeyValue;
            } else {
                // Create a new layer in the JSON
                $output .= '"' . $value . '" : {';
            }
        }

        /**
         * Since we didn't close the curly brackets yet we will need to close
         * every layer we created. So we will again loop through the array and
         * add a closing curly bracket for every layer.
         */
        foreach ($array as $arrayKey => $value) {
            $output .= '}';
        }

        // Conver the json to an array and return it
        return json_decode($output, true);
    }
}