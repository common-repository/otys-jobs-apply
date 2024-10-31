<?php

namespace Otys\OtysPlugin\Models;

use Otys\OtysPlugin\Models\BaseModel;
use Otys\OtysPlugin\Includes\OtysApi;
use WP_Error;

class FilesModel extends BaseModel
{
    public static function upload(array $file = [])
    {
        if (
            !isset($file['tmp_name']) ||
            empty($file['tmp_name']) ||
            !isset($file['type']) ||
            empty($file['type']) ||
            !isset($file['name']) ||
            empty($file['name'])
        ) {
            return [];
        }

        $response = $file;
        $response['error'] = false;

        $boundary = wp_generate_password(24);
        $body = '';
        $body .= '--' . $boundary . "\r\n";
        $body .= 'Content-Disposition: form-data; name="file"; filename="' . basename($file['name']) . "\"\r\n";
        $body .= 'Content-Type: ' . $file['type'] . "\r\n\r\n";
        $body .= file_get_contents($file['tmp_name']) . "\r\n";
        $body .= '--' . $boundary . "\r\n";
        $body .= 'Content-Disposition: form-data; name="sessionId"' . "\r\n";
        $body .= 'Content-Type: application/json' . "\r\n\r\n";
        $body .= OtysApi::getSession() . "\r\n";
        $body .= '--' . $boundary . '--' . "\r\n";

        $args = [
            'body' => $body,
            'timeout' => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'cookies' => [],
            'headers' => [
                'accept' => 'application/json',
                // The API returns JSON
                'content-type' => 'multipart/form-data;boundary=' . $boundary,
                // Set content type to multipart/form-data
            ]
        ];

        $response = wp_remote_post('https://ows.otys.nl/fileService.php', $args);

        if (is_wp_error($response) || !isset($response['body'])) {
            return [];
        }

        $responseBody = json_decode($response['body'], true);

        return reset($responseBody);
    }

    /**
     * Format number to bytes
     *
     * @param float $bytes
     * @param integer $force_unit
     * @param string $format
     * @param boolean $si
     * @return string
     */
    public static function formatBytes(float $bytes, $force_unit = 0, string $format = '', bool $si = TRUE): string
    {
        // Format string
        $format = ($format === '') ? '%01.2f %s' : (string) $format;

        // IEC prefixes (binary)
        if ($si == FALSE or strpos($force_unit, 'i') !== FALSE) {
            $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
            $mod = 1024;
        }
        // SI prefixes (decimal)
        else {
            $units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB');
            $mod = 1000;
        }

        // Determine unit to use
        if (($power = array_search((string) $force_unit, $units)) === FALSE) {
            $power = ($bytes > 0) ? floor(log($bytes, $mod)) : 0;
        }

        return sprintf($format, $bytes / pow($mod, $power), $units[$power]);
    }

    /**
     * Validate document upload
     *
     * @param array $file
     * @return WP_Error
     */
    public static function validateDocumentUpload(array $upload, bool $required = false)
    {
        $errors = new WP_Error();

        // Check if file has valid file upload information
        if (
            !array_key_exists('name', $upload) ||
            !array_key_exists('type', $upload) ||
            !array_key_exists('tmp_name', $upload) ||
            !array_key_exists('error', $upload) ||
            !array_key_exists('size', $upload)
        ) {
            $errors->add('otys_document', __('Invalid file upload.', 'otys-jobs-apply'));

            return $errors;
        }

        // Make upload same as multiupload so we can loop through it the same way
        if (!is_array($upload['name'])) {
            $upload = array_map(function($value) {
                return [$value];
            }, $upload);
        }

        foreach ($upload['name'] as $fileKey => $name) {
            $file = [
                'name' => $upload['name'][$fileKey],
                'type' => $upload['type'][$fileKey],
                'tmp_name' => $upload['tmp_name'][$fileKey],
                'error' => $upload['error'][$fileKey],
                'size' => $upload['size'][$fileKey]
            ];

            // Check if the request is invalid
            if (!isset($file['error']) || is_array($file['error'])) {
                $errors->add('otys_document_' . $fileKey, sprintf(__('%s is an invalid file.', 'otys-jobs-apply'), $file['name']));
                continue;
            }

            // Check errors
            switch ($file['error']) {
                case UPLOAD_ERR_OK:
                    $maxFilesize = 8000000;
                    $formattedFileSize = static::formatBytes($maxFilesize);
        
                    if ($file['size'] > $maxFilesize) {
                        $errors->add(
                            'otys_document_' . $fileKey,
                            sprintf(
                                __('File %1$s exceeds filesize limit. Max file size is %2$s.', 'otys-jobs-apply'),
                                $file['name'],
                                $formattedFileSize
                            )
                        );
                    }
        
                    // Allowed extensions
                    $allowedExtensions = [
                        'image/jpeg',
                        'image/pjpeg',
                        'image/png',
                        'image/x-png',
                        'image/gif',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/msword',
                        'text/plain',
                        'text/rtf',
                        'application/pdf',
                        'application/octet-stream'
                    ];
        
                    // Check upload file type
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
        
                    if (
                        ($extension = array_search(
                            $finfo->file($file['tmp_name']),
                            $allowedExtensions,
                            true
                        )) === false
                    ) {
                        $errors->add('otys_document_' . $fileKey, sprintf(__('File extension of %s is not allowed.', 'otys-jobs-apply'), $file['name']));
                    }

                    break;
                case UPLOAD_ERR_NO_FILE:
                    if ($required) {
                        $errors->add('otys_document_' . $fileKey, __('No file uploaded', 'otys-jobs-apply'));
                    }
                    break;
                case UPLOAD_ERR_INI_SIZE:
                    $errors->add('otys_document_' . $fileKey, sprintf(__('File %s exceeds filesize limit.', 'otys-jobs-apply'), $file['name']));
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $errors->add('otys_document_' . $fileKey, sprintf(__('File %s exceeds filesize limit.', 'otys-jobs-apply'), $file['name']));
                    break;
                default:
                    $errors->add('otys_document_' . $fileKey, __('Something went wrong while uploading your documents.', 'otys-jobs-apply'));
                    break;
            }

            if ($errors->has_errors()) {
                continue;
            }

        }

        return $errors;
    }

    /**
     * Attach document to dossier
     *
     * @param array $options
     * @return WP_Error|array
     * @since 1.0.0
     */
    public static function attach(array $options = [])
    {
        $options = array_merge([
            'subject' => __('no subject', 'otys-jobs-apply'),
            'private' => false,
            'customerRightsLevel' => 0,
            'alwaysOnTop' => false,
            'description' => '',
            'fileUid' => false
        ], $options);

        return OtysApi::post([
            'method' => 'Otys.Services.AttachedDocumentsService.add',
            'params' => [
                $options
            ]
        ]);
    }
}