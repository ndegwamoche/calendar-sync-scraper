<?php

namespace Calendar_Sync_Scraper;

class DB_Init
{
    // Declare class properties
    public $seasons_table;
    public $regions_table;
    public $age_groups_table;
    public $tournament_levels_table;
    public $tournament_pools_table;
    public $logs_table;
    public $colors_table;

    public function __construct()
    {
        global $wpdb;

        // Define table names with WordPress prefix
        $this->seasons_table = $wpdb->prefix . 'cal_sync_seasons';
        $this->regions_table = $wpdb->prefix . 'cal_sync_regions';
        $this->age_groups_table = $wpdb->prefix . 'cal_sync_age_groups';
        $this->tournament_levels_table = $wpdb->prefix . 'cal_sync_tournament_levels';
        $this->tournament_pools_table = $wpdb->prefix . 'cal_sync_tournament_pools';
        $this->logs_table = $wpdb->prefix . 'cal_sync_logs';
        $this->colors_table = $wpdb->prefix . 'cal_sync_colors';
    }

    public function create_tables()
    {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();

        // Seasons table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->seasons_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_name VARCHAR(50) NOT NULL,
            season_value INT NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY season_value (season_value)
        ) $charset_collate;";
        dbDelta($sql);

        // Regions table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->regions_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            region_name VARCHAR(100) NOT NULL,
            region_value INT NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY region_value (region_value)
        ) $charset_collate;";
        dbDelta($sql);

        // Age Groups table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->age_groups_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            age_group_name VARCHAR(100) NOT NULL,
            age_group_value INT NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY age_group_value (age_group_value)
        ) $charset_collate;";
        dbDelta($sql);

        // Tournament Levels table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tournament_levels_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            level_name VARCHAR(200) NOT NULL,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            region_id BIGINT(20) UNSIGNED NOT NULL,
            age_group_id BIGINT(20) UNSIGNED NOT NULL,
            color VARCHAR(50) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY level_name (level_name)
        ) $charset_collate;";
        dbDelta($sql);

        // Tournament Pools table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tournament_pools_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tournament_level VARCHAR(200) NOT NULL,
            pool_name VARCHAR(100) NOT NULL,
            pool_value INT NOT NULL,
            is_playoff TINYINT(1) DEFAULT 0,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            region_id BIGINT(20) UNSIGNED NOT NULL,
            age_group_id BIGINT(20) UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY pool_value (pool_value)
        ) $charset_collate;";
        dbDelta($sql);

        // Logs table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->logs_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            start_datetime DATETIME NOT NULL,
            close_datetime DATETIME DEFAULT NULL,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            region_id BIGINT(20) UNSIGNED NOT NULL,
            age_group_id BIGINT(20) UNSIGNED NOT NULL,
            pool_id BIGINT(20) UNSIGNED NOT NULL,
            status VARCHAR(50) NOT NULL,
            error_message TEXT DEFAULT NULL,
            total_matches INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql);

        // Colors table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->colors_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            color_name VARCHAR(50) NOT NULL,
            hex_code VARCHAR(7) NOT NULL,
            google_color_id INT DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY color_name (color_name),
            UNIQUE KEY hex_code (hex_code)
        ) $charset_collate;";
        dbDelta($sql);

        // Debugging: Check if tables were created
        $tables = [$this->seasons_table, $this->regions_table, $this->age_groups_table, $this->tournament_levels_table, $this->tournament_pools_table];
        foreach ($tables as $table) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            if ($table_exists) {
                error_log("Table $table created successfully on " . current_time('mysql'));
            } else {
                error_log("Failed to create table $table on " . current_time('mysql'));
            }
        }
    }

    public function insert_initial_data()
    {
        global $wpdb;

        // Check if the tournament_pools table exists before inserting data
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->tournament_pools_table}'");
        if (!$table_exists) {
            error_log("Cannot insert data: Table {$this->tournament_pools_table} does not exist on " . current_time('mysql'));
            return;
        }

        // Insert Colors
        $colors = [
            ['color_name' => 'Lavender', 'hex_code' => '#A4BDFC', 'google_color_id' => 1],
            ['color_name' => 'Sage', 'hex_code' => '#33B679', 'google_color_id' => 2],
            ['color_name' => 'Grape', 'hex_code' => '#8E24AA', 'google_color_id' => 3],
            ['color_name' => 'Flamingo', 'hex_code' => '#F4511E', 'google_color_id' => 4],
            ['color_name' => 'Banana', 'hex_code' => '#F6BF26', 'google_color_id' => 5],
            ['color_name' => 'Tangerine', 'hex_code' => '#F09300', 'google_color_id' => 6],
            ['color_name' => 'Peacock', 'hex_code' => '#039BE5', 'google_color_id' => 7],
            ['color_name' => 'Graphite', 'hex_code' => '#616161', 'google_color_id' => 8],
            ['color_name' => 'Blueberry', 'hex_code' => '#3F51B5', 'google_color_id' => 9],
            ['color_name' => 'Basil', 'hex_code' => '#0B8043', 'google_color_id' => 10],
            ['color_name' => 'Tomato', 'hex_code' => '#D50000', 'google_color_id' => 11],
        ];

        foreach ($colors as $color) {
            $wpdb->replace($this->colors_table, $color);
        }

        // Insert Seasons
        $seasons = [
            ['season_name' => '2011/2012', 'season_value' => 42011],
            ['season_name' => '2012/2013', 'season_value' => 42012],
            ['season_name' => '2013/2014', 'season_value' => 42013],
            ['season_name' => '2014/2015', 'season_value' => 42014],
            ['season_name' => '2015/2016', 'season_value' => 42015],
            ['season_name' => '2016/2017', 'season_value' => 42016],
            ['season_name' => '2017/2018', 'season_value' => 42017],
            ['season_name' => '2018/2019', 'season_value' => 42018],
            ['season_name' => '2019/2020', 'season_value' => 42019],
            ['season_name' => '2020/2021', 'season_value' => 42020],
            ['season_name' => '2021/2022', 'season_value' => 42021],
            ['season_name' => '2022/2023', 'season_value' => 42022],
            ['season_name' => '2023/2024', 'season_value' => 42023],
            ['season_name' => '2024/2025', 'season_value' => 42024],
        ];
        foreach ($seasons as $season) {
            $wpdb->replace($this->seasons_table, $season);
        }

        // Insert Regions
        $regions = [
            ['region_name' => 'Bordtennis Danmark/DT', 'region_value' => 4000],
            ['region_name' => 'DGI', 'region_value' => 4005],
            ['region_name' => 'BORNHOLM', 'region_value' => 4001],
            ['region_name' => 'DGI Bornholm', 'region_value' => 4006],
            ['region_name' => 'DGI Jylland Nord', 'region_value' => 4008],
            ['region_name' => 'DGI Jylland Syd', 'region_value' => 4007],
            ['region_name' => 'DGI Sjælland', 'region_value' => 4010],
            ['region_name' => 'MIDT (Fyn)', 'region_value' => 4002],
            ['region_name' => 'VEST (Jylland)', 'region_value' => 4003],
            ['region_name' => 'ØST (Sjælland, Lolland F.)', 'region_value' => 4004],
        ];
        foreach ($regions as $region) {
            $wpdb->replace($this->regions_table, $region);
        }

        // Insert Age Groups
        $age_groups = [
            ['age_group_name' => 'Puslinge', 'age_group_value' => 4001],
            ['age_group_name' => 'Ydr/Ypg', 'age_group_value' => 4002],
            ['age_group_name' => 'Dr/Pg', 'age_group_value' => 4003],
            ['age_group_name' => 'Junior', 'age_group_value' => 4004],
            ['age_group_name' => 'U 21', 'age_group_value' => 4005],
            ['age_group_name' => 'Senior', 'age_group_value' => 4006],
            ['age_group_name' => 'Old Girls', 'age_group_value' => 4007],
            ['age_group_name' => 'Veteran 40', 'age_group_value' => 4008],
            ['age_group_name' => 'Veteran 50', 'age_group_value' => 4009],
            ['age_group_name' => 'Veteran 60', 'age_group_value' => 4010],
            ['age_group_name' => 'Veteran 65', 'age_group_value' => 4011],
            ['age_group_name' => 'Veteran 70', 'age_group_value' => 4012],
            ['age_group_name' => 'Veteran 75', 'age_group_value' => 4013],
            ['age_group_name' => 'Veteran 80', 'age_group_value' => 4014],
            ['age_group_name' => 'Veteran 85', 'age_group_value' => 4015],
            ['age_group_name' => 'Ungdom', 'age_group_value' => 4016],
            ['age_group_name' => 'Minipuslinge', 'age_group_value' => 4017],
            ['age_group_name' => 'Oldies', 'age_group_value' => 4018],
            ['age_group_name' => 'Veteran', 'age_group_value' => 4019],
            ['age_group_name' => 'Åben', 'age_group_value' => 4020],
        ];
        foreach ($age_groups as $age_group) {
            $wpdb->replace($this->age_groups_table, $age_group);
        }

        // Insert Tournament Levels
        $tournament_levels = [
            ['level_name' => 'Østserien', 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['level_name' => 'Serie 1', 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['level_name' => 'Serie 2', 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['level_name' => 'Serie 3', 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['level_name' => 'Serie 4', 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['level_name' => 'Serie 5', 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['level_name' => 'Kval.kampe til østserien', 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['level_name' => 'Kval.kampe til serie 1', 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['level_name' => 'Slutspil Østserien / Kval til 3.div', 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['level_name' => 'Slutspil Serie 1', 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['level_name' => 'Slutspil Serie 2', 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['level_name' => 'Slutspil Serie 3', 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['level_name' => 'Slutspil Serie 4', 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['level_name' => 'Slutspil Serie 5', 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
        ];
        foreach ($tournament_levels as $level) {
            $wpdb->replace($this->tournament_levels_table, $level);
        }

        // Insert Tournament Pools
        $tournament_pools = [
            // Østserien
            ['tournament_level' => 'Østserien', 'pool_name' => 'Pulje 1', 'pool_value' => 14795, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Østserien', 'pool_name' => 'Pulje 2', 'pool_value' => 14796, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            // Serie 1
            ['tournament_level' => 'Serie 1', 'pool_name' => 'Pulje 1', 'pool_value' => 14802, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Serie 1', 'pool_name' => 'Pulje 2', 'pool_value' => 14803, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Serie 1', 'pool_name' => 'Pulje 3', 'pool_value' => 14804, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Serie 1', 'pool_name' => 'Pulje 4', 'pool_value' => 14805, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            // Serie 2
            ['tournament_level' => 'Serie 2', 'pool_name' => 'Pulje 1', 'pool_value' => 14806, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Serie 2', 'pool_name' => 'Pulje 2', 'pool_value' => 14807, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Serie 2', 'pool_name' => 'Pulje 3', 'pool_value' => 14808, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Serie 2', 'pool_name' => 'Pulje 4', 'pool_value' => 14809, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Serie 2', 'pool_name' => 'Pulje 5', 'pool_value' => 14810, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Serie 2', 'pool_name' => 'Pulje 6', 'pool_value' => 14811, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            // Serie 3
            ['tournament_level' => 'Serie 3', 'pool_name' => 'Pulje 1', 'pool_value' => 14812, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Serie 3', 'pool_name' => 'Pulje 2', 'pool_value' => 14813, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Serie 3', 'pool_name' => 'Pulje 3', 'pool_value' => 14814, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Serie 3', 'pool_name' => 'Pulje 4', 'pool_value' => 14815, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Serie 3', 'pool_name' => 'Pulje 5', 'pool_value' => 14816, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Serie 3', 'pool_name' => 'Pulje 6', 'pool_value' => 14817, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            // Serie 4
            ['tournament_level' => 'Serie 4', 'pool_name' => 'Pulje 1', 'pool_value' => 14818, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Serie 4', 'pool_name' => 'Pulje 2', 'pool_value' => 14819, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Serie 4', 'pool_name' => 'Pulje 3', 'pool_value' => 14820, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Serie 4', 'pool_name' => 'Pulje 4', 'pool_value' => 14821, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Serie 4', 'pool_name' => 'Pulje 5', 'pool_value' => 14822, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Serie 4', 'pool_name' => 'Pulje 6', 'pool_value' => 14823, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Serie 4', 'pool_name' => 'Pulje 7', 'pool_value' => 14824, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Serie 4', 'pool_name' => 'Pulje 8', 'pool_value' => 14825, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            // Serie 5
            ['tournament_level' => 'Serie 5', 'pool_name' => 'Pulje 1', 'pool_value' => 14899, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Serie 5', 'pool_name' => 'Pulje 2', 'pool_value' => 14900, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Serie 5', 'pool_name' => 'Pulje 3', 'pool_value' => 14901, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            // Kval.kampe til østserien
            ['tournament_level' => 'Kval.kampe til østserien', 'pool_name' => 'Kval.kampe til østserien', 'pool_value' => 15004, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            // Kval.kampe til serie 1
            ['tournament_level' => 'Kval.kampe til serie 1', 'pool_name' => 'Kval.kampe til serie 1', 'pool_value' => 15005, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            // Playoffs
            ['tournament_level' => 'Slutspil Østserien / Kval til 3.div', 'pool_name' => 'Slutspil Østserien', 'pool_value' => 15011, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Slutspil Serie 1', 'pool_name' => 'Slutspil Serie 1', 'pool_value' => 15006, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Slutspil Serie 2', 'pool_name' => 'Slutspil Serie 2', 'pool_value' => 15007, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Slutspil Serie 3', 'pool_name' => 'Slutspil Serie 3', 'pool_value' => 15008, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Slutspil Serie 4', 'pool_name' => 'Slutspil Serie 4', 'pool_value' => 15009, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            ['tournament_level' => 'Slutspil Serie 5', 'pool_name' => 'Slutspil Serie 5', 'pool_value' => 15010, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
        ];

        foreach ($tournament_pools as $pool) {
            $wpdb->replace($this->tournament_pools_table, $pool);
        }
    }
}
