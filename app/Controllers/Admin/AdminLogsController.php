<?php

namespace Otys\OtysPlugin\Controllers\Admin;

use Otys\OtysPlugin\Includes\Core\Logs;
use Otys\OtysPlugin\Models\Admin\AdminLogsModel;
use Otys\OtysPlugin\Models\FilesModel;

class AdminLogsController extends AdminBaseController
{
    public static function onInit()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'delete_all') {
            Logs::deleteLogs();

            wp_redirect(admin_url('admin.php?page=otys_logs'));
            exit;
        
        }
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;

        $logs = Logs::getList($pagenum);
        
        $size = AdminLogsModel::getSize();

        $this->parseArgs('size', $size === null ? 0 : FilesModel::formatBytes($size));
        $this->parseArgs('logs', $logs['list']);
        $this->parseArgs('pagination', $logs['pagination']);
    }
}
