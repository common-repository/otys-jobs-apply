<?php

namespace Otys\OtysPlugin\Includes\Core;

use Otys\OtysPlugin\Includes\Core\Hooks;

class AdminMessages extends Base
{
    protected static $messages = [];

    public function __construct()
    {
        Hooks::addAction('admin_notices', __CLASS__, 'display', '10', 1);
    }

    /**
     * Undocumented function
     *
     * @param string $message
     * @param string $type Options:
     * - success
     * - warning
     * - error
     * - info
     * @return void
     */
    public static function add(string $message = '', string $type = 'notice-success'): void
    {
        static::$messages[] = [
            'message' => $message,
            'type' => $type
        ];
    }

    /**
     * Displays messages in admin panel
     *
     * @return void
     */
    public static function display()
    {
        foreach (static::$messages as $message) {
            echo "<div class=\"notice notice-{$message['type']} is-dismissible\">
                <p>{$message['message']}</p>
            </div>"; 
        }
    }
}
