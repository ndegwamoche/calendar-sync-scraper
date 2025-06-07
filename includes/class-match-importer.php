<?php

namespace Calendar_Sync_Scraper;

/**
 * Standalone script to import all table tennis matches into Google Calendar at once.
 * Run via CLI: php wp-content/plugins/your-plugin/import_matches.php --season=42024 --venue="Grøndal MultiCenter"
 * Or trigger via WordPress admin hook.
 */

define('WP_USE_THEMES', false);
// Adjust path to wp-load.php based on your actual plugin's location relative to WordPress root.
// For example, if your plugin is in wp-content/plugins/your-plugin-name/, and this file is in its root:
// require_once dirname(__FILE__) . '/../../../wp-load.php';
// If this file is in a subdirectory like 'includes':
// require_once dirname(__FILE__) . '/../../../../wp-load.php';
// The provided path assumes this file is 4 levels deep from WordPress root.
$wp_load_path = dirname(__FILE__) . '/../../../../wp-load.php';
if (!file_exists($wp_load_path)) {
    // Fallback for common plugin root structure if original path fails
    $wp_load_path = dirname(__FILE__) . '/../../../wp-load.php';
    if (!file_exists($wp_load_path)) {
        error_log("FATAL ERROR: wp-load.php not found at expected paths. Please verify 'require_once' path in MatchImporter.php.");
        echo "FATAL ERROR: wp-load.php not found. Please check plugin path configuration.\n";
        exit(1);
    }
}
require_once $wp_load_path;
error_log("wp-load.php loaded successfully.");
echo "wp-load.php loaded successfully.\n";

// Ensure Composer autoloader is correctly referenced
require_once __DIR__ . '/../vendor/autoload.php';

// Ensure your custom classes are loaded if not handled by Composer's autoloader or they are in a different namespace
// For example, if Scraper, Logger, Google_Calendar_Sync are in 'src' directory sibling to 'vendor'
// require_once __DIR__ . '/../src/Scraper.php';
// require_once __DIR__ . '/../src/Logger.php';
// require_once __DIR__ . '/../src/Google_Calendar_Sync.php';

// Use necessary classes
use Calendar_Sync_Scraper\Scraper;
use Calendar_Sync_Scraper\Logger;
use Calendar_Sync_Scraper\Google_Calendar_Sync;

class MatchImporter
{
    private $wpdb;
    private $logger;
    private $scraper;
    private $cache_duration = 24 * 3600; // 24 hours
    private $session_id;
    private $log_id;
    private $log_data; // Holds messages and matches for the current session

    // Database table names
    private $seasons_table;
    private $regions_table;
    private $age_groups_table;
    private $tournament_levels_table;
    private $tournament_pools_table;
    private $colors_table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->logger = new Logger();
        $this->scraper = new Scraper();
        $this->session_id = (string) time(); // Unique session ID for this run
        $this->log_data = [
            'total_matches' => 0,
            'messages' => [], // Accumulated messages for the log entry
            'matches' => [], // Accumulated matches for final Google Calendar insert
        ];

        // Initialize table names with WordPress prefix
        $this->seasons_table = $wpdb->prefix . 'cal_sync_seasons';
        $this->regions_table = $wpdb->prefix . 'cal_sync_regions';
        $this->age_groups_table = $wpdb->prefix . 'cal_sync_age_groups';
        $this->tournament_levels_table = $wpdb->prefix . 'cal_sync_tournament_levels';
        $this->tournament_pools_table = $wpdb->prefix . 'cal_sync_tournament_pools';
        $this->colors_table = $wpdb->prefix . 'cal_sync_colors';

