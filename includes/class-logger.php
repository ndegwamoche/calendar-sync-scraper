<?php

namespace Calendar_Sync_Scraper;

class Logger
{
    private $wpdb;
    private $logs_table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        $this->logs_table = $wpdb->prefix . 'cal_sync_logs';
    }

    public function start_log($season_id, $region_id, $age_group_id, $pool_id)
    {
        $this->wpdb->insert(
            $this->logs_table,
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
        return $this->wpdb->insert_id;
    }

    public function update_log($log_id, $total_matches = 0, $error_message = null, $status = 'failed')
    {
        $data = ['total_matches' => $total_matches];
        if ($error_message) {
            $data['error_message'] = $error_message;
            $data['status'] = $status;
        }
        $this->wpdb->update(
            $this->logs_table,
            $data,
            ['id' => $log_id]
        );
    }

    public function complete_log($log_id)
    {
        $this->wpdb->update(
            $this->logs_table,
            [
                'close_datetime' => current_time('mysql'),
                'status' => 'completed'
            ],
            ['id' => $log_id]
        );
    }

    public function log($status, $message = '')
    {
        $this->wpdb->insert(
            $this->logs_table,
            [
                'start_datetime' => current_time('mysql'),
                'status' => $status,
                'message' => $message
            ]
        );
    }

    /**
     * Fetch logs from the database
     *
     * @param int $limit Number of logs to fetch
     * @return array Array of log objects
     */
    public function get_log_info()
    {
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM {$this->logs_table} ORDER BY start_datetime DESC LIMIT %d", 30),
            OBJECT
        );

        if (empty($results)) {
            return [];
        } else {
            wp_send_json_success($results);
        }
    }
}
