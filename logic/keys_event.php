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
      $keys[] = array(
        'text'          => $event['name'],
        'callback_data' => formatCallbackData($callbackData)
      );
    }
  }
  if($admin_access[0] === true) {
    $callbackData['e'] = 'X';
    $keys[] = array(
      'text'          => getTranslation("Xstars"),
      'callback_data' => formatCallbackData($callbackData)
    );
  }
  // Get the inline key array.
  $keys = inline_key_array($keys, 1);

  return $keys;
}
