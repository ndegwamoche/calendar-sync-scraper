<?php

/**
 * Plugin Name: Calendar Sync Scraper
 * Description: Scrapes calendar data from a specified URL and syncs it to Google Calendar. Supports automated scheduling and logs each run for tracking.
 * Version: 1.0.0
 * Author: Martin Ndegwa Moche
 */

defined('ABSPATH') || exit;

define('CAL_SYNC_SCRAPER_PATH', plugin_dir_path(__FILE__));
define('CAL_SYNC_SCRAPER_URL', plugin_dir_url(__FILE__));

// Include classes
require_once CAL_SYNC_SCRAPER_PATH . 'includes/class-admin-ui.php';
require_once CAL_SYNC_SCRAPER_PATH . 'includes/class-scraper.php';
require_once CAL_SYNC_SCRAPER_PATH . 'includes/class-logger.php';
require_once CAL_SYNC_SCRAPER_PATH . 'includes/class-db-init.php';
require_once CAL_SYNC_SCRAPER_PATH . 'includes/class-data-loader.php';
require_once CAL_SYNC_SCRAPER_PATH . 'includes/class-google-calendar.php';
require_once CAL_SYNC_SCRAPER_PATH . 'includes/class-events-calendar.php';

// Instantiate DB_Init
$db_init = new Calendar_Sync_Scraper\DB_Init();

// Register activation hooks
register_activation_hook(__FILE__, array($db_init, 'create_tables'));
register_activation_hook(__FILE__, array($db_init, 'insert_initial_data'));

// Hook into plugins_loaded to initialize other classes
add_action('plugins_loaded', function () {
    new Calendar_Sync_Scraper\Admin_UI();
});
