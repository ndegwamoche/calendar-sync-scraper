<?php

namespace Calendar_Sync_Scraper;

require_once __DIR__ . '/../vendor/autoload.php';

use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use GuzzleHttp\Client as GuzzleClient;

class Google_Calendar_Sync
{
    private $client;
    private $service;
    private $client_id;
    private $client_secret;
    private $refresh_token;
    private $calendar_id;

    public function __construct()
    {
        $this->client_id = get_option('cal_sync_client_id');
        $this->client_secret = get_option('cal_sync_client_secret');
        $this->refresh_token = get_option('cal_sync_refresh_token');
        $this->calendar_id = 'primary';

        // Validate that the credentials exist
        if (!$this->client_id || !$this->client_secret || !$this->refresh_token) {
            throw new Exception('Google Calendar API credentials are missing. Please configure client ID, client secret, and refresh token in the plugin settings.');
        }

        $this->client = new Google_Client();
        // Configure Guzzle to disable SSL verification (insecure, use as last resort)
        $guzzleClient = new GuzzleClient(['verify' => false]);
        $this->client->setHttpClient($guzzleClient);

        $this->client->setClientId($this->client_id);
        $this->client->setClientSecret($this->client_secret);
        $this->client->setScopes(['https://www.googleapis.com/auth/calendar']);
        $this->client->setAccessType('offline');
        $this->client->setApprovalPrompt('force');

        // Use the refresh token to fetch a new access token
        $this->refreshAccessToken();

        // Initialize the calendar service
        $this->service = new Google_Service_Calendar($this->client);
    }

    private function refreshAccessToken()
    {
        try {
            // Fetch a new access token using the refresh token
            $accessToken = $this->client->fetchAccessTokenWithRefreshToken($this->refresh_token);
            if (isset($accessToken['access_token'])) {
                $this->client->setAccessToken($accessToken);
            } else {
                throw new Exception('Failed to fetch access token with refresh token.');
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Failed to refresh Google Calendar access token: ' . $e->getMessage()]);
            throw $e;
        }
    }

    private function parseTidToDateTime(string $tid): string
    {
        // Remove weekday and normalize dash characters
        $tid = preg_replace('/^\w+\s+/', '', $tid); // e.g., "ti 15‑10‑2024 19:30" -> "15‑10‑2024 19:30"
        $tid = str_replace(['‑', '–'], '-', $tid);  // Normalize dashes

        $dateTime = \DateTime::createFromFormat('d-m-Y H:i', $tid, new \DateTimeZone('Europe/Copenhagen'));

        if (!$dateTime) {
            throw new \Exception("Invalid datetime format: $tid");
        }

        return $dateTime->format(\DateTime::RFC3339);
    }

    public function insertMatches($matches, $season_name, $region_name, $age_group_name, $pool_name, $tournament_level, $color_id, $season, $region, $ageGroup, $pool)
    {
        foreach ($matches as $match) {
            try {
                $startDateTime = $this->parseTidToDateTime($match['tid']);

                // Assuming match lasts 2 hours; adjust as needed
                $endDateTime = (new \DateTime($startDateTime))->modify('+3 hours')->format(\DateTime::RFC3339);

                $description = "$region_name $season_name\n\n" .
                    "Serie 1 Pulje 1\n" .
                    "https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#3,{$season},{$pool},{$ageGroup},{$region},{$match['hjemmehold_id']},,4203\n\n" .
                    "{$match['hjemmehold']}\n" .
                    "https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#2,{$season},{$pool},{$ageGroup},{$region},,,4203\n" .
                    "{$match['udehold']}, {$match['spillested']}\n\n" .
                    "Resultat: {$match['resultat']}\n" .
                    "Point: {$match['point']}\n\n" .
                    "Kampdetaljer\n" .
                    "https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#5,{$season},{$pool},{$ageGroup},{$region},,{$match['no']},4203";

                $event = new \Google_Service_Calendar_Event([
                    'summary' => "$age_group_name $tournament_level $pool_name",
                    'location' => $match['spillested'],
                    'description' => $description,
                    'start' => ['dateTime' => $startDateTime, 'timeZone' => 'Europe/Copenhagen'],
                    'end' => ['dateTime' => $endDateTime, 'timeZone' => 'Europe/Copenhagen'],
                    'colorId' => $color_id,
                ]);

                $this->service->events->insert($this->calendar_id, $event);
            } catch (\Exception $e) {

                wp_send_json_error(['message' => "Failed to insert match {$match['no']}: " . $e->getMessage()]);
            }
        }
    }

    public function save_google_credentials()
    {
        check_ajax_referer('calendar_scraper_nonce', '_ajax_nonce');

        $clientId = sanitize_text_field($_POST['client_id']);
        $clientSecret = sanitize_text_field($_POST['client_secret']);
        $refreshToken = sanitize_text_field($_POST['refresh_token']);

        if (!$clientId || !$clientSecret || !$refreshToken) {
            wp_send_json_error(['message' => 'All fields are required.']);
            return;
        }

        update_option('cal_sync_client_id', $clientId);
        update_option('cal_sync_client_secret', $clientSecret);
        update_option('cal_sync_refresh_token', $refreshToken);

        wp_send_json_success(['message' => 'Credentials saved successfully.']);
    }

    public function get_google_credentials()
    {
        $clientId = get_option('cal_sync_client_id', '');
        $clientSecret = get_option('cal_sync_client_secret', '');
        $refreshToken = get_option('cal_sync_refresh_token', '');

        wp_send_json_success([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
        ]);
    }

    public function clear_google_calendar_events()
    {
        try {
            $pageToken = null;
            $eventsCleared = 0;

            do {
                $optParams = ['pageToken' => $pageToken];
                $events = $this->service->events->listEvents($this->calendar_id, $optParams);

                foreach ($events->getItems() as $event) {
                    $this->service->events->delete($this->calendar_id, $event->getId());
                    $eventsCleared++;
                }

                $pageToken = $events->getNextPageToken();
            } while ($pageToken);

            wp_send_json_success(['message' => "Successfully cleared $eventsCleared calendar events."]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Failed to clear calendar events: ' . $e->getMessage()]);
        }
    }
}
