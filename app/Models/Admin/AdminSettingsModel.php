<?php

namespace Otys\OtysPlugin\Models\Admin;

use Otys\OtysPlugin\Includes\OtysApi;
use Otys\OtysPlugin\Models\DocumentsModel;

class AdminSettingsModel extends AdminBaseModel
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Create default documents
     *
     * @return void
     * @since 1.2.1
     */
    public static function createDefaultDocuments(): void
    {
        $languages = OtysApi::getLanguages();

        // Load documents json
        $file = OTYS_PLUGIN . '/documents.json';
        $documentFile = file_get_contents($file, false);

        $currentDocuments = DocumentsModel::getAllWordPressDocuments();

        if ($documentFile !== false) {
            $documentsArray = json_decode($documentFile, true);

            $snippets = [];
            $documents = [];

            /**
             * Create snippets
             */
            foreach ($documentsArray as $documentKey => $document) {
                foreach ($languages as $countryCode => $language) {
                    /**
                     * Check if the there is document information for the current
                     * language and if the correct information has been given.
                     */
                    if (
                        isset($document['content']) &&
                        array_key_exists($countryCode, $document['content']) &&
                        isset($document['content'][$countryCode]['name']) &&
                        isset($document['content'][$countryCode]['content']) &&
                        isset($document['content'][$countryCode]['subject'])
                    ) {
                        // Find if there are documents with the same name
                        $existingDocument = array_filter($currentDocuments, function ($documentItem) use ($document, $countryCode) {
                            return $documentItem['name'] === $document['content'][$countryCode]['name'];
                        });

                        if (!empty($existingDocument)) {
                            continue;
                        }

                        /**
                         * Create document for language
                         */
                        $documentArgs = [
                            'subject' => $document['content'][$countryCode]['subject'],
                            'defaultOutput' => 'Email',
                            'isGlobal' => true,
                            'defaultFormat' => 'Word',
                            'languageCode' => $countryCode
                        ];

                        //If there are already documents use the first document as parent document for the other languages                         
                        if (!empty($documents) && isset($documents[$documentKey]) && is_array($documents[$documentKey]) && count($documents[$documentKey]) > 0) {
                            $parentDocumentLanguage = array_key_first($documents[$documentKey]);
                            $parentDocumentId = $documents[$documentKey][$parentDocumentLanguage];
                            $multilangOuid = DocumentsModel::getMultiLangId($parentDocumentId, $language);
                            $documentArgs['multiLangOuid'] = $multilangOuid;
                            $documentArgs['parentUid'] = $parentDocumentId;
                        }

                        $documentCreateResponse = DocumentsModel::create($document['content'][$countryCode]['name'], $documentArgs, $countryCode);

                        if ($documentCreateResponse) {
                            $documents[$documentKey][$countryCode] = $documentCreateResponse;

                            /**
                             * Create snippet for the current language
                             */
                            $snippetResponse = DocumentsModel::createSnippet(
                                $document['content'][$countryCode]['name'],
                                $document['content'][$countryCode]['content'],
                                $countryCode
                            );

                            // Check if snippet was created successfully
                            if ($snippetResponse) {
                                // Assign snippet to created snippets
                                $snippets[$documentKey][$countryCode] = $snippetResponse;
                                
                                /**
                                 * Create questions
                                 */
                                if (array_key_exists('questions', $document) && is_array($document['questions']) && is_int($snippetResponse)) {
                                    // Loop through questions and try to add them to the created snippet
                                    foreach ($document['questions'] as $question) {
                                        // Check if information for question is given required 'name', 'code', 'question'
                                        if (
                                            array_key_exists('name', $question) && array_key_exists('code', $question) && array_key_exists('question', $question) &&
                                            is_string($question['name']) && is_string($question['code']) && is_string($question['question'])
                                        ) {
                                            DocumentsModel::createQuestion($snippetResponse, $question['name'], $question['code'], $question['question'], 'textField', $countryCode);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            foreach ($documents as $documentKey => $documentData) {
                foreach ($documentData as $countryCode => $documentId) {
                    /**
                     * Assign snippet to document
                     */
                    if ($documentCreateResponse) {
                        DocumentsModel::addSnippetToDocument($snippets[$documentKey][$countryCode], $documentId, $countryCode);
                    }
                }
            }

            update_option('otys_option_created_standard_documents', true);
        }
    }
}
