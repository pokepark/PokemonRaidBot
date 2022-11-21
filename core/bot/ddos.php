<?php
// Write to log
debug_log('DDOS Check');
if ($metrics){
  $ddos_old_updates_total = $metrics->registerCounter($namespace, 'ddos_old_updates_total', 'Total old updates received');
  $ddos_last_update = $metrics->registerGauge($namespace, 'ddos_last_update', 'Last known update_id');
  $ddos_state = $metrics->registerGauge($namespace, 'ddos_state', 'current DDoS values', ['user_id']);
  $ddos_fails_total = $metrics->registerCounter($namespace, 'ddos_fails_total', 'Total DDoS failures', ['user_id']);
}

verifyUpdate($update, $data);
ddosCheck($update, $data);

function verifyUpdate($update, $data) {
  global $metrics;
  if ($update['type'] == 'callback_query'
    && in_array($data['callbackAction'], ['overview_refresh', 'refresh_polls', 'getdb', 'update_bosses'])
    or ($data['callbackAction'] == 'post_raid' && $update['skip_ddos'] == true)
    or isset($update['cleanup']))
  {
    debug_log('Skipping DDOS check...','!');
    return;
  }
  $id_file = DDOS_PATH . '/update_id';

  // Update the update_id and reject old updates
  // Get update_ids from Telegram and locally stored in the file
  $update_id = isset($update['update_id']) ? $update['update_id'] : 0;
  $last_update_id = is_file($id_file) ? file_get_contents($id_file) : 0;
  if ($metrics){
    $GLOBALS['ddos_last_update']->set($last_update_id);
  }

  // End script if update_id is older than stored update_id
  if ($update_id < $last_update_id) {
    info_log("FATAL ERROR! Received old update_id: {$update_id} vs {$last_update_id}",'!');
    if ($metrics){
      $GLOBALS['ddos_old_updates_total']->incBy(1);
    }
    exit();
  }
  // Write update_id to file
  file_put_contents($id_file, $update_id);
}

function ddosCheck($update, $data) {
  global $botUser, $metrics, $config;
  // DDOS protection
  // Init DDOS count
  $ddos_count = 0;
  // Get callback query data
  if (!isset($update['callback_query']) or !$update['callback_query']['data']) return;
  // Split callback data and assign to data array.
  $splitAction = explode('_', $data['callbackAction']);
  // Check the action
  if ($splitAction[0] != 'vote') return;

  // Get the user_id and set the related ddos file
  $ddos_id = $update['callback_query']['from']['id'];
  $ddos_file = (DDOS_PATH . '/' . $ddos_id);
  // Check if ddos file exists and is not empty
  if (!file_exists($ddos_file) || filesize($ddos_file) == 0) {
    // Create file with initial DDOS count
    $ddos_count = 1;
    file_put_contents($ddos_file, $ddos_count);
    if ($metrics){
      $GLOBALS['ddos_state']->set($ddos_count, [$ddos_id]);
    }
    return;
  }
  // Get current time and last modification time of file
  $now = date("YmdHi");
  $lastchange = date("YmdHi", filemtime($ddos_file));
  // Get DDOS count or rest DDOS count if new minute
  if ($now == $lastchange) {
    // Get DDOS count from file
    $ddos_count = file_get_contents($ddos_file);
    $ddos_count = $ddos_count + 1;
    if ($metrics){
      $GLOBALS['ddos_state']->set($ddos_count, [$ddos_id]);
    }
  // Reset DDOS count to 1
  } else {
    $ddos_count = 1;
    if ($metrics){
      $GLOBALS['ddos_state']->set(1, [$ddos_id]);
    }
  }
  // Exit if DDOS of user_id count is exceeded.
  if ($ddos_count > $config->DDOS_MAXIMUM) {
    if ($metrics){
      $GLOBALS['ddos_fails_total']->inc([$ddos_id]);
    }
    exit();
  }
  file_put_contents($ddos_file, $ddos_count);
  $botUser->ddosCount = $ddos_count;
}
