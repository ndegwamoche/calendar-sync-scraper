<?php

namespace Calendar_Sync_Scraper;

class Logger
{
    public function __construct() {}

    public function start_log($season_id, $region_id, $age_group_id, $pool_id)
    {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'cal_sync_logs',
            [
                'start_datetime' => current_time('mysql'),
                'season_id' => $season_id,
                'region_id' => $region_id,
                'age_group_id' => $age_group_id,
                'pool_id' => $pool_id,
                'status' => 'running',
                'total_matches' => 0
            ]
        );
        return $wpdb->insert_id;
    }

    public function update_log($log_id, $total_matches = 0, $error_message = null)
    {
        global $wpdb;
        $data = ['total_matches' => $total_matches];
        if ($error_message) {
            $data['error_message'] = $error_message;
            $data['status'] = 'failed';
        }
        $wpdb->update(
            $wpdb->prefix . 'cal_sync_logs',
            $data,
            ['id' => $log_id]
        );
    }

    public function complete_log($log_id)
    {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'cal_sync_logs',
            [
                'close_datetime' => current_time('mysql'),
                'status' => 'completed'
            ],
            ['id' => $log_id]
        );
    }

    public function log($status, $message = '')
    {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'cal_sync_logs',
            [
                'start_datetime' => current_time('mysql'),
                'status' => $status,
                'message' => $message
            ]
        );
    }

    public function get_logs($limit = 20)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'cal_sync_logs'; // Corrected table name
        return $wpdb->get_results("SELECT * FROM $table ORDER BY start_datetime DESC LIMIT $limit", OBJECT);
    }
}
