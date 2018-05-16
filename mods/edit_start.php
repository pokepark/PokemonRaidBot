<?php
// Write to log.
debug_log('edit_start()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check raid access.
raid_access_check($update, $data);

// Set the id.
$id = $data['id'];

// Set the arg.
$arg = $data['arg'];
$slot_switch = 0;
if (strpos($arg, ',') !== false)
{ 
    $args = explode(",", $arg);
    $arg = $args[0];
    $slot_switch = $args[1];
    debug_log('More options got requested for raid duration!');
    debug_log('Received argument and start_time: ' . $arg . ', ' . $slot_switch);
} else {
    $slot_switch = $arg;
}

if (true || $arg == "more-options" || $arg == "ex-raid") {
    if ($arg != "more-options" && $arg !="ex-raid") {
        // Current date
        $current_date = date('Y-m-d', strtotime('now'));
        debug_log('Today is a raid day! Setting raid date to ' . $current_date);
        debug_log('Received the following time (hour-minute) for the raid: ' . $arg);
        debug_log('Formatting the date now properly...');
        // Replace "-" with ":"
        $arg = str_replace('-', ':', $arg);
        $start_date_time = $current_date . ' ' . $arg . ':00';
        debug_log('Writing the formatted date to the database now: ' . $start_date_time);
        // Build query.
        my_query(
            "
            UPDATE    raids
            SET       start_time = '{$start_date_time}'
              WHERE   id = {$id}
            "
        );
    }

    // Init empty keys array.
    $keys = array();

    // Raid pokemon duration short or 1 Minute / 5 minute time slots
    if($arg == "more-options") {
        if ($slot_switch == 0) {
	    $slotmax = RAID_POKEMON_DURATION_SHORT;
	    $slotsize = 1;
        } else {
	    $slotmax = RAID_POKEMON_DURATION_LONG;
	    $slotsize = 5;
        }

        for ($i = $slotmax; $i >= 15; $i = $i - $slotsize) {
            // Create the keys.
            $keys[] = array(
	        // Just show the time, no text - not everyone has a phone or tablet with a large screen...
                'text'          => floor($i / 60) . ':' . str_pad($i % 60, 2, '0', STR_PAD_LEFT),
                'callback_data' => $id . ':edit_left:' . $i
            );
        }
    } else {
        // Use raid pokemon duration short.
        $keys[] = array(
            'text'          => '0:' . RAID_POKEMON_DURATION_SHORT,
            'callback_data' => $id . ':edit_left:' . RAID_POKEMON_DURATION_SHORT
        );

        // Button for more options.
        $keys[] = array(
            'text'          => getTranslation('expand'),
            'callback_data' => $id . ':edit_start:more-options,' . $slot_switch
        );
    }

    // Get the inline key array.
    $keys = inline_key_array($keys, 5);

    // Write to log.
    debug_log($keys);

} else {
    // Edit pokemon.
    $keys = raid_edit_start_keys($id);
}

// Edit the message.
edit_message($update, getTranslation('how_long_raid'), $keys);

// Build callback message string.
if ($arg != "more-options" && $arg !="ex-raid") {
    $callback_response = getTranslation('start_date_time') . ' ' . $arg;
} else {
    $callback_response = getTranslation('raid_starts_when_view_changed');
}

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);
