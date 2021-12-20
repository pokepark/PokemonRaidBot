<?php
// Write to log.
debug_log('START()');

// For debug.
//debug_log($update);
//debug_log($data);

$new_user = new_user($update['message']['from']['id']);
$access = bot_access_check($update, 'create', true, true, $new_user);
if(!$access && bot_access_check($update, 'list', true) && !$new_user){
    debug_log('No access to create, will do a list instead');
    require('list.php');
    exit;
}
if($config->TUTORIAL_MODE && $new_user && (!$access or $access == 'BOT_ADMINS')) {
    // Tutorial
    if(is_file(ROOT_PATH . '/config/tutorial.php')) {
        require_once(ROOT_PATH . '/config/tutorial.php');
    }
	$msg = $tutorial[0]['msg_new'];
	$keys = [
	[
		[
			'text'          => getTranslation("next"),
			'callback_data' => '0:tutorial:1'
		]
	]
	];
    $photo = $tutorial[0]['photo'];
    send_photo($update['message']['from']['id'], $photo, $msg, $keys, ['disable_web_page_preview' => 'true'],false);
}else {
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

    // Get the keys by gym name search.
    $keys = '';
    if(!empty($searchterm)) {
        $keys = raid_get_gyms_list_keys($searchterm);
    } 

    // Get the keys if nothing was returned. 
    if(!$keys) {
        $keys_and_gymarea = raid_edit_gyms_first_letter_keys('raid_by_gym', false, false, 'raid_by_gym_letter');
        $keys = $keys_and_gymarea['keys'];
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
    }else {
        $keys[] = [
                [
                    'text'          => getTranslation('abort'),
                    'callback_data' => '0:exit:0'
                ]
            ];
    }

    // Set message.
    if($config->ENABLE_GYM_AREAS) {
        if($keys_and_gymarea['gymarea_name'] == '') {
            $msg .= '<b>' . getTranslation('select_gym_area') . '</b>' . CR;
        }elseif(($config->DEFAULT_GYM_AREA == false && $data['id'] == 0) or $config->DEFAULT_GYM_AREA != false) {
            if($keys_and_gymarea['letters']) {
                $msg .= '<b>' . getTranslation('select_gym_first_letter_or_gym_area') . '</b>' . CR;
            }else {
                $msg .= '<b>' . getTranslation('select_gym_name_or_gym_area') . '</b>' . CR;
            }
        }elseif($keys_and_gymarea['letters']) {
            $msg .= '<b>' . getTranslation('select_gym_first_letter') . '</b>' . CR;
        }else {
            $msg .= '<b>' . getTranslation('select_gym_name') . '</b>' . CR;
        }
    }else {
        $msg .= '<b>' . getTranslation('select_gym_first_letter') . '</b>' . CR;
    }
    $msg.= (($keys_and_gymarea['gymarea_name'] != '') ? CR . CR . getTranslation('current_gymarea') . ': ' . $keys_and_gymarea['gymarea_name'] : '');
    $msg.= ($config->RAID_VIA_LOCATION ? (CR . CR .  getTranslation('send_location')) : '');

    // Send message.
    send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);
}
?>
