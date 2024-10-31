<?php

namespace Otys\OtysPlugin\Includes\Core;

/**
 * Manage session data stored in the session of the candidate
 * 
 * @since 2.0.0
 */
class ApplicationSessions
{
    /**
     * Stores default values
     *
     * @since 2.0.0
     * @var array
     */
    private static array $defaultValues = [
        'vacancy_uid' => 0,
        'candidate_uid' => '',
        'procedure_uid' => '',
        'completed' => false,
        'form_data' => [],
        'confirmationcode' => 0
    ];

    /**
     * encodeCode
     *
     * @param integer $code
     * @param array $postData
     * @return string
     */
    public static function getConfirmationCodeHash(int $code, string $email): string
    {
        return sha1($code . $email);
    }

    /**
     * Save confirmation code in session
     *
     * @since 2.0.0
     * @param string $vacancyUid
     * @param integer $code
     * @return void
     */
    public static function setConfirmationcode(string $vacancyUid, int $code, string $email): void
    {
        $_SESSION['otys_applications'][$vacancyUid]['confirmationcode'] = static::getConfirmationCodeHash($code, $email);
    }

    /**
     * Reset session confirmation code
     *
     * @since 2.0.0
     * @param string $vacancyUid
     * @return void
     */
    public static function resetConfirmationcode(string $vacancyUid): void
    {
        unset($_SESSION['otys_applications'][$vacancyUid]['confirmationcode']);
    }

    /**
     * Get confirmation code from session
     *
     * @since 2.0.0
     * @param string $vacancyUid
     * @return mixed
     */
    public static function getConfirmationcode(string $vacancyUid): string
    {
        return isset($_SESSION['otys_applications'][$vacancyUid]['confirmationcode']) ? $_SESSION['otys_applications'][$vacancyUid]['confirmationcode'] : false;
    }

    /**
     * Add application data to session
     *
     * @since 2.0.0
     * @param string $uid
     * @param array $data 
     * [
     * string vacancy_uid,
     * string candidate_uid,
     * string procedure_uid,
     * bool completed,
     * array form_data
     * ]
     * @return void
     */
    public static function add(string $vacancyUid, array $data): void
    {
        $vacancyUid = $vacancyUid === '' ? 'open-application' : $vacancyUid;

        $defaultValues = static::$defaultValues;

        // Always add the vacancy uid
        $defaultValues['vacancy_uid'] = $vacancyUid;

        // Get existing values if they exist
        $existingValues = static::get($vacancyUid);
        
        // Combine existing values with the default values
        $defaultValues = array_replace_recursive($defaultValues, $existingValues);

        // Make sure the data only accepts the keys provided in the default values
        $data = array_filter($data, function($key) use ($defaultValues) {
            return array_key_exists($key, $defaultValues);
        }, ARRAY_FILTER_USE_KEY);

        // Replace default values with the new values
        $data = array_replace_recursive($defaultValues, $data);

        // Add data to the session
        $_SESSION['otys_applications'][$vacancyUid] = $data;
    }

    /**
     * Get application session data
     *
     * @since 2.0.0
     * @param string $uid
     * @return array
     * ['uid' => [
     * string vacancy_uid,
     * string candidate_uid,
     * string procedure_uid,
     * bool completed,
     * array form_data
     * ]]
     */
    public static function get(string $vacancyUid = ''): array
    {
        $vacancyUid = $vacancyUid === '' ? 'open-application' : $vacancyUid;

        $sessionData =
            isset($_SESSION['otys_applications']) &&
            array_key_exists($vacancyUid, $_SESSION['otys_applications']) ?
            $_SESSION['otys_applications'][$vacancyUid] : static::$defaultValues;

        return (array) $sessionData;
    }

    /**
     * Get data of specific question from specific vacancy
     *
     * @since 2.0.0
     * @param string $vacancyUid
     * @param integer $questionId
     * @return mixed
     */
    public static function getQuestion(string $vacancyUid, string $questionId)
    {
        $vacancyUid = $vacancyUid === '' ? 'open-application' : $vacancyUid;

        if (
            isset($_SESSION['otys_applications']) &&
            array_key_exists($vacancyUid, $_SESSION['otys_applications']) &&
            array_key_exists('form_data', $_SESSION['otys_applications'][$vacancyUid]) &&
            array_key_exists($questionId, $_SESSION['otys_applications'][$vacancyUid]['form_data'])
        ) {
            
            return $_SESSION['otys_applications'][$vacancyUid]['form_data'][$questionId];
        }

        return [];
    }

    /**
     * Unset application session data
     *
     * @since 2.0.0
     * @return void
     */
    public static function reset(): void
    {
        unset($_SESSION['otys_applications']);
    }

    /**
     * Filter callback for custom filter
     *
     * @since 2.0.0
     * @param string $vacancyUid
     * @return array
     */
    public static function filterCallback($vacancyUid = ''): array
    {
        $vacancyUid = !is_string($vacancyUid) ? '' : $vacancyUid;

        return array_filter(static::get($vacancyUid), function($value) {
            return array_key_exists('completed', $value) && $value['completed'];
        });
    }
}