<?php
require_once(LOGIC_PATH . '/get_raid_times.php');
require_once(LOGIC_PATH . '/get_user.php');
/**
 * Send raid alerts to user.
 * @param array $raid_id_array raid id or the result of get_raid()
 * @param int $user_id ID of the user that executed the call
 * @param string $action which alarm action to perform
 * @param string $info additional info for the action if required
 * @param array $tg_json multicurl array
 * @return array multicurl array
 */
function alarm($raid_id_array, $user_id, $action, $info = '', $tg_json = [])
{
  // Get config
  global $config;

  // Get user info if it's needed for the alarm
  if(!empty($user_id)) {
    // Name of the user, which executes a status update
    $requestUserinfo = my_query('SELECT * FROM users WHERE user_id = ? LIMIT 1', [$user_id]);
    $answer_quests = $requestUserinfo->fetch();
    // Get Trainername
    $answer_quests = check_trainername($answer_quests);
    $username = '<a href="tg://user?id=' . $answer_quests['user_id'] . '">' . $answer_quests['name'] . '</a>';
    // Get Trainercode
    $trainercode = $answer_quests['trainercode'];
  }else {
    // Set this to 0 so we get every attendee from database
    $user_id = 0;
  }

  // Gym name and raid times
  $raid = (is_array($raid_id_array) ? $raid_id_array : get_raid($raid_id_array));

  $raid_id = $raid['id'];

  $gymname = $raid['gym_name'];
  $raidtimes = str_replace(CR, '', str_replace(' ', '', get_raid_times($raid, false, true)));

  // Get attend time.
  if(!in_array($action, ['new_att','new_boss','change_time','group_code_private','group_code_public'])) {
    $r = my_query('SELECT DISTINCT attend_time FROM attendance WHERE raid_id = ? and user_id = ? LIMIT 1', [$raid_id, $user_id]);
    $a = $r->fetch();

    $attendtime = isset($a['attend_time']) ? $a['attend_time'] : 0;
  }

  if($action == 'group_code_public' or $action == 'group_code_private') {
    $request = my_query('
      SELECT DISTINCT attendance.user_id, attendance.remote, users.lang
      FROM attendance
      LEFT JOIN users
      ON users.user_id = attendance.user_id
      WHERE raid_id = :raidId
      AND attend_time = (SELECT attend_time from attendance WHERE raid_id = :raidId AND user_id = :userId)
      ', ['raidId' => $raid_id, 'userId' => $user_id]);
  }else {
    $request = my_query('
      SELECT DISTINCT attendance.user_id, users.lang
      FROM attendance
      LEFT JOIN users
      ON users.user_id = attendance.user_id
      WHERE raid_id = :raidId
      AND attendance.user_id != :userId
      AND cancel = 0
      AND raid_done = 0
      AND alarm = 1
  ', ['raidId' => $raid_id, 'userId' => $user_id]);
  }

  while($answer = $request->fetch())
  {
    if(!isset($answer['lang']) or empty($answer['lang'])) $recipient_language = $config->LANGUAGE_PUBLIC;
    else $recipient_language = $GLOBALS['languages'][$answer['lang']];
    // Adding a guest
    if($action == 'extra') {
      debug_log('Alarm additional trainer: ' . $info);
      $icons = ['alien' => EMOJI_ALIEN, 'in_person' => EMOJI_IN_PERSON];

      // Sending message
      if($info == 'alien') {
        $msg_text = '<b>' . getTranslation('alert_add_alien_trainer', $recipient_language) . '</b>' . CR;
      }else {
        $msg_text = '<b>' . getTranslation('alert_add_trainer', $recipient_language) . '</b>' . CR;
      }
      $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
      $msg_text .= EMOJI_SINGLE . SP . $username . SP . '+' . $icons[$info] . CR;
      $msg_text .= EMOJI_CLOCK . SP . check_time($attendtime, $recipient_language);
      $msg_text .= create_traincode_msg($trainercode);

    // Updating status - here or cancel
    } else if($action == 'status') {
      // If trainer changes state (to late or cancelation)
      if($info == 'late') {
        debug_log('Alarm late: ' . $info);
        // Send message.
        $msg_text = '<b>' . getTranslation('alert_later', $recipient_language) . '</b>' . CR;
        $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
        $msg_text .= EMOJI_SINGLE . SP . $username . CR;
        $msg_text .= EMOJI_CLOCK . SP . check_time($attendtime, $recipient_language);
        $msg_text .= create_traincode_msg($trainercode);
      } else if($info == 'cancel') {
        debug_log('Alarm cancel: ' . $info);
        $msg_text = '<b>' . getTranslation('alert_cancel', $recipient_language) . '</b>' . CR;
        $msg_text .= TEAM_CANCEL . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
        $msg_text .= EMOJI_SINGLE . SP . $username . CR;
        $msg_text .= EMOJI_CLOCK . SP . check_time($attendtime, $recipient_language);
      }

    // Updating pokemon
    } else if($action == 'pok_individual') {
      debug_log('Alarm Pokemon: ' . $info);

      if($info != '0') {
        // Only a specific pokemon
        $pokemon = explode("-",$info,2);
        $poke_name = get_local_pokemon_name($pokemon[0],$pokemon[1]);
        $msg_text = '<b>' . getTranslation('alert_individual_poke', $recipient_language) . SP . $poke_name . '</b>' . CR;
      } else {
        // Any pokemon
        $msg_text = '<b>' . getTranslation('alert_every_poke', $recipient_language) . '</b>' . CR;
      }
      $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
      $msg_text .= EMOJI_SINGLE . SP . $username . CR;
      $msg_text .= EMOJI_CLOCK . SP . check_time($attendtime, $recipient_language);
      $msg_text .= create_traincode_msg($trainercode);

    // Cancel pokemon
    } else if($action == 'pok_cancel_individual') {
      debug_log('Alarm Pokemon: ' . $info);
      $pokemon = explode("-",$info,2);
      $poke_name = get_local_pokemon_name($pokemon[0],$pokemon[1]);
      $msg_text = '<b>' . getTranslation('alert_cancel_individual_poke', $recipient_language) . SP . $poke_name . '</b>' . CR;
      $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
      $msg_text .= EMOJI_SINGLE . SP . $username . CR;
      $msg_text .= EMOJI_CLOCK . SP . check_time($attendtime, $recipient_language);
      $msg_text .= create_traincode_msg($trainercode);

    } else if($action == 'new_boss') {
      $msg_text = '<b>' . getTranslation('alert_raid_boss', $recipient_language) . '</b>' . CR;
      $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
      $msg_text .= EMOJI_EGG . SP . '<b>' . get_local_pokemon_name($raid['pokemon'], $raid['pokemon_form']) . '</b>' . CR;

    // New attendance
    } else if($action == 'new_att') {
      debug_log('Alarm new attendance: ' . $info);
      // Will Attend
      $msg_text = '<b>' . getTranslation('alert_new_att', $recipient_language) . '</b>' . CR;
      $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
      $msg_text .= EMOJI_SINGLE . SP . $username . CR;
      $msg_text .= EMOJI_CLOCK . SP . check_time($info, $recipient_language);
      $msg_text .= create_traincode_msg($trainercode);

    // Attendance time change
    } else if($action == 'change_time') {
      debug_log('Alarm changed attendance time: ' . $info);
      // Changes Time
      $msg_text = '<b>' . getTranslation('alert_change_time', $recipient_language) . '</b>' . CR;
      $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
      $msg_text .= EMOJI_SINGLE . SP . $username . CR;
      $msg_text .= EMOJI_CLOCK . SP . '<b>' . check_time($info, $recipient_language) . '</b>';
      $msg_text .= create_traincode_msg($trainercode);

    // Attendance from remote
    } else if($action == 'remote') {
      debug_log('Alarm remote attendance changed: ' . $info);
      // Changes Time
      $msg_text = '<b>' . getTranslation('alert_remote', $recipient_language) . '</b>' . CR;
      $msg_text .= EMOJI_REMOTE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
      $msg_text .= EMOJI_SINGLE . SP . $username . CR;
      $msg_text .= EMOJI_CLOCK . SP . '<b>' . check_time($attendtime, $recipient_language) . '</b>';
      $msg_text .= create_traincode_msg($trainercode);

    // Attendance no longer from remote
    } else if($action == 'no_remote') {
      debug_log('Alarm remote attendance changed: ' . $info);
      // Changes Time
      $msg_text = '<b>' . getTranslation('alert_no_remote', $recipient_language) . '</b>' . CR;
      $msg_text .= EMOJI_REMOTE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
      $msg_text .= EMOJI_SINGLE . SP . $username . CR;
      $msg_text .= EMOJI_CLOCK . SP . '<b>' . check_time($attendtime, $recipient_language) . '</b>';

    // No additional trainer
    } else if($action == 'extra_alone') {
      debug_log('Alarm no additional trainers: ' . $info);
      $msg_text = '<b>' . getTranslation('alert_extra_alone', $recipient_language) . '</b>' . CR;
      $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
      $msg_text .= EMOJI_SINGLE . SP . $username . CR;
      $msg_text .= EMOJI_CLOCK . SP . check_time($attendtime, $recipient_language);
      $msg_text .= create_traincode_msg($trainercode);

    // Group code public
    } else if($action == 'group_code_public') {
      debug_log('Alarm for group code: ' . $info);
      $msg_text = '<b>' . getTranslation('alert_raid_starts_now', $recipient_language) . CR . getTranslation('alert_raid_get_in', $recipient_language) . '</b>' . CR . CR;
      $msg_text .= '<b>' . getTranslation('alert_public_group', $recipient_language) . '</b>' . CR;
      $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
      $msg_text .= EMOJI_SINGLE . SP . $username . CR;
      $msg_text .= EMOJI_REMOTE . SP . $info;

    // Group code private
    } else if($action == 'group_code_private') {
      debug_log('Alarm for group code: ' . $info);
      $msg_text = '<b>' . getTranslation('alert_raid_starts_now', $recipient_language) . CR . getTranslation('alert_raid_get_in', $recipient_language) . '</b>' . CR . CR;
      $msg_text .= '<b>' . getTranslation('alert_private_group', $recipient_language) . '</b>' . CR;
      $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
      $msg_text .= EMOJI_SINGLE . SP . $username . CR;

      // Send code to remote raiders
      if($answer['remote'] == 1) {
        $msg_text .= EMOJI_REMOTE . SP . '<b>' . $info . '</b>';
      }

      // Send message to local raiders
      if($answer['remote'] == 0) {
        $msg_text .= EMOJI_REMOTE . SP . '<b>' . getTranslation('group_code_only_for_remote_raiders', $recipient_language) . '</b>';
      }
    // Attendance from remote
    } else if($action == 'want_invite') {
      debug_log('Alarm invite begging changed: ' . $info);
      $msg_text = '<b>' . getTranslation('alert_want_invite', $recipient_language) . '</b>' . CR;
      $msg_text .= EMOJI_WANT_INVITE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
      $msg_text .= EMOJI_SINGLE . SP . $username . CR;
      $msg_text .= EMOJI_CLOCK . SP . '<b>' . check_time($attendtime, $recipient_language) . '</b>';
      $msg_text .= create_traincode_msg($trainercode);

    // Attendance no longer from remote
    } else if($action == 'no_want_invite') {
      debug_log('Alarm invite begging changed: ' . $info);
      $msg_text = '<b>' . getTranslation('alert_no_want_invite', $recipient_language) . '</b>' . CR;
      $msg_text .= EMOJI_WANT_INVITE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
      $msg_text .= EMOJI_SINGLE . SP . $username . CR;
      $msg_text .= EMOJI_CLOCK . SP . '<b>' . check_time($attendtime, $recipient_language) . '</b>';
      $msg_text .= create_traincode_msg($trainercode);

    // Let others know you are not playing, but can invite others
    } else if($action == 'can_invite') {
      debug_log('Alarm: ' . $action);
      $msg_text = '<b>' . getTranslation('alert_can_invite', $recipient_language) . '</b>' . CR;
      $msg_text .= EMOJI_CAN_INVITE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
      $msg_text .= EMOJI_SINGLE . SP . $username . CR;
      $msg_text .= EMOJI_CLOCK . SP . '<b>' . check_time($attendtime, $recipient_language) . '</b>';
      $msg_text .= create_traincode_msg($trainercode);

    // Let others know you are not longer able to invite them
    } else if($action == 'no_can_invite') {
      debug_log('Alarm: ' . $action);
      $msg_text = '<b>' . getTranslation('alert_no_can_invite', $recipient_language) . '</b>' . CR;
      $msg_text .= EMOJI_CAN_INVITE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
      $msg_text .= EMOJI_SINGLE . SP . $username . CR;
      $msg_text .= EMOJI_CLOCK . SP . '<b>' . check_time($attendtime, $recipient_language) . '</b>';
      $msg_text .= create_traincode_msg($trainercode);
    }
    $tg_json[] = send_message($answer['user_id'], $msg_text, false, false, true);
  }
  return $tg_json;
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

/**
 * Check attendance time against anytime.
 * @param $time
 * @param $recipientLanguage
 */
function check_time($time, $recipientLanguage)
{
  // Raid anytime?
  if(strcmp($time, ANYTIME)===0){
    return getTranslation('anytime', $recipientLanguage);
  }
  return dt2time($time);
}
