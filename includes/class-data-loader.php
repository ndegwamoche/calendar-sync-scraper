<?php

namespace Calendar_Sync_Scraper;

class Data_Loader
{
    private $wpdb;
    private $seasons_table;
    private $regions_table;
    private $age_groups_table;
    private $tournament_levels_table;
    private $tournament_pools_table;
    private $colors_table;
    private $teams_table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        $this->seasons_table = $wpdb->prefix . 'cal_sync_seasons';
        $this->regions_table = $wpdb->prefix . 'cal_sync_regions';
        $this->age_groups_table = $wpdb->prefix . 'cal_sync_age_groups';
        $this->tournament_levels_table = $wpdb->prefix . 'cal_sync_tournament_levels';
        $this->tournament_pools_table = $wpdb->prefix . 'cal_sync_tournament_pools';
        $this->colors_table = $wpdb->prefix . 'cal_sync_colors';
        $this->teams_table = $wpdb->prefix . 'cal_sync_teams';
    }

    public function get_seasons()
    {
        return $this->wpdb->get_results(
            "SELECT season_name, season_value FROM {$this->seasons_table} ORDER BY season_value DESC",
            ARRAY_A
        ) ?: [];
    }

    public function get_regions()
    {
        return $this->wpdb->get_results(
            "SELECT region_name, region_value FROM {$this->regions_table} ORDER BY region_order DESC",
            ARRAY_A
        ) ?: [];
    }

    public function get_age_groups()
    {
        return $this->wpdb->get_results(
            "SELECT age_group_name, age_group_value FROM {$this->age_groups_table}",
            ARRAY_A
        ) ?: [];
    }

    public function get_teams()
    {
        $results = $this->wpdb->get_results(
            "SELECT id, team_name, team_value, image_id, image_url FROM {$this->teams_table}",
            OBJECT
        );

        if (empty($results)) {
            wp_send_json_success([]);
            return [];
        }

        foreach ($results as $team) {
            if (!empty($team->image_id)) {
                $thumb = wp_get_attachment_image_src($team->image_id, 'thumbnail');
                if ($thumb && !is_wp_error($thumb)) {
                    $team->image_url = $thumb[0];
                }
            }
        }

        wp_send_json_success($results);
        return $results;
    }

    public function get_tournament_levels()
    {
        return $this->wpdb->get_results(
            "SELECT
                tl.id,
                CONCAT(
                    r.region_name,
                    '->',
                    ag.age_group_name,
                    '->',
                    level_name
                )level_name
            FROM
                {$this->tournament_levels_table} tl
            JOIN {$this->regions_table} r ON r.region_value = tl.region_id
            JOIN {$this->age_groups_table} ag ON ag.age_group_value = tl.age_group_id",
            ARRAY_A
        ) ?: [];
    }

    public function get_all_tournament_levels()
    {

        check_ajax_referer('calendar_scraper_nonce', '_ajax_nonce');

        $season_id = sanitize_text_field($_POST['season_id']);

        $levels = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT
                tl.id,
                CONCAT(
                    r.region_name,
                    '->',
                    ag.age_group_name,
                    '->',
                    tl.level_name
                ) AS level_name
            FROM
                {$this->tournament_levels_table} tl
            JOIN {$this->regions_table} r ON r.region_value = tl.region_id
            JOIN {$this->age_groups_table} ag ON ag.age_group_value = tl.age_group_id
            WHERE tl.season_id = %s",
                $season_id
            ),
            ARRAY_A
        ) ?: [];

        wp_send_json_success($levels);
        wp_die();
    }

    public function get_tournament_levels_by_region_age()
    {
        check_ajax_referer('calendar_scraper_nonce', '_ajax_nonce');

        $season_id = sanitize_text_field($_POST['season_id']);
        $region_id = sanitize_text_field($_POST['region_id']);
        $age_group_id = sanitize_text_field($_POST['age_group_id']);

        $levels = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT
                tl.id,
                CONCAT(
                    r.region_name,
                    '->',
                    ag.age_group_name,
                    '->',
                    tl.level_name
                ) AS level_name
            FROM
                {$this->tournament_levels_table} tl
            JOIN {$this->regions_table} r ON r.region_value = tl.region_id
            JOIN {$this->age_groups_table} ag ON ag.age_group_value = tl.age_group_id
            WHERE tl.region_id = %s AND tl.age_group_id = %s AND tl.season_id = %s",
                $region_id,
                $age_group_id,
                $season_id
            ),
            ARRAY_A
        ) ?: [];

        wp_send_json_success(['data' => $levels]);
        wp_die();
    }

    public function get_tournament_pools()
    {
        $season = isset($_GET['season']) ? sanitize_text_field($_GET['season']) : '';
        $region = isset($_GET['region']) ? sanitize_text_field($_GET['region']) : '';
        $age_group = isset($_GET['age_group']) ? sanitize_text_field($_GET['age_group']) : '';

        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT
                    tp.id,
                    tp.pool_name,
                    tp.pool_value,
                    tp.tournament_level,
                    s.season_name,
                    r.region_name,
                    a.age_group_name,
                    tl.google_color_id
                FROM
                    {$this->tournament_pools_table} tp
                JOIN {$this->seasons_table} s ON s.season_value = tp.season_id
                JOIN {$this->regions_table} r ON r.region_value = tp.region_id
                JOIN {$this->age_groups_table} a ON a.age_group_value = tp.age_group_id
                LEFT JOIN {$this->tournament_levels_table} tl 
                    ON tl.level_name = tp.tournament_level 
                    AND tl.season_id = tp.season_id 
                    AND tl.region_id = tp.region_id 
                    AND tl.age_group_id = tp.age_group_id
                WHERE
                    tp.season_id = %s
                    AND tp.region_id = %s
                    AND tp.age_group_id = %s",
                $season,
                $region,
                $age_group
            ),
            ARRAY_A
        ) ?: [];
    }

    public function get_all_tournament_pools($season_id)
    {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT
                    tp.id,
                    tp.pool_name,
                    tp.pool_value,
                    tp.tournament_level,
                    s.season_name,
                    r.region_name,
                    a.age_group_name,
                    tl.google_color_id,
                    tl.hex_color,
                    tp.region_id,
	                tp.age_group_id,
                    r.region_order
                FROM
                    {$this->tournament_pools_table} tp
                JOIN {$this->seasons_table} s ON s.season_value = tp.season_id
                JOIN {$this->regions_table} r ON r.region_value = tp.region_id
                JOIN {$this->age_groups_table} a ON a.age_group_value = tp.age_group_id
                LEFT JOIN {$this->tournament_levels_table} tl 
                    ON tl.level_name = tp.tournament_level 
                    AND tl.season_id = tp.season_id 
                    AND tl.region_id = tp.region_id 
                    AND tl.age_group_id = tp.age_group_id
                WHERE
                    tp.season_id = %d",
                $season_id,
            ),
            ARRAY_A
        ) ?: [];
    }

    public function get_all_data()
    {
        return [
            'seasons' => $this->get_seasons(),
            'regions' => $this->get_regions(),
            'age_groups' => $this->get_age_groups(),
            'tournament_levels' => $this->get_tournament_levels(),
            'tournament_pools' => $this->get_tournament_pools(),
        ];
    }

    public function save_level_color()
    {
        $level_id = sanitize_text_field($_POST['level_id']);
        $google_color_id = sanitize_text_field($_POST['google_color_id']);
        $hex_color = sanitize_text_field($_POST['hex_color']);

        if (!isset($level_id) || !isset($google_color_id)) {
            wp_send_json_error(['message' => 'Invalid request']);
            return;
        }

        if (intval($google_color_id) > 0) {
            $existing_color = $this->wpdb->get_var(
                $this->wpdb->prepare("SELECT COUNT(*) FROM $this->colors_table WHERE google_color_id = %s", $google_color_id)
            );

            if (!$existing_color) {
                wp_send_json_error(['message' => 'Invalid Google color ID']);
                return;
            }
        }

        $existing_level = $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM $this->tournament_levels_table WHERE id = %d", $level_id)
        );

        if ($existing_level > 0) {
            $result = $this->wpdb->update(
                $this->tournament_levels_table,
                //['google_color_id' => $google_color_id],
                ['hex_color' => $hex_color],
                ['id' => $level_id],
                ['%s'],
                ['%d']
            );
        }

        if ($result === false) {
            wp_send_json_error(['message' => 'Failed to save color for level ID: ' . $level_id]);
        } else {
            wp_send_json_success(['message' => 'Color saved successfully']);
        }
    }

    public function get_level_colors()
    {
        $results = $this->wpdb->get_results(
            "SELECT id, google_color_id, hex_color FROM {$this->tournament_levels_table} WHERE hex_color IS NOT NULL",
            ARRAY_A
        );

        $colors = [];
        foreach ($results as $row) {
            $colors[$row['id']] = [
                'google_color_id' => $row['google_color_id'],
                'hex_color' => $row['hex_color'],
            ];
        }

        wp_send_json_success($colors);
    }

    public function remove_level_color()
    {
        $level_id = isset($_POST['level_id']) ? intval($_POST['level_id']) : 0;

        if ($level_id <= 0) {
            wp_send_json_error(['message' => 'Invalid level ID']);
            return;
        }

        $existing = $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM $this->tournament_levels_table WHERE id = %d", $level_id)
        );

        if ($existing > 0) {
            $result = $this->wpdb->update(
                $this->tournament_levels_table,
                ['google_color_id' => null],
                ['id' => $level_id],
                ['%s'],
                ['%d']
            );
        }

        if ($result === false) {
            wp_send_json_error(['message' => 'Failed to remove color for level ID: ' . $level_id]);
        } else {
            wp_send_json_success(['message' => 'Color removed successfully']);
        }
    }

    public function get_google_colors()
    {
        $results = $this->wpdb->get_results(
            "SELECT id, color_name, hex_code, google_color_id FROM {$this->colors_table} ORDER BY id ASC",
            ARRAY_A
        );

        $colors = [];
        foreach ($results as $row) {
            $colors[$row['id']] = [
                'color_name' => $row['color_name'],
                'hex_code' => $row['hex_code'],
                'google_color_id' => $row['google_color_id'],
            ];
        }

        wp_send_json_success($colors);
    }

    public function clear_level_colors()
    {
        $result = $this->wpdb->query("UPDATE {$this->tournament_levels_table} SET google_color_id = NULL");
        if ($result !== false) {
            wp_send_json_success(['message' => 'All colors cleared successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to clear colors']);
        }
    }

    public function upload_team_image_from_library()
    {
        check_ajax_referer('calendar_scraper_nonce', '_ajax_nonce');

        $team_id = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
        $team_name = isset($_POST['team_name']) ? sanitize_text_field($_POST['team_name']) : '';
        $media_id = isset($_POST['media_id']) ? intval($_POST['media_id']) : 0;

        if (!$team_id || !$media_id) {
            wp_send_json_error(['message' => 'Invalid team or media ID']);
        }

        $image_url = wp_get_attachment_url($media_id);
        if (!$image_url) {
            wp_send_json_error(['message' => 'Invalid media ID']);
        }

        // Update team data
        $updated = $this->wpdb->update(
            $this->teams_table,
            [
                'image_url' => $image_url,
                'image_id'  => $media_id,
            ],
            ['id' => $team_id],
            ['%s', '%d'],
            ['%d']
        );

        if ($updated === false) {
            wp_send_json_error(['message' => 'Failed to update team image: ' . $this->wpdb->last_error]);
        }

        // Find and update events with this team
        $events = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT ID FROM {$this->wpdb->posts} WHERE post_type = 'tribe_events' AND post_status = 'publish' AND post_content LIKE %s",
            '%' . $this->wpdb->esc_like("<a href='https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#2,") . '%' . $this->wpdb->esc_like($team_name) . '</a>%'
        ));

        if ($events) {
            foreach ($events as $event) {

                if ($media_id && !is_wp_error($media_id)) {
                    set_post_thumbnail($event->ID, $media_id);
                } else {
                    error_log("Team Image Updater: Failed to set featured image for event {$event->ID}");
                }
            }
        }

        wp_send_json_success(['image_url' => $image_url]);
    }

    public function insert_teams($teams, $season, $region, $ageGroup, $poolValue)
    {
        if (empty($teams) || !is_array($teams)) {
            error_log("Insert_teams: No teams provided or invalid data");
            return false;
        }

        $inserted = 0;
        foreach ($teams as $team) {
            if (!isset($team['team_id']) || !isset($team['team_name'])) {
                error_log("Insert_teams: Skipping invalid team data: " . print_r($team, true));
                continue;
            }

            $team_id = intval($team['team_id']);
            $team_name = sanitize_text_field($team['team_name']);
            $season_id = intval($season);
            $region_id = intval($region);
            $age_group_id = intval($ageGroup);
            $pool_id = intval($poolValue);

            // Check for existing team by team_name to prevent duplicates
            $exists = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->teams_table} WHERE team_name = %s",
                $team_name
            ));

            if ($exists > 0) {
                error_log("Insert_teams: Skipping duplicate team name '{$team_name}' (team_value: {$team_id}, season: {$season_id}, region: {$region_id}, age group: {$age_group_id}, pool: {$pool_id})");
                continue;
            }

            // Insert or update team
            $result = $this->wpdb->replace(
                $this->teams_table,
                [
                    'team_name'    => $team_name,
                    'team_value'   => $team_id,
                    'image_id'     => 0,
                    'image_url'    => '',
                ],
                ['%s', '%d', '%d', '%s']
            );

            if ($result === false) {
                error_log("Insert_teams: Failed to insert/update team {$team_id}: " . $this->wpdb->last_error);
            } else {
                $inserted++;
            }
        }

        error_log("Insert_teams: Inserted/updated $inserted teams");
        return $inserted > 0;
    }
}
