<?php

namespace Otys\OtysPlugin\Models;

use Otys\OtysPlugin\Includes\Core\Cache;
use Otys\OtysPlugin\Includes\Core\Routes;
use Otys\OtysPlugin\Includes\OtysApi;
use WP_Error;

class WebhooksModel extends BaseModel
{
    /**
     * Undocumented function
     *
     * @param array $request
     * @return array
     * @since 1.2.13
     */
    public function deleteInteractionCache(array $request): array
    {
        if (isset($request['result']) && isset($request['result']['data']) && isset($request['result']['data']['answerSetId']) && is_int($request['result']['data']['answerSetId'])) {
            $keywords = [];
            $transients = [];
            $answerSetId = $request['result']['data']['answerSetId'];

            $entities = [];

            $detailRequest = OtysApi::post([
                'method' => 'Otys.Services.FreeInteractionAnswerSetService.getDetail',
                'params' => [
                    $answerSetId,
                    [
                        'uid' => 1,
                        'Bindings' => 1
                    ]
                ]
            ]);

            if (is_wp_error($detailRequest) || !isset($detailRequest['Bindings'])) {
                foreach ($detailRequest['Bindings'] as $binding) {
                    if (isset($binding['uid'])) {
                        $keywords[] = $binding['uid'];
                    }

                    if (isset($binding['entity'])) {
                        $entities[] = $binding['entity'];
                    }
                }
            }

            $publishTransients = Cache::search('vacancyservice_getlist');
            if (!empty($publishTransients)) {
                foreach ($publishTransients as $transient) {
                    $transients[] = $transient;
                }
            }

            $publishTransients = Cache::search('vacancyservice_getdetail');
            if (!empty($publishTransients)) {
                foreach ($publishTransients as $transient) {
                    $transients[] = $transient;
                }
            }
    
            $responses = [];
            foreach ($transients as $transient) {
                $responses[] = Cache::delete($transient['name'], true);
            }

            return $responses;
        }

        return [];
    }
}