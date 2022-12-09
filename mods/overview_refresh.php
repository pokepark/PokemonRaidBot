<?php
// Write to log.
debug_log('overview_refresh()');
require_once(LOGIC_PATH . '/get_chat_title_username.php');
require_once(LOGIC_PATH . '/get_overview.php');
require_once(LOGIC_PATH . '/resolve_raid_boss.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Get chat ID from data
$chat_id = 0;
$chat_id = $data['arg'];

// Get all or specific overview
$query_chat = '';
if ($chat_id != 0) {
  $query_chat = 'WHERE chat_id = ' . $chat_id;
}

$request_overviews = my_query('
  SELECT  chat_id, message_id, chat_title, chat_username, (IF(updated < DATE(NOW()) or updated IS NULL, 1, 0)) as update_needed
  FROM    overview
  ' . $query_chat . '
');
// Array of chat id's
$overviews = $request_overviews->fetchAll();

// Get active raids for every overview
$active_raids = [];
$tg_json = [];
foreach($overviews as $overview_row) {
  $request_raids = my_query('
    SELECT raids.pokemon, raids.pokemon_form, raids.spawn, raids.level, raids.id, raids.start_time, raids.end_time, raids.gym_id,
      MAX(cleanup.message_id) as message_id,
      events.name as event_name,
      gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym,
      TIME_FORMAT(TIMEDIFF(end_time, UTC_TIMESTAMP()) + INTERVAL 1 MINUTE, \'%k:%i\') AS t_left
    FROM    cleanup
    LEFT JOIN raids
    ON    raids.id = cleanup.raid_id
    LEFT JOIN gyms
    ON    raids.gym_id = gyms.id
    LEFT JOIN  events
    ON     events.id = raids.event
    WHERE   cleanup.chat_id = ?
    AND     raids.end_time>UTC_TIMESTAMP()
    GROUP BY  raids.id, raids.pokemon, raids.pokemon_form, raids.start_time, raids.end_time, raids.gym_id, gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym, events.name
    ORDER BY  raids.end_time ASC, gyms.gym_name
    ', [$overview_row['chat_id']]
  );
  // Write active raids to array
  $active_raids = $request_raids->fetchAll();
  debug_log('Active raids:');
  debug_log($active_raids);

  if($overview_row['update_needed'] == 1) {
    [$chat_title, $chat_username] = get_chat_title_username($overview_row['chat_id']);
    my_query('
      UPDATE  overview
      SET   chat_title = ?,
          chat_username = ?,
          updated = DATE(NOW())
      WHERE   chat_id = ?
      ',[$chat_title, $chat_username, $overview_row['chat_id']]
    );
  }else {
    $chat_title = $overview_row['chat_title'];
    $chat_username = $overview_row['chat_username'];
  }
  $overview_message = get_overview($active_raids, $chat_title, $chat_username);
  // Triggered from user or cronjob?
  if (!empty($update['callback_query']['id'])) {
    // Answer the callback.
    answerCallbackQuery($update['callback_query']['id'], 'OK');
    $message_id = $update['callback_query']['message']['message_id'];
    $chat_id = $update['callback_query']['from']['id'];
    $keys[] = [
      [
        'text'          => EMOJI_REFRESH,
        'callback_data' => '0:overview_refresh:' . $overview_row['chat_id']
      ],
      [
        'text'          => getTranslation('done'),
        'callback_data' => formatCallbackData(['exit', 'd' => '1'])
      ]
    ];
  }else {
    $message_id = $overview_row['message_id'];
    $chat_id = $overview_row['chat_id'];
    $keys = [];
  }

  $tg_json[] = editMessageText($message_id, $overview_message, $keys, $chat_id, ['disable_web_page_preview' => 'true'], true);
}
// Telegram multicurl request.
curl_json_multi_request($tg_json);

$dbh=null;
exit;
