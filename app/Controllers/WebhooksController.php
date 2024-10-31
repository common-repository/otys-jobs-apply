<?php

namespace Otys\OtysPlugin\Controllers;

use Otys\OtysPlugin\Includes\Core\Routes;
use Otys\OtysPlugin\Includes\Core\Webhooks;
use Otys\OtysPlugin\Includes\Core\Cache;
use Otys\OtysPlugin\Models\WebhooksModel;

final class WebhooksController extends BaseController
{
    private $model;

    private $webhooks;

    public function __construct()
    {
        parent::__construct();

        $this->model = new WebhooksModel;
        $this->webhooks = new Webhooks();
    }

    /**
     * Entry point for webhook callbacks
     *
     * @return void
     * @since 1.0.0
     */
    public function index()
    {
        // Retrieve sent data
        $data = file_get_contents('php://input');
        $request = json_decode($data, true);

        // Get action slug
        $method = isset($request['request']) ?  str_replace('Otys.Services.', '', $request['request']['method']) : 'none';
        $slug = sanitize_title($method);

        $webhook = $this->webhooks->get($slug);

        // Check if the webhook exists & is callable if so run the callable function
        if ($webhook !== false && array_key_exists('callback', $webhook) && is_callable($webhook['callback'])) {
            $response = call_user_func(
                [$webhook["callback"][0], $webhook['callback'][1]],
                $request
            );

            // Call custom action otys_webhook
            do_action('otys_webhook', $request, $webhook, json_decode($response, true));
            echo $response;
            die();
        }

        // Webhook was not found
        $this->jsonResponse([
            'type' => 'error',
            'code' => 'webhook_action_not_found',
            'message' => 'The given action ' . $slug . ' does not exists.'
        ]);

        return;
    }

    /**
     * Filter transients by keyword
     *
     * @param array $transients
     * @param array $keywords
     * @return array
     */
    public static function filter(array $transients, array $keywords): array
    {
        foreach ($transients as $key => $transient) {
            $match = false;

            foreach ($keywords as $keyword) {
                // Check if the transient contains the vacancy uid
                if (strpos($transient['value'], $keyword)) {
                    $match = true;
                }
            }
            
            if ($match === false) {
                unset($transients[$key]);
            }
        }

        return $transients;
    }

    /**
     * Webhook when interactions is called
     *
     * @param array $request
     * @return mixed
     */
    public function webhookInteractions(array $request)
    {
        $response = $this->model->deleteInteractionCache($request);

        if (!empty($response)) {
            return static::jsonResponse([
                'type' => 'success',
                'code' => 'webhook_interactions',
                'message' => 'Deleted cache',
                'data' => $response
            ], false);
        }

        return static::jsonResponse([
            'type' => 'success',
            'code' => 'webhook_no_response',
            'message' => 'Action was called but no response was given'
        ], false);
    }
    
    /**
     * Webhook when vacancy is updated
     *
     * @param array $request
     * @return mixed
     * @since 1.0.0
     */
    public static function webhookVacancyUpdate(array $request)
    {
        $request = $request['request'];

        // Init vars
        $args =  isset($request['args']) ? $request['args'] : [];
        $identifier = isset($args[1]) ? $args[1] : [];
        $data = isset($args[2]) ? $args[2] : [];
        $keywords = [];
        $transients = [];

        
        // Check what keywords to search for when doing a update
        $keywords[] = $identifier;

        if (isset($data['userEmail'])) {
            $keywords[] = $data['userEmail'];
        }

        $keywordTransiensts = Cache::getList([
            'keywords' => $keywords,
            'includeExpired' => false
        ]);
        if (array_key_exists('list', $keywordTransiensts) && !empty($keywordTransiensts['list'])) {
            foreach ($keywordTransiensts['list'] as $transient) {
                $transients[] = $transient;
            }
        }

        /**
         * Check if current request contains a key published
         * If it does we assume the request was done from the
         * publication widget and the request could be changing
         * something about the publication. Therefore we'll refresh
         * all vacancies list requests
         */
        $publishCacheFound = false;

        if (
            isset($data['published']) ||
            isset($data['publishLanguages']) ||
            isset($data['publishedInShortlist']) ||
            isset($data['publishSupplierPortal']) ||
            isset($data['premiumPublish']) ||
            isset($data['publishedInShortlistWebsites']) ||
            isset($data['publishedWebsites']) ||
            isset($data['defaultWebsiteId']) ||
            isset($data['premiumPostMultiWebsites'])
        ) {
            $publishCacheFound = true;
        }
       
        if ($request['method'] === 'Otys.Services.VacancyService.setExtraFieldValue') {
            $publishCacheFound = true;
        }

        if ($request['method'] === 'Otys.Services.VacancyService.delete' && !empty($keywordTransiensts)) {
            $publishCacheFound = true;
        }

        if ($publishCacheFound) {
            $publishTransients = Cache::search('vacancyservice_getlist');
            
            if (!empty($publishTransients)) {
                foreach ($publishTransients as $transient) {
                    $transients[] = $transient;
                }
            }
        }

        $responses = [];
        foreach ($transients as $transient) {
            $responses[] = Cache::delete($transient['name'], true);
        }
    
        /**
         * Field names that trigger the vacancy overview cache refresh
         */
        return static::jsonResponse([
            'type' => 'success',
            'code' => 'webhook_vacancy_update',
            'message' => 'Deleted cache',
            'data' => $responses
        ], false);
    }

