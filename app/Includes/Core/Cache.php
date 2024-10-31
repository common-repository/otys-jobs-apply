<?php

/**
 * Cache using Wordpress Transients API
 * @package otys-jobs-apply
 */

namespace Otys\OtysPlugin\Includes\Core;

final class Cache extends Base
{
    public function __construct()
    {
        // Make checklogs available via hook
        Hooks::addAction('otys_cache_cron', static::class, 'deleteExpired');

        // Runn add cron on init
        Hooks::addAction('init', static::class, 'addCron');
    }

    public static function addCron()
    {
        if (!wp_next_scheduled('otys_cache_cron')) {
            wp_schedule_event(time(), 'daily', 'otys_cache_cron');
        }
    }

    /**
     * Add cache using set_transient from the Wordpress Transient API
     *
     * @param  mixed $modulename is used for to keep track at which module the cache belongs to
     * @param  mixed $args is used to keep track if a call is the same as the previous appendix can
     * for example be a key generated based on the arguments given
     * @param  mixed $time
     *
     * @return void
     *
     * @since 1.0.0
     */
    public static function add($name, $request, $value, int $time = 600): void
    {
        if ($value === null) {
            return;
        }

        $cacheObject = [
            'request' => $request,
            'value' => $value
        ];

        // $compressedCacheObject = static::compress($cacheObject);
        $compressedCacheObject = $cacheObject;

        set_transient(self::getTransientName($name, $request), $compressedCacheObject, $time);
    }

    /**
     * Generates a appendix based on the value. Expects array or string
     *
     * @param $value
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function getAppendix($value): string
    {
        if (is_string($value) && !empty($value) && substr($value, 0, 1) == '{') {
            // If the first chracter is a curly bracket we assume the value is json
            $appendix = sha1(sanitize_key($value));
        } else if(is_array($value)) {
            $appendix = sha1(sanitize_key(json_encode($value)));
        } else {
            $appendix = sanitize_key(sha1($value));
        }


        return $appendix;
    }

    /**
     * Get transient (cache) if it exists otherwise return false
     *
     * @param  string $modulename - module name used as indentifier for the transient
     * @param  string $action - action within the module used as indentifier for the transient
     * @param  mixed $appendix - actions for example a list of arguments used as indentifier for the transient
     * may either be a string or an array
     *
     * @return mixed
     * @since 1.0.0
     */
    public static function get(string $name, $request)
    {
        // Check if a transientname can be generated if not return false if true check if the transient exists
        if ($transientName = self::getTransientName($name, $request)) {
            if ($transient = get_transient($transientName)) {
                // return static::decompress($transient);
                return $transient;
            }

            return false;
        }

        return false;
    }

    /**
     * Generate transient name based on the module name, action and a appendix
     * a module name is atleast required to generate a transient name
     *
     * @param  string $name
     * @param  mixed $value
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function getTransientName(string $name, $request = null): string
    {
        $transient_prefix = 'otys_' .explode('_', get_locale())[0];

        $transientName = $transient_prefix;

        if ($name !== '') {
            $transientName .= '_' . $name;
        }

        if ($request !== null) {
            $transientName .= '_' . self::getAppendix($request);
        }

        if ($transient_prefix === $transientName) {
            return false;
        }

        return $transientName;
    }


    /**
     * Get all plugin cache
     *
     * Returns all cache belonging to the plugin
     *
     * @return array
     * @since 1.0.0
     */
    public static function getList(array $args = [])
    {
        global $wpdb;

        $args = wp_parse_args($args, [
            'keywords' => [],
            'includeExpired' => true,
            'limit' => 20,
            'pagenum' => 1,
            'pagination' => false
        ]);

        $pagenum = ($args['pagenum'] === 0) ? 1 : $args['pagenum'];
        
        $table_name = $wpdb->options;

        $offset = ( $pagenum - 1 ) * $args['limit'];
        $limit = $args['limit'];
        $total = $wpdb->get_var( "SELECT COUNT(`option`.`option_id`) FROM $table_name as `option`  WHERE `option`.`option_name` LIKE '%transient_otys%'" );
        $totalPages = ceil( $total / $args['limit'] );

        $query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );

        $likes = ['%' . $wpdb->esc_like('transient_otys') . '%'];

        // If table does not exist create the table
        if ($wpdb->get_var( $query ) == $table_name) {
            $sql =  "SELECT `option`.`option_id`, `option`.`autoload`, `option`.`option_name` AS `name`, `option`.`option_value` AS `value`, `expire`.`option_value` AS `expire`
            FROM {$wpdb->options} AS `option`
            LEFT JOIN {$wpdb->options} AS `expire` ON `expire`.option_name =
            REPLACE(`option`.option_name, '_transient_otys', '_transient_timeout_otys')
            WHERE `option`.`option_name` LIKE %s ";

            // Check if we should include expired transients
            if ($args['includeExpired'] === false) {
                $sql .= " AND `expire`.`option_value` > UNIX_TIMESTAMP() ";
            }

            // Keyword search
            if (!empty($args['keywords']) && is_array($args['keywords'])) {
                
                $sql .= "AND (";
                
                foreach ($args['keywords'] as $key => $keyword) {
                    $likes[] = '%' . $keyword . '%';
                    if ($key === 0) {
                        $sql .= "`option`.`option_value` LIKE %s ";
                        continue;
                    }
                    $sql .= " OR `option`.`option_value` LIKE %s";
                }
                
                $sql .= ")";
            }
            
            $sql .= " ORDER BY `expire` DESC";
            
            if ($args['pagination'] === true) {
                $sql .= " LIMIT $offset, $limit";
            }

            $prepare = $wpdb->prepare($sql, $likes);

            $searchResult = $wpdb->get_results($prepare, ARRAY_A);

            return [
                'list' => $searchResult,
                'pagination' => [
                    'current_page' => $pagenum,
                    'limit' => 5,
                    'offset' => $offset,
                    'total' => $total,
                    'total_pages' => $totalPages
                ]
            ];
        }

