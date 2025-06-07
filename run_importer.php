<?php
// Adjust path to wp-load.php from the plugin directory
require_once dirname(__FILE__) . '/../../../wp-load.php';

// Adjust path to the MatchImporter class file
require_once dirname(__FILE__) . '/includes/class-match-importer.php';

use Calendar_Sync_Scraper\MatchImporter;

// Output headers for browser
header('Content-Type: text/plain; charset=utf-8');

// Initialize importer
$importer = new MatchImporter();
$season = '42024';
$venue = 'Grøndal MultiCenter';

// Run importer
try {
    $importer->run($season, $venue);
    echo "Script completed. Check wp-content/scraper_debug.log for details.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