    /**
     * Webhook when match criteria is updated
     *
     * @param array $request
     * @return mixed
     */
    public static function webhookMatchCriteriaUpdate(array $request)
    {
        if (!isset($request['request']) || !is_array($request['request'])) {
            /**
             * Field names that trigger the vacancy overview cache refresh
             */
            return static::jsonResponse([
                'type' => 'success',
                'code' => 'webhook_match_criteria_update',
                'message' => 'Deleted cache',
                'data' => ''
            ], false);
        }
        
        $request = $request['request'];

        // Init vars
        $args =  array_key_exists('args', $request) ? $request['args'] : [];
        $identifier = array_key_exists(1, $args) ? $args[1] : [];
        $keywords = [];

        if (isset($args[1])) {
            $identifier = $args[1];
            
            // Check what keywords to search for when doing a update
            if ($request['method'] === 'Otys.Services.MatchCriteriaService.update') {
                $ids = explode ('_', $identifier);
                $keywords[] = $ids[1]; // Use the option search
            } elseif ($request['method'] === 'Otys.Services.MatchCriteriaSettingsService.update') {
                $keywords[] = "C_{$identifier}";
                $keywords[] = "matchCriteria_{$identifier}";
            }
        }
        
        $transientsResult = Cache::getList([
            'keywords' => $keywords,
            'includeExpired' => false
        ]);
        
        $transients = static::filter($transientsResult['list'], $keywords);
        
        $responses = [];
        foreach ($transients as $transient) {
            $responses[] = Cache::delete($transient['name'], true);
        }

        /**
         * Field names that trigger the vacancy overview cache refresh
         */
        return static::jsonResponse([
            'type' => 'success',
            'code' => 'webhook_match_criteria_update',
            'message' => 'Deleted cache',
            'data' => $responses
        ], false);
    }

    /**
     * Webook when questionset is updated
     *
     * @param array $request
     * @return mixed
     */
    public function webhookQuestionsetUpdate(array $request = [])
    {
        $deleted = Cache::delete('questionsetservice');

        /**
         * Field names that trigger the vacancy overview cache refresh
        */
        return static::jsonResponse([
            'type' => 'success',
            'code' => 'webhook_questionset_update',
            'message' => 'Deleted cache',
            'data' => [
                $deleted
            ]
        ], false);
    }

    /**
     * Webhook when CSM is updated
     *
     * @param array $request
     * @return mixed
     */
    public function webhookCsmUpdate(array $request)
    {
        // Init vars
        $request = $request['request'] ?? [];
        $args =  isset($request['args']) ? $request['args'] : [];
        $identifier = $args[1] ?? [];
        $keywords = [];
        
        // If setting SE3452 is updated, delete all cache
        if (array_search('SE3452', $args) !== false) {
            Cache::deleteAll();

            // Communicate new routes to OTYS
            Routes::setOtysUrlFormat();

            return static::jsonResponse([
                'type' => 'success',
                'code' => 'webhook_csm_update',
                'message' => 'Deleted all cache'
            ], false);
        }

        // Check what keywords to search for when doing a update
        $keywords[] = $identifier;

        $transientsResult = Cache::getList([
            'keywords' => $keywords,
            'includeExpired' => false
        ]);

        $transients = static::filter($transientsResult['list'], $keywords);
        
        $responses = [];
        foreach ($transients as $transient) {
            $responses[] = Cache::delete($transient['name'], true);
        }

        /**
         * Field names that trigger the vacancy overview cache refresh
        */
        return static::jsonResponse([
            'type' => 'success',
            'code' => 'webhook_csm_update',
            'message' => empty($responses) ? 'No cache deleted' : 'Deleted cache',
            'data' => $responses
        ], false);
    }
}
