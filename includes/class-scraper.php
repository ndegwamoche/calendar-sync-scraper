<?php

/**
 * Scraper class for the Calendar Sync Scraper plugin.
 *
 * This class handles web scraping operations using Selenium WebDriver to extract
 * team and match data from specified URLs, integrating with the plugin's logging
 * and data loading functionality.
 *
 * @package Calendar_Sync_Scraper
 */

namespace Calendar_Sync_Scraper;

/**
 * Include the autoloader for third-party dependencies.
 */
require_once __DIR__ . '/../vendor/autoload.php';

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Exception\UnexpectedAlertOpenException;
use Symfony\Component\DomCrawler\Crawler;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;

/**
 * Class Scraper
 *
 * Manages web scraping operations for calendar and team data.
 */
class Scraper
{
    /**
     * @var wpdb WordPress database access object.
     */
    private $wpdb;

    /**
     * @var Logger Instance of the Logger class for logging activities.
     */
    private $logger;

    /**
     * @var Data_Loader Instance of the Data_Loader class for data operations.
     */
    private $loader;

    /**
     * @var RemoteWebDriver|null Selenium WebDriver instance for browser automation.
     */
    private $driver;

    /**
     * Constructor.
     *
     * Initializes class properties and dependencies.
     */
    public function __construct()
    {
        // Access global WordPress database object.
        global $wpdb;
        $this->wpdb = $wpdb;

        // Instantiate logger and data loader.
        $this->logger = new Logger();
        $this->loader = new Data_Loader();
        $this->driver = null; // WebDriver initialized on demand.
    }

    /**
     * Initialize Selenium WebDriver with Chrome in headless mode.
     *
     * @return RemoteWebDriver The initialized WebDriver instance.
     * @throws \Exception If WebDriver initialization fails.
     */
    private function init_driver()
    {
        if (!$this->driver) {
            $serverUrl = 'http://localhost:9515'; // Selenium server URL
            $capabilities = DesiredCapabilities::chrome();
            $capabilities->setCapability('goog:chromeOptions', [
                'args' => [
                    '--headless',                     // Run Chrome in headless mode
                    '--disable-gpu',                  // Disable GPU acceleration
                    '--no-sandbox',                   // Disable sandbox for compatibility
                    '--disable-dev-shm-usage',        // Avoid shared memory issues
                    '--user-data-dir=' . '/tmp/chrome-profile-' . uniqid(), // Unique profile directory
                    '--disable-extensions',           // Reduce overhead
                    '--blink-settings=imagesEnabled=false', // Disable images for faster loading
                ]
            ]);
            try {
                $this->driver = RemoteWebDriver::create($serverUrl, $capabilities);
            } catch (\Exception $e) {
                $errorMessage = 'WebDriver initialization failed: ' . htmlspecialchars($e->getMessage());
                $this->logger->log('error', $errorMessage);
                throw new \Exception($errorMessage);
            }
        }
        return $this->driver;
    }

    /**
     * Safely quit the WebDriver session.
     */
    private function quit_driver()
    {
        if ($this->driver) {
            try {
                $this->driver->quit(); // Close the browser session
            } catch (\Exception $e) {
                $this->logger->log('error', 'Error quitting WebDriver: ' . htmlspecialchars($e->getMessage()));
            }
            $this->driver = null; // Reset driver instance
        }
    }

