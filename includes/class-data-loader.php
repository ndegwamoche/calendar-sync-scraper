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

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        // Define table names with WordPress prefix
        $this->seasons_table = $wpdb->prefix . 'cal_sync_seasons';
        $this->regions_table = $wpdb->prefix . 'cal_sync_regions';
        $this->age_groups_table = $wpdb->prefix . 'cal_sync_age_groups';
        $this->tournament_levels_table = $wpdb->prefix . 'cal_sync_tournament_levels';
        $this->tournament_pools_table = $wpdb->prefix . 'cal_sync_tournament_pools';
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
            "SELECT region_name, region_value FROM {$this->regions_table}",
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
                    tp.tournament_level
                FROM
                    {$this->tournament_pools_table} tp
                WHERE tp.season_id = %d
                AND tp.region_id = %d
                AND tp.age_group_id = %d",
                $season,
                $region,
                $age_group
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
}
