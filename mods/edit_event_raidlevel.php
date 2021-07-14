<?php
// Write to log.
debug_log('edit_event_raidlevel()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'event-raids');

// Get gym data via ID
$id_data = explode(",", $data['id']);
$gym_id = $id_data[0];
$gym_first_letter = $id_data[1];
$gym = get_gym($gym_id);

// Get event ID
$event_id = $data['arg'];

// Back key id, action and arg
$back_id = $data['id'];
$back_action = 'edit_event';
$back_arg = $data['arg'];

// Telegram JSON array.
$tg_json = array();

//Initiate admin rights table [ ex-raid , raid-event ]
$admin_access = [false,1];
// Check access - user must be admin for raid_level X
$admin_access[0] = bot_access_check($update, 'ex-raids', true);

// Get the keys.
$keys = raid_edit_raidlevel_keys($gym_id, $gym_first_letter, $admin_access, $event_id);

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
    // Add navigation keys.
    $nav_keys = [];
    $nav_keys[] = universal_inner_key($nav_keys, $back_id, $back_action, $back_arg, getTranslation('back'));
    $nav_keys[] = universal_inner_key($nav_keys, $gym_id, 'exit', '2', getTranslation('abort'));
    $nav_keys = inline_key_array($nav_keys, 2);
    // Merge keys.
    $keys = array_merge($keys, $nav_keys);
}
// Get event info 
$q = my_query("SELECT name, description FROM events WHERE id='{$event_id}' LIMIT 1");
$rs = $q->fetch();

// Build message.
if($event_id == 'X') {
    $msg = "<b>".getTranslation('Xstars')."</b>".CR;
}else {
    $msg = "<b>{$rs['name']}</b>".CR."{$rs['description']}".CR;
}
$msg.= getTranslation('create_raid') . ': <i>' . (($gym['address']=="") ? $gym['gym_name'] : $gym['address']) . '</i>';

// Build callback message string.
$callback_response = getTranslation('gym_saved');

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit the message.
$tg_json[] = edit_message($update, $msg . CR . getTranslation('select_raid_level') . ':', $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();

