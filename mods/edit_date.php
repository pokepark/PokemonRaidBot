<?php
// Write to log.
debug_log('edit_date()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access - user must be admin!
$admin_access = bot_access_check($update, BOT_ADMINS, true);
if (!$admin_access) {
    // Do not edit message, but send access denied back to user and exit then
    $response_msg = '<b>' . getTranslation('bot_access_denied') . '</b>';
    sendMessage($update['callback_query']['from']['id'], $response_msg);
    exit;
}

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

// Check amount of "-" in $arg to add day, hour and minute of ex-raid date
// Received: Year-Month / 1970-01 / 1x "-"
if(substr_count($raid_time, '-') == 1) {
    debug_log('Generating buttons for each day in the given year and month: ' . $raid_time);

    // Number of days in month
    $days_in_month = date('t', strtotime($raid_time));

    // Formatting stuff.
    $month = substr($raid_time, -2);
    $year = substr($raid_time, 0, 4);

    // Is month the current month then start from current day, otherwise from 1st day of the month
    $start_date = (date('m') == $month) ? date('j') : 1;

    // Buttons for each day in the given month
    for ($i = $start_date; $i <= $days_in_month; $i = $i + 1) {
        // Create the keys.
        $keys[] = array(
            //'text'          => $arg . '-' . str_pad($i, 2, '0', STR_PAD_LEFT),
            'text'          => str_pad($i, 2, '0', STR_PAD_LEFT) . ' ' . getTranslation('month_' . $month) . ' ' . $year,
            'callback_data' => $id . ':edit_date:' . $arg . '-' . str_pad($i, 2, '0', STR_PAD_LEFT)
        );
    }

    //Set message
    $msg = getTranslation('raid_select_date');
// Received: Year-Month-Day / 1970-01-01 / 2x "-"
} else if (substr_count($raid_time, '-') == 2) {
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
    debug_log('Waiting for confirmation to save the raid');

    // Adding button to continue with next step in raid creation
    $keys[] = array(
        'text'          => getTranslation('next'),
        'callback_data' => $id . ':edit_time:' . $arg . ',X,0'
    );

    // Set message.
    $msg = getTranslation('start_date_time') . ':' . CR .'<b>' . $raid_time . '</b>';
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

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

// Edit the message.
edit_message($update, $msg, $keys);

// Exit.
exit();
