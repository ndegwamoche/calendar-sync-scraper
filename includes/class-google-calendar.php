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
            error_log('Failed to refresh Google Calendar access token: ' . $e->getMessage());
            throw $e;
        }
    }

    public function insertMatches($matches)
    {
        foreach ($matches as $match) {
            try {
                $event = new Google_Service_Calendar_Event([
                    'summary' => "Match: {$match['hjemmehold']} vs {$match['udehold']}",
                    'location' => $match['spillested'],
                    'description' => $match['resultat'],
                    'start' => $this->createEventDateTime($match['start_datetime'] ?? null),
                    'end' => $this->createEventDateTime($match['end_datetime'] ?? null),
                ]);

                $this->service->events->insert($this->calendar_id, $event);
            } catch (Exception $e) {
                error_log("Failed to insert match {$match['no']}: " . $e->getMessage());
            }
        }
    }

    private function createEventDateTime($datetime)
    {
        if (!$datetime) {
            // Default to a placeholder date if no datetime is available
            $datetime = date('Y-m-d\TH:i:sP', strtotime('+1 day'));
        }
        return new Google_Service_Calendar_EventDateTime([
            'dateTime' => $datetime,
            'timeZone' => 'UTC',
        ]);
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
}
