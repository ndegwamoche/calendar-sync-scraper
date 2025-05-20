<?php

namespace Calendar_Sync_Scraper;

class Scheduler
{
    public function __construct()
    {
        add_action('calendar_scraper_daily_event', [$this, 'scheduled_scrape']);
        $this->schedule_event();
    }

    public function schedule_event()
    {
        if (!wp_next_scheduled('calendar_scraper_daily_event')) {
            wp_schedule_event(time(), 'daily', 'calendar_scraper_daily_event');
        }
    }

    public function scheduled_scrape()
    {
        $scraper = new Scraper();
        $scraper->run_scraper();
    }
}
