<?php
require_once(LOGIC_PATH . '/get_raid_times.php');

/**
 * Sends notification to user if Alarm is on or off
 * @param int $raid_id
 * @param int $user_id
 * @param boolean $alarm
 * @param array $raid Raid array from get_raid()
 */
function sendAlertOnOffNotice($raid_id, $user_id, $alarm = null, $raid = null){
  if(empty($raid)){
    // Request limited raid info
    $request = my_query('
      SELECT    g.gym_name, r.start_time, r.end_time
      FROM      raids as r
      LEFT JOIN gyms as g
      ON        r.gym_id = g.id
      WHERE     r.id = ?
    ', [$raid_id]);
    $raid = $request->fetch();
  }
  $gymname = '<b>' . $raid['gym_name'] . '</b>';
  // parse raidtimes
  $raidtimes = str_replace(CR, '', str_replace(' ', '', get_raid_times($raid, false, true)));

  if(empty($alarm)){
    // Get the new value
    $rs = my_query('
      SELECT  alarm
      FROM    attendance
      WHERE   raid_id = ?
      AND   user_id = ?
      ',[$raid_id, $user_id]
    );
    $answer = $rs->fetch();
    $alarm = $answer['alarm'];
  }

  if($alarm) {// Enable alerts message.
    $msg_text = EMOJI_ALARM . SP . '<b>' . getTranslation('alert_updates_on') . '</b>' . CR;
  } else {// Disable alerts message.
    $msg_text = EMOJI_NO_ALARM . SP . '<b>' . getTranslation('alert_no_updates') . '</b>' . CR;
	}
  $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')';
  send_message($user_id, $msg_text);
}
