<?php
// Write to log.
debug_log('edit_starttime()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'create');

// Get the argument.
$arg_data = explode(",", $data['arg']);
$event_id = $arg_data[0];
$raid_level = $arg_data[1];
$pokemon_id = $arg_data[2];
$arg = "";
// Check for options.
if (isset($arg_data[3]))
{ 
    // Switch time display ( min / clock )
    $arg = $arg_data[3];
}
// Set the id.
$gym_id_plus_letter  = $data['id'];
$gym_id = explode(',', $data['id'])[0];

// Are we creating an event?
if($event_id != 'N' or $raid_level == 9) {
    // Init empty keys array.
    $keys = [];

    // Current time from the user
    // We let the user pick the raid date and time and convert to UTC afterwards in edit_date.php
    $tz = $config->TIMEZONE;
    $today = new DateTimeImmutable('now', new DateTimeZone($tz));

    // Create date buttons. Two days for Elite Raids, 15 days for EX raids.
    $days = ($raid_level == 9) ? 1 : 14;
    for ($d = 0; $d <= $days; $d++) {
        // Add day to today.
        $today_plus_d = $today->add(new DateInterval("P".$d."D"));

        // Format date, e.g 14 April 2019
        $date_tz = $today_plus_d->format('Y-m-d');
        $text_split = explode('-', $date_tz);
        $text_day = $text_split[2];
        $text_month = getTranslation('month_' . $text_split[1]);
        $text_year = $text_split[0];
         
        // Add keys.
        $cb_date = $today_plus_d->format('Y-m-d');
        $keys[] = array(
            'text'          => $text_day . SP . $text_month . SP . $text_year,
            'callback_data' => $gym_id_plus_letter . ':edit_date:' . $event_id . ',' . $raid_level . ',' . $pokemon_id . ',' . $cb_date
        );
    }

    // Get the inline key array.
    $keys = inline_key_array($keys, 2);

// Not creating an event
} else {
    if ($arg != "min" && $arg != "clock") {
        // Get default raid duration style from config
        if ($config->RAID_DURATION_CLOCK_STYLE) {
            $arg = "clock";
        } else {
            $arg = "min";
        }
    }

    // Init empty keys array.
    $keys = [];

    // Now 
    $now = utcnow();

    if ($arg == "min") {
        // Set switch view.
        $switch_text = getTranslation('raid_starts_when_clocktime_view');
        $switch_view = "clock";
        $key_count = 5;

        for ($i = 1; $i <= $config->RAID_EGG_DURATION; $i = $i + 1) {
            // Create new DateTime object, add minutes and convert back to string.
            $now_plus_i = new DateTime($now, new DateTimeZone('UTC'));
            $now_plus_i->add(new DateInterval('PT'.$i.'M'));
            $now_plus_i = $now_plus_i->format("Y-m-d H:i:s");
            // Create the keys.
            $keys[] = array(
                // Just show the time, no text - not everyone has a phone or tablet with a large screen...
                'text'          => floor($i / 60) . ':' . str_pad($i % 60, 2, '0', STR_PAD_LEFT),
                'callback_data' => $gym_id_plus_letter  . ':edit_time:' . $event_id . ',' . $raid_level . ',' . $pokemon_id . ',' . utctime($now_plus_i,"H-i")
            );
        }
    } else {
        // Set switch view.
        $switch_text = getTranslation('raid_starts_when_minutes_view');
        $switch_view = "min";
        // Small screen fix
        $key_count = 4;

        for ($i = 1; $i <= $config->RAID_EGG_DURATION; $i = $i + 1) {
            // Create new DateTime object, add minutes and convert back to string.
            $now_plus_i = new DateTime($now, new DateTimeZone('UTC'));
            $now_plus_i->add(new DateInterval('PT'.$i.'M'));
            $now_plus_i = $now_plus_i->format("Y-m-d H:i:s");
            // Create the keys.
            $keys[] = array(
	        // Just show the time, no text - not everyone has a phone or tablet with a large screen...
	        'text'	        => dt2time($now_plus_i),
                'callback_data' => $gym_id_plus_letter . ':edit_time:' . $event_id . ',' . $raid_level . ',' . $pokemon_id . ',' . utctime($now_plus_i,"H-i") 
            );
        }
    }

    // Get the inline key array.
    $keys = inline_key_array($keys, $key_count);

    // Init empty keys other options array.
    $keys_opt = [];

    // Raid already running
    $keys_opt[] = array(
        'text'	        => getTranslation('is_raid_active'),
        'callback_data' => $gym_id_plus_letter . ':edit_time:' . $event_id . ',' . $raid_level . ',' . $pokemon_id . ',' . utctime($now,"H-i").",more,0"
    );

    // Switch view: clocktime / minutes until start
    $keys_opt[] = array(
        'text'	        => $switch_text,
        'callback_data' => $gym_id_plus_letter . ':edit_starttime:' . $event_id . ',' . $raid_level . ',' . $pokemon_id . ',' . $switch_view
    );

    // Get the inline key array.
    $keys_opt = inline_key_array($keys_opt, 2);

    // Merge keys
    $keys = array_merge($keys, $keys_opt);

    // Write to log.
    debug_log($keys);

}

// No keys found.
if (!$keys) {
    // Create the keys.
    $keys = [
        [
            [
                'text'          => getTranslation('abort'),
                'callback_data' => '0:exit:0'
            ]
        ]
    ];
} else {
    // Back key id, action and arg
    $back_id = $gym_id_plus_letter;
    $back_action = 'edit_pokemon';
    $back_arg = $event_id . ',' . $raid_level;

    // Add navigation keys.
    $nav_keys = [];
    $nav_keys[] = universal_inner_key($nav_keys, $back_id, $back_action, $back_arg, getTranslation('back'));
    $nav_keys[] = universal_inner_key($nav_keys, $gym_id, 'exit', '2', getTranslation('abort'));
    $nav_keys = inline_key_array($nav_keys, 2);

    // Merge keys.
    $keys = array_merge($keys, $nav_keys);
}

// Build callback message string.
if ($arg != "min" && $arg != "clock") {
    $callback_response = getTranslation('pokemon_saved');
} else {
    $callback_response = getTranslation('raid_starts_when_view_changed');
}

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Set the message.
if ($arg == 'min') {
    $msg = getTranslation('raid_starts_when_minutes');
} else if ($event_id == EVENT_ID_EX) {
    $msg = getTranslation('raid_starts_when');
    $msg .= CR . CR . getTranslation('raid_select_date');
} else {
    $msg = getTranslation('raid_starts_when');
}

// Edit the message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();
