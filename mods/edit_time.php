<?php
// Write to log.
debug_log('edit_time()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'create');

// Get count of ID and argument.
$count_id = substr_count($data['id'], ',');
$count_arg = substr_count($data['arg'], ',');

// Set the id.
// Count 0 means we just received the raid_id
// Count 1 means we received gym_id and gym_first_letter
$raid_id = 0;
$gym_id = 0;
$gym_letter = 99;
if($count_id == 0) {
    $raid_id = $data['id'];
} else if($count_id == 1) {
    $gym_id_letter = explode(',', $data['id']);
    $gym_id = $gym_id_letter[0];
    $gym_letter = $gym_id_letter[1];
}

// Set the arg.
// Count 1 means we received pokemon_id and starttime 
// Count 2 means we received pokemon_id, starttime and an optional argument
// Count 3 means we received pokemon_id, starttime, optional argument and slot switch
$pokemon_time = explode(',', $data['arg']);
$opt_arg = 'new-raid';
$slot_switch = 0;
if($count_arg == 1) {
    $pokemon_id = $pokemon_time[0];
    $starttime = $pokemon_time[1];
} else if($count_arg == 2) {
    $pokemon_id = $pokemon_time[0]; 
    $starttime = $pokemon_time[1];
    $opt_arg = $pokemon_time[2];
} else if($count_arg == 3) {
    $pokemon_id = $pokemon_time[0];
    $starttime = $pokemon_time[1];
    $opt_arg = $pokemon_time[2];
    $slot_switch = $pokemon_time[3];
}

// Write to log.
debug_log('count_id: ' . $count_id);
debug_log('count_arg: ' . $count_arg);
debug_log('opt_arg: ' . $opt_arg);
debug_log('slot_switch: ' . $slot_switch);

// Telegram JSON array.
$tg_json = array();

// Create raid under the following conditions::
// raid_id is 0, means we did not create it yet
// gym_id is not 0, means we have a gym_id for creation
if ($raid_id == 0 && $gym_id != 0) {
    // Replace "-" with ":" to get proper time format
    debug_log('Formatting the raid time properly now.');
    $arg_time = str_replace('-', ':', $starttime);

    // Ex-Raid or normal raid?
    if($opt_arg == 'X') {
        debug_log('Ex-Raid time :D ... Setting raid date to ' . $arg_time);
        $start_date_time = $arg_time;
    } else {
        // Current date
        $current_date = date('Y-m-d', strtotime('now'));
        debug_log('Today is a raid day! Setting raid date to ' . $current_date);
        // Raid time
        $start_date_time = $current_date . ' ' . $arg_time . ':00';
        debug_log('Received the following time for the raid: ' . $start_date_time);
    }

    // Duration and end time.
    $duration = $config->RAID_POKEMON_DURATION_SHORT;
    $end = date('Y-m-d H:i:s', strtotime('+' . $duration . ' minutes', strtotime($start_date_time)));

    // Check for duplicate raid
    $duplicate_id = 0;
    if($raid_id == 0) {
        $duplicate_id = active_raid_duplication_check($gym_id);
    }

    // Continue with raid creation
    if($duplicate_id == 0) {
        // Now.
        $now = utcnow();

        $pokemon_id_form = explode("-",$pokemon_id);
        
        // Create raid in database.
        $rs = my_query(
            "
            INSERT INTO   raids
            SET           user_id = {$update['callback_query']['from']['id']},
			  pokemon = '{$pokemon_id_form[0]}',
			  pokemon_form = '{$pokemon_id_form[1]}',
			  first_seen = UTC_TIMESTAMP(),
			  start_time = '{$start_date_time}',
                          end_time = DATE_ADD(start_time, INTERVAL {$duration} MINUTE),
			  gym_id = '{$gym_id}'
            "
        );

        // Get last insert id from db.
        $raid_id = $dbh->lastInsertId();

        // Write to log.
        debug_log('ID=' . $raid_id);

    // Tell user the raid already exists and exit!
    } else {
        $keys = [];
        $raid_id = $duplicate_id;
        $raid = get_raid($raid_id);
        $msg = EMOJI_WARN . SP . getTranslation('raid_already_exists') . SP . EMOJI_WARN . CR . show_raid_poll_small($raid);

        // Check if the raid was already shared.
        $rs_share = my_query(
            "
            SELECT  COUNT(*) AS raid_count
            FROM    cleanup
            WHERE   raid_id = '{$raid_id}'
            "
        );

        $shared = $rs_share->fetch();

        // Add keys for sharing the raid.
        if($shared['raid_count'] == 0) {
            $keys = share_keys($raid_id, 'raid_share', $update);

            // Exit key
            $empty_exit_key = [];
            $key_exit = universal_key($empty_exit_key, '0', 'exit', '0', getTranslation('abort'));
            $keys = array_merge($keys, $key_exit);
        }

        // Answer callback.
        $tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('raid_already_exists'), true);

        // Edit the message.
        $tg_json[] = edit_message($update, $msg, $keys, false, true);

        // Telegram multicurl request.
        curl_json_multi_request($tg_json);

        // Exit.
        exit();
    }
}

