<?php

namespace Otys\OtysPlugin\Controllers;

use Otys\OtysPlugin\Controllers\BaseController;
use Otys\OtysPlugin\Includes\Core\Routes;
use Otys\OtysPlugin\Models\Shortcodes\AuthModel;

/**
 * Vacancies application page controller
 *
 * @since 2.0.44
 */
class AuthController extends BaseController
{
    /**
     * Candidate logout callback
     *
     * @return void
     */
    public function logout(): void
    {
        AuthModel::logout();

        if (isset($_GET['redirect'])) {
            wp_redirect($_GET['redirect']);
            exit();
        }

        wp_redirect(trailingslashit(get_home_url()));
        exit();
    }

    /**
     * Candidate candidatePortal callback
     *
     * Redirects to the candidate portal or login page if the user is not logged in
     *
     * @return void
     */
    public function candidatePortal(): void
    {
        $loggedInUser = AuthModel::getUser();

        if (!$loggedInUser) {
            $candidateLoginUrl = Routes::get('candidate_login');
            $candidateProfileUrl = Routes::get('candidate_portal');

            wp_redirect($candidateLoginUrl . '?redirect=' . esc_url($candidateProfileUrl));
            exit();
        }

        $auth = new AuthModel();
        $auth->getUserLoginLink($loggedInUser);

        wp_redirect($auth->getUserLoginLink($loggedInUser));

        exit;
    }
}