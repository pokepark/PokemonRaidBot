<?php
// Write to log.
debug_log('vote_pokemon()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check if the user has voted for this raid before.
$rs = my_query(
    "
    SELECT    *
    FROM      attendance
      WHERE   raid_id = {$data['id']}
        AND   user_id = {$update['callback_query']['from']['id']}
    "
);

// Init empty attendances array and counter.
$atts = [];
$count = 0;

// Fill array with attendances.
while ($row = $rs->fetch()) {
    $atts[] = $row;
    $count = $count + 1;
}

// Write to log.
debug_log($atts);

// User has voted before.
if(!empty($atts)) {
    // Any pokemon?
    if($data['arg'] == 0) {
        // Update attendance.
        my_query(
        "
        UPDATE    attendance
        SET       pokemon = '{$data['arg']}'
        WHERE     raid_id = {$data['id']}
        AND       user_id = {$update['callback_query']['from']['id']}
        "
        );

        // Delete any attendances except the first one.
        my_query(
        "
        DELETE FROM attendance
        WHERE id NOT IN (
            SELECT * FROM (
                SELECT MIN(id)
                FROM   attendance
                WHERE  raid_id = {$data['id']}
                AND    user_id = {$update['callback_query']['from']['id']}
            ) AS AVOID_MYSQL_ERROR_1093
        )
        AND raid_id = {$data['id']}
        AND user_id = {$update['callback_query']['from']['id']}
        "
        );

        // Send alarm
        alarm($data['id'],$update['callback_query']['from']['id'],'pok_individual',$data['arg']);
    } else {
        // Init found and count.
        $found = false;

        // Loop thru attendances
        foreach($atts as $att_row => $att_data) {
            // Remove vote for specific pokemon
            if($att_data['pokemon'] == $data['arg']) {
                // Is it the only vote? Update to "Any raid boss" instead of deleting it!
                if($count == 1) {
                    my_query(
                    "
                    UPDATE    attendance
                    SET       pokemon = '0'
                    WHERE     raid_id = {$data['id']}
                    AND       user_id = {$update['callback_query']['from']['id']}
                    "
                    );
                // Other votes are still there, delete this one!
                } else {
                    my_query(
                    "
                    DELETE FROM attendance
                    WHERE  raid_id = {$data['id']}
                    AND   user_id = {$update['callback_query']['from']['id']}
                    AND   pokemon = '{$data['arg']}'
                    "
                    );
                }
                // Send alarm
                alarm($data['id'],$update['callback_query']['from']['id'],'pok_cancel_individual',$data['arg']);

                // Update count.
                $count = $count - 1;

                // Found and break.
                $found = true;
                break;
            }
        }

        // Not found? Insert!
        if(!$found) {
            // Send alarm
            alarm($data['id'],$update['callback_query']['from']['id'],'pok_individual',$data['arg']);

            // Insert vote.
            my_query(
            "
            INSERT INTO attendance
            (
              user_id,
              raid_id,
              attend_time,
              extra_in_person,
              extra_alien,
              arrived,
              raid_done,
              cancel,
              late,
              remote,
              invite,
              pokemon,
              alarm,
              want_invite,
              can_invite
            )
            VALUES(
            '{$atts[0]['user_id']}',
            '{$atts[0]['raid_id']}',
            '{$atts[0]['attend_time']}',
            '{$atts[0]['extra_in_person']}',
            '{$atts[0]['extra_alien']}',
            '{$atts[0]['arrived']}',
            '{$atts[0]['raid_done']}',
            '{$atts[0]['cancel']}',
            '{$atts[0]['late']}',
            '{$atts[0]['remote']}',
            '{$atts[0]['invite']}',
            '{$data['arg']}',
            '{$atts[0]['alarm']}',
            '{$atts[0]['want_invite']}',
            '{$atts[0]['can_invite']}'
            )
            "
            );

            // Update counter.
            $count = $count + 1;
        }

        // Delete "Any raid boss" vote if count is larger than 0
        if($count > 0) {
            my_query(
            "
            DELETE FROM attendance
            WHERE  raid_id = {$data['id']}
            AND   user_id = {$update['callback_query']['from']['id']}
            AND   pokemon = '0'
            "
            );
        }
    }

    require_once(LOGIC_PATH . '/update_raid_poll.php');

    $tg_json = update_raid_poll($data['id'], false, $update);

    $tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('vote_updated'), true);

    curl_json_multi_request($tg_json);
} else {
    // Send vote time first.
    send_vote_time_first($update);
}

exit();
