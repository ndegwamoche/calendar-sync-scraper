<?php

namespace Calendar_Sync_Scraper;

class Admin_UI
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_admin_page()
    {
        add_menu_page(
            'Calendar Scraper',
            'Calendar Scraper',
            'manage_options',
            'calendar-sync-scraper',
            [$this, 'render_settings_page'],
            'dashicons-calendar-alt'
        );
    }

    public function enqueue_assets($hook)
    {
        if ($hook !== 'toplevel_page_calendar-sync-scraper') return;

        wp_enqueue_style('calendar-sync-scraper-css', CAL_SYNC_SCRAPER_URL . 'assets/css/admin-style.css');
        wp_enqueue_script('calendar-sync-scraper-js', CAL_SYNC_SCRAPER_URL . 'assets/js/admin-scraper.js', ['jquery'], null, true);
        wp_localize_script('calendar-sync-scraper-js', 'calendarScraperAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('calendar_scraper_nonce')
        ]);
    }

    public function render_settings_page()
    {
        include CAL_SYNC_SCRAPER_PATH . 'templates/admin-ui.php';
    }
}
