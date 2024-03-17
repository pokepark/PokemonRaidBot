<?php
/**
 * Get raid data.
 * @param $raid_id
 * @return array
 */
require_once(LOGIC_PATH . '/get_user.php');
require_once(LOGIC_PATH . '/resolve_raid_boss.php');
function get_raid($raid_id)
{
  // Remove all non-numeric characters
  $raidid = preg_replace( '/[^0-9]/', '', $raid_id );

  // Get the raid data by id.
  $rs = my_query('
    SELECT    raids.pokemon, raids.pokemon_form, raids.id, raids.user_id, raids.spawn, raids.start_time, raids.end_time, raids.gym_team, raids.gym_id, raids.level, raids.move1, raids.move2, raids.gender, raids.event, raids.costume, raids.event_note,
              gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym, gyms.gym_note,
              users.name, users.trainername, users.nick, users.display_name,
              events.name as event_name, events.description as event_description, events.vote_key_mode as event_vote_key_mode, events.time_slots as event_time_slots, events.raid_duration as event_raid_duration, events.hide_raid_picture as event_hide_raid_picture, events.pokemon_title as event_pokemon_title, events.poll_template as event_poll_template,
              TIME_FORMAT(TIMEDIFF(end_time, UTC_TIMESTAMP()) + INTERVAL 1 MINUTE, \'%k:%i\') AS t_left,
              IF(UTC_TIMESTAMP() > end_time, 1, 0) as raid_ended
    FROM      raids
    LEFT JOIN gyms
    ON        raids.gym_id = gyms.id
    LEFT JOIN users
    ON        raids.user_id = users.user_id
    LEFT JOIN events
    ON        events.id = raids.event
    WHERE     raids.id = ?
    LIMIT 1
    ', [$raidid]
  );
  // Get the row.
  $raid = $rs->fetch();

  if ($rs->rowCount() == 0) {
    // Fail gracefully when a raid id is unknown
    edit_message($GLOBALS['update'], getTranslation('internal_error'), []);
    exit;
  }
  // Resolve the boss id
  $resolved_boss = resolve_raid_boss($raid['pokemon'], $raid['pokemon_form'], $raid['spawn'], $raid['level']);
  $raid['pokemon'] = $resolved_boss['pokedex_id'];
  $raid['pokemon_form'] = $resolved_boss['pokemon_form_id'];

  $raid['shadow'] = in_array($raid['level'], RAID_LEVEL_SHADOW) ? 1 : 0;

  if (!$raid){
    $rs = my_query('SELECT * FROM raids WHERE raids.id = ?', [$raid_id]);
    $row = json_encode($rs->fetch());
    throw new Exception("Failed to fetch raid id {$raid_id}, data we do have on it: {$row}");
  }

  // Check trainername
  $raid = check_trainername($raid);

  debug_log($raid);

  return $raid;
}
