<?php
// Step 1: Call the web service to get the table HTML
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.bordtennisportalen.dk/SportsResults/Components/WebService1.asmx/GetLeagueStanding');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json; charset=utf-8',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
]);

// Parameters from the URL hash
$params = [
    'subPage' => 4,
    'seasonID' => 42024,
    'leagueGroupID' => 14822,
    'ageGroupID' => 4006,
    'regionID' => 4004,
    'leagueGroupTeamID' => '',
    'leagueMatchID' => '',
    'clubID' => '',
    'playerID' => ''
];

// Encode parameters as JSON
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

// Temporary workaround: Disable SSL verification (insecure, for testing only)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    die("cURL Error: " . curl_error($ch));
}

curl_close($ch);

// Parse the JSON response from the web service
$response_data = json_decode($response, true);
if (!isset($response_data['d']['html'])) {
    die("Error: Web service did not return expected HTML content.");
}

$html = $response_data['d']['html'];

// Debug: Save the HTML to a file for inspection
file_put_contents('debug_table.html', $html);

// Step 2: Load the HTML into DOMDocument
$doc = new DOMDocument();
libxml_use_internal_errors(true); // Suppress HTML parsing warnings
$doc->loadHTML($html);
libxml_clear_errors();

// Step 3: Use XPath to locate the match table
$xpath = new DOMXPath($doc);
// The table in the response likely has a class like 'leaguematches'
$rows = $xpath->query("//table[contains(@class, 'leaguematches')]//tr[position() > 1]"); // Skip header row

$matches = [];

if ($rows->length === 0) {
    die("No table rows found. Check the XPath or HTML structure in debug_table.html.");
}

foreach ($rows as $row) {
    $cols = $row->getElementsByTagName('td');
    if ($cols->length >= 7) { // Ensure row has at least 7 columns (date, time, home, away, score, location, match_id)
        $match = [
            'date' => trim($cols->item(0)->textContent),
            'time' => trim($cols->item(1)->textContent),
            'home_team' => trim($cols->item(2)->textContent),
            'away_team' => trim($cols->item(3)->textContent),
            'score' => trim($cols->item(4)->textContent) ?: null,
            'location' => trim($cols->item(5)->textContent),
            'match_id' => trim($cols->item(6)->textContent)
        ];

        // Convert date and time to a consistent format (e.g., YYYY-MM-DD and HH:MM:SS)
        $match['match_date'] = date('Y-m-d', strtotime($match['date']));
        $match['match_time'] = date('H:i:s', strtotime($match['time']));

        $matches[] = $match;
    }
}

// Output the results
header('Content-Type: application/json');
echo json_encode($matches, JSON_PRETTY_PRINT);
