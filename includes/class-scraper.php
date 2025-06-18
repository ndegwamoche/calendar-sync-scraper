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

    private function scrape_team_results($driver, $url)
    {
        try {
            $driver->get($url);
            $wait = new WebDriverWait($driver, 15);

            $lastHeight = $driver->executeScript('return document.body.scrollHeight;');
            for ($i = 0; $i < 2; $i++) {
                $driver->executeScript('window.scrollTo(0, document.body.scrollHeight);');
                usleep(500000);
                $newHeight = $driver->executeScript('return document.body.scrollHeight;');
                $rowCount = $driver->executeScript('return document.querySelectorAll("table.groupstandings tr:not(.headerrow)").length;');
                if ($rowCount > 0 || $newHeight === $lastHeight) break;
                $lastHeight = $newHeight;
            }

            $wait->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(
                    WebDriverBy::cssSelector('table.groupstandings tr:not(.headerrow)')
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
            $tableCrawler = $crawler->filter('table.groupstandings');

            if ($tableCrawler->count() === 0) {
                return ["->no groupstandings table"];
            }

            $teams = [];
            $tableCrawler->filter('tr:not(.headerrow)')->each(function (Crawler $tr) use (&$teams) {
                $teamNode = $tr->filter('td.team a');
                if ($teamNode->count() === 0) {
                    return; // Skip rows without a team link (e.g., "Vakant 1")
                }

                $teamName = trim($teamNode->text());
                $onclick = $teamNode->attr('onclick');
                $teamId = null;

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

    private function scrape_results($driver, $url, $venue)
    {
        try {
            $driver->get($url);
            $wait = new WebDriverWait($driver, 15);

            $lastHeight = $driver->executeScript('return document.body.scrollHeight;');
            for ($i = 0; $i < 2; $i++) {
                $driver->executeScript('window.scrollTo(0, document.body.scrollHeight);');
                usleep(500000);
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
                $hex_color = $pool['hex_color'] ?? '#039be5';

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
                    'matches' => [],
                    'status' => $log->status
                ]
            ]);
        } else {
            wp_send_json(['success' => false, 'data' => ['message' => 'No log found for session']]);
        }
    }

    public function fetch_page_html()
    {
        check_ajax_referer('calendar_scraper_nonce', '_ajax_nonce');

        $season = sanitize_text_field($_POST['season']);
        $ageGroup = sanitize_text_field($_POST['age_group']);
        $region = sanitize_text_field($_POST['region']);
        $url = sanitize_text_field($_POST['link_structure']);

        $messages = [];

        try {
            $url = str_replace(
                ['{season}', '{group}', '{region}'],
                [$season, $ageGroup, $region],
                "https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#1,{season},,{group},{region},,,,"
            );
            $messages[] = "Generated URL: $url";

            $serverUrl = 'http://localhost:9515';
            $capabilities = DesiredCapabilities::chrome();
            $capabilities->setCapability('goog:chromeOptions', [
                'args' => [
                    '--headless',
                    '--disable-gpu',
                    '--no-sandbox',
                    '--disable-dev-shm-usage',
                    '--user-data-dir=' . '/tmp/chrome-profile-' . uniqid(),
                ]
            ]);

            $messages[] = "Initializing WebDriver...";
            $driver = RemoteWebDriver::create($serverUrl, $capabilities);
            $messages[] = "Navigating to URL...";
            $driver->get($url);
            $driver->manage()->timeouts()->implicitlyWait(10);

            try {
                $messages[] = "Fetching page source...";
                $html = $driver->getPageSource();
            } catch (UnexpectedAlertOpenException $e) {
                $messages[] = "Unexpected alert encountered: " . $e->getMessage();
                $alert = $driver->switchTo()->alert();
                $alert->dismiss();
                $html = $driver->getPageSource();
            } finally {
                $messages[] = "Closing WebDriver...";
                $driver->quit();
            }

            wp_send_json([
                'success' => true,
                'html' => $html,
                'messages' => $messages
            ]);
        } catch (Exception $e) {
            $errorMessage = 'Error fetching page HTML: ' . htmlspecialchars($e->getMessage());
            $messages[] = $errorMessage;
            wp_send_json([
                'success' => false,
                'error' => $errorMessage,
                'messages' => $messages
            ]);
        }
    }

    public function check_tournament_level()
    {
        check_ajax_referer('calendar_scraper_nonce', '_ajax_nonce');

        $level_name = sanitize_text_field($_POST['level_name']);
        $season_id = sanitize_text_field($_POST['season_id']);
        $region_id = sanitize_text_field($_POST['region_id']);
        $age_group_id = sanitize_text_field($_POST['age_group_id']);

        $existingLevel = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT id FROM {$this->wpdb->prefix}cal_sync_tournament_levels 
            WHERE level_name = %s 
            AND season_id = %s 
            AND region_id = %s 
            AND age_group_id = %s",
                $level_name,
                $season_id,
                $region_id,
                $age_group_id
            )
        );

        wp_send_json([
            'success' => true,
            'exists' => !empty($existingLevel),
            'level_id' => $existingLevel ? $existingLevel->id : null,
            'error' => $this->wpdb->last_error
        ]);
    }

    public function insert_tournament_level()
    {
        check_ajax_referer('calendar_scraper_nonce', '_ajax_nonce');

        $level_name = sanitize_text_field($_POST['level_name']);
        $season_id = sanitize_text_field($_POST['season_id']);
        $region_id = sanitize_text_field($_POST['region_id']);
        $age_group_id = sanitize_text_field($_POST['age_group_id']);
        $google_color_id = '0';

        $this->wpdb->insert(
            "{$this->wpdb->prefix}cal_sync_tournament_levels",
            [
                'level_name' => $level_name,
                'season_id' => $season_id,
                'region_id' => $region_id,
                'age_group_id' => $age_group_id,
                'google_color_id' => $google_color_id
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );

        wp_send_json([
            'success' => $this->wpdb->insert_id !== false,
            'level_id' => $this->wpdb->insert_id,
            'error' => $this->wpdb->last_error
        ]);
    }

    public function check_tournament_pool()
    {
        check_ajax_referer('calendar_scraper_nonce', '_ajax_nonce');

        $tournament_level = sanitize_text_field($_POST['tournament_level']);
        $pool_value = sanitize_text_field($_POST['pool_value']);
        $season_id = sanitize_text_field($_POST['season_id']);
        $region_id = sanitize_text_field($_POST['region_id']);
        $age_group_id = sanitize_text_field($_POST['age_group_id']);

        $existingPool = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}cal_sync_tournament_pools 
            WHERE tournament_level = %s 
            AND pool_value = %s 
            AND season_id = %s 
            AND region_id = %s 
            AND age_group_id = %s",
                $tournament_level,
                $pool_value,
                $season_id,
                $region_id,
                $age_group_id
            )
        );

        wp_send_json([
            'success' => true,
            'exists' => $existingPool > 0,
            'error' => $this->wpdb->last_error
        ]);
    }

    public function insert_tournament_pool()
    {
        check_ajax_referer('calendar_scraper_nonce', '_ajax_nonce');

        $tournament_level = sanitize_text_field($_POST['tournament_level']);
        $pool_name = sanitize_text_field($_POST['pool_name']);
        $pool_value = sanitize_text_field($_POST['pool_value']);
        $is_playoff = intval($_POST['is_playoff']);
        $season_id = sanitize_text_field($_POST['season_id']);
        $region_id = sanitize_text_field($_POST['region_id']);
        $age_group_id = sanitize_text_field($_POST['age_group_id']);

        $this->wpdb->insert(
            "{$this->wpdb->prefix}cal_sync_tournament_pools",
            [
                'tournament_level' => $tournament_level,
                'pool_name' => $pool_name,
                'pool_value' => $pool_value,
                'is_playoff' => $is_playoff,
                'season_id' => $season_id,
                'region_id' => $region_id,
                'age_group_id' => $age_group_id
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s', '%s']
        );

        wp_send_json([
            'success' => $this->wpdb->insert_id !== false,
            'pool_id' => $this->wpdb->insert_id,
            'error' => $this->wpdb->last_error
        ]);
    }

    public function run_all_teams_scraper()
    {
        check_ajax_referer('calendar_scraper_nonce', '_ajax_nonce');

        $season = sanitize_text_field($_POST['season']);
        $linkStructure = sanitize_text_field($_POST['link_structure']);

        $all_pools = $this->loader->get_all_tournament_pools($season);

        // Initialize the WebDriver once
        $driver = $this->init_driver();

        foreach ($all_pools as $pool) {
            $region = $pool['region_id'];
            $ageGroup = $pool['age_group_id'];
            $poolValue = $pool['pool_value'];

            $url = str_replace(
                ['{season}', '{region}', '{group}', '{pool}'],
                [$season, $region, $ageGroup, $poolValue],
                $linkStructure
            );


            $teams = $this->scrape_team_results($driver, $url);

            $this->loader->insert_teams($teams, $season, $region, $ageGroup, $poolValue);
        }

        $this->quit_driver();

        $response['data']['message'] = "Scraping teams completed successfully!";

        wp_send_json($response);
    }
}
