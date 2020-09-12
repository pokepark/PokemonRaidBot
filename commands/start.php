<?php
// Write to log.
debug_log('START()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get gym by name.
// Trim away everything before "/start "
$searchterm = $update['message']['text'];
$searchterm = substr($searchterm, 7);
debug_log($searchterm, 'SEARCHTERM');

// Start raid message.
if(strpos($searchterm , 'c0de-') === 0) {
    $code_raid_id = explode("-", $searchterm, 2)[1];
    require_once(ROOT_PATH . '/mods/code_start.php');
    exit();
} 

// Check access, don't die if no access.
$access = bot_access_check($update, 'create', true);

if(!$access && bot_access_check($update, 'list', true)){
  debug_log('No access to create, will do a list instead');
  require('list.php');
  exit;
} else {
  $access = bot_access_check($update, 'create', false, true);
}
// Raid event?
if($config->RAID_POKEMON_DURATION_EVENT != $config->RAID_POKEMON_DURATION_SHORT) {
    // Always allow for Admins.
    if($access && $access == 'BOT_ADMINS') {
        debug_log('Bot Admin detected. Allowing further raid creation during the raid hour');
    } else {
        // Get number of raids for the current user.
        $rs = my_query(
            "
            SELECT     count(id) AS created_raids_count
            FROM       raids
            WHERE      end_time>UTC_TIMESTAMP()
            AND        user_id = {$update['message']['chat']['id']}
            "
        );

        $info = $rs->fetch();
        $creation_limit = $config->RAID_EVENT_CREATION_LIMIT - 1;

        // Check raid count
        if($info['created_raids_count'] > $creation_limit) {
            // Set message and keys.
            if($config->RAID_EVENT_CREATION_LIMIT == 1) {
                $msg = '<b>' . getTranslation('raid_event_creation_limit_one') . '</b>';
            } else {
                $msg = '<b>' . str_replace('RAID_EVENT_CREATION_LIMIT', $config->RAID_EVENT_CREATION_LIMIT, getPublicTranslation('raid_event_creation_limit')) . '</b>';
            }
            $keys = [];

            // Send message.
            send_message($update['message']['chat']['id'], $msg, ['inline_keyboard' => $keys, 'selective' => true, 'one_time_keyboard' => true]);

            // Exit.
            exit();
        }
    }
}

// Get gym by name.
// Trim away everything before "/start "
$searchterm = $update['message']['text'];
$searchterm = substr($searchterm, 7);

// Get the keys by gym name search.
$keys = '';
if(!empty($searchterm)) {
    $keys = raid_get_gyms_list_keys($searchterm);
} 

// Get the keys if nothing was returned. 
if(!$keys) {
    $keys = raid_edit_gyms_first_letter_keys();
}

// No keys found.
if (!$keys) {
    // Create the keys.
    $keys = [
        [
            [
                'text'          => getTranslation('not_supported'),
                'callback_data' => '0:exit:0'
            ]
        ]
    ];
}

// Set message.
$msg = '<b>' . getTranslation('select_gym_first_letter') . '</b>' . ($config->RAID_VIA_LOCATION ? (CR2 . CR .  getTranslation('send_location')) : '');

// Send message.
send_message($update['message']['chat']['id'], $msg, ['inline_keyboard' => $keys, 'selective' => true, 'one_time_keyboard' => true]);

?>