        return [];
    }

    /**
     * Deletes all cache belong to the plugin
     * @return void
     * @since 1.0.0
     */
    public static function deleteAll(): void
    {
        global $wpdb;

        $table_name = $wpdb->options;

        $query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );

        $likes = ['%' . $wpdb->esc_like('transient_otys') . '%'];

        // If table does not exist create the table
        if ($wpdb->get_var( $query ) == $table_name) {
            $sql = "DELETE `option`, `expire`
            FROM {$wpdb->options} AS `option`
            LEFT JOIN {$wpdb->options} AS `expire` ON `expire`.option_name =
            REPLACE(`option`.option_name, '_transient_otys', '_transient_timeout_otys')
            WHERE `option`.`option_name` LIKE %s";

            $prepare = $wpdb->prepare($sql, $likes);
            
            $wpdb->get_results($prepare, ARRAY_A);

            // Call custom action otys_cache_deleted
            do_action('otys_cache', 'delete_all', []);
        }
    }

    /**
     * Deletes all expired cache that belongs to the plugin
     *
     * @return void
     */
    public static function deleteExpired(): void
    {
        global $wpdb;

        $table_name = $wpdb->options;

        $query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );

        $likes = ['%' . $wpdb->esc_like('transient_otys') . '%'];

        // If table does not exist create the table
        if ($wpdb->get_var( $query ) == $table_name) {
            $sql = "DELETE `option`, `expire`
            FROM {$wpdb->options} AS `option`
            LEFT JOIN {$wpdb->options} AS `expire` ON `expire`.option_name =
            REPLACE(`option`.option_name, '_transient_otys', '_transient_timeout_otys')
            WHERE `option`.`option_name` LIKE %s AND `expire`.`option_value` < UNIX_TIMESTAMP()";

            $prepare = $wpdb->prepare($sql, $likes);
            
            $wpdb->get_results($prepare, ARRAY_A);

            // Call custom action otys_cache_deleted
            do_action('otys_cache', 'delete_expired', []);
        }
    }

    /**
     * Search for transient
     *
     * Uses LIKE to find transient and matches the part of the search string
     *
     * Note that this only applies to transients which contain _transient_otys to avoid affecting
     * options and only effect transients of this plugin
     *
     * @param string $search
     * @return array
     * @since 1.0.0
     */
    public static function search(string $transient, $exact = false)
    {
        global $wpdb;

        $search = esc_sql($transient);

        // Check if log tables actually exists
        $table_name = $wpdb->options;
        $query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );

        // Check if table exists then do query
        if ($wpdb->get_var( $query ) == $table_name) {
            $where = $exact ?
            " WHERE `option`.`option_name` LIKE ('%_transient_otys%') AND `option`.`option_name` = '{$search}'"
            : "WHERE `option`.`option_name` LIKE ('%_transient_otys%') AND `option`.`option_name` LIKE '%{$search}%' ";

            $sql  ="
            SELECT `option`.`autoload`, `option`.`option_name` AS `name`, `expire`.`option_value` AS `expire`, `option`.`option_value` AS `value`
            FROM {$wpdb->options} AS `option`
            LEFT JOIN {$wpdb->options} AS `expire` ON `expire`.option_name =
            REPLACE(`option`.`option_name`, '_transient_otys', '_transient_timeout_otys')
            {$where}
            ORDER BY `expire` DESC";

            return $wpdb->get_results($sql, ARRAY_A);
        }

        return [];
    }

    /**
     * Deletes all transients that match the transient
     *
     * Note that this only applies to transients which contain _transient_otys to avoid affecting
     * options and only effect transients of this plugin
     *
     * @param string $transient
     * @return mixed
     * @since 1.0.0
     */
    public static function delete(string $transientName, $exact = false)
    {
        global $wpdb;

        $table_name = $wpdb->options;
        $query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table_name));

        if ($wpdb->get_var($query) != $table_name) {
            return [];
        }

        $transient = esc_sql($transientName);

        $where = $exact
        ? "WHERE `option_name` LIKE ('%_transient_otys%') AND `option_name` = '{$transientName}'"
        : "WHERE `option_name` LIKE ('%_transient_otys%') AND `option_name` LIKE ('%{$transientName}%')";

        $sql = "SELECT * FROM `{$wpdb->options}` {$where}";

        $transients = $wpdb->get_results($sql, ARRAY_A);

        if ($transients === null) {
            return [];
        }

        $deleted = [];

        foreach ($transients as $transient) {
            $name = str_replace('_transient_', '', $transient['option_name']);
  
            $deleted[$name] = delete_transient($name);
        }

        $deletedTransients = array_keys($deleted);

        do_action('otys_cache', 'delete', $deletedTransients);

        return $deleted;
    }
}
