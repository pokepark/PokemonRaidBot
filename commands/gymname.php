<?php
// Write to log.
debug_log('GYMNAME()');
require_once(LOGIC_PATH . '/get_gym_by_telegram_id.php');
require_once(LOGIC_PATH . '/get_gym_details.php');
require_once(LOGIC_PATH . '/get_gym.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('gym-name');

// Get gym by name.
// Trim away everything before "/gymname "
$input = trim(substr($update['message']['text'], 9));

// Init vars.
$gym = false;
$id = 0;
$tg_id = '#' . $update['message']['from']['id'];

// Maybe get gym by telegram id?
$gym = get_gym_by_telegram_id($tg_id);

// Update gym info.
if($gym && !empty($input) && $gym['id'] > 0) {
  debug_log('Changing name for gym with ID: ' . $gym['id']);
  debug_log('Gym name: ' . $input);
  my_query('
    UPDATE  gyms
    SET     gym_name = :info
    WHERE   id = :id
    ', ['info' => $input, 'id' => $gym['id']]
  );
  $gym['gym_name'] = $input;
  // Set message.
  $msg = get_gym_details($gym);
  $msg .= CR . '<b>' . getTranslation('gym_name_updated') . '</b>';
} else if($gym && empty($info)) {
  debug_log('Missing gym name!');
  // Set message.
  $msg = CR . '<b>' . getTranslation('gym_id_name_missing') . '</b>';
  $msg .= CR . CR . getTranslation('gym_name_instructions');
  $msg .= CR . getTranslation('gym_name_example');
} else {
  // Set message.
  $msg = getTranslation('gym_not_found');
}

// Send message.
send_message($update['message']['chat']['id'], $msg, [], ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true], 'disable_web_page_preview' => 'true']);
