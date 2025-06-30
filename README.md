  <h1>Calendar Sync Scraper</h1>
    <h2>Overview</h2>
    <p>The <strong>Calendar Sync Scraper</strong> is a WordPress plugin designed to scrape calendar data from a specified URL and synchronize it with Google Calendar. It supports automated scheduling and maintains logs for tracking each run. This plugin enhances event management by integrating external calendar data into your WordPress site seamlessly.</p>
    <h2>Requirements</h2>
    <ul>
        <li><strong>WordPress</strong>: Version 5.8 or higher</li>
        <li><strong>PHP</strong>: Version 7.4 or higher</li>
    </ul>
    <h2>Installation</h2>
    <ol>
        <li>Download the plugin from the <a href="https://github.com/ndegwamoche/calendar-sync-scraper">GitHub repository</a>.</li>
        <li>Upload the <code>calendar-sync-scraper</code> folder to the <code>/wp-content/plugins/</code> directory on your WordPress site.</li>
        <li>Activate the plugin through the 'Plugins' menu in WordPress.</li>
        <li>Upon activation, the plugin will automatically create necessary database tables and insert initial data.</li>
    </ol>
    <h2>Usage</h2>
    <ul>
        <li>After activation, the plugin sets up an admin interface accessible via the WordPress admin dashboard.</li>
        <li>Configure the scraper settings to specify the target URL and Google Calendar integration details.</li>
        <li>The plugin will periodically scrape data and sync events, with logs available for monitoring.</li>
    </ul>
    <h2>Features</h2>
    <ul>
        <li><strong>Data Scraping</strong>: Fetches calendar events from a specified URL.</li>
        <li><strong>Google Calendar Sync</strong>: Integrates scraped data with Google Calendar.</li>
        <li><strong>Automated Scheduling</strong>: Supports automated event updates.</li>
        <li><strong>Logging</strong>: Tracks each run for debugging and auditing.</li>
        <li><strong>Database Management</strong>: Creates and manages tables for seasons, regions, age groups, tournament levels, pools, logs, colors, and teams.</li>
    </ul>
    <h2>File Structure</h2>
    <pre>
    calendar-sync-scraper/
    ├── build/
    ├── css/
    ├── includes/
    │   ├── class-admin-ui.php
    │   ├── class-scraper.php
    │   ├── class-logger.php
    │   ├── class-db-init.php
    │   ├── class-data-loader.php
    │   ├── class-google-calendar.php
    │   └── class-events-calendar.php
    ├── node_modules/
    ├── src/
    ├── vendor/
    ├── calendar-sync-scraper.php
    ├── composer.json
    ├── package.json
    </pre>
    <h2>Configuration</h2>
    <p>Edit the <code>calendar-sync-scraper.php</code> file to adjust plugin constants or include additional configurations. Use the admin UI to set up scraping schedules and Google Calendar credentials.</p>
    <h2>Contributing</h2>
    <p>Contributions are welcome! Please fork the repository at <a href="https://github.com/ndegwamoche/calendar-sync-scraper">https://github.com/ndegwamoche/calendar-sync-scraper</a> and submit pull requests for any improvements or bug fixes.</p>
    <h2>License</h2>
    <p>This plugin is licensed under the <a href="https://www.gnu.org/licenses/gpl-2.0.html">GPL-2.0-or-later</a>.</p>
    <h2>Author</h2>
    <ul>
        <li><strong>Martin Ndegwa Moche</strong></li>
        <li><a href="https://github.com/ndegwamoche/">GitHub Profile</a></li>
    </ul>
    <h2>Support</h2>
    <p>For issues or questions, please open an issue on the <a href="https://github.com/ndegwamoche/calendar-sync-scraper/issues">GitHub repository</a> or contact the author directly.</p>
    <h2>Changelog</h2>
    <ul>
        <li><strong>1.0.0</strong>: Initial release with basic scraping, Google Calendar sync, and logging functionality.</li>
    </ul>
    <p><em>Last updated: 06:22 PM EAT, Tuesday, June 24, 2025</em></p>
