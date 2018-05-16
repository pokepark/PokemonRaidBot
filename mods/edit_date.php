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

// Get the argument.
$arg = $data['arg'];

// Number of days in month
$days_in_month = date('t', strtotime($arg));

// Init empty keys array and set keys count.
$keys = array();
$keys_count = 2;

// Check amount of "-" in $arg to add day, hour and minute of ex-raid date
// Received: Year-Month / 1970-01 / 1x "-"
if(substr_count($arg, '-') == 1) {
    debug_log('Generating buttons for each day in the given year and month: ' . $arg);

    // Formatting stuff.
    $month = substr($arg, -2);
    $year = substr($arg, 0, 4);

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
} else if (substr_count($arg, '-') == 2) {
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
} else if (substr_count($arg, '-') == 3) {
    debug_log('Generating buttons for minute of the hour');
    $hour = explode(" ", $arg);
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
} else if (substr_count($arg, '-') == 4) {
    debug_log('Received the following date for the raid: ' . $arg);
    debug_log('Formatting the date now properly...');
    // Replace last 2 occurences of "-" with ":"
    $start_dt = explode(" ", $arg);
    $date = $start_dt[0];
    $time = str_replace('-', ':', $start_dt[1]);
    $start_date_time = $date . ' ' . $time;
    debug_log('Writing the formatted date to the database now: ' . $start_date_time);
    // Build query.
    my_query(
        "
        UPDATE    raids
        SET       start_time = '{$start_date_time}'
          WHERE   id = {$id}
        "
    );

    // Adding button to continue with next step in raid creation
    $keys[] = array(
        'text'          => getTranslation('next'),
        'callback_data' => $id . ':edit_start:ex-raid'
    );

    // Set message.
    $msg = getTranslation('start_date_time') . ':' . CR .'<b>' . $start_date_time . '</b>';
}

// Get the inline key array.
$keys = inline_key_array($keys, $keys_count);

// Edit the message.
edit_message($update, $msg, $keys);

// Build callback message string.
$callback_response = 'OK';

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);
