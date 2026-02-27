<?php
// Write to log.
debug_log('vote_pokemon()');
require_once(LOGIC_PATH . '/alarm.php');
require_once(LOGIC_PATH . '/send_vote_time_first.php');

// For debug.
//debug_log($update);
//debug_log($data);

$raidId = $data['r'];
$pokemon = $data['p'] ?? 0;

// Check if the user has voted for this raid before.
$rs = my_query('
  SELECT  *
  FROM    attendance
    WHERE   raid_id = ?
    AND   user_id = ?
  ', [$raidId, $update['callback_query']['from']['id']]
);

// Init empty attendances array and counter.
$atts = $rs->fetchAll();
$count = $rs->rowCount();

// Write to log.
debug_log($atts);

// User has voted before.
if(empty($atts)) {
  // Send vote time first.
  send_vote_time_first($update);
  exit;
}
// Any pokemon?
if($pokemon == 0) {
  // Delete any attendances except the first one.
  my_query('
    DELETE FROM attendance
    WHERE id NOT IN (
      SELECT * FROM (
        SELECT MIN(id)
        FROM   attendance
        WHERE  raid_id = :raidId
        AND  user_id = :userId
      ) AS AVOID_MYSQL_ERROR_1093
    )
    AND raid_id = :raidId
    AND user_id = :userId
    ', ['raidId' => $raidId, 'userId' => $update['callback_query']['from']['id']]
  );

  // Update attendance.
  my_query('
    UPDATE  attendance
    SET     pokemon = ?
    WHERE   raid_id = ?
    AND     user_id = ?
    ', [$pokemon, $raidId, $update['callback_query']['from']['id']]
  );

  // Send alarm
  $tg_json = alarm($raidId,$update['callback_query']['from']['id'],'pok_individual',$pokemon);
} else {
  // Init found and count.
  $found = false;

  // Loop thru attendances
  foreach($atts as $att_row => $att_data) {
    // Remove vote for specific pokemon
    if($att_data['pokemon'] == $pokemon) {
      // Is it the only vote? Update to "Any raid boss" instead of deleting it!
      if($count == 1) {
        my_query('
        UPDATE  attendance
        SET     pokemon = 0
        WHERE   raid_id = ?
        AND     user_id = ?
        ', [$raidId, $update['callback_query']['from']['id']]
        );
      // Other votes are still there, delete this one!
      } else {
        my_query('
        DELETE FROM attendance
        WHERE  raid_id = ?
        AND   user_id = ?
        AND   pokemon = ?
        ', [$raidId, $update['callback_query']['from']['id'], $pokemon]
        );
      }
      // Send alarm
      $tg_json = alarm($raidId,$update['callback_query']['from']['id'],'pok_cancel_individual',$pokemon);

      // Update count.
      $count = $count - 1;

      // Found and break.
      $found = true;
      break;
    }
  }

  // Not found? Insert!
  if(!$found) {
    // Send alarm
    $tg_json = alarm($raidId,$update['callback_query']['from']['id'],'pok_individual',$pokemon);

    $keys = $values = '';
    $binds = [];
    foreach($atts[0] as $key => $value) {
      if($key == 'id') continue;
      $keys .= $key . ',';
      $values .= '?,';
      $binds[] = ($key == 'pokemon') ? $pokemon : $value;
    }
    $keys = rtrim($keys, ',');
    $values = rtrim($values, ',');
    // Insert vote.
    my_query('
      INSERT INTO attendance (' . $keys . ')
      VALUES (' . $values . ')
      ', $binds
    );

    // Update counter.
    $count = $count + 1;
  }

  // Delete "Any raid boss" vote if count is larger than 0
  if($count > 1) {
    my_query('
      DELETE FROM attendance
      WHERE  raid_id = ?
      AND   user_id = ?
      AND   pokemon = 0
      ', [$raidId, $update['callback_query']['from']['id']]
    );
  }
}

require_once(LOGIC_PATH . '/update_raid_poll.php');

$tg_json = update_raid_poll($raidId, false, $update, $tg_json);

$tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('vote_updated'), true);

curl_json_multi_request($tg_json);

exit();
