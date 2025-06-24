<?php

/**
 * Plugin Name:       Calendar Sync Scraper
 * Plugin URI:        https://github.com/ndegwamoche/calendar-sync-scraper
 * Description:       Scrapes calendar data from a specified URL and synchronizes it with Google Calendar. Supports automated scheduling and logs each run for tracking.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Martin Ndegwa Moche
 * Author URI:       https://github.com/ndegwamoche/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       calendar-sync-scraper
 *
 * @package Calendar_Sync_Scraper
 */

namespace Calendar_Sync_Scraper;

/**
 * Prevent direct access to this file for security.
 */
defined('ABSPATH') || exit;

/**
 * Define plugin constants.
 */
define('CAL_SYNC_SCRAPER_PATH', plugin_dir_path(__FILE__)); // Path to the plugin directory
define('CAL_SYNC_SCRAPER_URL', plugin_dir_url(__FILE__)); // URL to the plugin directory

/**
 * Include required class files for the plugin's functionality.
 */
require_once CAL_SYNC_SCRAPER_PATH . 'includes/class-admin-ui.php';          // Admin interface class
require_once CAL_SYNC_SCRAPER_PATH . 'includes/class-scraper.php';           // Web scraping functionality
require_once CAL_SYNC_SCRAPER_PATH . 'includes/class-logger.php';            // Logging functionality
require_once CAL_SYNC_SCRAPER_PATH . 'includes/class-db-init.php';           // Database initialization
require_once CAL_SYNC_SCRAPER_PATH . 'includes/class-data-loader.php';       // Data loading utilities
require_once CAL_SYNC_SCRAPER_PATH . 'includes/class-google-calendar.php';   // Google Calendar integration
require_once CAL_SYNC_SCRAPER_PATH . 'includes/class-events-calendar.php';   // Events calendar management

/**
 * Instantiate the database initialization class to set up necessary tables and data.
 */
$db_init = new Calendar_Sync_Scraper\DB_Init();

/**
 * Register plugin activation hooks to create database tables and insert initial data.
 */
register_activation_hook(__FILE__, array($db_init, 'create_tables'));       // Create tables on plugin activation
register_activation_hook(__FILE__, array($db_init, 'insert_initial_data')); // Insert initial data on activation


/**
 * Hook into plugins_loaded to initialize the admin UI class.
 */
add_action('plugins_loaded', function () {
    new Calendar_Sync_Scraper\Admin_UI(); // Initialize the admin interface
});

/**
 * Redirect single event pages to the events archive page.
 */
add_action('template_redirect', function () {
    if (is_singular('tribe_events')) { // Check if viewing a single event post
        wp_redirect(home_url('/events/'), 301); // Redirect to /events/ with permanent redirect
        exit; // Terminate script after redirect
    }
});
