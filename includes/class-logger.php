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

    public function start_log($season_id, $region_id, $age_group_id, $pool_id, $session_id)
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
                'total_matches' => 0,
                'session_id' => $session_id
            ]
        );
        return $this->wpdb->insert_id;
    }

    public function update_log($log_id, $total_matches = 0, $error_message = null, $status = 'failed')
    {
        $this->wpdb->update(
            $this->wpdb->prefix . 'cal_sync_logs',
            [
                'total_matches' => $total_matches,
                'error_message' => serialize($error_message), // Serialize the array of messages
                'status' => $status,
                'close_datetime' => ($status === 'completed' || $status === 'failed') ? current_time('mysql') : null,
            ],
            ['id' => $log_id],
            ['%d', '%s', '%s', '%s'],
            ['%d']
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

    public function complete_scraper_log()
    {
        check_ajax_referer('calendar_scraper_nonce', '_ajax_nonce');

        $session_id = sanitize_text_field($_POST['session_id']);

        if (empty($session_id)) {
            wp_send_json(['success' => false, 'data' => ['message' => 'Session ID is required.']]);
            return;
        }

        $this->wpdb->update(
            $this->logs_table,
            [
                'close_datetime' => current_time('mysql'),
                'status' => 'completed'
            ],
            ['session_id' => $session_id]
        );

        wp_send_json(['success' => true, 'data' => ['message' => 'Log completed successfully.']]);
        wp_die();
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