    /**
     * Scrape team results from a given URL.
     *
     * @param RemoteWebDriver $driver The WebDriver instance.
     * @param string $url The URL to scrape.
     * @return array Array of team data or error message.
     */
    private function scrape_team_results($driver, $url)
    {
        try {
            $driver->get($url); // Navigate to the URL
            $wait = new WebDriverWait($driver, 15); // Wait up to 15 seconds for elements

            // Scroll to load dynamic content
            $lastHeight = $driver->executeScript('return document.body.scrollHeight;');
            for ($i = 0; $i < 2; $i++) {
                $driver->executeScript('window.scrollTo(0, document.body.scrollHeight);');
                usleep(500000); // Wait 0.5 seconds for content to load
                $newHeight = $driver->executeScript('return document.body.scrollHeight;');
                $rowCount = $driver->executeScript('return document.querySelectorAll("table.groupstandings tr:not(.headerrow)").length;');
                if ($rowCount > 0 || $newHeight === $lastHeight) {
                    break; // Stop if rows are found or no new content loaded
                }
                $lastHeight = $newHeight;
            }

            // Wait for table rows to be visible
            $wait->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(
                    WebDriverBy::cssSelector('table.groupstandings tr:not(.headerrow)')
                )
            );

            // Handle page source, accounting for possible alerts
            try {
                $html = $driver->getPageSource();
            } catch (UnexpectedAlertOpenException $e) {
                $alert = $driver->switchTo()->alert();
                $alert->dismiss(); // Dismiss any alert
                $html = $driver->getPageSource();
            }

            if (empty($html)) {
                return ["->error: Empty page source returned for URL: $url"];
            }

            $crawler = new Crawler($html);
            $tableCrawler = $crawler->filter('table.groupstandings');

            if ($tableCrawler->count() === 0) {
                return ["->no groupstandings table"];
            }

            $teams = [];
            $tableCrawler->filter('tr:not(.headerrow)')->each(function (Crawler $tr) use (&$teams) {
                $teamNode = $tr->filter('td.team a');
                if ($teamNode->count() === 0) {
                    return; // Skip rows without a team link
                }

                $teamName = trim($teamNode->text());
                $onclick = $teamNode->attr('onclick');
                $teamId = null;

                // Extract team ID from onclick attribute
                if (preg_match("/ShowStanding\((?:'[^']*',\s*){5}'(\d+)'/", $onclick, $matches)) {
                    $teamId = $matches[1];
                }

                if ($teamId) {
                    $teams[] = [
                        'team_id' => $teamId,
                        'team_name' => $teamName
                    ];
                }
            });

            return $teams;
        } catch (\Exception $e) {
            return ["->error: " . htmlspecialchars($e->getMessage())];
        }
    }

    /**
     * Scrape match results from a given URL, filtered by venue.
     *
     * @param RemoteWebDriver $driver The WebDriver instance.
     * @param string $url The URL to scrape.
     * @param string $venue The venue to filter matches by.
     * @return array Array of match data or error message.
     */
    private function scrape_results($driver, $url, $venue)
    {
        try {
            $driver->get($url); // Navigate to the URL
            $wait = new WebDriverWait($driver, 15); // Wait up to 15 seconds for elements

            // Scroll to load dynamic content
            $lastHeight = $driver->executeScript('return document.body.scrollHeight;');
            for ($i = 0; $i < 2; $i++) {
                $driver->executeScript('window.scrollTo(0, document.body.scrollHeight);');
                usleep(500000); // Wait 0.5 seconds for content to load
                $newHeight = $driver->executeScript('return document.body.scrollHeight;');
                $rowCount = $driver->executeScript('return document.querySelectorAll("table.matchlist tr:not(.headerrow)").length;');
                if ($rowCount > 0 || $newHeight === $lastHeight) {
                    break; // Stop if rows are found or no new content loaded
                }
                $lastHeight = $newHeight;
            }

            // Wait for table rows to be visible
            $wait->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(
                    WebDriverBy::cssSelector('table.matchlist tr:not(.headerrow)')
                )
            );

            // Handle page source, accounting for possible alerts
            try {
                $html = $driver->getPageSource();
            } catch (UnexpectedAlertOpenException $e) {
                $alert = $driver->switchTo()->alert();
                $alert->dismiss(); // Dismiss any alert
                $html = $driver->getPageSource();
            }

            if (empty($html)) {
                return ["->error: Empty page source returned for URL: $url"];
            }

            $crawler = new Crawler($html);
            $tableCrawler = $crawler->filter('table.matchlist');

            if ($tableCrawler->count() === 0) {
                return ["->no matchlist table"];
            }

            // Extract table headers
            $headers = [];
            $tableCrawler->filter('tr.headerrow td')->each(function (Crawler $node) use (&$headers) {
                $headerText = strtolower(str_replace(' ', '', $node->text()));
                $headerText = ($headerText === '#') ? 'no' : $headerText;
                $headers[] = $headerText;
            });

            $matches = [];
            $tableCrawler->filter('tr:not(.headerrow)')->each(function (Crawler $tr) use ($headers, &$matches, $venue) {
                $rowData = [];
                $tr->filter('td')->each(function (Crawler $td, $index) use ($headers, &$rowData) {
                    $value = trim($td->text());
                    // Extract team IDs from links in home and away team columns
                    if ($td->filter('a')->count() && in_array($headers[$index], ['hjemmehold', 'udehold'])) {
                        $onclick = $td->filter('a')->attr('onclick');
                        if (preg_match("/ShowStanding\((?:'[^']*',\s*){5}'(\d+)'/", $onclick, $matches)) {
                            $rowData[$headers[$index] . '_id'] = $matches[1];
                        }
                    }
                    if ($index < count($headers)) {
                        $rowData[$headers[$index]] = $value;
                    }
                });
                // Filter matches by venue
                if (!empty($rowData) && isset($rowData['spillested']) && strtolower(trim($rowData['spillested'])) === strtolower(trim($venue))) {
                    $matches[] = $rowData;
                }
            });

            return $matches;
        } catch (\Exception $e) {
            return ["->error: " . htmlspecialchars($e->getMessage())];
        }
    }

    /**
     * Run the calendar scraper via AJAX to fetch and process match data.
     */
    public function run_all_calendar_scraper()
    {
        // Increase script execution time limit
        set_time_limit(0);

        // Register shutdown function to handle fatal errors
        register_shutdown_function(function () use (&$log_id) {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                $message = 'Fatal error: ' . $error['message'];
                if (isset($log_id)) {
                    $logger = new \Calendar_Sync_Scraper\Logger();
                    $logger->update_log($log_id, 0, $message, 'failed');
                }
                $this->quit_driver();
                if (!headers_sent()) {
                    header('Content-Type: application/json');
                }
                echo json_encode(['success' => false, 'data' => ['message' => $message]]);
                exit;
            }
        });

        // Verify AJAX nonce for security
        check_ajax_referer('calendar_scraper_nonce', '_ajax_nonce');

        // Initialize response array
        $response = ['success' => false, 'data' => ['message' => '', 'matches' => []]];
        $log_data = ['messages' => []];
        $grand_total_matches = 0;

        try {
            // Sanitize POST data
            $season = sanitize_text_field($_POST['season']);
            $linkStructure = sanitize_text_field($_POST['link_structure']);
            $venue = sanitize_text_field($_POST['venue']);
            $session_id = sanitize_text_field($_POST['session_id']);

            // Retrieve all tournament pools for the season
            $all_pools = $this->loader->get_all_tournament_pools($season);

            // Initialize log entry
            $initial_message = "Starting session $session_id, searching venue: $venue";
            $log_id = $this->logger->start_log($season, 0, 0, 0, $session_id, $initial_message);
            $log_data['messages'][] = $initial_message;

            // Initialize WebDriver
            $driver = $this->init_driver();

            // Process each tournament pool
            foreach ($all_pools as $pool) {
                $region = $pool['region_id'];
                $ageGroup = $pool['age_group_id'];
                $poolValue = $pool['pool_value'];
                $tournament_level = $pool['tournament_level'];
                $pool_name = $pool['pool_name'];
                $season_name = $pool['season_name'];
                $region_name = $pool['region_name'];
                $age_group_name = $pool['age_group_name'];
                $google_color_id = $pool['google_color_id'] ?? 0;
                $hex_color = $pool['hex_color'] ?? '#039be5';

                // Construct URL for scraping
                $url = str_replace(
                    ['{season}', '{region}', '{group}', '{pool}'],
                    [$season, $region, $ageGroup, $poolValue],
                    $linkStructure
                );

                // Scrape match results
                $matches = $this->scrape_results($driver, $url, $venue);

                $total_matches = is_array($matches) && !isset($matches['error']) ? count($matches) : 0;

                if (isset($matches['error'])) {
                    $log_data['messages'][] = "Error in pool $tournament_level - $pool_name ($poolValue): " . $matches['error'];
                    $response['data']['message'] = $matches['error'];
                } else {
                    $response['success'] = true;
                    $log_data['messages'][] = "Season: $season_name, Region: $region_name, Age Group: $age_group_name -> Pool $tournament_level - $pool_name ($poolValue): <strong>Found $total_matches matches</strong>";

                    // Insert matches into Events Calendar
                    $events_calendar_sync = new Events_Calendar_Sync();
                    $events_calendar_sync->insertMatches(
                        $matches,
                        $season_name,
                        $region_name,
                        $age_group_name,
                        $pool_name,
                        $tournament_level,
                        $google_color_id,
                        $season,
                        $region,
                        $ageGroup,
                        $poolValue,
                        $venue,
                        $hex_color
                    );

                    // Uncomment to enable Google Calendar sync
                    // $google_calendar_sync = new Google_Calendar_Sync();
                    // $google_calendar_sync->insertMatches(
                    //     $matches,
                    //     $season_name,
                    //     $region_name,
                    //     $age_group_name,
                    //     $pool_name,
                    //     $tournament_level,
                    //     $google_color_id,
                    //     $season,
                    //     $region,
                    //     $ageGroup,
                    //     $poolValue
                    // );

                    $response['data']['matches'] = array_merge($response['data']['matches'], $matches);
                }

                $grand_total_matches += $total_matches;
                $this->logger->update_log($log_id, $grand_total_matches, implode("\n", $log_data['messages']), 'running');
            }

            // Clean up WebDriver
            $this->quit_driver();

            // Finalize log entry
            $this->logger->update_log($log_id, $grand_total_matches, implode("\n", $log_data['messages']), 'completed');
            $response['data']['message'] = "Scraping completed! Found $grand_total_matches matches";

            wp_send_json($response);
        } catch (\Exception $e) {
            $errorMessage = 'Error fetching data: ' . htmlspecialchars($e->getMessage());
            $this->logger->log('error', $errorMessage);

            if (isset($log_id)) {
                $log_data['messages'][] = $errorMessage;
                $this->logger->update_log($log_id, $grand_total_matches, implode("\n", $log_data['messages']), 'failed');
            }

            // Ensure WebDriver is closed on error
            if (isset($driver)) {
                $this->quit_driver();
            }

            $response['data']['message'] = $errorMessage;
            wp_send_json($response);
        }
    }

    /**
     * Retrieve scraper progress via AJAX.
     */
    public function get_scraper_progress()
    {
        // Sanitize GET parameters
        $session_id = sanitize_text_field($_GET['session_id'] ?? '');
        $total_matches = sanitize_text_field($_GET['total_matches'] ?? '');

        // Query log data from database
        $log = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT
                    `status`,
                    total_matches,
                    error_message
                FROM
                    {$this->wpdb->prefix}cal_sync_logs
                WHERE
                    session_id = %s",
                $session_id
            )
        );

        if ($log) {
            // Calculate progress percentage
            $calculated_progress = 0;
            if (floatval($total_matches) > 0) {
                $calculated_progress = round(floatval($log->total_matches) / floatval($total_matches) * 100);
                $calculated_progress = max(1, min(100, $calculated_progress));
            }

            // Send JSON response with progress data
            wp_send_json([
                'success' => true,
                'data' => [
                    'progress' => $calculated_progress,
                    'message' => ($messages = @unserialize($log->error_message)) && is_array($messages)
                        ? end($messages)
                        : $log->error_message,
                    'matches' => [],
                    'status' => $log->status
                ]
            ]);
        } else {
            // Send error response if no log found
            wp_send_json(['success' => false, 'data' => ['message' => 'No log found for session']]);
        }
    }

    /**
     * Run the team scraper via AJAX to fetch and process team data.
     */
    public function run_all_teams_scraper()
    {
        // Verify AJAX nonce for security
        check_ajax_referer('calendar_scraper_nonce', '_ajax_nonce');

        // Sanitize POST data
        $season = sanitize_text_field($_POST['season']);
        $linkStructure = sanitize_text_field($_POST['link_structure']);

        // Retrieve all tournament pools for the season
        $all_pools = $this->loader->get_all_tournament_pools($season);

        // Initialize WebDriver
        $driver = $this->init_driver();

        // Process each tournament pool
        foreach ($all_pools as $pool) {
            $region = $pool['region_id'];
            $ageGroup = $pool['age_group_id'];
            $poolValue = $pool['pool_value'];

            // Construct URL for scraping
            $url = str_replace(
                ['{season}', '{region}', '{group}', '{pool}'],
                [$season, $region, $ageGroup, $poolValue],
                $linkStructure
            );

            // Scrape team results
            $teams = $this->scrape_team_results($driver, $url);

            // Insert teams into database
            $this->loader->insert_teams($teams, $season, $region, $ageGroup, $poolValue);
        }

        // Clean up WebDriver
        $this->quit_driver();

        // Send success response
        $response['data']['message'] = "Scraping teams completed successfully!";

        wp_send_json($response);
    }
}
