<?php
require_once(LOGIC_PATH . '/keys_vote.php');
require_once(LOGIC_PATH . '/show_raid_poll.php');
/**
 * Raid list.
 * @param $update
 */
function raid_list($update)
{
  // Init raid id.
  $iqq = 0;

  // Botname:raid_id received?
  if (substr_count($update['inline_query']['query'], ':') == 1) {
    // Botname: received, is there a raid_id after : or not?
    if(strlen(explode(':', $update['inline_query']['query'])[1]) != 0) {
      // Raid ID.
      $iqq = intval(explode(':', $update['inline_query']['query'])[1]);
    }
  }

  // Inline list polls.
  $ids = [['id' => $iqq]];
  if ($iqq == 0) {
    // If no id was given, search for two raids saved by the user
    $request = my_query('
      SELECT  id
      FROM    raids
      WHERE   user_id = ?
      AND     end_time>UTC_TIMESTAMP()
      ORDER BY  id DESC LIMIT 2
      ', [$update['inline_query']['from']['id']]
    );
    $ids = $request->fetchAll();
  }

  $contents = [];
  $i = 0;
  foreach ($ids as $raid) {
    $row = get_raid($raid['id']);
    // Get raid poll.
    $contents[$i]['text'] = show_raid_poll($row, true)['full'];

    // Set the title.
    $contents[$i]['title'] = get_local_pokemon_name($row['pokemon'],$row['pokemon_form'], true) . ' ' . getPublicTranslation('from') . ' ' . dt2time($row['start_time'])  . ' ' . getPublicTranslation('to') . ' ' . dt2time($row['end_time']);

    // Get inline keyboard.
    $contents[$i]['keyboard'] = keys_vote($row);

    // Set the description.
    $contents[$i]['desc'] = strval($row['gym_name']);
    $i++;
  }

  debug_log($contents);
  answerInlineQuery($update['inline_query']['id'], $contents);
}
