<?php

namespace Otys\OtysPlugin\Models;

use Otys\OtysPlugin\Includes\OtysApi;

class DocumentsModel extends BaseModel
{
    /**
     * Get document
     *
     * @since 2.0.0
     * @param integer $id Document ID
     * @param array $args Arguments used to fill in merge fields
     * @return array | \WP_Error
     */
    public static function get(int $uid, array $args = [], $relativeToCurrentLocale = true)
    {
        $args = wp_parse_args($args, [
            'relatedEntities' => [],
            'answers' => []
        ]);

        if ($relativeToCurrentLocale) {
            if (($translatedUid = static::getDocumentTranslationUid($uid, OtysApi::getLanguage())) !== 0) {
                $uid = $translatedUid;
            }
        }

        $owsRequest =  OtysApi::post([
            'method' => 'Otys.Services.Sdv2StandardDocumentService.mergeDocument',
            'params' => [
                $uid,
                $args
            ]
        ]);

        return $owsRequest;
    }

    /**
     * Get all WordPress documents for current client
     *
     * @return array All WordPress documents for current client
     */
    public static function getAllWordPressDocuments()
    {
        // Get available languages for client
        $languages = OtysApi::getLanguages();

        // Get all WordPress documents for each language
        $documentsByLanguage = [];

        foreach ($languages as $languageKey => $language) {
            $documentsByLanguage[$languageKey] = static::getList($languageKey, true);
        }

        // Merge all documents into one array with uid as key
        $result = [];

        foreach ($documentsByLanguage as $documents) {
            foreach ($documents as $document) {
                $result[$document['uid']] = $document;
            }
        }

        return $result;
    }

    /**
     * Get list of documents
     *
     * @since 2.0.0
     * @return array
     */
    public static function getList($language = true, $wordPressOnly = false): array
    {
        $conditions = [
            [
                'type' => 'COND',
                'field' => 'defaultOutput',
                'op' => 'EQ',
                'param' => 'Email'
            ]
        ];

        if ($wordPressOnly) {
            $conditions[] = [
                'type' => 'COND',
                'field' => 'name',
                'op' => 'LIKE',
                'param' => '%WordPress%'
            ];
        }

        $documents = OtysApi::post([
            'method' => 'Otys.Services.Sdv2StandardDocumentService.getListEx',
            'params' => [
                [
                    'what' => [
                        'uid' => 1,
                        'name' => 1,
                        'isGlobal' => 1,
                        'entryDateTime' => 1,
                        'addedByUserId' => 1,
                        'addedByUser' => 1,
                        'modifiedDateTime' => 1,
                        'modifiedByUserId' => 1,
                        'modifiedByUser' => 1,
                        'category' => 1,
                        'categoryId' => 1,
                        'defaultOutput' => 1
                    ],
                    'limit' => 500,
                    'offset' => 0,
                    'getTotalCount' => true,
                    'sort' => [
                        'name' => 'ASC'
                    ],
                    'condition' => [
                        'type' => 'AND',
                        'items' => $conditions
                    ]
                ]
            ]
        ], true, $language);

        if (!is_wp_error($documents) && is_array($documents) && isset($documents['listOutput'])) {
            return $documents['listOutput'];
        }

        return [];
    }

    /**
     * Get translation of document
     *
     * @since 2.0.0
     * @param integer $uid
     * @return \WP_Error|string
     */
    public static function getDocumentTranslationUid(int $uid, $languageCode)
    {
        $translationUid = OtysApi::post([
            'method' => 'Otys.Services.Sdv2StandardDocumentService.fetchLanguageVersionUid',
            'params' => [
                $uid,
                $languageCode
            ]
        ]);

        if (is_wp_error($translationUid)) {
            return $translationUid;
        }

        return (string) $translationUid;
    }

    /**
     * Check if a document exists
     *
     * @since 2.0.0
     * @param string $name
     * @return int
     */
    public static function exists(string $name, string $language = ''): int
    {
        $document = OtysApi::post([
            'method' => 'Otys.Services.Sdv2StandardDocumentService.getListEx',
            'params' => [
                [
                    'what' => [
                        'uid' => 1
                    ],
                    'limit' => 1,
                    'offset' => 0,
                    'getTotalCount' => true,
                    'sort' => [
                        'uid' => 'DESC'
                    ],
                    'condition' => [
                        'type' => 'OR',
                        'items' => [
                            [
                                'type' => 'COND',
                                'field' => 'name',
                                'op' => 'LIKE',
                                'param' => "%{$name}%"
                            ]
                        ]
                    ]
                ]
            ]
        ], false, $language);

        if (is_wp_error($document) || $document['totalCount'] === 0) {
            return 0;
        }
        
        return $document['listOutput'][0]['uid'];
    }

