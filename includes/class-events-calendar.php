<?php

namespace Calendar_Sync_Scraper;

class Events_Calendar_Sync
{
    private $time_offset;

    public function __construct()
    {
        $this->time_offset = get_option('cal_sync_time_offset', '+1 hour');
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
                $endDateTime = (new \DateTime($startDateTime))
                    ->modify($this->time_offset)
                    ->format('Y-m-d H:i:s');

                $description = "<strong>{$region_name} {$season_name}</strong><br>" .
                    "<a href='https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#3,{$season},{$pool},{$ageGroup},{$region},{$match['hjemmehold_id']},,4203'>$tournament_level $pool_name</a><br><br>" .
                    "<a href='https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#2,{$season},{$pool},{$ageGroup},{$region},,,4203'>{$match['hjemmehold']}</a><br>" .
                    "{$match['udehold']}, Grøndal MultiCenter<br><br>" .
                    "<strong>Resultat: {$match['resultat']}<br>" .
                    "Point: {$match['point']}</strong><br><br>" .
                    "<a href='https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#5,{$season},{$pool},{$ageGroup},{$region},,{$match['no']},4203'>Kampdetaljer</a>";

                $event_color = $this->map_color_id_to_hex($color_id);

                $event_data = [
                    'post_type' => 'tribe_events',
                    'post_title' => "$age_group_name $tournament_level $pool_name",
                    'post_content' => $description,
                    'post_status' => 'publish',
                    'meta_input' => [
                        '_EventStartDate' => $startDateTime,
                        '_EventEndDate' => $endDateTime,
                        '_EventVenueID' => 209,
                        '_EventOrganizerID' => 206,
                        '_EventCost' => 0,
                        '_tribe_event_color' => $event_color,
                    ]
                ];

                error_log("Event data: " . print_r($event_data, true));
                $event_id = wp_insert_post($event_data, true);

                if (is_wp_error($event_id)) {
                    error_log("Events Calendar Sync: Failed to insert match {$match['no']}: " . $event_id->get_error_message());
                    continue;
                }

                error_log("Event inserted with ID: $event_id, Meta: " . print_r(get_post_meta($event_id), true));
                $this->apply_event_color($event_id, $event_color);
            } catch (\Exception $e) {
                error_log("Events Calendar Sync: Failed to insert match {$match['no']}: " . $e->getMessage());
            }
        }
    }

    private function parseTidToDateTime(string $tid): string
    {
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

            error_log("Parsed datetime: " . $dateTime->format('Y-m-d H:i:s'));
            return $dateTime->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            error_log("Failed to parse datetime '$tid': " . $e->getMessage());
            throw $e;
        }
    }

    private function map_color_id_to_hex($color_id)
    {
        $color_map = [
            '1' => '#a4bdfc', // Lavender
            '2' => '#7ae7bf', // Sage
            '3' => '#dbadff', // Grape
            '4' => '#ff887c', // Flamingo
            '5' => '#fbd75b', // Banana
            '6' => '#ffb878', // Tangerine
            '7' => '#46d6db', // Peacock
            '8' => '#e1e1e1', // Graphite
            '9' => '#5484ed', // Blueberry
            '10' => '#51b749', // Basil
            '11' => '#dc2127', // Tomato
        ];
        return $color_map[$color_id] ?? '#666666'; // Default gray
    }

    private function apply_event_color($event_id, $event_color)
    {
        update_post_meta($event_id, '_tribe_event_color', $event_color);

        add_action('wp_footer', function () use ($event_id, $event_color) {
            echo "<style>
                .tribe-events-calendar .tribe-event-id-$event_id,
                .tribe-events-list .tribe-event-id-$event_id {
                    background-color: $event_color !important;
                    border-left: 4px solid $event_color !important;
                    color: #ffffff !important;
                }
            </style>";
        });
    }
}
