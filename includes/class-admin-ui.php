<?php

/**
 * Admin UI class for the Calendar Sync Scraper plugin.
 *
 * This class handles the administration interface, including menu pages, asset enqueuing,
 * and AJAX actions for the plugin's backend functionality.
 *
 * @package Calendar_Sync_Scraper
 */

namespace Calendar_Sync_Scraper;

/**
 * Class Admin_UI
 *
 * Manages the admin interface and related AJAX actions for the Calendar Sync Scraper plugin.
 */
class Admin_UI
{
    /**
     * @var Data_Loader Instance of the Data_Loader class for retrieving data.
     */
    private $data_loader;

    /**
     * @var Scraper Instance of the Scraper class for web scraping operations.
     */
    private $scraper;

    /**
     * @var Logger Instance of the Logger class for logging plugin activities.
     */
    private $logger;

    /**
     * @var Google_Calendar_Sync Instance of the Google_Calendar_Sync class for Google Calendar integration.
     */
    private $google_calendar;

    /**
     * @var Events_Calendar_Sync Instance of the Events_Calendar_Sync class for events calendar management.
     */
    private $events_calendar;

    /**
     * Constructor.
     *
     * Initializes class properties and registers WordPress hooks for admin functionality.
     */
    public function __construct()
    {
        // Instantiate dependency classes.
        $this->data_loader = new Data_Loader();
        $this->scraper = new Scraper();
        $this->logger = new Logger();
        $this->google_calendar = new Google_Calendar_Sync();
        $this->events_calendar = new Events_Calendar_Sync();

        // Register admin menu page.
        add_action('admin_menu', [$this, 'register_admin_page']);

        // Enqueue admin scripts and styles.
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // Add custom stylesheet for week view fixes in the footer.
        add_action('wp_print_footer_scripts', function () {
            echo '<link rel="stylesheet" href="' . esc_url(CAL_SYNC_SCRAPER_URL . 'css/week-view-fixes.css') . '?v=' . filemtime(CAL_SYNC_SCRAPER_PATH . 'css/week-view-fixes.css') . '" type="text/css" media="all">';
        }, 100);

        // Register AJAX actions for scraper functionality.
        add_action('wp_ajax_run_all_calendar_scraper', [$this->scraper, 'run_all_calendar_scraper']);
        add_action('wp_ajax_get_scraper_progress', [$this->scraper, 'get_scraper_progress']);

        // Register AJAX action for retrieving log information.
        add_action('wp_ajax_get_log_info', [$this->logger, 'get_log_info']);

        // Register AJAX actions for tournament data retrieval.
        add_action('wp_ajax_get_tournament_options', [$this, 'get_tournament_options']);
        add_action('wp_ajax_get_tournament_levels_by_region_age', [$this->data_loader, 'get_tournament_levels_by_region_age']);
        add_action('wp_ajax_get_all_tournament_levels', [$this->data_loader, 'get_all_tournament_levels']);

        // Register AJAX actions for managing Google Calendar settings.
        add_action('wp_ajax_save_level_color', [$this->data_loader, 'save_level_color']);
        add_action('wp_ajax_get_level_colors', [$this->data_loader, 'get_level_colors']);
        add_action('wp_ajax_remove_level_color', [$this->data_loader, 'remove_level_color']);
        add_action('wp_ajax_clear_level_colors', [$this->data_loader, 'clear_level_colors']);
        add_action('wp_ajax_get_google_colors', [$this->data_loader, 'get_google_colors']);

        // Register AJAX actions for managing Google Calendar settings.
        add_action('wp_ajax_save_google_credentials', [$this->google_calendar, 'save_google_credentials']);
        add_action('wp_ajax_get_google_credentials', [$this->google_calendar, 'get_google_credentials']);
        add_action('wp_ajax_clear_google_calendar_events', [$this->google_calendar, 'clear_google_calendar_events']);
        add_action('wp_ajax_delete_all_events_permanently', [$this->events_calendar, 'delete_all_events_permanently']);

        // Register AJAX actions for pools scraping.
        add_action('wp_ajax_fetch_page_html', [$this->scraper, 'fetch_page_html']);
        add_action('wp_ajax_check_tournament_level', [$this->scraper, 'check_tournament_level']);
        add_action('wp_ajax_check_tournament_pool', [$this->scraper, 'check_tournament_pool']);
        add_action('wp_ajax_insert_tournament_level', [$this->scraper, 'insert_tournament_level']);
        add_action('wp_ajax_insert_tournament_pool', [$this->scraper, 'insert_tournament_pool']);

        // Register AJAX actions for team management.
        add_action('wp_ajax_get_teams', [$this->data_loader, 'get_teams']);
        add_action('wp_ajax_upload_team_image_from_library', [$this->data_loader, 'upload_team_image_from_library']);
        add_action('wp_ajax_run_all_teams_scraper', [$this->scraper, 'run_all_teams_scraper']);
        add_action('wp_ajax_update_team_color', [$this->data_loader, 'update_team_color']);
    }

