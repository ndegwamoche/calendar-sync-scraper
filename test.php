<?php
$nodePath = 'C:\\Program Files\\nodejs\\node.exe';
$scriptPath = 'C:\\Apache24\\htdocs\\calendar-sync-scraper\\scrape.js';

$output = shell_exec("\"$nodePath\" \"$scriptPath\" 2>&1");

echo "<h2>Scraped Content</h2>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";
