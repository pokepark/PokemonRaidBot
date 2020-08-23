<?php
/**
 * Raid list.
 * @param $update
 */
function raid_list($update)
{
    // Init empty rows array and query type.
    $rows = [];

    // Init raid id.
    $iqq = 0;

    // Botname:raid_id received?
    if (substr_count($update['inline_query']['query'], ':') == 1) {
        // Botname: received, is there a raid_id after : or not?
        if(strlen(explode(':', $update['inline_query']['query'])[1]) != 0) {
            // Raid ID.
            $iqq = intval(explode(':', $update['inline_query']['query'])[1]);
        }
    }

    // Inline list polls.
    if ($iqq != 0) {

        // Raid by ID.
        $request = my_query(
            "
            SELECT              raids.*,
                                raids.id AS iqq_raid_id,
                                gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym, gyms.gym_note,
                                TIME_FORMAT(TIMEDIFF(end_time, UTC_TIMESTAMP()) + INTERVAL 1 MINUTE, '%k:%i') AS t_left,
                                users.name
		    FROM        raids
                    LEFT JOIN   gyms
                    ON          raids.gym_id = gyms.id
                    LEFT JOIN   users
                    ON          raids.user_id = users.user_id
		      WHERE     raids.id = {$iqq}
                      AND       end_time>UTC_TIMESTAMP()
            "
        );

        while ($answer = $request->fetch()) {
            $rows[] = $answer;
        }

    } else {
        // Get raid data by user.
        $request = my_query(
            "
            SELECT              raids.*,
                                raids.id AS iqq_raid_id,
                                gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym, gyms.gym_note,
                                TIME_FORMAT(TIMEDIFF(end_time, UTC_TIMESTAMP()) + INTERVAL 1 MINUTE, '%k:%i') AS t_left,
                                users.name
		    FROM        raids
                    LEFT JOIN   gyms
                    ON          raids.gym_id = gyms.id
                    LEFT JOIN   users
                    ON          raids.user_id = users.user_id
		      WHERE     raids.user_id = {$update['inline_query']['from']['id']}
                      AND       end_time>UTC_TIMESTAMP()
		      ORDER BY  iqq_raid_id DESC LIMIT 2
            "
        );

        while ($answer_raids = $request->fetch()) {
            $rows[] = $answer_raids;
        }

    }

    // Init array.
    $contents = array();

    // For each rows.
    foreach ($rows as $key => $row) {
            // Get raid poll.
	    $contents[$key]['text'] = show_raid_poll($row)['full'];

            // Set the title.
            $contents[$key]['title'] = get_local_pokemon_name($row['pokemon'],$row['pokemon_form'], true) . ' ' . getPublicTranslation('from') . ' ' . dt2time($row['start_time'])  . ' ' . getPublicTranslation('to') . ' ' . dt2time($row['end_time']);

            // Get inline keyboard.
            $contents[$key]['keyboard'] = keys_vote($row);

            // Set the description.
            $contents[$key]['desc'] = strval($row['gym_name']);
    }

    debug_log($contents);
    answerInlineQuery($update['inline_query']['id'], $contents);
}

?>
