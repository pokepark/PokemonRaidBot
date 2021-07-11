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

// Count 3 means we received pokemon_table_id and starttime 
// Count 4 means we received pokemon_table_id, starttime and an optional argument
// Count 5 means we received pokemon_table_id, starttime, optional argument and slot switch
$arg_data = explode(',', $data['arg']);
$count_arg = count($arg_data);
$event_id = $arg_data[0];
$raid_level = $arg_data[1];
$opt_arg = 'new-raid';
$slot_switch = 0;
if($count_arg >= 4) {
    $pokemon_table_id = $arg_data[2];
    $starttime = $arg_data[3];
} 
if($count_arg >= 5) {
    $opt_arg = $arg_data[4];
}
if($count_arg >= 6) {
    $slot_switch = $arg_data[5];
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

    // Event Raid or normal/EX raid?
    if($event_id == 'X') {
        debug_log('Ex-Raid time :D ... Setting raid date to ' . $arg_time);
        $start_date_time = $arg_time;
        $duration = $config->RAID_DURATION;
    }elseif($event_id != 'N') {
        debug_log('Event time :D ... Setting raid date to ' . $arg_time);
        $start_date_time = $arg_time;
        $query = my_query("SELECT raid_duration FROM events WHERE id = '{$event_id}' LIMIT 1");
        $result = $query->fetch();
        $duration = $result['raid_duration'];
    } else {
        // Current date
        $current_date = date('Y-m-d', strtotime('now'));
        debug_log('Today is a raid day! Setting raid date to ' . $current_date);
        // Raid time
        $start_date_time = $current_date . ' ' . $arg_time . ':00';
        debug_log('Received the following time for the raid: ' . $start_date_time);
        $duration = $config->RAID_DURATION;
    }

    // Check for duplicate raid
    $duplicate_id = 0;
    if($raid_id == 0) {
        $duplicate_id = active_raid_duplication_check($gym_id);
    }

    // Continue with raid creation
    if($duplicate_id == 0) {
        // Now.
        $now = utcnow();

        $pokemon_id_formid = get_pokemon_by_table_id($pokemon_table_id);

        // Saving event info to db. N = null
        $event = (($event_id == "N") ? "NULL" : (($event_id=="X") ? EVENT_ID_EX : $event_id ));
        debug_log("Event: ".$event);
        debug_log("Event-id: ".$event_id);
        debug_log("Raid level: ".$raid_level);
        debug_log("Pokemon: ".$pokemon_id_formid['pokedex_id']."-".$pokemon_id_formid['pokemon_form_id']);

        // Create raid in database.
        $rs = my_query(
            "
            INSERT INTO   raids
            SET           user_id = {$update['callback_query']['from']['id']},
                          pokemon = '{$pokemon_id_formid['pokedex_id']}',
                          pokemon_form = '{$pokemon_id_formid['pokemon_form_id']}',
                          start_time = '{$start_date_time}',
                          spawn = DATE_SUB(start_time, INTERVAL ".$config->RAID_EGG_DURATION." MINUTE),
                          end_time = DATE_ADD(start_time, INTERVAL {$duration} MINUTE),
                          gym_id = '{$gym_id}',
                          level = {$raid_level},
                          event = {$event}
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
    // 1-minute selection
    $slotsize = 1;

    // Raid hour?
    if ($config->RAID_HOUR) {
        $slotmax = $config->RAID_HOUR_DURATION;
    // Raid day?
    } elseif ($config->RAID_DAY) {
        $slotsize = 5;
        $slotmax = $config->RAID_DAY_DURATION;
    // No event
    } else {
        $slotmax = $config->RAID_DURATION;
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
    debug_log('Comparing slot switch and argument for fast forward');
    if ($slot_switch == 0) {
        // Raid hour?
        if ($config->RAID_HOUR) {
            $raidduration = $config->RAID_HOUR_DURATION;
        // Raid day?
        } elseif ($config->RAID_DAY) {
            $raidduration = $config->RAID_DAY_DURATION;
        // No event
        } else {
            $raidduration = $config->RAID_DURATION;
        }

        // Write to log.
        debug_log('Doing a fast forward now!');
        debug_log('Changing data array first...');

        // Reset data array
        $data = [];
        $data['id'] = $raid_id;
        $data['action'] = 'edit_save';
        $data['arg'] = $raidduration;

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
        // Use normal raid duration.
        $keys[] = array(
            'text'          => '0:' . $config->RAID_DURATION,
            'callback_data' => $raid_id . ':edit_save:' . $config->RAID_DURATION
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
if ($opt_arg != 'more' && $event_id == 'N') {
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
