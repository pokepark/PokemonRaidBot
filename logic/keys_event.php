<?php
/**
 * Event keys.
 * @param $callbackData
 * @param $action
 * @param $admin_access array of access rights [ex-raids, event-raids]
 * @return array
 */
function keys_event($callbackData, $action, $admin_access = [false,false]) {
  $keys = [];
  $callbackData[0] = $action;
  if($admin_access[1] === true) {
    $q = my_query('
      SELECT  id,
              name
      FROM    events
      WHERE   id != 999
    ');
    while($event = $q->fetch()) {
      if(empty($event['name'])) {
        info_log('Invalid event name on event '. $event['id']);
        continue;
      }
      $callbackData['e'] = $event['id'];
      $keys[] = button($event['name'], $callbackData);
    }
  }
  if($admin_access[0] === true) {
    $callbackData['e'] = EVENT_ID_EX;
    $keys[] = button(getTranslation(RAID_ID_EX . 'stars'), $callbackData);
  }
  // Get the inline key array.
  $keys = inline_key_array($keys, 1);

  return $keys;
}
