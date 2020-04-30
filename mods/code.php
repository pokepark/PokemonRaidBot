<?php
// Write to log.
debug_log('code()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'list');

// Set the raid id.
$raid_id = $data['id'];

// Get the current code
$arg = $data['arg'];
$data = explode("-", $arg);
$c1 = $data[0];
$c2 = $data[1];
$c3 = $data[2];

// Get pokemon names.
$p1 = ($c1 > 0) ? get_local_pokemon_name($c1) : '';
$p2 = ($c2 > 0) ? get_local_pokemon_name($c2) : '';
$p3 = ($c3 > 0) ? get_local_pokemon_name($c3) : '';

// Action to do: Ask to send or add code?
$action = $data[3];

// Log
debug_log('Code #1: ' . $c1 . SP . '(' . $p1 . ')');
debug_log('Code #2: ' . $c2 . SP . '(' . $p2 . ')');
debug_log('Code #3: ' . $c3 . SP . '(' . $p3 . ')');
debug_log('Action: ' . $action);

// Get raid info.
$raid = get_raid($raid_id);
$gym_name = $raid['gym_name'];
if(empty($gym_name)) {
    $gym_name = '';
}
$msg = $gym_name . CR;
$raid_day = dt2date($raid['start_time']);
$now = utcnow();
$today = dt2date($now);
$start = dt2time($raid['start_time']);
$end = dt2time($raid['end_time']);
$msg .= get_local_pokemon_name($raid['pokemon']) . SP . '—' . SP . (($raid_day == $today) ? '' : ($raid_day . ', ')) . $start . SP . getTranslation('to') . SP . $end . CR . CR;

// Add digits to cp
if($action == 'add') {
    // Init empty keys array.
    $keys = [];

    // Get the keys.
    $keys = group_code_keys($raid_id, 'code', $arg);

    // Back and abort.
    $keys[] = [
        [
            'text'          => getTranslation('back'),
            'callback_data' => $raid_id . ':code_gym:' . $c1 . '-' . $c2 . '-' . $c3 . '-add'
        ],
        [
            'text'          => getTranslation('abort'),
            'callback_data' => '0:exit:0'
        ]
    ];

    // Build callback message string.
    $callback_response = 'OK';

    // Set the message.
    $msg .= '<b>' . getTranslation('group_code') . ':</b>' . CR;
    $msg .= ($c1 > 0) ? ($p1 . CR) : '';
    $msg .= ($c2 > 0) ? ($p2 . CR) : '';
    $msg .= ($c3 > 0) ? ($p3 . CR) : '';

// Send code to users
} else if($action == 'send') {
    // Groupcode
    $group_code = $p1 . CR;
    $group_code .= $p2 . CR;
    $group_code .= $p3 . CR;

    $group_code = $p1 . '--' . SP;
    $group_code .= $p2 . '--' . SP;
    $group_code .= $p3 . CR;

    // Send code via alarm function
    alarm($raid_id,$update['callback_query']['from']['id'],'group_code',$group_code);

    // Init empty keys array.
    $keys = [];

    // Build callback message string.
    $callback_response = getTranslation('code_send');

    // Set the message.
    $msg .= '<b>' . getTranslation('code_send') . '</b>' . CR . CR;
    $msg .= getTranslation('group_code') . ':' . CR;
    $msg .= $group_code;
}

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();
