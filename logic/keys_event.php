<?php
/**
 * Event keys.
 * @param $gym_id_plus_letter
 * @param $action
 * @param $admin_access array of access rights [ex-raids, event-raids]
 * @return array
 */
function keys_event($gym_id_plus_letter, $action, $admin_access = [false,false]) {
  $keys = [];
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
      $keys[] = array(
        'text'          => $event['name'],
        'callback_data' => $gym_id_plus_letter . ':' . $action . ':' . $event['id']
      );
    }
  }
  if($admin_access[0] === true) {
    $keys[] = array(
      'text'          => getTranslation("Xstars"),
      'callback_data' => $gym_id_plus_letter . ':' . $action . ':X'
    );
  }
  // Get the inline key array.
  $keys = inline_key_array($keys, 1);

  return $keys;
}
