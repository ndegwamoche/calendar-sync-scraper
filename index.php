<?php
// Include Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Fetches and parses table tennis results from the specified URL using a headless browser.
 *
 * @return string Plain text for CLI, or error message.
 */
function ttr_scrape_results()
{
    $url = 'https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#4,42024,14822,4006,4004,,,,';

    try {
        // Set up ChromeDriver connection
        $serverUrl = 'http://localhost:61428';
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability('goog:chromeOptions', ['args' => ['--headless', '--disable-gpu']]);

        // Start ChromeDriver
        $driver = RemoteWebDriver::create($serverUrl, $capabilities);

        // Navigate to the URL and wait for page to load
        $driver->get($url);
        $driver->manage()->timeouts()->implicitlyWait(10); // Wait up to 10 seconds for elements

        // Get the page source after JavaScript rendering
        $html = $driver->getPageSource();

        // Close the browser
        $driver->quit();

        // Initialize DomCrawler with the rendered HTML
        $crawler = new Crawler($html);

        // Filter the table.matchlist
        $tableCrawler = $crawler->filter('table.matchlist');

        // Remove <a> tags and keep their text content
        $tableCrawler->filter('a')->each(function (Crawler $node) {
            $text = $node->text();
            $node->getNode(0)->parentNode->replaceChild(
                $node->getNode(0)->ownerDocument->createTextNode($text),
                $node->getNode(0)
            );
        });

        // Output the modified table HTML
        echo "<table class='matchlist'>\n";
        echo $tableCrawler->html();
        echo "</table>\n";
        exit;
    } catch (Exception $e) {
        return 'Error fetching data: ' . htmlspecialchars($e->getMessage());
    }
}

echo ttr_scrape_results();
