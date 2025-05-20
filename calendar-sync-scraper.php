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
require_once CAL_SYNC_SCRAPER_PATH . 'includes/class-scheduler.php';

// Hook into plugins_loaded to initialize
add_action('plugins_loaded', function () {
    new Calendar_Sync_Scraper\Admin_UI();
    new Calendar_Sync_Scraper\Scheduler();
});
