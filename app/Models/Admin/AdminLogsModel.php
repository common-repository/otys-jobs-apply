<?php

namespace Otys\OtysPlugin\Models\Admin;

use Otys\OtysPlugin\Includes\OtysApi\OtysApi;

class AdminLogsModel extends AdminBaseModel
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get total logs table size. Size excludes index size.
     *
     * @return null|string
     */
    public static function getSize()
    {
        global $wpdb;
        $table_name = "{$wpdb->base_prefix}otys_log";
        $size = $wpdb->get_var(
            "SELECT sum(row_size)
            from (
              select 
                char_length(id)+
                char_length(request)+
                char_length(response)+
                char_length(timestamp)
              as row_size 
              from $table_name
            ) as tbl1"
        );


        return $size;
    }
}
