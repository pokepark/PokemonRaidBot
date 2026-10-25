<?php

// Authenticate based on the POST $update and trigger cleanup.
// Used for curl based cleanup triggering.
function cleanup_auth_and_run($update){
  global $config;
  cleanup_log('Cleanup request received.');
  if ($update['cleanup']['secret'] != $config->CLEANUP_SECRET) {
    $error = 'Cleanup authentication failed.';
    http_response_code(403);
    error_log($error);
    die($error);
  }
  cleanup_log('Cleanup is authenticated.');
  perform_cleanup();
  exit();
}

// Do the actual cleanup. Authentication or authorization is not considered but config is checked
function perform_cleanup(){
  global $config, $metrics, $namespace;
  // Run nothing is cleanup is not enabled.
  if (!$config->CLEANUP) {
    debug_log('Not running cleanup, not enabled.');
    return;
  }

  if ($metrics){
    $cleanup_total = $metrics->registerCounter($namespace, 'cleanup_total', 'Total items cleaned up', ['type']);
  }

  debug_log('Running cleanup tasks, for more debug enable & see the separate cleanup debug logging.');

  // Check configuration, cleanup of telegram needs to happen before database cleanup!
  if ($config->CLEANUP_TIME_TG > $config->CLEANUP_TIME_DB) {
    $error = 'Configuration issue! Cleanup time for telegram messages needs to be lower or equal to database cleanup time!';
    info_log($error);
    throw new Exception($error);
  }
  // Start cleanup when at least one parameter is set to trigger cleanup
  if (!$config->CLEANUP_TELEGRAM && !$config->CLEANUP_DATABASE) {
    cleanup_log('Cleanup was called, but nothing was done. Check your config and cleanup request for which actions you would like to perform (Telegram and/or database cleanup)');
    return;
  }
  // Query for telegram cleanup without database cleanup
  if ($config->CLEANUP_TELEGRAM) {
    // Get cleanup info for telegram cleanup.
    $rs = my_query('
      SELECT    cleanup.id, cleanup.raid_id, cleanup.chat_id, cleanup.message_id, raids.gym_id,
                IF(date_of_posting < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 48 HOUR), 1, 0) as skip_del_message
      FROM      cleanup
      LEFT JOIN raids
      ON        cleanup.raid_id = raids.id
      WHERE     raids.end_time < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$config->CLEANUP_TIME_TG.' MINUTE)
    ');
    $cleanup_ids = [];
    cleanup_log('Telegram cleanup starting. Found ' . $rs->rowCount() . ' entries for cleanup.');
    if($rs->rowCount() > 0) {
      while($row = $rs->fetch()) {
        if($row['skip_del_message'] == 1) {
          cleanup_log('Chat message for raid '.$row['raid_id'].' in chat '.$row['chat_id'].' is over 48 hours old. It can\'t be deleted by the bot. Skipping deletion and removing database entry.');
          continue;
        }
        if(delete_message($row['chat_id'], $row['message_id']) === false) continue;
        $cleanup_ids[] = $row['id'];
        cleanup_log('Deleting raid: '.$row['raid_id'].' from chat '.$row['chat_id'].' (message_id: '.$row['message_id'].')');
        if ($metrics){
          $cleanup_total->inc(['telegram']);
        }
      }
      my_query('DELETE FROM cleanup WHERE id IN (' . implode(',', $cleanup_ids) . ')');
    }
  }
  if($config->CLEANUP_DATABASE) {
    cleanup_log('Database cleanup called.');
    $rs_temp_gyms = my_query('
      SELECT      gyms.id, gyms.gym_name
      FROM        gyms
      LEFT JOIN   raids
      ON          raids.gym_id = gyms.id
      WHERE       (raids.end_time < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$config->CLEANUP_TIME_DB.' MINUTE))
      AND         temporary_gym = 1
    ');
    if($rs_temp_gyms->rowCount() > 0) {
      $cleanup_gyms = [];
      while($row = $rs_temp_gyms->fetch()) {
        $cleanup_gyms[] = $row['id'];
        cleanup_log('Deleting temporary gym ' . $row['id'] . ' from database.');
      }
      if(count($cleanup_gyms) > 0) {
        my_query('DELETE FROM gyms WHERE id IN (' . implode(',', $cleanup_gyms) . ')');
      }
    }
    $q_a = my_query('DELETE FROM attendance WHERE raid_id IN (SELECT id FROM raids WHERE raids.end_time < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$config->CLEANUP_TIME_DB.' MINUTE))');
    $q_r = my_query('DELETE FROM raids WHERE end_time < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$config->CLEANUP_TIME_DB.' MINUTE)');
    $q_p = my_query('DELETE photo_cache FROM photo_cache LEFT JOIN raids ON photo_cache.raid_id = raids.id WHERE photo_cache.ended = 0 AND raids.id IS NULL');
    cleanup_log('Cleaned ' . $q_a->rowCount() . ' rows from attendance table');
    cleanup_log('Cleaned ' . $q_r->rowCount() . ' rows from raids table');
    cleanup_log('Cleaned ' . $q_p->rowCount() . ' rows from photo_cache table');
    if ($metrics){
      $cleanup_total->incBy($q_a->rowCount(), ['db_attendance']);
      $cleanup_total->incBy($q_r->rowCount(), ['db_raids']);
    }
  }
  // Write to log.
  cleanup_log('Finished with cleanup process!');
}