// Init empty keys array.
$keys = [];

// Raid pokemon duration short or 1 Minute / 5 minute time slots
if($opt_arg == 'more') {
    if ($slot_switch == 0) {
        // Event running?
        if($config->RAID_POKEMON_DURATION_EVENT != $config->RAID_POKEMON_DURATION_SHORT) {
	    $slotmax = $config->RAID_POKEMON_DURATION_EVENT;
        } else {
	    $slotmax = $config->RAID_POKEMON_DURATION_SHORT;
        }
	$slotsize = 1;
    } else {
	$slotmax = $config->RAID_POKEMON_DURATION_LONG;
	$slotsize = 5;
    }

    for ($i = $slotmax; $i >= 15; $i = $i - $slotsize) {
        // Create the keys.
        $keys[] = array(
	    // Just show the time, no text - not everyone has a phone or tablet with a large screen...
            'text'          => floor($i / 60) . ':' . str_pad($i % 60, 2, '0', STR_PAD_LEFT),
            'callback_data' => $raid_id . ':edit_save:' . $i
        );
    }

} else {
    debug_log('Comparing slot switch and argument for fast forward using RAID_POKEMON_DURATION_SHORT');
    if ($slot_switch == 0) {
        // Write to log.
        debug_log('Doing a fast forward now!');
        debug_log('Changing data array first...');

        // Reset data array
        $data = [];
        $data['id'] = $raid_id;
        $data['action'] = 'edit_save';
        $data['arg'] = $config->RAID_POKEMON_DURATION_SHORT;

        // Write to log.
        debug_log($data, '* NEW DATA= ');

        // Set module path by sent action name.
        $module = ROOT_PATH . '/mods/edit_save.php';

        // Write module to log.
        debug_log($module);

        // Check if the module file exists.
        if (file_exists($module)) {
            // Dynamically include module file and exit.
            include_once($module);
            exit();
        }
    } else {

        // Use raid pokemon duration short.
        $keys[] = array(
            'text'          => '0:' . $config->RAID_POKEMON_DURATION_SHORT,
            'callback_data' => $raid_id . ':edit_save:' . $config->RAID_POKEMON_DURATION_SHORT
        );

        // Button for more options.
        $keys[] = array(
            'text'          => getTranslation('expand'),
            'callback_data' => $raid_id . ':edit_time:' . $pokemon_id . ',' . $start_time . ',more,' . $slot_switch
        );


        }
}

// Get the inline key array.
$keys = inline_key_array($keys, 5);

// Write to log.
debug_log($keys);

// Build callback message string.
if ($opt_arg != 'more' && $opt_arg !='X') {
    $callback_response = getTranslation('start_date_time') . ' ' . $arg_time;
} else {
    $callback_response = getTranslation('raid_starts_when_view_changed');
}

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit the message.
$tg_json[] = edit_message($update, getTranslation('how_long_raid'), $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();
