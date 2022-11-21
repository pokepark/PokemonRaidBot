<?php
// Write to log.
debug_log('raid_by_gym_letter()');
require_once(LOGIC_PATH . '/raid_edit_gyms_first_letter_keys.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck($update, 'create');

// Back key id, action and arg
$back_id = 'n';
$back_action = 'raid_by_gym_letter';
$back_arg = 0;

// Get the keys.
$keys_and_gymarea = raid_edit_gyms_first_letter_keys('raid_by_gym', false, ($data['id'] == 'n' ? false : $data['id']), 'raid_by_gym_letter');
$keys = $keys_and_gymarea['keys'];

// Add navigation keys.
$nav_keys = [];
if($data['id'] != 'n') {
  $nav_keys[] = universal_inner_key($nav_keys, $back_id, $back_action, $back_arg, getTranslation('back'));
}
$nav_keys[] = universal_inner_key($nav_keys, '0', 'exit', '0', getTranslation('abort'));
$nav_keys = inline_key_array($nav_keys, 2);
// Merge keys.
$keys = array_merge($keys, $nav_keys);

// No keys found.
if (!$keys) {
  // Create the keys.
  $keys = [
    [
      [
        'text'          => getTranslation('not_supported'),
        'callback_data' => '0:exit:0'
      ]
    ]
  ];
}

// Build callback message string.
$callback_response = getTranslation('select_gym');

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

$msg = '';
// Edit the message.
if($config->ENABLE_GYM_AREAS) {
  if($keys_and_gymarea['gymarea_name'] == '') {
    $msg .= '<b>' . getTranslation('select_gym_area') . '</b>' . CR;
  }elseif($config->DEFAULT_GYM_AREA !== false) {
    if($keys_and_gymarea['letters']) {
      $msg .= '<b>' . getTranslation('select_gym_first_letter_or_gym_area') . '</b>' . CR;
    }else {
      $msg .= '<b>' . getTranslation('select_gym_name_or_gym_area') . '</b>' . CR;
    }
  }else {
    if($keys_and_gymarea['letters']) {
      $msg .= '<b>' . getTranslation('select_gym_first_letter') . '</b>' . CR;
    }else {
      $msg .= '<b>' . getTranslation('select_gym_name') . '</b>' . CR;
    }
  }
}elseif($keys_and_gymarea['letters']) {
  $msg .= '<b>' . getTranslation('select_gym_first_letter') . '</b>' . CR;
}else {
  $msg .= '<b>' . getTranslation('select_gym_name') . '</b>' . CR;
}
$msg.= (($keys_and_gymarea['gymarea_name'] != '') ? CR . CR . getTranslation('current_gymarea') . ': ' . $keys_and_gymarea['gymarea_name'] : '');
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
