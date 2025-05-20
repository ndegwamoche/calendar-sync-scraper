<?php

namespace Calendar_Sync_Scraper;

class Scraper
{

    protected $logger;

    public function __construct()
    {
        $this->logger = new Logger();
        add_action('wp_ajax_run_calendar_scraper', [$this, 'run_scraper']);
    }

    public function run_scraper()
    {
        check_ajax_referer('calendar_scraper_nonce');

        // Simulate scraping process
        $success = true;

        if ($success) {
            $this->logger->log('success', 'Scraper ran successfully.');
            wp_send_json_success(['message' => 'Scraper completed successfully.']);
        } else {
            $this->logger->log('error', 'Scraper failed.');
            wp_send_json_error(['message' => 'Scraper failed.']);
        }
    }
}
