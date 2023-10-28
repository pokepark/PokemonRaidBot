<?php
require_once(LOGIC_PATH . '/get_raid_times.php');
require_once(LOGIC_PATH . '/createRaidBossList.php');
/**
 * Return the overview message for a specific chat.
 * @param array $active_raids - Custom array of gym and raid info
 * @param string $chat_title - String
 * @param string $chat_username - String
 * @return string
 */
function get_overview( $active_raids, $chat_title, $chat_username )
{
  global $config;

  $msg = '<b>' . getPublicTranslation('raid_overview_for_chat') . ' ' . $chat_title . ' ' . getPublicTranslation('from') . ' '. dt2time('now') . '</b>' .  CR . CR;

  if(count($active_raids) == 0) {
    $msg .= getPublicTranslation('no_active_raids') . CR . CR;
    if($config->RAID_BOSS_LIST) {
      $msg .=  createRaidBossList() . CR . CR;
    }
    //Add custom message from the config.
    if (!empty($config->RAID_PIN_MESSAGE)) {
      $msg .=  $config->RAID_PIN_MESSAGE;
    }
    return $msg;
  }
  $now = utcnow();
  foreach($active_raids as $row) {
    // Set variables for easier message building.
    $raid_id = $row['id'];
    $resolved_boss = resolve_raid_boss($row['pokemon'], $row['pokemon_form'], $row['spawn'], $row['level']);
    $row['pokemon'] = $resolved_boss['pokedex_id'];
    $row['pokemon_form'] = $resolved_boss['pokemon_form_id'];
    $pokemon = get_local_pokemon_name($row['pokemon'], $row['pokemon_form'], true);
    $gym = $row['gym_name'];
    $ex_raid_gym_marker = (strtolower($config->RAID_EX_GYM_MARKER) == 'icon') ? EMOJI_STAR : '<b>' . $config->RAID_EX_GYM_MARKER . '</b>';
    $start_time = $row['start_time'];
    $time_left = $row['t_left'];

    debug_log($pokemon . '@' . $gym . ' found for overview.');
    // Build message and add each gym in this format - link gym_name to raid poll chat_id + message_id if possible
    /* Example:
      * Raid Overview from 18:18h
      *
      * Train Station Gym
      * Raikou - still 0:24h
      *
      * Bus Station Gym
      * Level 5 Egg 18:41 to 19:26
    */
    // Gym name.
    $msg .= $row['ex_gym'] ? $ex_raid_gym_marker . SP : '';
    $msg .= !empty($chat_username) ? '<a href="https://t.me/' . $chat_username . '/' . $row['message_id'] . '">' . htmlspecialchars($gym) . '</a>' : $gym;
    $msg .= CR;

    if(isset($row['event_name']) && $row['event_name'] != '') {
      $msg .= '<b>' . $row['event_name'] . '</b>' . CR;
    }

    // Raid has not started yet - adjust time left message
    if ($now < $start_time) {
      $msg .= get_raid_times($row, true);
    // Raid has started already
    } else {
      // Add time left message.
      $msg .= $pokemon . ' — <b>' . getPublicTranslation('still') . SP . $time_left . 'h</b>' . CR;
    }
    $exclude_pokemon_sql = '';
    if(!in_array($row['pokemon'], EGGS)) {
      $exclude_pokemon_sql = 'AND (pokemon = \''.$row['pokemon'].'-'.$row['pokemon_form'].'\' or pokemon = \'0\')';
    }
    // Count attendances
    $rs_att = my_query('
      SELECT
        count(attend_time)                                          AS count,
        sum(want_invite = 0 && remote = 0 && can_invite = 0) + sum(case when want_invite = 0 && remote = 0 then attendance.extra_in_person else 0 end)  AS count_in_person,
        sum(want_invite = 0 && remote = 1 && can_invite = 0) + sum(case when want_invite = 0 && remote = 1 then attendance.extra_in_person else 0 end)  AS count_remote,
        sum(case when want_invite = 0 && can_invite = 0 then attendance.extra_alien else 0 end)                                                         AS extra_alien,
        sum(case when want_invite = 1 && can_invite = 0 then 1 + attendance.extra_in_person else 0 end)                                                 AS count_want_invite,
        sum(can_invite = 1)                                                                                                                             AS count_can_invite
      FROM (
        SELECT DISTINCT attend_time, user_id, extra_in_person, extra_alien, remote, want_invite, can_invite
        FROM attendance
        WHERE raid_id = ?
        AND attend_time IS NOT NULL
        AND ( attend_time > UTC_TIMESTAMP() or attend_time = \'' . ANYTIME . '\' )
        AND raid_done != 1
        AND cancel != 1
        ' .$exclude_pokemon_sql . '
      ) as attendance
      LEFT JOIN   users
      ON      attendance.user_id = users.user_id
      ', [$raid_id]
    );

    $att = $rs_att->fetch();

    if ($att['count'] == 0) {
      $msg .= CR;
      continue;
    }
    // Add to message.
    $msg .= EMOJI_GROUP . '<b> ' . ($att['count_in_person'] + $att['count_remote'] + $att['extra_alien'] + $att['count_want_invite']) . '</b> — ';
    $msg .= ((($att['count_can_invite']) > 0) ? EMOJI_CAN_INVITE . ($att['count_can_invite']) . '  ' : '');
    $msg .= ((($att['count_in_person']) > 0) ? EMOJI_IN_PERSON . ($att['count_in_person']) . '  ' : '');
    $msg .= ((($att['count_remote']) > 0) ? EMOJI_REMOTE . ($att['count_remote']) . '  ' : '');
    $msg .= ((($att['extra_alien']) > 0) ? EMOJI_ALIEN . ($att['extra_alien']) . '  ' : '');
    $msg .= (($att['count_want_invite'] > 0) ? EMOJI_WANT_INVITE . $att['count_want_invite'] : '');
    $msg .= CR . CR;
  }
  if($config->RAID_BOSS_LIST) {
    $msg .=  createRaidBossList() . CR . CR;
  }
  //Add custom message from the config.
  if (!empty($config->RAID_PIN_MESSAGE)) {
    $msg .=  $config->RAID_PIN_MESSAGE;
  }
  return $msg;
}
