<?php

namespace Otys\OtysPlugin\Models\Admin;

use Otys\OtysPlugin\Includes\OtysApi\OtysApi;

class AdminCacheModel extends AdminBaseModel
{
    private $otysApi;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get total size of all otys cache records excluding index size.
     *
     * @return null|string
     */
    public static function getSize()
    {
        global $wpdb;
        $table_name = $wpdb->options;

        $size = $wpdb->get_var(
            "SELECT sum(row_size)
            from (
              select 
                char_length(option_id)+
                char_length(option_name)+
                char_length(option_value)+
                char_length(autoload)
              as row_size 
              from $table_name
            ) as tbl1"
        );

        return $size;
    }
}
