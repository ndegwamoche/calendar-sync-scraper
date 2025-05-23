<?php

namespace Calendar_Sync_Scraper;

require_once __DIR__ . '/../vendor/autoload.php';

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Exception\UnexpectedAlertOpenException;
use Symfony\Component\DomCrawler\Crawler;

class Scraper
{
    private $wpdb;
    private $logger;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->logger = new Logger();
    }

    public function run_scraper()
    {
        // Register a shutdown handler to catch fatal errors
        register_shutdown_function(function () use (&$log_id) {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                $message = 'Fatal error: ' . $error['message'];

                if (isset($log_id)) {
                    $logger = new \Calendar_Sync_Scraper\Logger();
                    $logger->update_log($log_id, 0, $message);
                }

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
            $pool = sanitize_text_field($_POST['pool']);

            $log_id = $this->logger->start_log($season, $region, $ageGroup, $pool);

            $url = str_replace(
                ['{season}', '{region}', '{group}', '{pool}'],
                [$season, $region, $ageGroup, $pool],
                $linkStructure
            );

            $matches = $this->scrape_results($url, $venue);
            $total_matches = is_array($matches) ? count($matches) : 0;

            if (isset($matches['error'])) {
                $this->logger->update_log($log_id, 0, $matches['error']);
                $response['data']['message'] = $matches['error'];
            } else {
                if ($total_matches == 0) {
                    $error_message = 'No matches found for venue ' . $venue;
                } else {
                    $error_message =  json_encode($matches, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                $this->logger->update_log($log_id, $total_matches, $error_message);
                $this->logger->complete_log($log_id);
                $response['success'] = true;
                $response['data']['message'] = $matches;
            }
        } catch (Exception $e) {
            $response['data']['message'] = 'Error fetching data: ' . htmlspecialchars($e->getMessage());
            $this->logger->log('error', htmlspecialchars($e->getMesssage()));
        }

        wp_send_json($response);
        wp_die();
    }

    /**
     * Fetches and parses table tennis results from the specified URL using a headless browser.
     *
     * @param string $url The URL to scrape.
     * @param string $venue The venue to filter by.
     * @return array JSON-encodable array of match data or error message.
     */
    private function scrape_results($url, $venue)
    {
        try {
            // Set up ChromeDriver connection
            $serverUrl = 'http://localhost:62447';

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

            // Start ChromeDriver
            $driver = RemoteWebDriver::create($serverUrl, $capabilities);

            // Navigate to the URL and wait for page to load
            $driver->get($url);
            $driver->manage()->timeouts()->implicitlyWait(10);

            try {
                $html = $driver->getPageSource();
            } catch (UnexpectedAlertOpenException $e) {
                // Handle the alert
                $alert = $driver->switchTo()->alert();
                $alertText = $alert->getText();
                $alert->dismiss();
                $html = $driver->getPageSource();
            } finally {
                $driver->quit();
            }

            // Initialize DomCrawler with the rendered HTML
            $crawler = new Crawler($html);

            // Filter the table.matchlist
            $tableCrawler = $crawler->filter('table.matchlist');

            if ($tableCrawler->count() === 0) {
                return ['error' => 'No matchlist table found on the page'];
            }

            $headers = [];
            $tableCrawler->filter('tr.headerrow td')->each(function (Crawler $node) use (&$headers) {
                $headerText = strtolower(str_replace(' ', '', $node->text()));
                $headerText = ($headerText === '#') ? 'no' : $headerText;
                $headers[] = $headerText;
            });

            // Get data rows
            $matches = [];
            $tableCrawler->filter('tr')->reduce(function (Crawler $tr) {
                return !$tr->matches('tr.headerrow');
            })->each(function (Crawler $tr) use ($headers, &$matches, $venue) {
                $rowData = [];
                $tr->filter('td')->each(function (Crawler $td, $index) use ($headers, &$rowData) {
                    $value = trim($td->text());
                    if ($index < count($headers)) {
                        $rowData[$headers[$index]] = $value;
                    }
                });
                if (!empty($rowData) && isset($rowData['spillested']) && $rowData['spillested'] === $venue) {
                    $matches[] = $rowData;
                }
            });

            return $matches;
        } catch (Exception $e) {
            $errorMessage = 'Error fetching data: ' . htmlspecialchars($e->getMessage());

            $this->logger->log('error', $errorMessage);

            return ['error' => $errorMessage];
        }
    }
}
