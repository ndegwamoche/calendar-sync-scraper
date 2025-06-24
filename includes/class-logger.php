<?php

/**
 * Logger class for the Calendar Sync Scraper plugin.
 *
 * This class handles logging operations for the plugin, including starting, updating,
 * and retrieving logs from the database.
 *
 * @package Calendar_Sync_Scraper
 */

namespace Calendar_Sync_Scraper;

/**
 * Class Logger
 *
 * Manages logging functionality for tracking scraper activities and errors.
 */
class Logger
{
    /**
     * @var wpdb WordPress database access object.
     */
    private $wpdb;

    /**
     * @var string Database table name for logs, with WordPress prefix.
     */
    private $logs_table;

    /**
     * Constructor.
     *
     * Initializes the database connection and sets the logs table name.
     */
    public function __construct()
    {
        // Access global WordPress database object.
        global $wpdb;
        $this->wpdb = $wpdb;

        // Define the logs table name with WordPress prefix.
        $this->logs_table = $wpdb->prefix . 'cal_sync_logs';
    }

    /**
     * Start a new log entry in the database.
     *
     * @param int|string $season_id The ID of the season being processed.
     * @param int|string $region_id The ID of the region being processed.
     * @param int|string $age_group_id The ID of the age group being processed.
     * @param int|string $pool_id The ID of the pool being processed.
     * @param string $session_id Unique identifier for the scraping session.
     * @param string|array $message Optional initial message for the log.
     * @return int The ID of the newly created log entry.
     */
    public function start_log($season_id, $region_id, $age_group_id, $pool_id, $session_id, $message = '')
    {
        // Insert a new log entry into the database.
        $this->wpdb->insert(
            $this->logs_table,
            [
                'start_datetime' => current_time('mysql'), // Current time in MySQL format
                'season_id' => $season_id,                 // Season ID
                'region_id' => $region_id,                 // Region ID
                'age_group_id' => $age_group_id,           // Age group ID
                'pool_id' => $pool_id,                     // Pool ID
                'status' => 'running',                     // Initial status
                'total_matches' => 0,                      // Initial match count
                'session_id' => $session_id,               // Session identifier
                'error_message' => is_array($message) ? serialize($message) : $message // Serialize array messages
            ]
        );
        return $this->wpdb->insert_id; // Return the ID of the new log entry
    }

    /**
     * Update an existing log entry in the database.
     *
     * @param int $log_id The ID of the log entry to update.
     * @param int $total_matches The total number of matches processed (default: 0).
     * @param string|array|null $error_message Error message or messages (default: null).
     * @param string $status The status of the log ('running', 'completed', 'failed'; default: 'failed').
     */
    public function update_log($log_id, $total_matches = 0, $error_message = null, $status = 'failed')
    {
        // Update the log entry with new data.
        $this->wpdb->update(
            $this->logs_table,
            [
                'total_matches' => $total_matches, // Update match count
                'error_message' => is_array($error_message) ? serialize($error_message) : $error_message, // Serialize array messages
                'status' => $status,               // Update status
                'close_datetime' => ($status === 'completed' || $status === 'failed') ? current_time('mysql') : null, // Set close time if completed or failed
            ],
            ['id' => $log_id],                     // Where condition
            ['%d', '%s', '%s', '%s'],              // Data format
            ['%d']                                 // Where format
        );
    }

    /**
     * Insert a simple log entry for general status messages.
     *
     * @param string $status The status of the log entry (e.g., 'error', 'info').
     * @param string $message The message to log (default: empty string).
     */
    public function log($status, $message = '')
    {
        // Insert a new log entry with minimal data.
        $this->wpdb->insert(
            $this->logs_table,
            [
                'start_datetime' => current_time('mysql'), // Current time in MySQL format
                'status' => $status,                       // Log status
                'message' => $message                      // Log message
            ]
        );
    }

    /**
     * Fetch logs from the database.
     *
     * @param int $limit Number of logs to fetch (default: 30).
     * @return array Array of log objects or empty array if none found.
     */
    public function get_log_info()
    {
        // Retrieve recent logs, ordered by start datetime.
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM {$this->logs_table} ORDER BY start_datetime DESC LIMIT %d", 30),
            OBJECT
        );

        if (empty($results)) {
            return []; // Return empty array if no logs found
        } else {
            // Send JSON success response with log data
            wp_send_json_success($results);
        }
    }
}
