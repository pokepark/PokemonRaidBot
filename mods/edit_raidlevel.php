<?php
// Write to log.
debug_log('edit_raidlevel()');
require_once(LOGIC_PATH . '/active_raid_duplication_check.php');
require_once(LOGIC_PATH . '/get_gym.php');
require_once(LOGIC_PATH . '/raid_edit_raidlevel_keys.php');
require_once(LOGIC_PATH . '/show_raid_poll_small.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('create');

$gym_id = $data['g'];
$gym = get_gym($gym_id);

// Telegram JSON array.
$tg_json = array();

//Initialize admin rights table [ ex-raid , raid-event ]
$admin_access = [false,false];
// Check access - user must be admin for raid_level X
$admin_access[0] = $botUser->accessCheck('ex-raids', true);
// Check access - user must be admin for raid event creation
$admin_access[1] = $botUser->accessCheck('event-raids', true);

// Active raid?
$duplicate_id = active_raid_duplication_check($gym_id);
if ($duplicate_id > 0) {
  // In case gym has already a normal raid saved to it and user has privileges to create an event raid, create a special menu
  if($admin_access[0] == true || $admin_access[1] == true) {
    $msg = EMOJI_WARN . SP . getTranslation('raid_already_exists') . CR;
    $msg .= getTranslation('inspect_raid_or_create_event') . ':';

    $eventData = $backData = $data;
    $eventData['callbackAction'] = 'edit_event';
    $backData['callbackAction'] = 'gymMenu';
    $backData['stage'] = 2;
    $backData['a'] = 'create';
    $keys = [
      [
        [
          'text'          => getTranslation('saved_raid'),
          'callback_data' => formatCallbackData(['callbackAction' => 'raids_list', 'id' => $duplicate_id])
        ]
      ],
      [
        [
          'text'          => getTranslation('create_event_raid'),
          'callback_data' => formatCallbackData($eventData)
        ]
      ],
      [
        [
          'text'          => getTranslation('back'),
          'callback_data' => formatCallbackData($backData)
        ],
        [
          'text'          => getTranslation('exit'),
          'callback_data' => formatCallbackData(['callbackAction' => 'exit'])
        ]
      ],
    ];
  } else {
    $keys = [];
    $raid_id = $duplicate_id;
    $raid = get_raid($raid_id);
    $msg = EMOJI_WARN . SP . getTranslation('raid_already_exists') . SP . EMOJI_WARN . CR . show_raid_poll_small($raid);
    $keys = share_keys($raid_id, 'raid_share', $update, $raid['level']);
    if($botUser->raidaccessCheck($raid['id'], 'pokemon', true)) {
      $keys[] = [
        [
          'text'          => getTranslation('update_pokemon'),
          'callback_data' => $raid['id'] . ':raid_edit_poke:' . $raid['level'],
        ]
      ];
    }
    if($botUser->raidaccessCheck($raid['id'], 'delete', true)) {
      $keys[] = [
        [
          'text'          => getTranslation('delete'),
          'callback_data' => $raid['id'] . ':raids_delete:0'
        ]
      ];
    }
    $keys[][] = [
      'text' => getTranslation('abort'),
      'callback_data' => formatCallbackData(['callbackAction' => 'exit'])
    ];
  }

  // Answer callback.
  $tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('raid_already_exists'), true);

  // Edit the message.
  $tg_json[] = edit_message($update, $msg, $keys, false, true);

  // Telegram multicurl request.
  curl_json_multi_request($tg_json);

  // Exit.
  exit();
}

// Get the keys.
$keys = raid_edit_raidlevel_keys($data, $admin_access);

$backData = $data;
$backData['callbackAction'] = 'gymMenu';
$backData['a'] = 'create';
$backData['stage'] = 2;
// Add navigation keys.
$keys[] = [
  [
    'text'          => getTranslation('back'),
    'callback_data' => formatCallbackData($backData)
  ],
  [
    'text'          => getTranslation('abort'),
    'callback_data' => formatCallbackData(['callbackAction' => 'exit'])
  ]
];

// Build message.
$msg = getTranslation('create_raid') . ': <i>' . (($gym['address']=="") ? $gym['gym_name'] : $gym['address']) . '</i>';

// Build callback message string.
$callback_response = getTranslation('gym_saved');

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit the message.
$tg_json[] = edit_message($update, $msg . CR . getTranslation('select_raid_level') . ':', $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
