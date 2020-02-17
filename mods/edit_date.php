<?php
// Write to log.
debug_log('edit_date()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'ex-raids');

// Set the id.
$id = $data['id'];
$gym_id = explode(',',$data['id'])[0];

// Get the argument.
$arg = $data['arg'];
$pokemon_id = explode(',', $arg)[0];
$raid_time = explode(',', $arg)[1];

// Init empty keys array and set keys count.
$keys = [];
$keys_count = 2;

// Received: Year-Month-Day / 1970-01-01 / 2x "-"
if (substr_count($raid_time, '-') == 2) {
    debug_log('Generating buttons for each hour of the day');
    // Buttons for each hour
    for ($i = 0; $i <= 23; $i = $i + 1) {
        // Create the keys.
        $keys[] = array(
            // Just show the time, no text - not everyone has a phone or tablet with a large screen...
            'text'          => str_pad($i, 2, '0', STR_PAD_LEFT) . ':xx',
            'callback_data' => $id . ':edit_date:' . $arg . ' ' . str_pad($i, 2, '0', STR_PAD_LEFT) . '-'
        );
    }
    // Set keys count and message.
    $keys_count = 4;
    $msg = getTranslation('raid_select_hour');
// Received: Year-Month-Day Hour- / 1970-01-01 00- / 3x "-"
} else if (substr_count($raid_time, '-') == 3) {
    debug_log('Generating buttons for minute of the hour');
    $hour = explode(" ", $raid_time);
    $hour = $hour[1];
    // Buttons for each minute
    for ($i = 0; $i <= 45; $i = $i + 15) {
        // Create the keys.
        $keys[] = array(
            // Just show the time, no text - not everyone has a phone or tablet with a large screen...
            'text'          => substr($hour, 0, -1) . ':' . str_pad($i, 2, '0', STR_PAD_LEFT),
            'callback_data' => $id . ':edit_date:' . $arg . str_pad($i, 2, '0', STR_PAD_LEFT) . '-00'
        );
    }
    // Set keys count and message.
    $keys_count = 4;
    $msg = getTranslation('raid_select_start_time');
// Received: Year-Month-Day Hour-Minute-Second / 1970-01-01 00-00-00 / 4x "-"
} else if (substr_count($raid_time, '-') == 4) {
    debug_log('Received the following date for the raid: ' . $raid_time);

    // Format date, e.g 14 April 2019, 15:15h
    $tz = $config->TIMEZONE;
    $tz_raid_time = DateTimeImmutable::createFromFormat('Y-m-d H-i-s', $raid_time, new DateTimeZone($tz));
    $date_tz = $tz_raid_time->format('Y-m-d');
    $text_split = explode('-', $date_tz);
    $text_day = $text_split[2];
    $text_month = getTranslation('month_' . $text_split[1]);
    $text_year = $text_split[0];
    $time_tz = $tz_raid_time->format('H:i') . 'h';

    // Raid time in UTC
    $utc_raid_time = $tz_raid_time->setTimezone(new DateTimeZone('UTC'));
    $utc_raid_time = $utc_raid_time->format('Y-m-d H-i-s');
    debug_log('Converting date to UTC to store in database');
    debug_log('UTC date for the raid: ' . $utc_raid_time);
    debug_log('Waiting for confirmation to save the raid');

    // Adding button to continue with next step in raid creation
    $keys[] = array(
        'text'          => getTranslation('next'),
        'callback_data' => $id . ':edit_time:' . $pokemon_id . ',' . $utc_raid_time . ',X,0'
    );

    // Set message.
    $msg = getTranslation('start_date_time') . ':' . CR .'<b>' . $text_day . SP . $text_month . SP . $text_year . ',' . SP . $time_tz . '</b>';
}

// Get the inline key array.
$keys = inline_key_array($keys, $keys_count);

// Add navigation keys.
$nav_keys = [];

// Back key id, action and arg
if(substr_count($raid_time, '-') == 1 || substr_count($raid_time, '-') == 4) {
    $back_id = $id;
    $back_action = 'edit_starttime';
    $back_arg = $arg;
    $nav_keys[] = universal_inner_key($nav_keys, $back_id, $back_action, $back_arg, getTranslation('back'));
}

$nav_keys[] = universal_inner_key($nav_keys, $gym_id, 'exit', '2', getTranslation('abort'));
$nav_keys = inline_key_array($nav_keys, 2);

// Merge keys.
$keys = array_merge($keys, $nav_keys);

// Build callback message string.
$callback_response = 'OK';

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit the message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();
