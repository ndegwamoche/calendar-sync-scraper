<?php

namespace Calendar_Sync_Scraper;

require_once __DIR__ . '/../vendor/autoload.php';

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Exception\UnexpectedAlertOpenException;
use Symfony\Component\DomCrawler\Crawler;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;

class Scraper
{
    private $wpdb;
    private $logger;
    private $loader;
    private $driver;
    private $batch_size = 1; // Number of concurrent scrapes; adjust based on server capacity

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->logger = new Logger();
        $this->loader = new Data_Loader();
        $this->driver = null;
    }

    private function init_driver()
    {
        if (!$this->driver) {
            $serverUrl = 'http://localhost:9515';
            $capabilities = DesiredCapabilities::chrome();
            $capabilities->setCapability('goog:chromeOptions', [
                'args' => [
                    '--headless',
                    '--disable-gpu',
                    '--no-sandbox',
                    '--disable-dev-shm-usage',
                    '--user-data-dir=' . '/tmp/chrome-profile-' . uniqid(),
                    '--disable-extensions', // Reduce overhead
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

    private function quit_driver()
    {
        if ($this->driver) {
            try {
                $this->driver->quit();
            } catch (\Exception $e) {
                $this->logger->log('error', 'Error quitting WebDriver: ' . htmlspecialchars($e->getMessage()));
            }
            $this->driver = null;
        }
    }

    public function run_scraper()
    {
        set_time_limit(0);

        register_shutdown_function(function () use (&$log_id) {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                $message = 'Fatal error: ' . $error['message'];
                if (isset($log_id)) {
                    $logger = new \Calendar_Sync_Scraper\Logger();
                    $logger->update_log($log_id, 0, $message);
                }
                $this->quit_driver();
                if (!headers_sent()) {
                    header('Content-Type: application/json');
                }
                echo json_encode(['success' => false, 'data' => ['message' => $message]]);
                exit;
            }
        });

        check_ajax_referer('calendar_scraper_nonce', '_ajax_nonce');

        $response = ['success' => false, 'data' => ['message' => '']];

        try {
            // Get POST data
            $season = sanitize_text_field($_POST['season']);
            $linkStructure = sanitize_text_field($_POST['link_structure']);
            $venue = sanitize_text_field($_POST['venue']);
            $region = sanitize_text_field($_POST['region']);
            $ageGroup = sanitize_text_field($_POST['age_group']);
            $age_group_name = sanitize_text_field($_POST['age_group_name'] ?? '');
            $pool = sanitize_text_field($_POST['pool']);
            $pool_name = sanitize_text_field($_POST['pool_name'] ?? '');
            $session_id = sanitize_text_field($_POST['session_id'] ?? '');
            $total_pools = (int) ($_POST['total_pools'] ?? 1);
            $tournament_level = sanitize_text_field($_POST['tournament_level'] ?? '');
            $color_id = sanitize_text_field($_POST['color_id'] ?? '');
            $region_name = sanitize_text_field($_POST['region_name'] ?? '');
            $season_name = sanitize_text_field($_POST['season_name'] ?? '');

            if (empty($session_id)) {
                throw new \Exception('Session ID is required.');
            }

            // Get or create log ID for the session
            $transient_key = 'scraper_log_' . $session_id;
            $log_data = get_transient($transient_key);

            if ($log_data === false) {
                $existing_log = $this->wpdb->get_row(
                    $this->wpdb->prepare(
                        "SELECT id, total_matches, error_message FROM {$this->wpdb->prefix}cal_sync_logs WHERE session_id = %s LIMIT 1",
                        $session_id
                    )
                );

                if ($existing_log) {
                    $log_id = $existing_log->id;
                    $messages = $existing_log->error_message ? unserialize($existing_log->error_message) : ["Resuming session $session_id for venue: $venue"];
                    $log_data = [
                        'log_id' => $log_id,
                        'total_matches' => $existing_log->total_matches ? (int)$existing_log->total_matches : 0,
                        'messages' => is_array($messages) ? $messages : [$messages],
                        'request_count' => 0,
                        'total_pools' => $total_pools,
                        'matches' => [], // Store matches for batch processing
                    ];
                } else {
                    $log_id = $this->logger->start_log($season, $region, $ageGroup, 'all_pools', $session_id);
                    $log_data = [
                        'log_id' => $log_id,
                        'total_matches' => 0,
                        'messages' => ["Starting session $session_id, searching venue: $venue"],
                        'request_count' => 0,
                        'total_pools' => $total_pools,
                        'matches' => [],
                    ];
                }
                set_transient($transient_key, $log_data, 3600);
            } else {
                $log_id = $log_data['log_id'];
            }

            $log_data['request_count']++;

            // Generate URL
            $url = str_replace(
                ['{season}', '{region}', '{group}', '{pool}'],
                [$season, $region, $ageGroup, $pool],
                $linkStructure
            );

            // Check cache
            $cache_key = 'scrape_' . md5($url . '_' . $venue);
            $cached_matches = get_transient($cache_key);

            if ($cached_matches !== false && is_array($cached_matches)) {
                $matches = $cached_matches;
                $log_data['messages'][] = "Season: $season_name, Region: $region_name, Age Group: $age_group_name -> Pool $tournament_level - $pool_name ($pool): Found " . count($matches) . " matches (from cache)";
            } else {
                $matches = $this->scrape_results($url, $venue);
                if (is_array($matches) && !isset($matches['error'])) {
                    set_transient($cache_key, $matches, 24 * 3600); // Cache for 24 hours
                }
            }

            $total_matches = is_array($matches) && !isset($matches['error']) ? count($matches) : 0;

            if (isset($matches['error'])) {
                $log_data['messages'][] = "Error in pool $tournament_level - $pool_name ($pool): " . $matches['error'];
                $response['data']['message'] = $matches['error'];
            } else {
                $log_data['total_matches'] += $total_matches;
                $log_data['messages'][] = "Season: $season_name, Region: $region_name, Age Group: $age_group_name -> Pool $tournament_level - $pool_name ($pool): Found $total_matches matches";
                $response['success'] = true;
                $response['data']['message'] = $matches;
                $log_data['matches'] = array_merge($log_data['matches'], $matches); // Collect matches for batch insert
            }

            // Batch update Google Calendar and log
            if ($log_data['request_count'] % $this->batch_size === 0 || $log_data['request_count'] >= $log_data['total_pools']) {
                if (!empty($log_data['matches'])) {
                    $google_calendar_sync = new Google_Calendar_Sync();
                    $google_calendar_sync->insertMatches(
                        $log_data['matches'],
                        $season_name,
                        $region_name,
                        $age_group_name,
                        $pool_name,
                        $tournament_level,
                        $color_id,
                        $season,
                        $region,
                        $ageGroup,
                        $pool
                    );
                    $log_data['matches'] = []; // Clear after insert
                }
                $log_message = implode('\n', $log_data['messages']);
                $this->logger->update_log($log_id, $log_data['total_matches'], $log_message, 'running');
                set_transient($transient_key, $log_data, 3600);
            }

            // Complete log if all pools are processed
            if ($log_data['request_count'] >= $log_data['total_pools']) {
                if (!empty($log_data['matches'])) {
                    $google_calendar_sync = new Google_Calendar_Sync();
                    $google_calendar_sync->insertMatches(
                        $log_data['matches'],
                        $season_name,
                        $region_name,
                        $age_group_name,
                        $pool_name,
                        $tournament_level,
                        $color_id,
                        $season,
                        $region,
                        $ageGroup,
                        $pool
                    );
                }
                $log_message = implode('\n', $log_data['messages']);
                $this->logger->update_log($log_id, $log_data['total_matches'], $log_message, 'completed');
                delete_transient($transient_key);
                $this->quit_driver();
            } else {
                set_transient($transient_key, $log_data, 3600);
            }

            wp_send_json($response);
        } catch (\Exception $e) {
            $response['data']['message'] = 'Error fetching data: ' . htmlspecialchars($e->getMessage());
            $this->logger->log('error', htmlspecialchars($e->getMessage()));
            if (isset($log_id)) {
                $log_data['messages'][] = $response['data']['message'];
                $log_message = implode('\n', $log_data['messages']);
                $this->logger->update_log($log_id, $log_data['total_matches'], $log_message, 'failed');
                set_transient($transient_key, $log_data, 3600);
            }
            $this->quit_driver();
            wp_send_json($response);
        }

        wp_die();
    }

    private function scrape_results($driver, $url, $venue)
    {
        try {
            $driver->get($url);
            $wait = new WebDriverWait($driver, 15);

            $lastHeight = $driver->executeScript('return document.body.scrollHeight;');
            for ($i = 0; $i < 2; $i++) {
                $driver->executeScript('window.scrollTo(0, document.body.scrollHeight);');
                usleep(200000);
                $newHeight = $driver->executeScript('return document.body.scrollHeight;');
                $rowCount = $driver->executeScript('return document.querySelectorAll("table.matchlist tr:not(.headerrow)").length;');
                if ($rowCount > 0 || $newHeight === $lastHeight) break;
                $lastHeight = $newHeight;
            }

            $wait->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(
                    WebDriverBy::cssSelector('table.matchlist tr:not(.headerrow)')
                )
            );

            try {
                $html = $driver->getPageSource();
            } catch (UnexpectedAlertOpenException $e) {
                $alert = $driver->switchTo()->alert();
                $alert->dismiss();
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
                if (!empty($rowData) && isset($rowData['spillested']) && strtolower(trim($rowData['spillested'])) === strtolower(trim($venue))) {
                    $matches[] = $rowData;
                }
            });

            return $matches;
        } catch (\Exception $e) {
            return ["->error: " . htmlspecialchars($e->getMessage())];
        }
    }

    public function run_all_calendar_scraper()
    {
        set_time_limit(0);

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

        check_ajax_referer('calendar_scraper_nonce', '_ajax_nonce');

        $response = ['success' => false, 'data' => ['message' => '', 'matches' => []]];
        $log_data = ['messages' => []];
        $grand_total_matches = 0;

        try {
            $season = sanitize_text_field($_POST['season']);
            $linkStructure = sanitize_text_field($_POST['link_structure']);
            $venue = sanitize_text_field($_POST['venue']);
            $session_id = sanitize_text_field($_POST['session_id']);

            $all_pools = $this->loader->get_all_tournament_pools($season);

            // Initialize log with starting message
            $initial_message = "Starting session $session_id, searching venue: $venue";
            $log_id = $this->logger->start_log($season, 0, 0, 0, $session_id, $initial_message);
            $log_data['messages'][] = $initial_message;

            // Initialize the WebDriver once
            $driver = $this->init_driver();

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

                $url = str_replace(
                    ['{season}', '{region}', '{group}', '{pool}'],
                    [$season, $region, $ageGroup, $poolValue],
                    $linkStructure
                );

                $matches = $this->scrape_results($driver, $url, $venue);

                $total_matches = is_array($matches) && !isset($matches['error']) ? count($matches) : 0;

                if (isset($matches['error'])) {
                    $log_data['messages'][] = "Error in pool $tournament_level - $pool_name ($poolValue): " . $matches['error'];
                    $response['data']['message'] = $matches['error'];
                } else {
                    $response['success'] = true;
                    $log_data['messages'][] = "Season: $season_name, Region: $region_name, Age Group: $age_group_name -> Pool $tournament_level - $pool_name ($poolValue): <strong>Found $total_matches matches</strong>";

                    $google_calendar_sync = new Google_Calendar_Sync();
                    $google_calendar_sync->insertMatches(
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
                        $poolValue
                    );

                    $response['data']['matches'] = array_merge($response['data']['matches'], $matches);
                }

                $grand_total_matches += $total_matches;
                $this->logger->update_log($log_id, $grand_total_matches, implode("\n", $log_data['messages']), 'running');
            }

            $this->quit_driver();

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

            // Ensure the driver quits if exception occurs
            if (isset($driver)) {
                $this->quit_driver();
            }

            $response['data']['message'] = $errorMessage;
            wp_send_json($response);
        }
    }


    public function get_scraper_progress()
    {
        $session_id = sanitize_text_field($_GET['session_id'] ?? '');
        $total_matches = sanitize_text_field($_GET['total_matches'] ?? '');

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

            $calculated_progress = 0;
            if (floatval($total_matches) > 0) {
                $calculated_progress = round(floatval($log->total_matches) / floatval($total_matches) * 100);
                $calculated_progress = max(1, min(100, $calculated_progress));
            }

            wp_send_json([
                'success' => true,
                'data' => [
                    'progress' => $calculated_progress,
                    'message' => ($messages = @unserialize($log->error_message)) && is_array($messages)
                        ? end($messages)
                        : $log->error_message,
                    'matches' => $log->status === 'completed' ? $this->get_matches_for_session($session_id) : [],
                    'status' => $log->status
                ]
            ]);
        } else {
            wp_send_json(['success' => false, 'data' => ['message' => 'No log found for session']]);
        }
    }

    private function get_matches_for_session($session_id)
    {
        // Placeholder: Adjust to fetch matches from your database or cache if stored
        return [];
    }
}
