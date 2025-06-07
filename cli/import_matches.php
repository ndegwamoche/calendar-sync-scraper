<?php
require_once dirname(__DIR__) . '/includes/class-match-importer.php';

// Use your class
use Calendar_Sync_Scraper\Includes\Match_Importer;

// Parse CLI args
$options = getopt('', ['season:', 'venue:']);

$season = $options['season'] ?? null;
$venue  = $options['venue'] ?? null;

if (!$season || !$venue) {
    echo "Usage: php import_matches.php --season=42024 --venue=\"Venue Name\"\n";
    exit(1);
}

$importer = new MatchImporter();
$importer->run($season, $venue); // Adjust method name based on your actual class
