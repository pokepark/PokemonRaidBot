<?php
// Write to log.
debug_log('LIST');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'list');

// Get the keys.
$keys_and_gymarea = raid_edit_gyms_first_letter_keys('list_by_gym', false, $data['id'], 'listall', 'list_raid');
$keys = $keys_and_gymarea['keys'];

if($data['id'] != 0) {
    // Add navigation keys.
    $nav_keys = [];
    $nav_keys[] = universal_inner_key($nav_keys, '', 'listall', '', getTranslation('back'));
    $nav_keys[] = universal_inner_key($nav_keys, '0', 'exit', '0', getTranslation('abort'));
    $nav_keys = inline_key_array($nav_keys, 2);
    // Merge keys.
    $keys = array_merge($keys, $nav_keys);
}

// Telegram JSON array.
$tg_json = array();

// Build callback message string.
$callback_response = getTranslation('select_gym');

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit the message.
$msg = '<b>' . getTranslation('list_all_active_raids') . '</b>' . CR;
if($config->ENABLE_GYM_AREAS) {
    if($keys_and_gymarea['gymarea_name'] == '') {
        $msg .= '<b>' . getTranslation('select_gym_area') . '</b>' . CR;
    }elseif(($config->DEFAULT_GYM_AREA == false && $data['id'] == 0) or $config->DEFAULT_GYM_AREA != false) {
        if($keys_and_gymarea['letters']) {
            $msg .= '<b>' . getTranslation('select_gym_first_letter_or_gym_area') . '</b>' . CR;
        }else {
            $msg .= '<b>' . getTranslation('select_gym_name_or_gym_area') . '</b>' . CR;
        }
    }elseif($keys_and_gymarea['letters']) {
        $msg .= '<b>' . getTranslation('select_gym_first_letter') . '</b>' . CR;
    }else {
        $msg .= '<b>' . getTranslation('select_gym_name') . '</b>' . CR;
    }
}else {
    $msg .= '<b>' . getTranslation('select_gym_first_letter') . '</b>' . CR;
}
$msg.= (($keys_and_gymarea['gymarea_name'] != '') ? CR . getTranslation('current_gymarea') . ': ' . $keys_and_gymarea['gymarea_name'] : '');
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();