        error_log("MatchImporter constructor initialized. Session ID: {$this->session_id}");
        echo "MatchImporter constructor initialized. Session ID: {$this->session_id}\n";
    }

    /**
     * Main method to run the match import process.
     *
     * @param string $season The season ID to scrape.
     * @param string $venue The venue name to filter matches by.
     */
    public function run($season_cli, $venue_cli)
    {
        set_time_limit(0); // Allow script to run indefinitely
        ignore_user_abort(true); // Continue script even if connection is lost

        // Configuration (can be overridden by CLI args)
        $config = [
            'season' => $season_cli ?? '42024',
            'venue' => $venue_cli ?? 'Grøndal MultiCenter',
            'link_structure' => 'https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#4,{season},{pool},{group},{region},,,,',
            'regions' => [
                ['region_name' => 'Bordtennis Danmark/DT', 'region_value' => 4000, 'region_order' => 1],
                ['region_name' => 'DGI', 'region_value' => 4005],
                ['region_name' => 'BORNHOLM', 'region_value' => 4001],
                ['region_name' => 'DGI Bornholm', 'region_value' => 4006],
                ['region_name' => 'DGI Jylland Nord', 'region_value' => 4008],
                ['region_name' => 'DGI Jylland Syd', 'region_value' => 4007],
                ['region_name' => 'DGI Sjælland', 'region_value' => 4010],
                ['region_name' => 'MIDT (Fyn)', 'region_value' => 4002],
                ['region_name' => 'VEST (Jylland)', 'region_value' => 4003],
                ['region_name' => 'ØST (Sjælland, Lolland F.)', 'region_value' => 4004, 'region_order' => 1],
            ],
            'age_groups' => [
                ['age_group_name' => 'Puslinge', 'age_group_value' => 4001],
                ['age_group_name' => 'Ydr/Ypg', 'age_group_value' => 4002],
                ['age_group_name' => 'Dr/Pg', 'age_group_value' => 4003],
                ['age_group_name' => 'Junior', 'age_group_value' => 4004],
                ['age_group_name' => 'U 21', 'age_group_value' => 4005],
                ['age_group_name' => 'Senior', 'age_group_value' => 4006],
                ['age_group_name' => 'Old Girls', 'age_group_value' => 4007],
                ['age_group_name' => 'Veteran 40', 'age_group_value' => 4008],
                ['age_group_name' => 'Veteran 50', 'age_group_value' => 4009],
                ['age_group_name' => 'Veteran 60', 'age_group_value' => 4010],
                ['age_group_name' => 'Veteran 65', 'age_group_value' => 4011],
                ['age_group_name' => 'Veteran 70', 'age_group_value' => 4012],
                ['age_group_name' => 'Veteran 75', 'age_group_value' => 4013],
                ['age_group_name' => 'Veteran 80', 'age_group_value' => 4014],
                ['age_group_name' => 'Veteran 85', 'age_group_value' => 4015],
                ['age_group_name' => 'Ungdom', 'age_group_value' => 4016],
                ['age_group_name' => 'Minipuslinge', 'age_group_value' => 4017],
                ['age_group_name' => 'Oldies', 'age_group_value' => 4018],
                ['age_group_name' => 'Veteran', 'age_group_value' => 4019],
                ['age_group_name' => 'Åben', 'age_group_value' => 4020],
            ],
        ];

        // Validate config
        $required = ['season', 'venue', 'link_structure', 'regions', 'age_groups'];
        foreach ($required as $key) {
            if (empty($config[$key])) {
                $this->log('error', "Missing required config: $key");
                echo "ERROR: Missing required config: $key\n"; // Echo critical errors to CLI
                exit(1);
            }
        }

        $season = sanitize_text_field($config['season']);
        $venue = sanitize_text_field($config['venue']);
        $link_structure = sanitize_text_field($config['link_structure']);
        $regions = $config['regions'];
        $age_groups = $config['age_groups'];

        // Start main log entry for this session
        $this->log_id = $this->logger->start_log($season, 'all', 'all', 'all_pools', $this->session_id);
        $this->log_data['messages'][] = "Starting session {$this->session_id}, searching venue: {$venue}";
        $transient_key = 'scraper_log_' . $this->session_id;
        set_transient($transient_key, $this->log_data, 3600); // Initial save of log data

        echo "Starting scraping process for season: {$season}, venue: {$venue}\n";
        $this->log('info', "Scraping process initiated for season: {$season}, venue: {$venue}");

        // Keep track of unique matches using match 'no' as key
        $unique_matches = [];

        try {
            // Loop through each region
            foreach ($regions as $region) {
                // Loop through each age group
                foreach ($age_groups as $age_group) {
                    echo "--- Processing Region: {$region['region_name']} ({$region['region_value']}), Age Group: {$age_group['age_group_name']} ({$age_group['age_group_value']}) ---\n";
                    $this->log('info', "Processing combination: Region: {$region['region_name']}, Age Group: {$age_group['age_group_name']}");

                    // Fetch pools for the current region and age group combination from the database
                    // Ensure get_tournament_options is now correctly integrated with your DB for pools
                    $pools_for_combination = $this->get_tournament_options($season, $region['region_value'], $age_group['age_group_value']);

                    if (empty($pools_for_combination)) {
                        $this->log('info', "No pools found in DB for combination: Season=$season, Region={$region['region_name']}, AgeGroup={$age_group['age_group_name']}. Skipping.");
                        echo "No pools found in DB for this combination. Skipping.\n";
                        continue; // Move to the next age_group
                    }

                    echo "Found " . count($pools_for_combination) . " pools in DB for this combination.\n";
                    $this->log('info', "Found " . count($pools_for_combination) . " pools in DB for this combination.");

                    // Scrape each pool found for this combination
                    foreach ($pools_for_combination as $pool) {
                        $this->log('info', "Scraping pool: {$pool['pool_name']} ({$pool['pool_value']}) for Region: {$region['region_name']}, Age Group: {$age_group['age_group_name']}");
                        echo "Attempting to scrape pool: {$pool['pool_name']} ({$pool['pool_value']})\n";

                        $url = str_replace(
                            ['{season}', '{region}', '{group}', '{pool}'],
                            [$season, $region['region_value'], $age_group['age_group_value'], $pool['pool_value']],
                            $link_structure
                        );

                        $cache_key = 'scrape_' . md5($url . '_' . $venue);
                        $matches_for_pool = get_transient($cache_key);

                        if ($matches_for_pool === false || !is_array($matches_for_pool)) {
                            // If not cached, perform the actual scraping using the Scraper instance
                            echo "Scraping from web: {$url}\n";
                            $matches_for_pool = $this->scraper->scrape_results($url, $venue);

                            // Check if scraper returned an error
                            if (isset($matches_for_pool['error'])) {
                                $error_message = $matches_for_pool['error'];
                                $this->log('error', "Error scraping URL {$url}: {$error_message}");
                                echo "ERROR scraping URL {$url}: {$error_message}\n";
                                // Don't cache error results
                            } else {
                                set_transient($cache_key, $matches_for_pool, $this->cache_duration);
                                $this->log('info', "Cached scrape results for {$url}.");
                            }
                        } else {
                            echo "Loading matches from cache for {$url}.\n";
                            $this->log('info', "Loaded matches from cache for {$url}.");
                        }

                        // Process the matches (if any were found/scraped successfully)
                        if (is_array($matches_for_pool) && !isset($matches_for_pool['error'])) {
                            foreach ($matches_for_pool as $match) {
                                // Use match 'no' as a unique key to prevent duplicate matches
                                if (!isset($unique_matches[$match['no']])) {
                                    $unique_matches[$match['no']] = $match;
                                    $this->log_data['matches'][] = $match; // Accumulate for final insert
                                }
                            }
                            $num_found = count($matches_for_pool);
                            $this->log('info', "Found {$num_found} matches for pool {$pool['pool_name']}. Total unique matches so far: " . count($unique_matches));
                            echo "Found {$num_found} matches for pool {$pool['pool_name']}. Total unique: " . count($unique_matches) . "\n";
                            $this->log_data['total_matches'] += $num_found; // Update total count
                        }
                    } // End foreach ($pools_for_combination as $pool)

                    // Quit WebDriver after each Region-AgeGroup combination processed
                    // This will release browser resources more frequently
                    $this->scraper->quit_driver();
                    echo "WebDriver quit after processing Region: {$region['region_name']}, Age Group: {$age_group['age_group_name']}.\n";
                    $this->log('info', "WebDriver quit for combination: Region: {$region['region_name']}, Age Group: {$age_group['age_group_name']}");


                    // Optional: Add a small delay between combinations to be polite or avoid rate limits
                    // sleep(1); // Wait 1 second before moving to next combination
                } // End foreach ($age_groups as $age_group)
            } // End foreach ($regions as $region)

            // --- Final Insert All Matches at Once (at the end of the entire process) ---
            if (!empty($this->log_data['matches'])) {
                echo "\n--- All combinations processed. Inserting all " . count($this->log_data['matches']) . " unique matches to Google Calendar ---\n";
                $this->log('info', "All combinations processed. Inserting all " . count($this->log_data['matches']) . " unique matches to Google Calendar.");

                // When inserting matches for the entire run, the 'pool', 'region', 'age_group' context
                // might not be uniformly available for 'insertMatches'.
                // If Google_Calendar_Sync::insertMatches requires these, you might need to adapt it
                // to handle a batch where each match has its own context, or if it simply uses
                // a generic context for all events from a single run.
                // For simplicity, passing a placeholder context. Your insertMatches method might
                // process each match in the batch based on its own data fields.
                $placeholder_pool_context = ['season_name' => $season, 'pool_name' => 'Batch Sync', 'tournament_level' => 'N/A', 'google_color_id' => '0', 'pool_value' => 'N/A'];
                $placeholder_region_context = ['region_name' => 'Batch Sync', 'region_value' => 'N/A'];
                $placeholder_age_group_context = ['age_group_name' => 'Batch Sync', 'age_group_value' => 'N/A'];

                $this->insert_matches(
                    $this->log_data['matches'],
                    $placeholder_pool_context,
                    $placeholder_region_context,
                    $placeholder_age_group_context,
                    $season
                );
                $this->log('info', "Successfully inserted " . count($this->log_data['matches']) . " matches into Google Calendar.");
                echo "Successfully inserted matches into Google Calendar.\n";
            } else {
                echo "No matches found across all combinations to insert into Google Calendar.\n";
                $this->log('info', "No matches found across all combinations to insert into Google Calendar.");
            }

            // Complete logging for the entire session
            $final_unique_match_count = count($unique_matches);
            $this->log('info', "Scraping completed. Found {$final_unique_match_count} unique matches overall.");
            echo "Scraping completed. Found {$final_unique_match_count} unique matches overall.\n";
            $this->logger->update_log($this->log_id, $final_unique_match_count, implode("\n", $this->log_data['messages']), 'completed');
            delete_transient($transient_key); // Clean up the session transient

        } catch (\Exception $e) { // Use \Exception for root namespace Exception
            $error_message = "Fatal error during importer run: {$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}";
            $this->log('error', $error_message);
            echo "FATAL ERROR: {$e->getMessage()}\n"; // Echo fatal errors to CLI

            // Ensure log is marked as failed
            if ($this->log_id) {
                $this->logger->update_log($this->log_id, $this->log_data['total_matches'], implode("\n", $this->log_data['messages']) . "\n" . $error_message, 'failed');
            } else {
                // If log_id was not set, log directly
                $this->logger->log('error', "Scraper startup error: " . $error_message);
            }
            delete_transient($transient_key);
            exit(1); // Exit with error code
        } finally {
            // Ensure WebDriver is quit at the very end of the script execution
            $this->scraper->quit_driver();
            echo "Final WebDriver quit.\n";
        }
    }

    /**
     * Retrieves pool options from the database for a given season, region, and age group.
     * This method assumes you have a way to query your database for these values.
     * Replace the dummy data with your actual database query.
     *
     * @param string $season The ID of the season.
     * @param string $region_value The value of the region.
     * @param string $age_group_value The value of the age group.
     * @return array An array of pool data. Each pool should have 'pool_value', 'pool_name',
     * 'tournament_level', 'google_color_id', 'season_name'.
     */
    private function get_tournament_options($season, $region_value, $age_group_value)
    {
        echo "DEBUG (get_tournament_options): ENTERING function for season=$season, region=$region_value, age_group=$age_group_value\n";

        $query = $this->wpdb->prepare(
            "SELECT
            tp.id, tp.pool_name, tp.pool_value, tp.tournament_level,
            s.season_name, r.region_name, a.age_group_name, tl.google_color_id
            FROM
            {$this->tournament_pools_table} tp
            JOIN {$this->seasons_table} s ON s.season_value = tp.season_id
            JOIN {$this->regions_table} r ON r.region_value = tp.region_id
            JOIN {$this->age_groups_table} a ON a.age_group_value = tp.age_group_id
            LEFT JOIN {$this->tournament_levels_table} tl
                ON tl.level_name = tp.tournament_level
                AND tl.season_id = tp.season_id
                AND tl.region_id = tp.region_id
                AND tl.age_group_id = tp.age_group_id
            WHERE
                tp.season_id = %s
                AND tp.region_id = %s
                AND tp.age_group_id = %s",
            $season,
            $region_value,
            $age_group_value
        );

        echo "DEBUG (get_tournament_options): Prepared SQL Query:\n" . $query . "\n";

        $results = $this->wpdb->get_results($query, ARRAY_A) ?: [];

        if ($this->wpdb->last_error) {
            echo "DEBUG (get_tournament_options): Database Error: " . $this->wpdb->last_error . "\n";
            $this->log('error', "Database error in get_tournament_options: " . $this->wpdb->last_error . " Query: " . $query);
            // Throw an exception to propagate the error if DB query fails criticaly
            throw new \Exception("Database query failed: " . $this->wpdb->last_error . " for query: " . $query);
        }

        echo "DEBUG (get_tournament_options): Query returned " . count($results) . " pools.\n";
        if (!empty($results)) {
            echo "DEBUG (get_tournament_options): First result: " . json_encode($results[0]) . "\n";
        }

        echo "DEBUG (get_tournament_options): EXITING function.\n";

        return $results;
    }

    /**
     * Inserts matches into Google Calendar.
     * This method requires an instance of Google_Calendar_Sync.
     *
     * @param array  $matches Array of match data to insert.
     * @param array  $pool Contextual data for the pool (e.g., season_name, pool_name).
     * @param array  $region Contextual data for the region (e.g., region_name).
     * @param array  $age_group Contextual data for the age group (e.g., age_group_name).
     * @param string $season The season ID.
     */
    private function insert_matches($matches, $pool, $region, $age_group, $season)
    {
        if (empty($matches)) {
            echo "DEBUG (insert_matches): No matches to insert.\n";
            return;
        }
        echo "DEBUG (insert_matches): Attempting to insert " . count($matches) . " matches into Google Calendar.\n";
        $google_calendar_sync = new Google_Calendar_Sync();
        $google_calendar_sync->insertMatches(
            $matches,
            $pool['season_name'],
            $region['region_name'],
            $age_group['age_group_name'],
            $pool['pool_name'],
            $pool['tournament_level'],
            $pool['google_color_id'],
            $season,
            $region['region_value'],
            $age_group['age_group_value'],
            $pool['pool_value']
        );
        echo "DEBUG (insert_matches): Google Calendar sync initiated.\n";
    }

    /**
     * Logs messages to the WordPress debug log and also accumulates them internally.
     *
     * @param string $level Log level (e.g., 'info', 'debug', 'error').
     * @param string $message The message to log.
     */
    private function log($level, $message)
    {
        // Update the main log entry in the database
        // Note: The total_matches parameter is fixed to 0 in this call as it's updated separately.
        $this->logger->update_log($this->log_id, $this->log_data['total_matches'], $message, $level);
        $this->log_data['messages'][] = "[$level] $message"; // Accumulate message for final log entry
        error_log("[$level] $message", 3, WP_CONTENT_DIR . '/debug.log'); // Write to PHP debug log
    }
}

// --- CLI Execution Script ---
// This part should be at the very bottom of your 'import_matches.php' file
// after the class definition.
if (php_sapi_name() === 'cli') {
    // Only run if accessed via command line interface
    try {
        // Parse CLI args (e.g., --season=42024 --venue="Grøndal MultiCenter")
        $options = getopt('', ['season:', 'venue:']);

        $season = $options['season'] ?? null;
        $venue  = $options['venue'] ?? null;

        if (!$season || !$venue) {
            echo "Usage: php import_matches.php --season=<season_id> --venue=\"<venue_name>\"\n";
            exit(1);
        }

        echo "CLI script started.\n";
        $importer = new MatchImporter();
        $importer->run($season, $venue);
        echo "CLI script finished.\n";
    } catch (\Exception $e) {
        // Catch any uncaught exceptions from the CLI script itself
        error_log("Unhandled CLI Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        echo "An unhandled error occurred: " . $e->getMessage() . "\n";
        exit(1);
    }
}
