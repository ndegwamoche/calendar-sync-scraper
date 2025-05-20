<?php

namespace Calendar_Sync_Scraper;

class Logger
{

    public function __construct()
    {
        register_activation_hook(__FILE__, [$this, 'create_log_table']);
    }

    public function create_log_table()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'calendar_scraper_log';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            run_time DATETIME NOT NULL,
            status VARCHAR(20) NOT NULL,
            message TEXT
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function log($status, $message = '')
    {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'calendar_scraper_log',
            [
                'run_time' => current_time('mysql'),
                'status' => $status,
                'message' => $message
            ]
        );
    }

    public function get_logs($limit = 20)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'calendar_scraper_log';
        return $wpdb->get_results("SELECT * FROM $table ORDER BY run_time DESC LIMIT %d", OBJECT, $limit);
    }
}
