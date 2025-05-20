<div class="wrap">
    <h1>Calendar Sync Scraper</h1>

    <h2 class="nav-tab-wrapper">
        <a href="#main" class="nav-tab nav-tab-active">Main</a>
        <a href="#sheet-colors" class="nav-tab">Sheet Colors</a>
    </h2>

    <div id="tab-content">

        <div id="main" class="tab-section active">
            <form id="calendar-scraper-form">
                <!-- Your Main tab elements from the screenshot go here -->

                <button type="button" id="run-scraper-now" class="button button-primary">Run Scraper Now</button>

                <div id="scraper-progress" style="display:none;">
                    <progress value="0" max="100"></progress>
                </div>

                <div id="scraper-log">
                    <!-- Logs will be loaded here dynamically -->
                </div>
            </form>
        </div>

        <div id="sheet-colors" class="tab-section" style="display:none;">
            <h2>Tab Sheet Colors for Each Tournament Level</h2>
            <!-- Add your Sheet Color settings here -->
            <p>Here you can configure color codes for various tournament levels.</p>
        </div>

    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // Tab switching
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            $('.tab-section').hide();
            const target = $(this).attr('href');
            $(target).show();
        });

        // Run scraper button
        $('#run-scraper-now').on('click', function() {
            $('#scraper-progress').show();
            $.post(calendarScraperAjax.ajax_url, {
                action: 'run_calendar_scraper',
                _ajax_nonce: calendarScraperAjax.nonce
            }, function(response) {
                alert(response.data.message);
                $('#scraper-progress').hide();
            });
        });
    });
</script>

<style>
    .tab-section {
        margin-top: 20px;
    }
</style>