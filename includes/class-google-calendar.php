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
    private $time_offset;

    public function __construct()
    {
        $this->client_id = get_option('cal_sync_client_id');
        $this->client_secret = get_option('cal_sync_client_secret');
        $this->refresh_token = get_option('cal_sync_refresh_token');
        $this->calendar_id = 'primary';
        $this->time_offset = get_option('cal_sync_time_offset');

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
        // Remove Danish weekday abbreviations (e.g., 'sø', 'ma', 'ti', etc.) and normalize
        $tid = preg_replace('/^(ma|ti|on|to|fr|lø|sø)\s+/i', '', trim($tid));
        $tid = str_replace(['‑', '–'], '-', $tid); // Normalize dashes

        try {
            $dateTime = \DateTime::createFromFormat(
                'd-m-Y H:i', // Format: DD-MM-YYYY HH:MM (24-hour)
                $tid,
                new \DateTimeZone('Europe/Copenhagen')
            );

            if ($dateTime === false) {
                throw new \Exception("Invalid datetime format: '$tid'");
            }

            return $dateTime->format(\DateTime::RFC3339);
        } catch (\Exception $e) {
            throw new \Exception("Failed to parse datetime '$tid': " . $e->getMessage());
        }
    }

    public function insertMatches($matches, $season_name, $region_name, $age_group_name, $pool_name, $tournament_level, $color_id, $season, $region, $ageGroup, $pool)
    {
        foreach ($matches as $match) {
            try {
                $startDateTime = $this->parseTidToDateTime($match['tid']);

                $endDateTime = (new \DateTime($startDateTime))->modify($this->time_offset)->format(\DateTime::RFC3339);

                $description = "<strong>{$region_name} {$season_name}</strong><br>" .
                    "<a href='https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#3,{$season},{$pool},{$ageGroup},{$region},{$match['hjemmehold_id']},,4203'>$tournament_level $pool_name</a><br><br>" .

                    "<a href='https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#2,{$season},{$pool},{$ageGroup},{$region},,,4203'>{$match['hjemmehold']}</a><br>" .
                    "{$match['udehold']}, {$match['spillested']}<br><br>" .

                    "<strong>Resultat: {$match['resultat']}<br>" .
                    "Point: {$match['point']}</strong><br><br>" .

                    "<a href='https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#5,{$season},{$pool},{$ageGroup},{$region},,{$match['no']},4203'>Kampdetaljer</a>";

                switch ($color_id) {
                    case '1':  // Lavender
                        $icon = '🟪';
                        break;
                    case '2':  // Sage
                        $icon = '🟩';
                        break;
                    case '3':  // Grape
                        $icon = '🟪';
                        break;
                    case '4':  // Flamingo
                        $icon = '🟥';
                        break;
                    case '5':  // Banana
                        $icon = '🟨';
                        break;
                    case '6':  // Tangerine
                        $icon = '🟧';
                        break;
                    case '7':  // Peacock
                        $icon = '🟦';
                        break;
                    case '8':  // Graphite
                        $icon = '⬛';
                        break;
                    case '9':  // Blueberry
                        $icon = '🟦';
                        break;
                    case '10': // Basil
                        $icon = '🟩';
                        break;
                    case '11': // Tomato
                        $icon = '🟥';
                        break;
                    default:
                        $icon = '⬜'; // Default white square
                        break;
                }

                $event = new \Google_Service_Calendar_Event([
                    'summary' => "$icon $age_group_name $tournament_level $pool_name",
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
        $time_offset = sanitize_text_field($_POST['time_offset']);

        if (!$clientId || !$clientSecret || !$refreshToken || !$time_offset) {
            wp_send_json_error(['message' => 'All fields are required.']);
            return;
        }

        update_option('cal_sync_client_id', $clientId);
        update_option('cal_sync_client_secret', $clientSecret);
        update_option('cal_sync_refresh_token', $refreshToken);
        update_option('cal_sync_time_offset', $time_offset);

        wp_send_json_success(['message' => 'Credentials saved successfully.']);
    }

    public function get_google_credentials()
    {
        $clientId = get_option('cal_sync_client_id', '');
        $clientSecret = get_option('cal_sync_client_secret', '');
        $refreshToken = get_option('cal_sync_refresh_token', '');
        $timeOffset = get_option('cal_sync_time_offset', '');

        wp_send_json_success([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'time_offset' => $timeOffset,
        ]);
    }

    public function clear_google_calendar_events()
    {
        try {
            $pageToken = null;
            do {
                $optParams = ['pageToken' => $pageToken];
                $events = $this->service->events->listEvents($this->calendar_id, $optParams);

                foreach ($events->getItems() as $event) {
                    $this->service->events->delete($this->calendar_id, $event->getId());
                }

                $pageToken = $events->getNextPageToken();
            } while ($pageToken);

            return true;
        } catch (\Exception $e) {
            error_log('Failed to clear calendar events: ' . $e->getMessage());
            return false;
        }
    }
}
