<?php
/**
 * Event keys.
 * @param $gym_id_plus_letter
 * @param $action
 * @return array
 */
function keys_event($gym_id_plus_letter, $action) {
    $q = my_query("
            SELECT      id,
                        name
            FROM        events
            WHERE       id != 999
            ");
    while($event = $q->fetch()) {
        if(!empty($event['name'])) {
            $keys[] = array(
                'text'          => $event['name'],
                'callback_data' => $gym_id_plus_letter . ':' . $action . ':' . $event['id']
            );
        }else {
            info_log('Invalid event name on event '. $event['id']);
        }
    }
    $keys[] = array(
        'text'          => getTranslation("Xstars"),
        'callback_data' => $gym_id_plus_letter . ':' . $action . ':X'
    );
    // Get the inline key array.
    $keys = inline_key_array($keys, 1);

    return $keys;
}
?>