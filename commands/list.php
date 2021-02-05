<?php
// Write to log.
debug_log('LIST()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'list');

// Include
include(ROOT_PATH . '/logic/get_active_raids.php');

// Init text and keys.
$text = '';
$keys = [];

// Get raids.
$raids = get_active_raids();

// Did we get any raids?
if(isset($raids[0]['r_active'])) {
    debug_log($raids[0]['r_active'], 'Active raids:');

    // More raids as we like?
    if($raids[0]['r_active'] > 12) {
        // Forward to /listall
        debug_log('Too much raids, forwarding to /listall');
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

            $text .= $gym_name . CR;
            $raid_day = dt2date($raid['start_time']);
            $now = utcnow();
            $today = dt2date($now);
            $start = dt2time($raid['start_time']);
            $end = dt2time($raid['end_time']);
            $text .= get_local_pokemon_name($raid['pokemon'], $raid['pokemon_form']) . SP . '-' . SP . (($raid_day == $today) ? '' : ($raid_day . ', ')) . $start . SP . getTranslation('to') . SP . $end . CR . CR;

            // Split pokemon and form to get the pokedex id.
            $pokedex_id = explode('-', $raid['pokemon'])[0];

            // Pokemon is an egg?
            $eggs = $GLOBALS['eggs'];
            if(in_array($pokedex_id, $eggs)) {
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
