<?php
// Write to log.
debug_log('vote_team()');

// For debug.
//debug_log($update);
//debug_log($data);

// Update team in users table directly.
if($data['arg'] == ('mystic' || 'valor' || 'instinct')) {
    my_query(
        "
        UPDATE  users
        SET     team = '{$data['arg']}'
        WHERE   user_id = {$update['callback_query']['from']['id']}
        "
    );
// No team was given - iterate thru the teams.
} else {
    my_query(
        "
        UPDATE    users
        SET    team = CASE
                 WHEN team = 'mystic' THEN 'valor'
                 WHEN team = 'valor' THEN 'instinct'
                 ELSE 'mystic'
               END
          WHERE   user_id = {$update['callback_query']['from']['id']}
        "
    );
}

// Message coming from raid or trainer info?
if($data['id'] == 'trainer') {
    // Send trainer info update.
    send_trainerinfo($update, true);
} else {
    // Send vote response.
    require_once(LOGIC_PATH . '/update_raid_poll.php');

    $tg_json = update_raid_poll($data['id'], false, $update);

    $tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('vote_updated'), true);

    curl_json_multi_request($tg_json);
}

exit();