    /**
     * Get document Multilangual OUID
     *
     * @since 2.0.0
     * @param int $documentId
     * @return string
     */
    public static function getMultiLangId(int $documentId, string $language = ''): string
    {
        $document = OtysApi::post([
            'method' => 'Otys.Services.Sdv2StandardDocumentService.fetchMultiLangOuid',
            'params' => [
                $documentId
            ]
        ], false, $language);

        if (is_wp_error($document) || !is_string($document)) {
            return '';
        }

        return $document;
    }

    /**
     * Create a new document
     *
     * @since 2.0.0
     * @param string $name
     * @param bool $unique
     * @return mixed
     */
    public static function create(string $name = '', array $args = [], string $language = ''): int
    {
        $args = wp_parse_args($args, [
            "name" => "{$name}"
        ]);

        $document = OtysApi::post([
            'method' => 'Otys.Services.Sdv2StandardDocumentService.add',
            'params' => [
                $args
            ]
        ], false, $language);

        if (is_wp_error($document)) {
            return 0;
        }

        // Need to update default output since it's not insertable
        OtysApi::post([
            'method' => 'Otys.Services.Sdv2StandardDocumentService.update',
            'params' => [
                $document,
                [
                    'defaultOutput' => $args['defaultOutput']
                ]
            ]
        ], false, $language);

        return (int) $document;
    }

    /**
     * Create a new snippet or returns id of existing snippet
     *
     * @since 2.0.0
     * @param string    $name       Name of snippet
     * @param bool      $unique     Make sure that name is unique
     * @return int
     */
    public static function createSnippet(string $name = '', string $content = '', string $language = ''): int
    {
        $document = OtysApi::post([
            'method' => 'Otys.Services.Sdv2SnippetService.add',
            'params' => [
                [
                    'title' => $name,
                    'categoryId' => 0,
                    'content' => $content,
                    'languageCode' => $language
                ]
            ]
        ], false, $language);

        if (is_wp_error($document) || (!is_string($document) && !is_int($document))) {
            return 0;
        }
        
        return intval($document);
    }

    /**
     * Create a question for a snippet
     *
     * @since 2.0.0
     * @param integer $snippetId
     * @param string $name
     * @param string $code
     * @param string $question
     * @param string $type
     * @return int
     */
    public static function createQuestion(int $snippetId, string $name, string $code, string $question = '', string $type = 'textField', string $language = ''): int
    {
        $questions = OtysApi::post([
            'method' => 'Otys.Services.Sdv2QuestionService.getList',
            'params' => [
                [
                    'condition' => [
                        'type' => 'COND',
                        'field' => 'snippetId',
                        'op' => 'EQ',
                        'param' => $snippetId
                    ],
                    'sort' => [
                        'rank' => 'ASC'
                    ],
                    'limit' => 200
                ]
            ]
        ], false, true);
        
        // If the question already exists use the question
        if (!is_wp_error($questions) && is_array($questions)) {
            foreach ($questions as $question) {
                if ($question['code'] === $code) {
                    return $question['uid'];
                }
            }
        }

        $question = OtysApi::post([
            'method' => 'Otys.Services.Sdv2QuestionService.add',
            'params' => [
                [
                    'name' => $name,
                    'code' => $code,
                    'snippetId' => $snippetId,
                    'question' => ($question !== '' ? $question : $code),
                    'type' => $type
                ]
            ]
        ], false, $language);

        if (is_wp_error($question) || (!is_int($question) || !is_string($question))) {
            return 0;
        }

        return (int) $question;
    }

    /**
     * Add snippet to document
     *
     * @since 2.0.0
     * @param string $snippetId
     * @param int $documentId
     * @param string $language
     * @return array
     */
    public static function addSnippetToDocument(int $snippetId, int $documentId, string $language = ''): array
    {
        $snippet = OtysApi::post([
            'method' => 'Otys.Services.Sdv2StandardDocumentSnippetService.addRecursive',
            'params' => [
                $snippetId,
                $documentId
            ]
        ], false, $language);

        if(is_wp_error($snippet) || !is_array($snippet)) {
            return [];
        }

        return $snippet;
    }
}