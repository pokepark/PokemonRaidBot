<?php
// Write to log.
debug_log('gym_delete()');
require_once(LOGIC_PATH . '/get_gym_details.php');
require_once(LOGIC_PATH . '/get_gym.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('gym-delete');

// Get the arg.
$gymId = $data['g'];
$confirm = $data['c'] == 1 ? true : false;

if ($gymId > 0 && $confirm == false) {
  $gym = get_gym($gymId);

  // Set message
  $msg = EMOJI_WARN . SP . '<b>' . getTranslation('delete_this_gym') . '</b>' . SP . EMOJI_WARN;
  $msg .= CR . get_gym_details($gym);

  // Create the keys.
  $keys = [
    [
      [
        'text'          => getTranslation('yes'),
        'callback_data' => formatCallbackData(['callbackAction' => 'gym_delete', 'g' => $gymId, 'c' => 1])
      ]
    ],
    [
      [
        'text'          => getTranslation('no'),
        'callback_data' => formatCallbackData(['callbackAction' => 'gym_edit_details', 'g' => $gymId])
      ]
    ]
  ];

// Delete the gym.
} else if ($gymId > 0 && $confirm == true) {
  require_once(LOGIC_PATH . '/get_gym_details.php');
  require_once(LOGIC_PATH . '/get_gym.php');
  debug_log('Deleting gym with ID ' . $gymId);
  // Get gym.
  $gym = get_gym($gymId);

  // Set message
  $msg = '<b>' . getTranslation('deleted_this_gym') . '</b>' . CR;
  $msg .= get_gym_details($gym);
  $keys = [];

  // Delete gym.
  my_query('
    DELETE FROM gyms
    WHERE   id = ?
    ', [$gymId]
  );
}

// Build callback message string.
$callback_response = getTranslation('here_we_go');

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit the message.
$tg_json[] = edit_message($update, $msg, $keys, ['disable_web_page_preview' => 'true'], true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
