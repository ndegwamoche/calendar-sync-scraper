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
            'dashicons-controls-repeat'
        );
    }

    public function enqueue_assets($hook)
    {
        if ($hook !== 'toplevel_page_calendar-sync-scraper') return;

        // Enqueue JS
        wp_enqueue_script(
            'calendar-sync-scraper-js',
            CAL_SYNC_SCRAPER_URL . 'build/index.js',
            ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components'],
            filemtime(CAL_SYNC_SCRAPER_PATH . 'build/index.js'),
            true
        );

        // Enqueue CSS
        wp_enqueue_style(
            'calendar-sync-scraper-css',
            CAL_SYNC_SCRAPER_URL . 'build/index.css',
            [],
            filemtime(CAL_SYNC_SCRAPER_PATH . 'build/index.css')
        );

        // Localize JS
        wp_localize_script('calendar-sync-scraper-js', 'calendarScraperAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('calendar_scraper_nonce')
        ]);
    }


    public function render_settings_page()
    {
        echo '<div id="calendar-sync-scraper-root"></div>'; // Your React app renders here
    }
}
