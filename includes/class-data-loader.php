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

        if (!isset($level_id) || !isset($google_color_id)) {
            wp_send_json_error(['message' => 'Invalid request']);
            return;
        }

        // Validate google_color_id exists in colors table
        $existing_color = $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM $this->colors_table WHERE google_color_id = %s", $google_color_id)
        );

        if (!$existing_color) {
            wp_send_json_error(['message' => 'Invalid Google color ID']);
            return;
        }

        $existing_level = $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM $this->tournament_levels_table WHERE id = %d", $level_id)
        );

        if ($existing_level > 0) {
            $result = $this->wpdb->update(
                $this->tournament_levels_table,
                ['google_color_id' => $google_color_id],
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
            "SELECT id, google_color_id FROM {$this->tournament_levels_table} WHERE google_color_id IS NOT NULL AND google_color_id != ''",
            ARRAY_A
        );

        $colors = [];
        foreach ($results as $row) {
            $colors[$row['id']] = $row['google_color_id'];
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
}
