<?php
/**
 * Event keys.
 * @param $gym_id_plus_letter
 * @param $action
 * @return array
 */
function event_keys($gym_id_plus_letter, $action) {
    $q = my_query("
            SELECT      id,
                        name
            FROM
                        events
            ");
    while($event = $q->fetch()) {
        $keys[] = array(
            'text'          => $event['name'],
            'callback_data' => $gym_id_plus_letter . ':' . $action . ':' . $event['id']
        );
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