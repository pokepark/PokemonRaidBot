<?php
// Write to log.
debug_log('LIST()');
require_once(LOGIC_PATH . '/resolve_raid_boss.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck($update, 'list');

// Init text and keys.
$text = '';
$keys = [];

$event_permissions = $botUser->accessCheck($update, 'event',true);

// Get last 12 active raids data.
$rs = my_query(
    '
    SELECT     raids.pokemon, raids.pokemon_form, raids.id, raids.user_id, raids.spawn, raids.start_time, raids.end_time, raids.gym_team, raids.gym_id, raids.level, raids.move1, raids.move2, raids.gender, raids.event, raids.event_note,
               gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym, gyms.gym_note,
               start_time, end_time,
               TIME_FORMAT(TIMEDIFF(end_time, UTC_TIMESTAMP()) + INTERVAL 1 MINUTE, \'%k:%i\') AS t_left,
               (SELECT COUNT(*) FROM raids WHERE end_time>UTC_TIMESTAMP()) AS r_active
    FROM       raids
    LEFT JOIN  gyms
    ON         raids.gym_id = gyms.id
    WHERE      end_time>UTC_TIMESTAMP()
    ' . ($event_permissions ? '' : 'AND event IS NULL' ) . '
    ORDER BY   end_time ASC
    LIMIT      12
    '
);

// Get the raids.
$raids = $rs->fetchAll();

debug_log($raids);

// Did we get any raids?
if(isset($raids[0]['r_active'])) {
    debug_log($raids[0]['r_active'], 'Active raids:');

    // More raids as we like?
    if($raids[0]['r_active'] > 12) {
        // Forward to /listall
        debug_log('Too much raids, forwarding to /listall');
        $skip_access = true;
        include_once(ROOT_PATH . '/commands/listall.php');
        exit();

    // Just enough raids to display at once
    } else {
        //while ($raid = $rs->fetch()) {
        foreach($raids as $raid) {
            // Set text and keys.
            $gym_name = $raid['gym_name'];
            if(empty($gym_name)) {
                $gym_name = '';
            }
            $resolved_boss = resolve_raid_boss($raid['pokemon'], $raid['pokemon_form'], $raid['spawn'], $raid['level']);

            $text .= $gym_name . CR;
            $raid_day = dt2date($raid['start_time']);
            $now = utcnow();
            $today = dt2date($now);
            $start = dt2time($raid['start_time']);
            $end = dt2time($raid['end_time']);
            $text .= get_local_pokemon_name($resolved_boss['pokedex_id'], $resolved_boss['pokemon_form_id']) . SP . '-' . SP . (($raid_day == $today) ? '' : ($raid_day . ', ')) . $start . SP . getTranslation('to') . SP . $end . CR . CR;

            // Pokemon is an egg?
            $eggs = $GLOBALS['eggs'];
            if(in_array($resolved_boss['pokedex_id'], $eggs)) {
                $keys_text = EMOJI_EGG . SP . $gym_name;
            } else {
                $keys_text = $gym_name;
            }

            $keys[] = array(
                'text'          => $keys_text,
                'callback_data' => $raid['id'] . ':raids_list:0'
            );
        }

        // Get the inline key array.
        $keys = inline_key_array($keys, 1);

        // Add exit key.
        $keys[] = [
            [
                'text'          => getTranslation('abort'),
                'callback_data' => '0:exit:0'
            ]
        ];

        // Build message.
        $msg = '<b>' . getTranslation('list_all_active_raids') . ':</b>' . CR;
        $msg .= $text;
        $msg .= '<b>' . getTranslation('select_gym_name') . '</b>' . CR;
    }

// No active raids
} else {
    $msg = '<b>' . getTranslation('no_active_raids_found') . '</b>';
}

// Send message.
send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);
?>
