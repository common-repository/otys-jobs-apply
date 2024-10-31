<?php

namespace Otys\OtysPlugin\Includes\Core;

class Logs extends Base
{
    public function __construct()
    {
        // Make checklogs available via hook
        Hooks::addAction('otys_log_cron', static::class, 'checklogs');

        // Runn add cron on init
        Hooks::addAction('init', static::class, 'addCron');
    }

    /**
     * Create logs table
     *
     * @return mixed
     * @since 1.0.0
     */
    public static function createTable()
    {
        global $wpdb;

        // set the default character set and collation for the table
        $charset_collate = $wpdb->get_charset_collate();

        // Check that the table does not already exist before continuing
        $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}otys_log` (
        `id` INT(11) NULL AUTO_INCREMENT,
        `request` TEXT NULL,
        `response` TEXT NULL,
        `timestamp` INT(11) NULL,
        INDEX `id` (`id`)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta($sql);
        $is_error = empty($wpdb->last_error);

        return $is_error;
    }

    /**
     * Delte table
     *
     * @return mixed
     * @since 1.0.0
     */
    public static function deleteTable()
    {
        global $wpdb;

        $query = "DROP TABLE IF EXISTS `{$wpdb->base_prefix}otys_log`";

        $wpdb->query($query);

        $is_error = empty($wpdb->last_error);

        return $is_error;
    }

    /**
     * Add log related crons
     *
     * @return void
     * @since 1.0.0
     */
    public static function addCron()
    {
        if (!wp_next_scheduled('otys_log_cron')) {
            wp_schedule_event(time(), 'daily', 'otys_log_cron');
        }
    }

    /**
     * Add request and response to LOG
     *
     * @param mixed $request array or JSON string
     * @param mixed $response array or JSON string
     * @return mixed
     * @since 1.0.0
     */
    public static function add($request, $response)
    {
        global $wpdb;

        $request = !is_string($request) ? json_encode($request) : $request;
        $response = !is_string($response) ? json_encode($response) : $response;

        $compressedRequest = static::compress($request);
        $compressedResponse = static::compress($response);

        $logCountquery = "select count(*) from {$wpdb->base_prefix}otys_log";
        $amountOfLogs = $wpdb->get_var($logCountquery);

        if ($amountOfLogs > 150) {
            $logDeleteQuery = "delete from {$wpdb->base_prefix}otys_log order by id asc limit 1";
            $wpdb->query($logDeleteQuery);
        }

        return $wpdb->insert("{$wpdb->base_prefix}otys_log", [
            'request' => $compressedRequest,
            'response' => $compressedResponse,
            'timestamp' => strtotime(current_time('mysql'))
        ]);
    }

    /**
     * Get list of logs
     *
     * @return array
     */
    public static function getList(int $pagenum = 1, int $limit = 20): array
    {
        global $wpdb;
        
        $pagenum = ($pagenum === 0) ? 1 : $pagenum;
        
        $table_name = "{$wpdb->base_prefix}otys_log";

        $offset = ( $pagenum - 1 ) * $limit;
        $total = $wpdb->get_var( "SELECT COUNT(`id`) FROM $table_name" );
        $totalPages = ceil( $total / $limit );

        // Check if log tables actually exists
        $query = $wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->esc_like( $table_name ));

        // If table does not exist create the table
        if (!$wpdb->get_var( $query ) == $table_name) {
            static::createTable();
        }

        // Get log results
        $sql = "SELECT * FROM `{$wpdb->base_prefix}otys_log` ORDER BY `id` DESC LIMIT $offset, $limit";

        $result =  $wpdb->get_results(
            $sql,
            ARRAY_A
        );

        $uncompressedList = array_map(function($log) {
            $log['request'] = static::decompress($log['request']);
            $log['response'] = static::decompress($log['response']);
            return $log;
        }, $result);

        return [
            'list' => $uncompressedList,
            'pagination' => [
                'current_page' => $pagenum,
                'limit' => 5,
                'offset' => $offset,
                'total' => $total,
                'total_pages' => $totalPages
            ]
        ];
    }

    /**
     * Delete all logs
     *
     * @return mixed
     */
    public static function deleteLogs()
    {
        global $wpdb;

        $sql  = "DELETE FROM `{$wpdb->base_prefix}otys_log`";

        return $wpdb->query(
            $sql,
            ARRAY_A
        );
    }

    /**
     * Check logs
     *
     * Removes logs which are older than 1 day
     *
     * @return mixed
     */
    public static function checkLogs()
    {
        global $wpdb;

        $sql  = "DELETE FROM `{$wpdb->base_prefix}otys_log` WHERE timestamp < (UNIX_TIMESTAMP() - 86400)";

        return $wpdb->query(
            $sql,
            ARRAY_A
        );
    }
}