    /**
     * Register the admin menu page for the plugin.
     */
    public function register_admin_page()
    {
        add_menu_page(
            'Calendar Scraper',                   // Page title
            'Calendar Scraper',                   // Menu title
            'manage_options',                     // Capability required
            'calendar-sync-scraper',              // Menu slug
            [$this, 'render_settings_page'],      // Callback function to render the page
            'dashicons-controls-repeat'           // Menu icon
        );
    }

    /**
     * Enqueue scripts and styles for the admin page.
     *
     * @param string $hook The current admin page hook.
     */
    public function enqueue_assets($hook)
    {
        // Only enqueue assets on the plugin's admin page.
        if ($hook !== 'toplevel_page_calendar-sync-scraper') {
            return;
        }

        // Enqueue WordPress media scripts for image uploads.
        wp_enqueue_media();

        // Enqueue JavaScript file with dependencies.
        wp_enqueue_script(
            'calendar-sync-scraper-js',           // Handle
            CAL_SYNC_SCRAPER_URL . 'build/index.js', // File URL
            ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'media-upload', 'media-editor'], // Dependencies
            filemtime(CAL_SYNC_SCRAPER_PATH . 'build/index.js'), // Version based on file modification time
            true                                  // Load in footer
        );

        // Enqueue CSS file.
        wp_enqueue_style(
            'calendar-sync-scraper-css',          // Handle
            CAL_SYNC_SCRAPER_URL . 'build/index.css', // File URL
            [],                                   // Dependencies
            filemtime(CAL_SYNC_SCRAPER_PATH . 'build/index.css') // Version based on file modification time
        );

        // Fetch initial data for the admin interface.
        $data = $this->data_loader->get_all_data();

        // Localize script with AJAX settings and initial data.
        wp_localize_script('calendar-sync-scraper-js', 'calendarScraperAjax', [
            'ajax_url' => admin_url('admin-ajax.php'), // AJAX URL
            'nonce'    => wp_create_nonce('calendar_scraper_nonce'), // Security nonce
            'seasons' => $data['seasons'],        // Season data
            'regions' => $data['regions'],        // Region data
            'age_groups' => $data['age_groups'],  // Age group data
            'tournament_levels' => $data['tournament_levels'], // Tournament level data
        ]);
    }

    /**
     * Handle AJAX request to retrieve tournament options.
     */
    public function get_tournament_options()
    {
        // Initialize response array.
        $response = ['success' => false, 'data' => []];

        // Retrieve tournament pools data.
        $pools = $this->data_loader->get_tournament_pools();

        // Update response with success status and data.
        $response['success'] = true;
        $response['data'] = [
            'pools' => $pools,
        ];

        // Send JSON response.
        wp_send_json($response);
    }

    /**
     * Render the settings page for the plugin.
     */
    public function render_settings_page()
    {
        // Output the root div for the React-based admin interface.
        echo '<div id="calendar-sync-scraper-root"></div>';
    }
}
