<?php
/**
 * Send raid alerts to user.
 * @param $raid_id
 * @param $user_id
 * @param $action
 * @param $info
 */
function alarm($raid_id, $user_id, $action, $info = '')
{
    // Get config
    global $config;
    // Name of the user, which executes a status update
    $request = my_query("SELECT * FROM users WHERE user_id = {$user}");
    $answer_quests = $request->fetch();
    // Get Trainername
    $answer_quests = check_trainername($answer_quests);
    $username = '<a href="tg://user?id=' . $answer_quests['user_id'] . '">' . $answer_quests['name'] . '</a>';
    // Get Trainercode
    $trainercode = $answer_quests['trainercode'];
    // Gym name and raid times
    $raid = get_raid($raid_id);

    $gymname = $raid['gym_name'];
    $raidtimes = str_replace(CR, '', str_replace(' ', '', get_raid_times($answer, false, true)));

    // Get attend time.
    if(!in_array($action, ['new_att','change_time','group_code_private','group_code_public'])) {
        $r = my_query("SELECT DISTINCT attend_time FROM attendance WHERE raid_id = {$raid_id} and user_id = {$user_id}");
        $a = $r->fetch();
        if(isset($a['attend_time'])) {
            $attendtime = $a['attend_time'];
        }else {
            $attendtime = 0;
        }
    }

    if($action == 'group_code_public' or $action == 'group_code_private') {
        $request = my_query("   SELECT DISTINCT attendance.user_id, attendance.remote, users.lang
                                FROM attendance
                                WHERE raid_id = {$raid_id}
                                AND attend_time = (SELECT attend_time from attendance WHERE raid_id = {$raid_id} AND user_id = {$user_id})
                            ");
    }else {
        $request = my_query("   SELECT DISTINCT attendance.user_id, users.lang
                                FROM attendance LEFT JOIN users on users.id = attendance.user_id
                                WHERE raid_id = {$raid_id}
                                AND attendance.user_id != {$user_id}
                                AND cancel = 0
                                AND raid_done = 0
                                AND alarm = 1
                            ");
    }
    $tg_json = [];
    while($answer = $request->fetch())
    {
        if(!isset($answer['lang']) or empty($answer['lang'])) $recipient_language = $config->LANGUAGE_PUBLIC;
        else $recipient_language = $answer['lang'];
        // Adding a guest
        if($action == "extra") {
            debug_log('Alarm additional trainer: ' . $info);
            $color_old = array('mystic', 'valor', 'instinct');
            $color_new = array (TEAM_B, TEAM_R, TEAM_Y);
            $color = str_replace($color_old, $color_new, $info);

            // Sending message
            $msg_text = '<b>' . getTranslation('alert_add_trainer', true, $recipient_language) . '</b>' . CR;
            $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
            $msg_text .= EMOJI_SINGLE . SP . $username . SP . '+' . $color . CR;
            $msg_text .= EMOJI_CLOCK . SP . check_time($attendtime);
            $msg_text .= create_traincode_msg($trainercode);
            $tg_json[] = sendMessage($answer['user_id'], $msg_text, true);

        // Updating status - here or cancel
        } else if($action == "status") {
            // If trainer changes state (to late or cancelation)
            if($info == 'late') {
                debug_log('Alarm late: ' . $info);
                // Send message.
                $msg_text = '<b>' . getTranslation('alert_later', true, $recipient_language) . '</b>' . CR;
                $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
                $msg_text .= EMOJI_SINGLE . SP . $username . CR;
                $msg_text .= EMOJI_CLOCK . SP . check_time($attendtime);
                $msg_text .= create_traincode_msg($trainercode);
                $tg_json[] = sendMessage($answer['user_id'], $msg_text, true);
            } else if($info == 'cancel') {
                debug_log('Alarm cancel: ' . $info);
                $msg_text = '<b>' . getTranslation('alert_cancel', true, $recipient_language) . '</b>' . CR;
                $msg_text .= TEAM_CANCEL . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
                $msg_text .= EMOJI_SINGLE . SP . $username . CR;
                $msg_text .= EMOJI_CLOCK . SP . check_time($attendtime);
                $tg_json[] = sendMessage($answer['user_id'], $msg_text, true);
            }

        // Updating pokemon
        } else if($action == "pok_individual") {
            debug_log('Alarm Pokemon: ' . $info);

            // Only a specific pokemon
            if($info != '0') {
                $pokemon = explode("-",$info,2);
                $poke_name = get_local_pokemon_name($pokemon[0],$pokemon[1]);
                $msg_text = '<b>' . getTranslation('alert_individual_poke', true, $recipient_language) . SP . $poke_name . '</b>' . CR;
                $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
                $msg_text .= EMOJI_SINGLE . SP . $username . CR;
                $msg_text .= EMOJI_CLOCK . SP . check_time($attendtime);
                $msg_text .= create_traincode_msg($trainercode);
                $tg_json[] = sendMessage($answer['user_id'], $msg_text, true);
            // Any pokemon
            } else {
                $msg_text = '<b>' . getTranslation('alert_every_poke', true, $recipient_language) . '</b>' . CR;
                $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
                $msg_text .= EMOJI_SINGLE . SP . $username . CR;
                $msg_text .= EMOJI_CLOCK . SP . check_time($attendtime);
                $msg_text .= create_traincode_msg($trainercode);
                $tg_json[] = sendMessage($answer['user_id'], $msg_text, true);
            }

        // Cancel pokemon
        } else if($action == "pok_cancel_individual") {
            debug_log('Alarm Pokemon: ' . $info);
            $pokemon = explode("-",$info,2);
            $poke_name = get_local_pokemon_name($pokemon[0],$pokemon[1]);
            $msg_text = '<b>' . getTranslation('alert_cancel_individual_poke', true, $recipient_language) . SP . $poke_name . '</b>' . CR;
            $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
            $msg_text .= EMOJI_SINGLE . SP . $username . CR;
            $msg_text .= EMOJI_CLOCK . SP . check_time($attendtime);
            $msg_text .= create_traincode_msg($trainercode);
            $tg_json[] = sendMessage($answer['user_id'], $msg_text, true);

        } else if($action == "new_boss") {
            $msg_text = '<b>' . getTranslation('alert_raid_boss') . '</b>' . CR;
            $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
            $msg_text .= EMOJI_EGG . SP . '<b>' . get_local_pokemon_name($raid['pokemon'], $raid['pokemon_form']) . '</b>' . CR;
            $tg_json[] = sendMessage($answer['user_id'], $msg_text, true);

        // New attendance
        } else if($action == "new_att") {
            debug_log('Alarm new attendance: ' . $info);
            // Will Attend
            $msg_text = '<b>' . getTranslation('alert_new_att', true, $recipient_language) . '</b>' . CR;
            $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
            $msg_text .= EMOJI_SINGLE . SP . $username . CR;
            $msg_text .= EMOJI_CLOCK . SP . check_time($info);
            $msg_text .= create_traincode_msg($trainercode);
            $tg_json[] = sendMessage($answer['user_id'], $msg_text, true);

        // Attendance time change
        } else if($action == "change_time") {
            debug_log('Alarm changed attendance time: ' . $info);
            // Changes Time
            $msg_text = '<b>' . getTranslation('alert_change_time', true, $recipient_language) . '</b>' . CR;
            $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
            $msg_text .= EMOJI_SINGLE . SP . $username . CR;
            $msg_text .= EMOJI_CLOCK . SP . '<b>' . check_time($info) . '</b>';
            $msg_text .= create_traincode_msg($trainercode);
            $tg_json[] = sendMessage($answer['user_id'], $msg_text, true);

        // Attendance from remote
        } else if($action == "remote") {
            debug_log('Alarm remote attendance changed: ' . $info);
            // Changes Time
            $msg_text = '<b>' . getTranslation('alert_remote', true, $recipient_language) . '</b>' . CR;
            $msg_text .= EMOJI_REMOTE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
            $msg_text .= EMOJI_SINGLE . SP . $username . CR;
            $msg_text .= EMOJI_CLOCK . SP . '<b>' . check_time($attendtime) . '</b>';
            $msg_text .= create_traincode_msg($trainercode);
            $tg_json[] = sendMessage($answer['user_id'], $msg_text, true);

        // Attendance no longer from remote
        } else if($action == "no_remote") {
            debug_log('Alarm remote attendance changed: ' . $info);
            // Changes Time
            $msg_text = '<b>' . getTranslation('alert_no_remote', true, $recipient_language) . '</b>' . CR;
            $msg_text .= EMOJI_REMOTE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
            $msg_text .= EMOJI_SINGLE . SP . $username . CR;
            $msg_text .= EMOJI_CLOCK . SP . '<b>' . check_time($attendtime) . '</b>';
            $tg_json[] = sendMessage($answer['user_id'], $msg_text, true);

        // No additional trainer
        } else if($action == "extra_alone") {
            debug_log('Alarm no additional trainers: ' . $info);
            $msg_text = '<b>' . getTranslation('alert_extra_alone', true, $recipient_language) . '</b>' . CR;
            $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
            $msg_text .= EMOJI_SINGLE . SP . $username . CR;
            $msg_text .= EMOJI_CLOCK . SP . check_time($attendtime);
            $msg_text .= create_traincode_msg($trainercode);
            $tg_json[] = sendMessage($answer['user_id'], $msg_text, true);

        // Group code public
        } else if($action == "group_code_public") {
            debug_log('Alarm for group code: ' . $info);
            $msg_text = '<b>' . getTranslation('alert_raid_starts_now', true, $recipient_language) . CR . getTranslation('alert_raid_get_in', true, $recipient_language) . '</b>' . CR . CR;
            $msg_text .= '<b>' . getTranslation('alert_public_group', true, $recipient_language) . '</b>' . CR;
            $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
            $msg_text .= EMOJI_SINGLE . SP . $username . CR;
            $msg_text .= EMOJI_REMOTE . SP . $info;
            $tg_json[] = sendMessage($answer['user_id'], $msg_text, true);

        // Group code private
        } else if($action == "group_code_private") {
            debug_log('Alarm for group code: ' . $info);
            $msg_text = '<b>' . getTranslation('alert_raid_starts_now', true, $recipient_language) . CR . getTranslation('alert_raid_get_in', true, $recipient_language) . '</b>' . CR . CR;
            $msg_text .= '<b>' . getTranslation('alert_private_group', true, $recipient_language) . '</b>' . CR;
            $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
            $msg_text .= EMOJI_SINGLE . SP . $username . CR;

            // Send code to remote raiders
            $msg_text_remote = $msg_text;
            $msg_text_remote .= EMOJI_REMOTE . SP . '<b>' . $info . '</b>';
            if($answer['remote'] == 1) {
                $tg_json[] = sendMessage($answer['user_id'], $msg_text_remote, true);
            }

            // Send message to local raiders
            $msg_text_local = $msg_text;
            $msg_text_local .= EMOJI_REMOTE . SP . '<b>' . getTranslation('group_code_only_for_remote_raiders', true, $recipient_language) . '</b>';
            if($answer['remote'] == 0) {
                $tg_json[] = sendMessage($answer['user_id'], $msg_text_local, true);
            }
        // Attendance from remote
        } else if($action == "want_invite") {
            debug_log('Alarm invite begging changed: ' . $info);
            $msg_text = '<b>' . getTranslation('alert_want_invite', true, $recipient_language) . '</b>' . CR;
            $msg_text .= EMOJI_WANT_INVITE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
            $msg_text .= EMOJI_SINGLE . SP . $username . CR;
            $msg_text .= EMOJI_CLOCK . SP . '<b>' . check_time($attendtime) . '</b>';
            $msg_text .= create_traincode_msg($trainercode);
            $tg_json[] = sendMessage($answer['user_id'], $msg_text, true);

        // Attendance no longer from remote
        } else if($action == "no_want_invite") {
            debug_log('Alarm invite begging changed: ' . $info);
            $msg_text = '<b>' . getTranslation('alert_no_want_invite', true, $recipient_language) . '</b>' . CR;
            $msg_text .= EMOJI_WANT_INVITE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
            $msg_text .= EMOJI_SINGLE . SP . $username . CR;
            $msg_text .= EMOJI_CLOCK . SP . '<b>' . check_time($attendtime) . '</b>';
            $msg_text .= create_traincode_msg($trainercode);
            $tg_json[] = sendMessage($answer['user_id'], $msg_text, true);
        }
    }
    if(count($tg_json) > 0) curl_json_multi_request($tg_json);
}

/**
 * Set Trainercode in alert message
 * @param $attendtime
 * @param $trainercode
 * @return $message
 */
function create_traincode_msg($trainercode){
  // Get config
  global $config;
  $message = '';
  if($config->RAID_POLL_SHOW_TRAINERCODE == true && !is_null($trainercode)) {
      $message = CR . EMOJI_FRIEND . SP . '<code>' . $trainercode . '</code>';
  }
  return $message;
}

?>
