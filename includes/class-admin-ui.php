<?php

namespace Calendar_Sync_Scraper;

class Admin_UI
{
    private $data_loader;
    private $scraper;
    private $logger;
    private $google_calendar;

    public function __construct()
    {
        // Instantiate Data_Loader
        $this->data_loader = new Data_Loader();

        // Instantiate Scraper
        $this->scraper = new Scraper();

        // Instantiate Logger
        $this->logger = new Logger();

        //Instantiate Google Calendar Sync
        $this->google_calendar = new Google_Calendar_Sync();

        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_run_calendar_scraper', [$this, 'handle_run_scraper']);
        add_action('wp_ajax_get_tournament_options', [$this, 'get_tournament_options']);
        add_action('wp_ajax_get_log_info', [$this->logger, 'get_log_info']);
        add_action('wp_ajax_save_level_color', [$this->data_loader, 'save_level_color']);
        add_action('wp_ajax_get_level_colors', [$this->data_loader, 'get_level_colors']);
        add_action('wp_ajax_remove_level_color', [$this->data_loader, 'remove_level_color']);
        add_action('wp_ajax_clear_level_colors', [$this->data_loader, 'clear_level_colors']);
        add_action('wp_ajax_get_google_colors', [$this->data_loader, 'get_google_colors']);
        add_action('wp_ajax_save_google_credentials', [$this->google_calendar, 'save_google_credentials']);
        add_action('wp_ajax_get_google_credentials', [$this->google_calendar, 'get_google_credentials']);
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

        // Fetch data using Data_Loader
        $data = $this->data_loader->get_all_data();

        // Localize JS
        wp_localize_script('calendar-sync-scraper-js', 'calendarScraperAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('calendar_scraper_nonce'),
            'seasons' => $data['seasons'],
            'regions' => $data['regions'],
            'age_groups' => $data['age_groups'],
            'tournament_levels' => $data['tournament_levels'],
        ]);
    }

    public function handle_run_scraper()
    {
        $this->scraper->run_scraper();
    }

    public function get_tournament_options()
    {
        $response = ['success' => false, 'data' => []];

        $pools = $this->data_loader->get_tournament_pools();

        $response['success'] = true;
        $response['data'] = [
            'pools' => $pools,
        ];

        wp_send_json($response);
    }

    public function render_settings_page()
    {
        echo '<div id="calendar-sync-scraper-root"></div>';
    }
}
