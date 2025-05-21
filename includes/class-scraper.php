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

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function run_scraper()
    {
        check_ajax_referer('calendar_scraper_nonce', '_ajax_nonce');

        $response = ['success' => false, 'data' => ['message' => '']];

        try {
            // Get POST data
            $season = sanitize_text_field($_POST['season']);
            $linkStructure = sanitize_text_field($_POST['link_structure']);
            $venue = sanitize_text_field($_POST['venue']);

            // Default values for region and age group (to be replaced with form inputs later)
            $region = 4006; // Default to ØST (Sjælland, Lolland F.)
            $ageGroup = 4006; // Default to Senior
            $pool = 14822; // Default to Pool

            // Construct the URL by replacing placeholders
            $url = str_replace(
                ['{season}', '{region}', '{group}', '{pool}'],
                [$season, $region, $ageGroup, $pool],
                $linkStructure
            );

            $html = $this->scrape_results($url, $venue);
            $response['success'] = true;
            $response['data']['message'] = $html;
        } catch (Exception $e) {
            $response['data']['message'] = 'Error fetching data: ' . htmlspecialchars($e->getMessage());
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
            $serverUrl = 'http://localhost:61428';
            $capabilities = DesiredCapabilities::chrome();
            $capabilities->setCapability('goog:chromeOptions', ['args' => ['--headless', '--disable-gpu']]);

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
            return ['error' => 'Error fetching data: ' . htmlspecialchars($e->getMessage())];
        }
    }
}
