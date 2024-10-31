<?php

/**
 * Base contains logic shared between all classes
 */

namespace Otys\OtysPlugin\Includes\Core;
use Otys\OtysPlugin\Includes\OtysApi;

use WP_Error;

abstract class Base
{
    /**
     * Saves errors occured in this model
     *
     * @var WP_Error
     */
    protected $errors;

    public function __construct()
    {
        $this->errors = new WP_Error;
    }

    /**
     * Return errors
     *
     * @return WP_Error
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Check if has errors
     *
     * @return boolean
     */
    public function hasErrors(): bool
    {
        return $this->errors->has_errors();
    }

    /**
     * Store all intances, used to keep track of all instances created
     *
     * @since 1.0.0
     */
    protected static $instances = array();

    /**
     * Provides access to a Single instance of a module using the singleton pattern
     *
     * @return self
     * @since 1.0.0
     */
    public static function getInstance(): self
    {
        $className = get_called_class();

        if (!isset(self::$instances[ $className ])) {
            self::$instances[ $className ] = new $className();
        }

        return self::$instances [ $className ];
    }

    /**
     * Sanitize UTM tags
     *
     * @param mixed $utmValue
     * @return mixed
     */
    public static function sanitizeUTM($utmValue)
    {
        if (is_array($utmValue)) {
            foreach ($utmValue as $utm => $value) {
                if (is_string($value)) {
                    $utmValue[$utm] = (string) preg_replace('/[^a-zA-Z0-9._-]/', '', $value);
                } else {
                    $utmValue[$utm] = '';
                }
            }

            return (array) $utmValue;
        } else {
            if (!is_string($utmValue)) {
                return '';
            }

            return (string) preg_replace('/[^a-zA-Z0-9._-]/', '', $utmValue);
        }
    }

    /**
     * Get UTM
     *
     * @return WP_Error|array
     */
    public static function getUTM()
    {
        $userUtmTags = [];

        // Try to get utm tags from session
        if (isset($_SESSION['utm_tags']) && is_array($_SESSION['utm_tags'])) {
            $userUtmTags = $_SESSION['utm_tags'];
        }

        // If no utm tags found in session try to get them from cookies
        if (empty($userUtmTags)) {
            $cookieUtmTagNames = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content'];

            $userUtmTags = [];

            foreach ($cookieUtmTagNames as $utmTagName) {
                if (isset($_COOKIE[$utmTagName])) {
                    $userUtmTags[str_replace('utm_' , '', $utmTagName)] = static::sanitizeUTM($_COOKIE[$utmTagName]);
                }
            }
        }

        // If utm tags are found in session or cookies we sanitize them
        if (!empty($userUtmTags) && is_array($userUtmTags)) {
            $utmTags = filter_var_array($userUtmTags, [
                'content' => [
                    'filter' => FILTER_CALLBACK,
                    'options' => function ($value) {
                        if (!is_string($value)) {
                            return false;
                        }
                  
                        return static::sanitizeUTM($value);
                    },
                ],
                'medium' => [
                    'filter' => FILTER_CALLBACK,
                    'options' => function ($value) {
                        if (!is_string($value)) {
                            return false;
                        }

                        return static::sanitizeUTM($value);
                    },
                ],
                'source' => [
                    'filter' => FILTER_CALLBACK,
                    'options' => function ($value) {
                        if (!is_string($value)) {
                            return false;
                        }
                        return static::sanitizeUTM($value);
                    },
                ],
                'campaign' => [
                    'filter' => FILTER_CALLBACK,
                    'options' => function ($value) {
                        if (!is_string($value)) {
                            return false;
                        }
                        return static::sanitizeUTM($value);
                    }
                ]
            ], false);

            return $utmTags;
        }

        return new WP_Error('utm_tags', 'No utm tags found.');
    }

    /**
     * Sanatize OUID
     *
     * @param mixed $uid
     * @return string
     */
    public static function sanitizeOUID($uid): string
    {
        if (is_string($uid)) {
            return preg_replace('/[^a-zA-Z0-9-_]/', '', $uid);
        }

        return '';
    }
    
    /**
     * Return values as array
     *
     * @param mixed $value
     * @return array
     */
    public static function arrayValue($value): array
    {
        // Convert values to array and add to filtered atts
        if (is_string($value)) {
            $value = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
            return explode(' ', $value);
        }

        return [];
    }

    // /**
    //  * Get OTYS site ID
    //  *
    //  * @return integer
    //  */
    // public static function getSiteId(): int
    // {
    //     $websiteOption = intval(get_option('otys_option_website', 1));
        
    //     // If website option is 1 we can return 1 because it's the default value
    //     if ($websiteOption === 1) {
    //         return 1;
    //     }

    //     // Get websites from API from current client
    //     $websites = OtysApi::getwebsites();

    //     // If website option is 0 or not set we can return 1 because it's the default value
    //     if ($websiteOption === 0 || !isset($websites[$websiteOption])) {
    //         return 1;
    //     }

    //     return $websiteOption;
    // }

    /**
     * Format array of files to readable array
     *
     * @param array $files
     * @return array
     */
    public static function reArrayFiles(array $files): array
    {
        return array_map(function($file_post) {
            $isMulti = is_array($file_post['name']);
            $file_count = $isMulti ? count($file_post['name']) : 1;
            $file_keys = array_keys($file_post);

            $file_ary = [];
            for ($i = 0; $i < $file_count; $i++)
                foreach ($file_keys as $key)
                    if ($isMulti)
                        $file_ary[$i][$key] = $file_post[$key][$i];
                    else
                        $file_ary[$i][$key] = $file_post[$key];

            return $file_ary;
        }, $files);
    }

     /**
     * Store data
     *
     * @var array
     */
    private static $storage = [];

    /**
     * Get storage key
     *
     * @param mixed $options
     * @return string
     */
    public static function getStorageKey($options): string
    {
        $keyValue = !is_string($options) && is_array($options) ? json_encode($options) : $options;

        if (!$keyValue || !is_string($keyValue)) {
            return '';
        }

        $key = sha1($keyValue);

        return $key;
    }

    /**
     * Store data
     * 
     * @param mixed $options
     * @param array $data
     * @return string
     */
    public static function storeData($options, $data): string
    {
        $key = static::getStorageKey($options);

        if ($key === '') {
            return '';
        }

        static::$storage[$key] = $data;

        return $key;
    }

    /**
     * Get data from storage
     *
     * @param mixed $key
     * @return mixed
     */
    public static function getData($options)
    {
        $key = static::getStorageKey($options);

        if (array_key_exists($key, static::$storage)) {
            return static::$storage[$key];
        }

        return false;
    }

    /**
     * Compress data
     *
     * @param mixed $data
     * @return string
     */
    protected static function compress($data): string
    {
        return base64_encode(gzcompress(json_encode($data), 9));
    }

    /**
     * Decompress data
     *
     * @param string $data
     * @return mixed
     */
    protected static function decompress(string $data)
    {
        return json_decode(gzuncompress(base64_decode($data)), true);
    }
}
