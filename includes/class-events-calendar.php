<?php

namespace Calendar_Sync_Scraper;

class Events_Calendar_Sync
{
    private $time_offset;
    private $colors_table;
    private $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        $this->time_offset = get_option('cal_sync_time_offset', '+3 hours');
        $this->colors_table = $wpdb->prefix . 'cal_sync_colors';
    }

    public function insertMatches($matches, $season_name, $region_name, $age_group_name, $pool_name, $tournament_level, $color_id, $season, $region, $ageGroup, $pool)
    {
        if (!is_array($matches) || empty($matches)) {
            error_log("Events Calendar Sync: No matches provided or invalid matches array");
            return;
        }

        foreach ($matches as $match) {
            try {
                $startDateTime = $this->parseTidToDateTime($match['tid']);
                $endDateTimeObj = (new \DateTime($startDateTime))->modify($this->time_offset);

                // If the day changed, cap it at 23:59:59 of the start day
                if ($endDateTimeObj->format('Y-m-d') !== (new \DateTime($startDateTime))->format('Y-m-d')) {
                    $endDateTime = (new \DateTime($startDateTime))->setTime(23, 59, 59)->format('Y-m-d H:i:s');
                } else {
                    $endDateTime = $endDateTimeObj->format('Y-m-d H:i:s');
                }

                $description = "<strong>{$region_name} {$season_name}</strong><br>" .
                    "<a href='https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#3,{$season},{$pool},{$ageGroup},{$region},{$match['hjemmehold_id']},,4203' target='_blank'>$tournament_level $pool_name</a><br><br>" .
                    "<a href='https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#2,{$season},{$pool},{$ageGroup},{$region},,,4203' target='_blank'>{$match['hjemmehold']}</a><br>" .
                    "{$match['udehold']}, Grøndal MultiCenter<br><br>" .
                    "<strong>Resultat: {$match['resultat']}<br>" .
                    "Point: {$match['point']}</strong><br><br>" .
                    "<a href='https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#5,{$season},{$pool},{$ageGroup},{$region},,{$match['no']},4203' target='_blank'>Kampdetaljer</a>";

                $colors = $this->map_color_id_to_hex($color_id);
                $event_color = $colors['background'];
                $font_color = $colors['font'];

                // Use Europe/Copenhagen timezone
                $tz = new \DateTimeZone('Europe/Copenhagen');
                $start = new \DateTime($startDateTime, $tz);
                $end   = new \DateTime($endDateTime, $tz);

                $event_data = [
                    'post_type'    => 'tribe_events',
                    'post_title'   => "$age_group_name $tournament_level $pool_name",
                    'post_content' => $description,
                    'post_status'  => 'publish',
                    'meta_input'   => [
                        '_EventStartDate'      => $start->format('Y-m-d H:i:s'),
                        '_EventEndDate'        => $end->format('Y-m-d H:i:s'),
                        '_EventStartDateUTC'   => $start->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
                        '_EventEndDateUTC'     => $end->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
                        '_EventVenueID'        => 209,
                        '_EventOrganizerID'    => 206,
                        '_EventCost'           => 0,
                        '_tribe_event_color'   => $event_color,
                        '_tribe_event_font_color' => $font_color,
                        '_EventOrigin'         => 'events-calendar',
                        '_EventShowMap'        => 'TRUE',
                        '_EventTimezone'       => 'Europe/Copenhagen',
                        '_EventTimezoneAbbr'   => $start->format('T'), // CET or CEST
                    ]
                ];

                $event_id = wp_insert_post($event_data, true);

                if (is_wp_error($event_id)) {
                    error_log("Events Calendar Sync: Failed to insert match {$match['no']}: " . $event_id->get_error_message());
                    continue;
                }
            } catch (\Exception $e) {
                error_log("Events Calendar Sync: Failed to insert match {$match['no']}: " . $e->getMessage());
            }
        }
    }

    private function parseTidToDateTime(?string $tid): string
    {
        if (empty($tid)) {
            error_log("Empty or null datetime string provided.");
            return '';
        }

        $tid = preg_replace('/^(ma|ti|on|to|fr|lø|sø)\s+/i', '', trim($tid));
        $tid = str_replace(['‑', '–'], '-', $tid);

        try {
            $dateTime = \DateTime::createFromFormat(
                'd-m-Y H:i',
                $tid,
                new \DateTimeZone('Europe/Copenhagen')
            );

            if ($dateTime === false) {
                throw new \Exception("Invalid datetime format: '$tid'");
            }

            return $dateTime->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            error_log("Failed to parse datetime '$tid': " . $e->getMessage());
            return '';
        }
    }


    private function map_color_id_to_hex($color_id)
    {
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT hex_code FROM {$this->colors_table} WHERE id = %d LIMIT 1",
                $color_id
            ),
            ARRAY_A
        );

        return [
            'background' => $row['hex_code'] ?? '#039be5',
            'font'       => '#FFFFFF',
        ];
    }

    public function delete_all_events_permanently()
    {
        check_ajax_referer('calendar_scraper_nonce', '_ajax_nonce'); // optional, but recommended

        try {
            $event_ids = $this->wpdb->get_col(
                "SELECT ID FROM {$this->wpdb->posts} WHERE post_type = 'tribe_events'"
            );

            $deleted_count = 0;

            foreach ($event_ids as $event_id) {
                if (wp_delete_post($event_id, true)) {
                    $deleted_count++;
                }
            }

            wp_send_json_success(['deleted' => $deleted_count]);
        } catch (\Exception $e) {
            error_log('Failed to delete tribe_events: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}
