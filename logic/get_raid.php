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

    // Get the raid data by id.
    $rs = my_query(
        '
        SELECT     raids.*,
                   gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym, gyms.gym_note,
                   users.name, users.trainername, users.nick,
                   events.name as event_name, events.description as event_description, events.vote_key_mode as event_vote_key_mode, events.time_slots as event_time_slots, events.raid_duration as event_raid_duration, events.hide_raid_picture as event_hide_raid_picture,
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

    if ($raid['pokemon'] == 0) {
        // Just an egg
        $query = '
                    SELECT  pokemon_form_id, 
                            pokedex_id 
                    FROM    raid_bosses 
                    WHERE   raid_level = :raid_level
                    AND     :spawn BETWEEN date_start AND date_end
                ';
        $statement = $dbh->prepare( $query );
        $statement->execute([
          ':raid_level' => $raid['level'],
          ':spawn' => dt2time($raid['spawn'],'Y-m-d H:i'),
        ]);
        if($statement->rowCount() == 1) {
            $result = $statement->fetch();
            $raid['pokemon'] = $result['pokedex_id'];
            $raid['pokemon_form'] = $result['pokemon_form_id'];
        }else {
            $raid['pokemon'] = '999' . $raid['level'];
        }
    }else {
        if ($raid['pokemon_form'] == '0') {
            // If no form is set, look up the normal form's id from pokemon table
            $form_query = my_query('(SELECT pokemon_form_id FROM pokemon
            WHERE
                pokedex_id = '.$raid['pokemon'].' AND
                pokemon_form_name = \'normal\'
            LIMIT 1)');
            $form_rs = $form_query->fetch();
            $raid['pokemon_form'] = $form_rs['pokemon_form_id'];
        }
    }

    // Check trainername
    $raid = check_trainername($raid);

    debug_log($raid);

    return $raid;
}

?>
