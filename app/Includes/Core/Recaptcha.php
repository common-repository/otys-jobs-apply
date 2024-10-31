<?php

namespace Otys\OtysPlugin\Includes\Core;

class Recaptcha extends Base
{
    private $secretKey;

    public function __construct()
    {
        // Get secret recaptcha key and assign it
        $this->secretKey = get_option('otys_option_recaptcha_secret_key');
    }

    /**
     * Verify recaptcha sent in POST
     *
     * Checks if the filled in recaptcha is correct by making a request to the Google API
     *
     * @return bool
     */
    public function verify(string $recaptchaResponse): bool
    {
        // Sanatize the recaptchaResponse
        $recaptchaResponse = sanitize_text_field($recaptchaResponse);

        $remoteip = is_string($_SERVER['REMOTE_ADDR']) ? preg_replace("/[^.:()a-zA-Z0-9\/]/", "", $_SERVER['REMOTE_ADDR']) : '';

        // Send a request to the Google API using the recaptcha response from the form
        $apiResponse = wp_remote_post(
            'https://www.google.com/recaptcha/api/siteverify?secret=' . $this->secretKey . '&response=' . $recaptchaResponse . '&remoteip=' . $remoteip,
            [
                'headers' => [
                    'content-type' => 'application/x-www-form-urlencoded'
                ]
            ]
        );

        // Check if the API request was succesfull, if not we will return false
        if (!is_wp_error($apiResponse)) {
            // Get the repsonse body
            $recaptchaResponseBody = json_decode($apiResponse["body"], JSON_OBJECT_AS_ARRAY);

            // Check if the recaptcha verification was succesfull and check if the score is proper if so we return true
            if ($recaptchaResponseBody["success"] && (isset($recaptchaResponseBody["score"]) && $recaptchaResponseBody["score"] >= get_option('otys_option_recaptcha_threshold'))) {
                return true;
            }
        }

        return false;
    }
}
