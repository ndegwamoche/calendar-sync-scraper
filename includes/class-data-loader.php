<?php

namespace Calendar_Sync_Scraper;

class Data_Loader
{
    private $wpdb;
    private $seasons_table;
    private $regions_table;
    private $age_groups_table;
    private $tournament_levels_table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        // Define table names with WordPress prefix
        $this->seasons_table = $wpdb->prefix . 'cal_sync_seasons';
        $this->regions_table = $wpdb->prefix . 'cal_sync_regions';
        $this->age_groups_table = $wpdb->prefix . 'cal_sync_age_groups';
        $this->tournament_levels_table = $wpdb->prefix . 'cal_sync_tournament_levels';
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
            "SELECT level_name FROM {$this->tournament_levels_table}",
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
        ];
    }
}
