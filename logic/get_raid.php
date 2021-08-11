<?php
/**
 * Get raid data.
 * @param $raid_id
 * @return array
 */
function get_raid($raid_id)
{
    global $dbh;
    // Remove all non-numeric characters
    $raidid = preg_replace( '/[^0-9]/', '', $raid_id );

    $tz_diff = tz_diff();

    // Get the raid data by id.
    $rs = my_query(
        '
        SELECT     IF (raids.pokemon = 0,
						IF((SELECT  count(*)
							FROM    raid_bosses
							WHERE   raid_level = raids.level
							AND     convert_tz(raids.spawn,"+00:00","'.$tz_diff.'")  BETWEEN date_start AND date_end) = 1,
							(SELECT  pokedex_id
							FROM    raid_bosses
							WHERE   raid_level = raids.level
							AND     convert_tz(raids.spawn,"+00:00","'.$tz_diff.'") BETWEEN date_start AND date_end),
                            (select concat(\'999\', raids.level) as pokemon)
                            )
                   ,pokemon) as pokemon,
                   IF (raids.pokemon = 0,
						IF((SELECT  count(*) as count
							FROM    raid_bosses
							WHERE   raid_level = raids.level
							AND     convert_tz(raids.spawn,"+00:00","'.$tz_diff.'") BETWEEN date_start AND date_end) = 1,
							(SELECT  pokemon_form_id
							FROM    raid_bosses
							WHERE   raid_level = raids.level
							AND     convert_tz(raids.spawn,"+00:00","'.$tz_diff.'") BETWEEN date_start AND date_end),
                            \'0\'
                            ),
                        IF(raids.pokemon_form = 0,
                            (SELECT pokemon_form_id FROM pokemon
                            WHERE
                                pokedex_id = raids.pokemon AND
                                pokemon_form_name = \'normal\'
                            LIMIT 1), raids.pokemon_form)
                           ) as pokemon_form,
                   raids.id, raids.user_id, raids.spawn, raids.start_time, raids.end_time, raids.gym_team, raids.gym_id, raids.level, raids.move1, raids.move2, raids.gender, raids.event, raids.event_note,
                   gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym, gyms.gym_note,
                   users.name, users.trainername, users.nick,
                   events.name as event_name, events.description as event_description, events.vote_key_mode as event_vote_key_mode, events.time_slots as event_time_slots, events.raid_duration as event_raid_duration, events.hide_raid_picture as event_hide_raid_picture, events.allow_remote as event_allow_remote, events.allow_invite as event_allow_invite,
                   TIME_FORMAT(TIMEDIFF(end_time, UTC_TIMESTAMP()) + INTERVAL 1 MINUTE, \'%k:%i\') AS t_left
        FROM       raids
        LEFT JOIN  gyms
        ON         raids.gym_id = gyms.id
        LEFT JOIN  users
        ON         raids.user_id = users.user_id
        LEFT JOIN  events
        ON         events.id = raids.event 
        WHERE      raids.id = '.$raid_id.'
        LIMIT 1
        '
    );
    // Get the row.
    $raid = $rs->fetch();

    // Check trainername
    $raid = check_trainername($raid);

    debug_log($raid);

    return $raid;
}

?>
