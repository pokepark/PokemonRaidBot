<?php
// Write to log
debug_log('DDOS Check');
if ($metrics){
    $ddos_old_updates_total = $metrics->registerCounter($namespace, 'ddos_old_updates_total', 'Total old updates received');
    $ddos_last_update = $metrics->registerGauge($namespace, 'ddos_last_update', 'Last known update_id');
    $ddos_state = $metrics->registerGauge($namespace, 'ddos_state', 'current DDoS values', ['user_id']);
    $ddos_fails_total = $metrics->registerCounter($namespace, 'ddos_fails_total', 'Total DDoS failures', ['user_id']);
}

// Update_ID file.
$id_file = DDOS_PATH . '/update_id';

// Skip DDOS check for specific stuff, e.g. cleanup and overview refresh.
$skip_ddos_check = 0;

// Update the update_id and reject old updates
if (file_exists($id_file) && filesize($id_file) > 0) {
    // Get update_ids from Telegram and locally stored in the file
    $update_id = isset($update['update_id']) ? $update['update_id'] : 0;
    $last_update_id = is_file($id_file) ? file_get_contents($id_file) : 0;
    if ($metrics){
        $ddos_last_update->set($last_update_id);
    }
    if (isset($update['callback_query'])) {
        $action = $data['action'];
        if ($action == 'overview_refresh') {
            $skip_ddos_check = 1;
            debug_log('Skipping DDOS check for overview refresh...','!');
        }else if ($action == 'refresh_polls') {
            $skip_ddos_check = 1;
            debug_log('Skipping DDOS check for poll refresh...','!');
        }else if ($action == 'post_raid' && $update['skip_ddos'] == true) {
            $skip_ddos_check = 1;
            debug_log('Skipping DDOS check for posting raid directly...','!');
        }else if ($action == 'getdb') {
            $skip_ddos_check = 1;
            debug_log('Skipping DDOS check for database update...','!');
        }else if ($action == 'update_bosses') {
            $skip_ddos_check = 1;
            debug_log('Skipping DDOS check for boss data update...','!');
        }
    } else if(isset($update['cleanup'])) {
            $skip_ddos_check = 1;
            debug_log('Skipping DDOS check for cleanup...','!');
    }

    // End script if update_id is older than stored update_id
    if ($update_id < $last_update_id && $skip_ddos_check == 0) {
        info_log("FATAL ERROR! Received old update_id: {$update_id} vs {$last_update_id}",'!');
        if ($metrics){
            $ddos_old_updates_total->incBy(1);
        }
        exit();
    }
} else {
    // Create file with initial update_id
    $update_id = 1;
}

// Write update_id to file
if($skip_ddos_check == 0) {
    file_put_contents($id_file, $update_id);
}

// Init DDOS count
$ddos_count = 0;

// DDOS protection
if (isset($update['callback_query'])) {
    // Get callback query data
    if ($update['callback_query']['data']) {
        // Split callback data and assign to data array.
        $splitAction = explode('_', $data['action']);
        // Check the action
        if ($splitAction[0] == 'vote') {
            // Get the user_id and set the related ddos file
            $ddos_id = $update['callback_query']['from']['id'];
            $ddos_file = (DDOS_PATH . '/' . $ddos_id);
            // Check if ddos file exists and is not empty
            if (file_exists($ddos_file) && filesize($ddos_file) > 0) {
                // Get current time and last modification time of file
                $now = date("YmdHi");
                $lastchange = date("YmdHi", filemtime($ddos_file));
                // Get DDOS count or rest DDOS count if new minute
                if ($now == $lastchange) {
                    // Get DDOS count from file
                    $ddos_count = file_get_contents($ddos_file);
                    $ddos_count = $ddos_count + 1;
                  if ($metrics){
                      $ddos_state->set($ddos_count, [$ddos_id]);
                  }
                // Reset DDOS count to 1
                } else {
                    $ddos_count = 1;
                    if ($metrics){
                        $ddos_state->set(1, [$ddos_id]);
                    }
                }
                // Exit if DDOS of user_id count is exceeded.
                if ($ddos_count > $config->DDOS_MAXIMUM) {
                    if ($metrics){
                        $ddos_fails_total->inc([$ddos_id]);
                    }
                    exit();
                // Update DDOS count in file
                } else {
                    file_put_contents($ddos_file, $ddos_count);
                }
            // Create file with initial DDOS count
            } else {
                $ddos_count = 1;
                file_put_contents($ddos_file, $ddos_count);
                if ($metrics){
                    $ddos_state->set($ddos_count, [$ddos_id]);
                }
            }
        }
    }
}
