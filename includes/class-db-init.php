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
    public $teams_table;

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
        $this->teams_table = $wpdb->prefix . 'cal_sync_teams';
    }

    public function create_tables()
    {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();

        //Teams table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->teams_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            team_name VARCHAR(100) NOT NULL,
            team_value INT(11) NOT NULL,
            team_color VARCHAR(10) NOT NULL DEFAULT '',
            image_id BIGINT(20) UNSIGNED DEFAULT NULL,
            image_url MEDIUMTEXT DEFAULT NULL,
            season_id INT(11) NOT NULL,
            region_id INT(11) NOT NULL,
            age_group_id INT(11) NOT NULL,
            pool_id INT(11) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql);

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
            region_order TINYINT(1) DEFAULT 0,
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
            google_color_id INT DEFAULT NULL,
            hex_color VARCHAR(10) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_level (level_name, season_id, region_id, age_group_id)
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
            session_id VARCHAR(255) DEFAULT NULL, 
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
            PRIMARY KEY (id),
            UNIQUE KEY session_id_unique (session_id)
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
            ['region_name' => 'Bordtennis Danmark/DT', 'region_value' => 4000, 'region_order' => 1],
            ['region_name' => 'DGI', 'region_value' => 4005],
            ['region_name' => 'BORNHOLM', 'region_value' => 4001],
            ['region_name' => 'DGI Bornholm', 'region_value' => 4006],
            ['region_name' => 'DGI Jylland Nord', 'region_value' => 4008],
            ['region_name' => 'DGI Jylland Syd', 'region_value' => 4007],
            ['region_name' => 'DGI Sjælland', 'region_value' => 4010],
            ['region_name' => 'MIDT (Fyn)', 'region_value' => 4002],
            ['region_name' => 'VEST (Jylland)', 'region_value' => 4003],
            ['region_name' => 'ØST (Sjælland, Lolland F.)', 'region_value' => 4004, 'region_order' => 1],
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
            ['level_name' => 'DrengeLiga', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            ['level_name' => 'Drengeliga Kvartfinaler', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            ['level_name' => 'Drengeliga Semifinaler', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            ['level_name' => 'Drengeliga Finale & 3. plads', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            ['level_name' => '2024/2025 Pigerække - Stævne 2', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            ['level_name' => '2024/2025 Pigerække - Stævne 3', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            ['level_name' => '2024/2025 Pigerække - Stævne 1', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            ['level_name' => 'JuniorLiga', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4004],
            ['level_name' => 'Juniorliga Semifinaler', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4004],
            ['level_name' => 'Juniorliga Finale & 3. plads', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4004],
            ['level_name' => 'Herre BordtennisLiga Semifinaler', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            ['level_name' => 'Herre BordtennisLiga Finale', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            ['level_name' => 'Herre BordtennisLiga Grundspil 1', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            ['level_name' => 'Herre BordtennisLiga Grundspil 2', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            ['level_name' => 'Herre 1. Division oprykningsspil', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            ['level_name' => 'Dame Finale 23/24', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            ['level_name' => 'Herre 1. division Nedrykningsspil', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            ['level_name' => 'Herre 2. Division Oprykningsspil', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            ['level_name' => 'Herre 2. Division Nedrykningsspil', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            ['level_name' => 'Herre 3. Division Oprykningsspil', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            ['level_name' => 'Herre 3. Division Nedrykningsspil', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            ['level_name' => 'Herre 1. Division Grundspil', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            ['level_name' => 'Herre 2. Division Grundspil', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            ['level_name' => 'Herre 3. Division Grundspil', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            ['level_name' => 'Dame 1. Division Grundspil', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            ['level_name' => 'Dame BordtennisLigaen Grundspil', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            ['level_name' => 'Dame BordtennisLiga Finale', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            ['level_name' => 'Dame Bordtennisligaen Semifinaler', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            ['level_name' => 'Old Girls', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4007],
            ['level_name' => 'Old Girls, Slutspil', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4007],
            ['level_name' => 'Veteran 40', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4008],
            ['level_name' => 'Veteran 40, Slutspil', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4008],
            ['level_name' => 'Veteran 50', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4009],
            ['level_name' => 'Veteran 50, Slutspil', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4009],
            ['level_name' => 'Veteran 60', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            ['level_name' => 'Bat 60+ Jylland (Vest)', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            ['level_name' => 'Bat 60+ Sjælland og øerne', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            ['level_name' => 'Veteran 60, Slutspil', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            ['level_name' => 'Veteran 70', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4012],
            ['level_name' => 'Veteran 75', 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4013],
            ['level_name' => 'Bornholmsserien', 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4006],
            ['level_name' => 'Serie 1', 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4006],
            ['level_name' => 'Serie 2', 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4006],
            ['level_name' => 'Serie 3', 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4006],
            ['level_name' => 'Slutspil-Bornholmsserien', 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4006],
            ['level_name' => 'Slutspil-Serie 1', 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4006],
            ['level_name' => 'Slutspil-Serie 2', 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4006],
            ['level_name' => 'Forårsturnering 2025', 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4006],
            ['level_name' => 'Veteran A', 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4008],
            ['level_name' => 'Sørens - 60+', 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4010],
            ['level_name' => 'Efterår 24', 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4006],
            ['level_name' => 'Forår 2025 Senior', 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4006],
            ['level_name' => 'Efterår 24 Ungdom', 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4016],
            ['level_name' => '2. halvdel forår 2025 Ungdom', 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4016],
            ['level_name' => 'DGI/BTDK Efterår 2024 - Jyllandsserien', 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['level_name' => 'DGI/BTDK Efterår 2024 - Serie 1', 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['level_name' => 'DGI/BTDK Efterår 2024 - Serie 2', 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['level_name' => 'DGI/BTDK Efterår 2024 - Serie 3', 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['level_name' => 'DGI/BTDK Efterår 2024 - Serie 4', 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['level_name' => 'DGI/BTDK Efterår 2024 - Serie 5', 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['level_name' => 'DGI/BTDK 2025 Forår - DT-kval.', 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['level_name' => 'DGI/BTDK 2025 Forår - Serie 1-kval.', 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['level_name' => 'DGI/BTDK 2025 Forår - Serie 2 kval.', 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['level_name' => 'DGI/BTDK 2025 Forår - Serie 3-kval.', 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['level_name' => 'DGI/BTDK 2025 Forår - Serie 4-kval.', 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['level_name' => 'DGI/BTDK 2025 Forår - Serie 5', 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['level_name' => 'DGI/BTDK 2025 Forår - JS-Kval.', 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['level_name' => 'Finalestævne 2025', 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['level_name' => 'Masters 40', 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4008],
            ['level_name' => 'Masters 50', 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4009],
            ['level_name' => 'Masters 60', 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4010],
            ['level_name' => 'Slutspil - Masters 60', 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4010],
            ['level_name' => 'Ungdom op til 12 år - Efterår', 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4016],
            ['level_name' => 'Ungdom 13-19 år - Efterår', 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4016],
            ['level_name' => 'Ungdom op til 12 år - Forår', 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4016],
            ['level_name' => 'Ungdom 13-19 år - Forår', 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4016],
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
            // DrengeLiga
            ['tournament_level' => 'DrengeLiga', 'pool_name' => 'Pulje 1', 'pool_value' => 14826, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            ['tournament_level' => 'DrengeLiga', 'pool_name' => 'Pulje 2', 'pool_value' => 14827, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            // Drengeliga Kvartfinaler
            ['tournament_level' => 'Drengeliga Kvartfinaler', 'pool_name' => 'Kvartfinale 1', 'pool_value' => 15038, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            ['tournament_level' => 'Drengeliga Kvartfinaler', 'pool_name' => 'Kvartfinale 2', 'pool_value' => 15039, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            // Drengeliga Semifinaler
            ['tournament_level' => 'Drengeliga Semifinaler', 'pool_name' => 'Semifinale 1', 'pool_value' => 15040, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            ['tournament_level' => 'Drengeliga Semifinaler', 'pool_name' => 'Semifinale 2', 'pool_value' => 15041, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            // Drengeliga Finale & 3. plads
            ['tournament_level' => 'Drengeliga Finale & 3. plads', 'pool_name' => 'Bronzekamp', 'pool_value' => 15042, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            ['tournament_level' => 'Drengeliga Finale & 3. plads', 'pool_name' => 'Finale', 'pool_value' => 15043, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            // 2024/2025 Pigerække - Stævne 2
            ['tournament_level' => '2024/2025 Pigerække - Stævne 2', 'pool_name' => 'Pigerækken ØST 1', 'pool_value' => 14902, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            ['tournament_level' => '2024/2025 Pigerække - Stævne 2', 'pool_name' => 'Pigerækken ØST 2', 'pool_value' => 14903, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            ['tournament_level' => '2024/2025 Pigerække - Stævne 2', 'pool_name' => 'Pigerækken ØST 3', 'pool_value' => 14904, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            // 2024/2025 Pigerække - Stævne 3
            ['tournament_level' => '2024/2025 Pigerække - Stævne 3', 'pool_name' => 'ØST 1', 'pool_value' => 15012, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            ['tournament_level' => '2024/2025 Pigerække - Stævne 3', 'pool_name' => 'ØST 2', 'pool_value' => 15013, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            ['tournament_level' => '2024/2025 Pigerække - Stævne 3', 'pool_name' => 'ØST 3', 'pool_value' => 15014, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            // 2024/2025 Pigerække - Stævne 1
            ['tournament_level' => '2024/2025 Pigerække - Stævne 1', 'pool_name' => 'Pigerække ØST 1', 'pool_value' => 14753, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            ['tournament_level' => '2024/2025 Pigerække - Stævne 1', 'pool_name' => 'Pigerække ØST 2', 'pool_value' => 14754, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            ['tournament_level' => '2024/2025 Pigerække - Stævne 1', 'pool_name' => 'Pigerække ØST 3', 'pool_value' => 14889, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4003],
            // JuniorLiga
            ['tournament_level' => 'JuniorLiga', 'pool_name' => 'Pulje 1', 'pool_value' => 14828, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4004],
            ['tournament_level' => 'JuniorLiga', 'pool_name' => 'Pulje 2', 'pool_value' => 14829, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4004],
            // Juniorliga Semifinaler
            ['tournament_level' => 'Juniorliga Semifinaler', 'pool_name' => 'Semifinale 1', 'pool_value' => 15017, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4004],
            ['tournament_level' => 'Juniorliga Semifinaler', 'pool_name' => 'Semifinale 2', 'pool_value' => 15018, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4004],
            // Juniorliga Finale & 3. plads
            ['tournament_level' => 'Juniorliga Finale & 3. plads', 'pool_name' => 'Finale', 'pool_value' => 15019, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4004],
            ['tournament_level' => 'Juniorliga Finale & 3. plads', 'pool_name' => '3. Plads', 'pool_value' => 15020, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4004],
            // Herre BordtennisLiga Semifinaler
            ['tournament_level' => 'Herre BordtennisLiga Semifinaler', 'pool_name' => 'Semifinale 1', 'pool_value' => 15021, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            ['tournament_level' => 'Herre BordtennisLiga Semifinaler', 'pool_name' => 'Semifinale 2', 'pool_value' => 15022, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            // Herre BordtennisLiga Finale
            ['tournament_level' => 'Herre BordtennisLiga Finale', 'pool_name' => 'Finale', 'pool_value' => 15023, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            // Herre BordtennisLiga Grundspil 1
            ['tournament_level' => 'Herre BordtennisLiga Grundspil 1', 'pool_name' => 'G1', 'pool_value' => 14644, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            // Herre BordtennisLiga Grundspil 2
            ['tournament_level' => 'Herre BordtennisLiga Grundspil 2', 'pool_name' => 'G2', 'pool_value' => 14905, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            // Herre 1. Division oprykningsspil
            ['tournament_level' => 'Herre 1. Division oprykningsspil', 'pool_name' => '1. Div Herre Oprykning', 'pool_value' => 14906, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            // Dame Finale 23/24
            ['tournament_level' => 'Dame Finale 23/24', 'pool_name' => 'Finale', 'pool_value' => 14755, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            // Herre 1. division Nedrykningsspil
            ['tournament_level' => 'Herre 1. division Nedrykningsspil', 'pool_name' => '1. Division Nedrykning', 'pool_value' => 14907, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            // Herre 2. Division Oprykningsspil
            ['tournament_level' => 'Herre 2. Division Oprykningsspil', 'pool_name' => '2. Division Oprykning', 'pool_value' => 14908, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            // Herre 2. Division Nedrykningsspil
            ['tournament_level' => 'Herre 2. Division Nedrykningsspil', 'pool_name' => '2. Division Nedrykning', 'pool_value' => 14909, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            // Herre 3. Division Oprykningsspil
            ['tournament_level' => 'Herre 3. Division Oprykningsspil', 'pool_name' => 'Herre 3. Division Oprykning', 'pool_value' => 14910, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            // Herre 3. Division Nedrykningsspil
            ['tournament_level' => 'Herre 3. Division Nedrykningsspil', 'pool_name' => 'Herre 3. Division Nedrykning', 'pool_value' => 14911, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            // Herre 1. Division Grundspil
            ['tournament_level' => 'Herre 1. Division Grundspil', 'pool_name' => 'Pulje 1', 'pool_value' => 14645, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            // Herre 2. Division Grundspil
            ['tournament_level' => 'Herre 2. Division Grundspil', 'pool_name' => 'Pulje 1', 'pool_value' => 14686, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            ['tournament_level' => 'Herre 2. Division Grundspil', 'pool_name' => 'Pulje 2', 'pool_value' => 14687, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            // Herre 3. Division Grundspil
            ['tournament_level' => 'Herre 3. Division Grundspil', 'pool_name' => 'Pulje 1', 'pool_value' => 14688, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            ['tournament_level' => 'Herre 3. Division Grundspil', 'pool_name' => 'Pulje 2', 'pool_value' => 14689, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            // Dame 1. Division Grundspil
            ['tournament_level' => 'Dame 1. Division Grundspil', 'pool_name' => 'Pulje 1', 'pool_value' => 14690, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            // Dame BordtennisLigaen Grundspil
            ['tournament_level' => 'Dame BordtennisLigaen Grundspil', 'pool_name' => 'Pulje 1', 'pool_value' => 14691, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            // Dame BordtennisLiga Finale
            ['tournament_level' => 'Dame BordtennisLiga Finale', 'pool_name' => 'Finale', 'pool_value' => 15026, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            // Dame Bordtennisligaen Semifinaler
            ['tournament_level' => 'Dame Bordtennisligaen Semifinaler', 'pool_name' => 'Semifinale 1', 'pool_value' => 15015, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            ['tournament_level' => 'Dame Bordtennisligaen Semifinaler', 'pool_name' => 'Semifinale 2', 'pool_value' => 15016, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4006],
            // Old Girls
            ['tournament_level' => 'Old Girls', 'pool_name' => 'DM-række', 'pool_value' => 14835, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4007],
            ['tournament_level' => 'Old Girls', 'pool_name' => '1. Division, Pulje 1', 'pool_value' => 14836, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4007],
            ['tournament_level' => 'Old Girls', 'pool_name' => '1. Division, Pulje 2', 'pool_value' => 14837, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4007],
            // Old Girls, Slutspil
            ['tournament_level' => 'Old Girls, Slutspil', 'pool_name' => '1. Division, Opspil', 'pool_value' => 14918, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4007],
            ['tournament_level' => 'Old Girls, Slutspil', 'pool_name' => '1. Division, Nedspil', 'pool_value' => 14919, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4007],
            // Veteran 40
            ['tournament_level' => 'Veteran 40', 'pool_name' => 'DM-række, Pulje 1', 'pool_value' => 14838, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4008],
            ['tournament_level' => 'Veteran 40', 'pool_name' => 'DM-række, Pulje 2', 'pool_value' => 14839, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4008],
            ['tournament_level' => 'Veteran 40', 'pool_name' => 'DM-række, Pulje 3', 'pool_value' => 14840, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4008],
            // Veteran 40, Slutspil
            ['tournament_level' => 'Veteran 40, Slutspil', 'pool_name' => 'DM-rækken, Medaljespil', 'pool_value' => 14912, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4008],
            ['tournament_level' => 'Veteran 40, Slutspil', 'pool_name' => 'DM-rækken, Nedspil', 'pool_value' => 14913, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4008],
            // Veteran 50
            ['tournament_level' => 'Veteran 50', 'pool_name' => 'DM-række, Pulje 1', 'pool_value' => 14841, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4009],
            ['tournament_level' => 'Veteran 50', 'pool_name' => 'DM-række, Pulje 2', 'pool_value' => 14842, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4009],
            ['tournament_level' => 'Veteran 50', 'pool_name' => 'DM-række, Pulje 3', 'pool_value' => 14843, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4009],
            ['tournament_level' => 'Veteran 50', 'pool_name' => '1. Divison, Pulje 1', 'pool_value' => 14844, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4009],
            // Veteran 50, Slutspil
            ['tournament_level' => 'Veteran 50, Slutspil', 'pool_name' => 'DM-rækken, Medaljespil', 'pool_value' => 14914, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4009],
            ['tournament_level' => 'Veteran 50, Slutspil', 'pool_name' => 'DM-rækken, Nedspil', 'pool_value' => 14915, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4009],
            // Veteran 60
            ['tournament_level' => 'Veteran 60', 'pool_name' => 'DM-række Pulje 1', 'pool_value' => 14846, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            ['tournament_level' => 'Veteran 60', 'pool_name' => 'DM-række, Pulje 2', 'pool_value' => 14847, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            ['tournament_level' => 'Veteran 60', 'pool_name' => 'DM-række, Pulje 3', 'pool_value' => 14848, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            ['tournament_level' => 'Veteran 60', 'pool_name' => '1. Division, Pulje 1', 'pool_value' => 14849, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            // Bat 60+ Jylland (Vest)
            ['tournament_level' => 'Bat 60+ Jylland (Vest)', 'pool_name' => 'Pulje 1 - A rækken', 'pool_value' => 14890, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            ['tournament_level' => 'Bat 60+ Jylland (Vest)', 'pool_name' => 'Pulje 2 - A rækken', 'pool_value' => 14891, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            ['tournament_level' => 'Bat 60+ Jylland (Vest)', 'pool_name' => 'Pulje 3 - B rækken', 'pool_value' => 14892, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            ['tournament_level' => 'Bat 60+ Jylland (Vest)', 'pool_name' => 'Pulje 4 - B rækken', 'pool_value' => 14893, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            ['tournament_level' => 'Bat 60+ Jylland (Vest)', 'pool_name' => 'Pulje 5 - B rækken', 'pool_value' => 14894, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            ['tournament_level' => 'Bat 60+ Jylland (Vest)', 'pool_name' => 'Pulje 7 - C rækken', 'pool_value' => 14896, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            ['tournament_level' => 'Bat 60+ Jylland (Vest)', 'pool_name' => 'Pulje 8 - C rækken', 'pool_value' => 14897, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            // Bat 60+ Sjælland og øerne
            ['tournament_level' => 'Bat 60+ Sjælland og øerne', 'pool_name' => 'Pulje A1', 'pool_value' => 14858, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            ['tournament_level' => 'Bat 60+ Sjælland og øerne', 'pool_name' => 'Pulje A2', 'pool_value' => 14859, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            ['tournament_level' => 'Bat 60+ Sjælland og øerne', 'pool_name' => 'Pulje B1', 'pool_value' => 14860, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            ['tournament_level' => 'Bat 60+ Sjælland og øerne', 'pool_name' => 'Pulje B2', 'pool_value' => 14861, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            ['tournament_level' => 'Bat 60+ Sjælland og øerne', 'pool_name' => 'Pulje B3', 'pool_value' => 14862, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            ['tournament_level' => 'Bat 60+ Sjælland og øerne', 'pool_name' => 'Pulje C1', 'pool_value' => 14863, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            ['tournament_level' => 'Bat 60+ Sjælland og øerne', 'pool_name' => 'Pulje C2', 'pool_value' => 14864, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            ['tournament_level' => 'Bat 60+ Sjælland og øerne', 'pool_name' => 'Pulje C3', 'pool_value' => 14865, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            ['tournament_level' => 'Bat 60+ Sjælland og øerne', 'pool_name' => 'Pulje D1', 'pool_value' => 14866, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            // Veteran 60, Slutspil
            ['tournament_level' => 'Veteran 60, Slutspil', 'pool_name' => 'DM-rækken, Medaljespil', 'pool_value' => 14916, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            ['tournament_level' => 'Veteran 60, Slutspil', 'pool_name' => 'DM-rækken, Nedspil', 'pool_value' => 14917, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4010],
            // Veteran 70
            ['tournament_level' => 'Veteran 70', 'pool_name' => 'DM-række, Pulje 1', 'pool_value' => 14850, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4012],
            // Veteran 75
            ['tournament_level' => 'Veteran 75', 'pool_name' => 'DM-række, Pulje 1', 'pool_value' => 14851, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4000, 'age_group_id' => 4013],
            // Bornholmsserien
            ['tournament_level' => 'Bornholmsserien', 'pool_name' => 'Bornholmsserien', 'pool_value' => 14830, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4006],
            // Serie 1
            ['tournament_level' => 'Serie 1', 'pool_name' => 'Serie 1', 'pool_value' => 14831, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4006],
            // Serie 2
            ['tournament_level' => 'Serie 2', 'pool_name' => 'Serie 2', 'pool_value' => 14832, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4006],
            // Serie 3
            ['tournament_level' => 'Serie 3', 'pool_name' => 'Serie 3', 'pool_value' => 14833, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4006],
            // Slutspil-Bornholmsserien
            ['tournament_level' => 'Slutspil-Bornholmsserien', 'pool_name' => 'Slutspil', 'pool_value' => 15001, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4006],
            // Slutspil-Serie 1
            ['tournament_level' => 'Slutspil-Serie 1', 'pool_name' => 'Slutspil', 'pool_value' => 15002, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4006],
            // Slutspil-Serie 2
            ['tournament_level' => 'Slutspil-Serie 2', 'pool_name' => 'Slutspil', 'pool_value' => 15003, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4006],
            // Forårsturnering 2025
            ['tournament_level' => 'Forårsturnering 2025', 'pool_name' => 'A-række', 'pool_value' => 15024, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4006],
            ['tournament_level' => 'Forårsturnering 2025', 'pool_name' => 'B-række', 'pool_value' => 15025, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4006],
            // Veteran A
            ['tournament_level' => 'Veteran A', 'pool_name' => 'Veteran A', 'pool_value' => 14834, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4008],
            // Sørens - 60+
            ['tournament_level' => 'Sørens - 60+', 'pool_name' => '60+ A', 'pool_value' => 14867, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4010],
            ['tournament_level' => 'Sørens - 60+', 'pool_name' => '60+ B', 'pool_value' => 14868, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4010],
            ['tournament_level' => 'Sørens - 60+', 'pool_name' => '60+ C', 'pool_value' => 14869, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4010],
            ['tournament_level' => 'Sørens - 60+', 'pool_name' => '60+ D', 'pool_value' => 14870, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4010],
            ['tournament_level' => 'Sørens - 60+', 'pool_name' => '60+ E', 'pool_value' => 14871, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4001, 'age_group_id' => 4010],
            // Efterår 24
            ['tournament_level' => 'Efterår 24', 'pool_name' => 'R1 Fynserie', 'pool_value' => 14789, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4006],
            ['tournament_level' => 'Efterår 24', 'pool_name' => 'R2 Serie 1', 'pool_value' => 14790, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4006],
            ['tournament_level' => 'Efterår 24', 'pool_name' => 'R3 Serie 2', 'pool_value' => 14791, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4006],
            ['tournament_level' => 'Efterår 24', 'pool_name' => 'R4 Serie 3', 'pool_value' => 14792, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4006],
            ['tournament_level' => 'Efterår 24', 'pool_name' => 'R5 Serie 4', 'pool_value' => 14793, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4006],
            ['tournament_level' => 'Efterår 24', 'pool_name' => 'R6 Serie 5', 'pool_value' => 14794, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4006],
            // Forår 2025 Senior
            ['tournament_level' => 'Forår 2025 Senior', 'pool_name' => 'R1 Fynserie Oprykning', 'pool_value' => 14957, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4006],
            ['tournament_level' => 'Forår 2025 Senior', 'pool_name' => 'R2 Fynserie Nedrykning', 'pool_value' => 14958, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4006],
            ['tournament_level' => 'Forår 2025 Senior', 'pool_name' => 'R3 Serie 1 Oprykning', 'pool_value' => 14959, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4006],
            ['tournament_level' => 'Forår 2025 Senior', 'pool_name' => 'R4 Serie 1 Nedrykning', 'pool_value' => 14960, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4006],
            ['tournament_level' => 'Forår 2025 Senior', 'pool_name' => 'R5 Serie 2 Oprykning', 'pool_value' => 14961, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4006],
            ['tournament_level' => 'Forår 2025 Senior', 'pool_name' => 'R6 Serie 2 Nedrykning', 'pool_value' => 14962, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4006],
            ['tournament_level' => 'Forår 2025 Senior', 'pool_name' => 'R7 Serie 3 Oprykning', 'pool_value' => 14963, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4006],
            ['tournament_level' => 'Forår 2025 Senior', 'pool_name' => 'R8 Serie 3 Nedrykning', 'pool_value' => 14964, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4006],
            ['tournament_level' => 'Forår 2025 Senior', 'pool_name' => 'R9 Serie 4 Oprykning', 'pool_value' => 14965, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4006],
            ['tournament_level' => 'Forår 2025 Senior', 'pool_name' => 'R10 Serie 4Kvalifikation', 'pool_value' => 14966, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4006],
            ['tournament_level' => 'Forår 2025 Senior', 'pool_name' => 'R11 Serie 5 Forår', 'pool_value' => 14967, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4006],
            // Efterår 24 Ungdom
            ['tournament_level' => 'Efterår 24 Ungdom', 'pool_name' => 'Pulje 1', 'pool_value' => 14885, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4016],
            ['tournament_level' => 'Efterår 24 Ungdom', 'pool_name' => 'Pulje 2', 'pool_value' => 14886, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4016],
            ['tournament_level' => 'Efterår 24 Ungdom', 'pool_name' => 'Pulje 3', 'pool_value' => 14887, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4016],
            ['tournament_level' => 'Efterår 24 Ungdom', 'pool_name' => 'Pulje 4', 'pool_value' => 14888, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4016],
            // 2. halvdel forår 2025 Ungdom
            ['tournament_level' => '2. halvdel forår 2025 Ungdom', 'pool_name' => 'Pulje 1', 'pool_value' => 14978, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4016],
            ['tournament_level' => '2. halvdel forår 2025 Ungdom', 'pool_name' => 'Pulje 2', 'pool_value' => 14979, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4016],
            ['tournament_level' => '2. halvdel forår 2025 Ungdom', 'pool_name' => 'Pulje 3', 'pool_value' => 14980, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4002, 'age_group_id' => 4016],
            // DGI/BTDK Efterår 2024 - Jyllandsserien
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Jyllandsserien', 'pool_name' => 'Pulje 1', 'pool_value' => 14757, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Jyllandsserien', 'pool_name' => 'Pulje 2', 'pool_value' => 14758, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Jyllandsserien', 'pool_name' => 'Pulje 3', 'pool_value' => 14759, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            // DGI/BTDK Efterår 2024 - Serie 1
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 1', 'pool_name' => 'Pulje 1', 'pool_value' => 14756, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 1', 'pool_name' => 'Pulje 2', 'pool_value' => 14760, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 1', 'pool_name' => 'Pulje 3', 'pool_value' => 14761, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 1', 'pool_name' => 'Pulje 4', 'pool_value' => 14762, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 1', 'pool_name' => 'Pulje 5', 'pool_value' => 14763, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            // DGI/BTDK Efterår 2024 - Serie 2
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 2', 'pool_name' => 'Pulje 1', 'pool_value' => 14764, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 2', 'pool_name' => 'Pulje 2', 'pool_value' => 14765, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 2', 'pool_name' => 'Pulje 3', 'pool_value' => 14766, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 2', 'pool_name' => 'Pulje 4', 'pool_value' => 14767, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 2', 'pool_name' => 'Pulje 5', 'pool_value' => 14768, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 2', 'pool_name' => 'Pulje 6', 'pool_value' => 14769, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 2', 'pool_name' => 'Pulje 7', 'pool_value' => 14770, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 2', 'pool_name' => 'Pulje 8', 'pool_value' => 14771, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            // DGI/BTDK Efterår 2024 - Serie 3
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 3', 'pool_name' => 'Pulje 1', 'pool_value' => 14772, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 3', 'pool_name' => 'Pulje 2', 'pool_value' => 14773, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 3', 'pool_name' => 'Pulje 3', 'pool_value' => 14774, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 3', 'pool_name' => 'Pulje 4', 'pool_value' => 14775, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 3', 'pool_name' => 'Pulje 5', 'pool_value' => 14776, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 3', 'pool_name' => 'Pulje 6', 'pool_value' => 14777, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 3', 'pool_name' => 'Pulje 7', 'pool_value' => 14778, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 3', 'pool_name' => 'Pulje 8', 'pool_value' => 14779, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            // DGI/BTDK Efterår 2024 - Serie 4
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 4', 'pool_name' => 'Pulje 1', 'pool_value' => 14780, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 4', 'pool_name' => 'Pulje 2', 'pool_value' => 14781, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 4', 'pool_name' => 'Pulje 3', 'pool_value' => 14782, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 4', 'pool_name' => 'Pulje 4', 'pool_value' => 14783, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 4', 'pool_name' => 'Pulje 5', 'pool_value' => 14784, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 4', 'pool_name' => 'Pulje 6', 'pool_value' => 14785, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            // DGI/BTDK Efterår 2024 - Serie 5
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 5', 'pool_name' => 'Pulje 1', 'pool_value' => 14786, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 5', 'pool_name' => 'Pulje 2', 'pool_value' => 14787, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK Efterår 2024 - Serie 5', 'pool_name' => 'Pulje 3', 'pool_value' => 14788, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            // DGI/BTDK 2025 Forår - DT-kval.
            ['tournament_level' => 'DGI/BTDK 2025 Forår - DT-kval.', 'pool_name' => 'DT-Kval.', 'pool_value' => 14923, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            // DGI/BTDK 2025 Forår - Serie 1-kval.
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 1-kval.', 'pool_name' => 'Pulje 1', 'pool_value' => 14927, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 1-kval.', 'pool_name' => 'Pulje 2', 'pool_value' => 14928, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 1-kval.', 'pool_name' => 'Pulje 3', 'pool_value' => 14929, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 1-kval.', 'pool_name' => 'Pulje 4', 'pool_value' => 14930, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 1-kval.', 'pool_name' => 'Pulje 5', 'pool_value' => 14931, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 1-kval.', 'pool_name' => 'Pulje 6', 'pool_value' => 14932, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            // DGI/BTDK 2025 Forår - Serie 2 kval.
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 2 kval.', 'pool_name' => 'Pulje 1', 'pool_value' => 14933, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 2 kval.', 'pool_name' => 'Pulje 2', 'pool_value' => 14934, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 2 kval.', 'pool_name' => 'Pulje 3', 'pool_value' => 14935, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 2 kval.', 'pool_name' => 'Pulje 4', 'pool_value' => 14936, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 2 kval.', 'pool_name' => 'Pulje 5', 'pool_value' => 14937, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 2 kval.', 'pool_name' => 'Pulje 6', 'pool_value' => 14938, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 2 kval.', 'pool_name' => 'Pulje 7', 'pool_value' => 14939, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 2 kval.', 'pool_name' => 'Pulje 8', 'pool_value' => 14940, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            // DGI/BTDK 2025 Forår - Serie 3-kval.
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 3-kval.', 'pool_name' => 'Pulje 1', 'pool_value' => 14941, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 3-kval.', 'pool_name' => 'Pulje 2', 'pool_value' => 14942, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 3-kval.', 'pool_name' => 'Pulje 3', 'pool_value' => 14943, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 3-kval.', 'pool_name' => 'Pulje 4', 'pool_value' => 14944, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 3-kval.', 'pool_name' => 'Pulje 5', 'pool_value' => 14945, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 3-kval.', 'pool_name' => 'Pulje 6', 'pool_value' => 14946, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 3-kval.', 'pool_name' => 'Pulje 7', 'pool_value' => 14947, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            // DGI/BTDK 2025 Forår - Serie 4-kval.
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 4-kval.', 'pool_name' => 'Pulje 1', 'pool_value' => 14948, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 4-kval.', 'pool_name' => 'Pulje 2', 'pool_value' => 14949, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 4-kval.', 'pool_name' => 'Pulje 3', 'pool_value' => 14950, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 4-kval.', 'pool_name' => 'Pulje 4', 'pool_value' => 14951, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 4-kval.', 'pool_name' => 'Pulje 5', 'pool_value' => 14952, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 4-kval.', 'pool_name' => 'Pulje 6', 'pool_value' => 14953, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            // DGI/BTDK 2025 Forår - Serie 5
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 5', 'pool_name' => 'Pulje 1', 'pool_value' => 14954, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 5', 'pool_name' => 'Pulje 2', 'pool_value' => 14955, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - Serie 5', 'pool_name' => 'Pulje 3', 'pool_value' => 14956, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            // DGI/BTDK 2025 Forår - JS-Kval.
            ['tournament_level' => 'DGI/BTDK 2025 Forår - JS-Kval.', 'pool_name' => 'Pulje 1', 'pool_value' => 14924, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - JS-Kval.', 'pool_name' => 'Pulje 2', 'pool_value' => 14925, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - JS-Kval.', 'pool_name' => 'Pulje 3', 'pool_value' => 14926, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'DGI/BTDK 2025 Forår - JS-Kval.', 'pool_name' => 'Pulje 4', 'pool_value' => 14968, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            // Finalestævne 2025
            ['tournament_level' => 'Finalestævne 2025', 'pool_name' => 'JS-kval.', 'pool_value' => 15027, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'Finalestævne 2025', 'pool_name' => 'Serie 1-kval.', 'pool_value' => 15028, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'Finalestævne 2025', 'pool_name' => 'Serie 2-kval.', 'pool_value' => 15029, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'Finalestævne 2025', 'pool_name' => 'Serie 3-kval.', 'pool_value' => 15030, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'Finalestævne 2025', 'pool_name' => 'Serie 4-kval.', 'pool_value' => 15031, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            ['tournament_level' => 'Finalestævne 2025', 'pool_name' => 'Serie 5', 'pool_value' => 15032, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4003, 'age_group_id' => 4006],
            // Masters 40
            ['tournament_level' => 'Masters 40', 'pool_name' => 'A, Pulje 1', 'pool_value' => 14872, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4008],
            // Masters 50
            ['tournament_level' => 'Masters 50', 'pool_name' => 'A, Pulje 1', 'pool_value' => 14873, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4009],
            // Masters 60
            ['tournament_level' => 'Masters 60', 'pool_name' => 'A, Pulje 1', 'pool_value' => 14874, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4010],
            ['tournament_level' => 'Masters 60', 'pool_name' => 'A, Pulje 2', 'pool_value' => 14875, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4010],
            // Slutspil - Masters 60
            ['tournament_level' => 'Slutspil - Masters 60', 'pool_name' => 'Slutspil Masters 60 A', 'pool_value' => 14989, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4010],
            // Ungdom op til 12 år - Efterår
            ['tournament_level' => 'Ungdom op til 12 år - Efterår', 'pool_name' => 'Pulje A', 'pool_value' => 14876, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4016],
            ['tournament_level' => 'Ungdom op til 12 år - Efterår', 'pool_name' => 'Pulje B', 'pool_value' => 14877, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4016],
            // Ungdom 13-19 år - Efterår
            ['tournament_level' => 'Ungdom 13-19 år - Efterår', 'pool_name' => 'Pulje A', 'pool_value' => 14878, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4016],
            ['tournament_level' => 'Ungdom 13-19 år - Efterår', 'pool_name' => 'Pulje B', 'pool_value' => 14879, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4016],
            ['tournament_level' => 'Ungdom 13-19 år - Efterår', 'pool_name' => 'Pulje C', 'pool_value' => 14880, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4016],
            // Ungdom op til 12 år - Forår
            ['tournament_level' => 'Ungdom op til 12 år - Forår', 'pool_name' => 'Pulje A', 'pool_value' => 14973, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4016],
            ['tournament_level' => 'Ungdom op til 12 år - Forår', 'pool_name' => 'Pulje B', 'pool_value' => 14974, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4016],
            ['tournament_level' => 'Ungdom op til 12 år - Forår', 'pool_name' => 'Pulje C', 'pool_value' => 14975, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4016],
            ['tournament_level' => 'Ungdom op til 12 år - Forår', 'pool_name' => 'Pulje D', 'pool_value' => 14976, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4016],
            // Ungdom 13-19 år - Forår
            ['tournament_level' => 'Ungdom 13-19 år - Forår', 'pool_name' => 'Pulje A', 'pool_value' => 14969, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4016],
            ['tournament_level' => 'Ungdom 13-19 år - Forår', 'pool_name' => 'Pulje B', 'pool_value' => 14970, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4016],
            ['tournament_level' => 'Ungdom 13-19 år - Forår', 'pool_name' => 'Pulje C', 'pool_value' => 14971, 'is_playoff' => 0, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4016],
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
            // Slutspil Østserien / Kval til 3.div
            ['tournament_level' => 'Slutspil Østserien / Kval til 3.div', 'pool_name' => 'Slutspil Østserien', 'pool_value' => 15011, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            // Slutspil Serie 1
            ['tournament_level' => 'Slutspil Serie 1', 'pool_name' => 'Slutspil Serie 1', 'pool_value' => 15006, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            // Slutspil Serie 2
            ['tournament_level' => 'Slutspil Serie 2', 'pool_name' => 'Slutspil Serie 2', 'pool_value' => 15007, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            // Slutspil Serie 3
            ['tournament_level' => 'Slutspil Serie 3', 'pool_name' => 'Slutspil Serie 3', 'pool_value' => 15008, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            // Slutspil Serie 4
            ['tournament_level' => 'Slutspil Serie 4', 'pool_name' => 'Slutspil Serie 4', 'pool_value' => 15009, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
            // Slutspil Serie 5
            ['tournament_level' => 'Slutspil Serie 5', 'pool_name' => 'Slutspil Serie 5', 'pool_value' => 15010, 'is_playoff' => 1, 'season_id' => 42024, 'region_id' => 4004, 'age_group_id' => 4006],
        ];

        foreach ($tournament_pools as $pool) {
            $wpdb->replace($this->tournament_pools_table, $pool);
        }
    }
}
