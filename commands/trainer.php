<?php
// Write to log.
debug_log('TRAINER()');
require_once(LOGIC_PATH . '/get_user.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('trainer');

// Set message.
$msg = '<b>' . getTranslation('trainerinfo_set_yours') . '</b>';

$user_id = $botUser->userId;
$msg .= CR . CR . get_user($user_id, false);

// Init empty keys array.
$keys = [];
// Create keys array.
if($config->CUSTOM_TRAINERNAME){
  $keys[0][] = button(getTranslation('name'), 'trainer_name');
}
if($config->RAID_POLL_SHOW_TRAINERCODE){
  $keys[0][] = button(getTranslation('trainercode'), 'trainer_code');
}
$keys[] = [
  button(getTranslation('team'), 'trainer_team'),
  button(getTranslation('level'), 'trainer_level')
];
if ($config->RAID_AUTOMATIC_ALARM == false) {
  $q_user = my_query('SELECT auto_alarm FROM users WHERE user_id = ? LIMIT 1', [$user_id]);
  $alarm_status = $q_user->fetch()['auto_alarm'];
  $buttonText = ($alarm_status == 1 ? getTranslation('switch_alarm_off') . ' ' . EMOJI_NO_ALARM : getTranslation('switch_alarm_on') . ' ' . EMOJI_ALARM);
  $keys[][] = button($buttonText, ['trainer', 'a' => 1]);
}
if ($config->LANGUAGE_PRIVATE == '') {
  $keys[][] = button(getTranslation('bot_lang'), 'bot_lang');
}
if ($config->ENABLE_GYM_AREAS == true) {
  $keys[][] = button(getTranslation('default_gymarea'), 'trainerGymarea');
}

// Check access.
$access = $botUser->accessCheck('trainer-share', true);

// Display sharing options for admins and users with trainer-share permissions
if($access) {
  // Add sharing keys.
  $share_keys = [];
  $share_keys[] = button(getTranslation('trainer_message_share'), 'trainer_add');
  $share_keys[] = button(getTranslation('trainer_message_delete'), 'trainer_delete');

  // Get the inline key array.
  $keys[] = $share_keys;

  // Add message.
  $msg .= CR . CR . getTranslation('trainer_message_share_or_delete');
}

// Add abort key.
$nav_keys = [];
$nav_keys[] = button(getTranslation('done'), 'exit');

// Get the inline key array.
$keys[] = $nav_keys;

// Send message.
send_message(create_chat_object([$update['message']['chat']['id']]), $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);
