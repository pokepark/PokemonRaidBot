<?php
// Write to log.
debug_log('raids_delete()');
require_once(LOGIC_PATH . '/show_raid_poll_small.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Get the action.
// 0 -> Confirmation required
// 1 -> Cancel deletion
// 2 -> Execute deletion
$action = $data['a'] ?? 0;

// Get the raid id.
$raidId = $data['r'];

// Access check.
$botUser->raidaccessCheck($raidId, 'delete');

// Execute the action.
if ($action == 0) {
  // Get raid.
  $raid = get_raid($raidId);

  // Write to log.
  debug_log('Asking for confirmation to delete the following raid:');
  debug_log($raid);

  // Create keys array.
  $keys = [
    [
      [
        'text'          => getTranslation('yes'),
        'callback_data' => formatCallbackData(['raids_delete', 'r' => $raid['id'], 'a' => 2])
      ],
      [
        'text'          => getTranslation('no'),
        'callback_data' => formatCallbackData(['raids_delete', 'r' => $raid['id'], 'a' => 1])
      ]
    ]
  ];

  // Set message.
  $msg = EMOJI_WARN . '<b> ' . getTranslation('delete_this_raid') . ' </b>' . EMOJI_WARN . CR . CR;
  $msg .= show_raid_poll_small($raid);
} else if ($action == 1) {
  debug_log('Raid deletion for ' . $raidId . ' was canceled!');
  // Set message.
  $msg = '<b>' . getTranslation('raid_deletion_was_canceled') . '</b>';

  // Set keys.
  $keys = [];
} else if ($action == 2) {
  debug_log('Confirmation to delete raid ' . $raidId . ' was received!');
  // Delete telegram messages for raid.
  $rs = my_query('
    SELECT  *
      FROM    cleanup
      WHERE   raid_id = ?
        AND   chat_id <> 0
    ', [$raidId]
  );

  // Counter
  $counter = 0;

  // Delete every telegram message
  while ($row = $rs->fetch()) {
    // Delete telegram message.
    debug_log('Deleting telegram message ' . $row['message_id'] . ' from chat ' . $row['chat_id'] . ' for raid ' . $row['raid_id']);
    delete_message($row['chat_id'], $row['message_id']);
    $counter = $counter + 1;
  }

  // Nothing to delete on telegram.
  if ($counter == 0) {
    debug_log('Raid with ID ' . $raidId . ' was not found in the cleanup table! Skipping deletion of telegram messages!');
  }

  // Delete raid from cleanup table.
  debug_log('Deleting raid ' . $raidId . ' from the cleanup table:');
  my_query('
    DELETE FROM   cleanup
    WHERE   raid_id = ?
    ', [$raidId]
  );

  // Delete raid from attendance table.
  debug_log('Deleting raid ' . $raidId . ' from the attendance table:');
  my_query('
    DELETE FROM   attendance
    WHERE  raid_id = ?
    ', [$raidId]
  );

  // Delete raid from raid table.
  debug_log('Deleting raid ' . $raidId . ' from the raid table:');
  my_query('
    DELETE FROM   raids
    WHERE   id = ?
    ', [$raidId]
  );

  // Set message.
  $msg = getTranslation('raid_successfully_deleted');

  // Set keys.
  $keys = [];
}

// Build callback message string.
$callback_response = 'OK';

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
