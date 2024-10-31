<?php

namespace Otys\OtysPlugin\Controllers\Admin;

use Otys\OtysPlugin\Includes\Core\Cache;

use Otys\OtysPlugin\Models\Admin\AdminCacheModel;
use Otys\OtysPlugin\Models\FilesModel;

class AdminCacheController extends AdminBaseController
{
    /**
     * On init admin page
     *
     * @return void
     */
    public static function onInit()
    {
        if (isset($_GET['delete_cache']) && $_GET['delete_cache'] === 'all') {
            Cache::deleteAll();
            wp_redirect(admin_url('admin.php?page=otys_cache'));
            exit;
        }

        if (isset($_GET['delete_cache']) && $_GET['delete_cache'] === 'expired') {
            Cache::deleteExpired();
            wp_redirect(admin_url('admin.php?page=otys_cache'));
            exit;
        }
    
        if (isset($_GET['delete_cache'])) {
            Cache::delete(sanitize_title($_GET['delete_cache']), true);
            wp_redirect(admin_url('admin.php?page=otys_cache'));
            exit;
        }

        if (isset($_GET['refresh_cache'])) {
            wp_redirect(admin_url('admin.php?page=otys_cache'));
            exit;
        }
    }

    public function __construct()
    {
        parent::__construct();

        $this->model = new AdminCacheModel();
    }

    /**
     * Inex page
     *
     * @return void
     */
    public function index()
    {        
        $pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
        
        $cache = Cache::getList([
            'pagenum' => $pagenum,
            'pagination' => true
        ]);

        // $cacheTotal = Cache::getList([
        //     'pagination' => false
        // ]);

        $size = AdminCacheModel::getSize();

        $this->parseArgs('size', $size === null ? 0 : FilesModel::formatBytes($size));
        $this->parseArgs('plugin_cache', $cache['list']);
        $this->parseArgs('pagination', $cache['pagination']);
    }
}
