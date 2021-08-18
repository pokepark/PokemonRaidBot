<?php
/**
 * Post a raid poll to all relevant chats
 * @param int $raid_id ID of the raid
 * @param array|false $raid Array received from get_raid() (optional).
 * @param int|array $chats chat ID or array of IDs
 * @param array|false $tg_json multicurl array
 * @return array multicurl array
 */

function send_raid_poll($raid_id, $raid = false, $chats, $tg_json = false) {
    global $config;

    // Get raid data.
    if($raid == false) $raid = get_raid($raid_id);

    // Get text and keys.
    $text = show_raid_poll($raid);
    $keys = keys_vote($raid);

    // Telegram JSON array.
    if($tg_json == false) $tg_json = [];

    // Raid picture
    if($config->RAID_PICTURE) {
        require_once(LOGIC_PATH . '/raid_picture.php');
        $picture_url = raid_picture_url($raid, $config->RAID_PICTURE_AUTOEXTEND);
    }

    // Send the message.
    $raid_picture_hide_level = explode(",",$config->RAID_PICTURE_HIDE_LEVEL);
    $raid_picture_hide_pokemon = explode(",",$config->RAID_PICTURE_HIDE_POKEMON);

    $raid_pokemon_id = $raid['pokemon'];
    $raid_level = $raid['level'];
    $raid_pokemon_form_name = get_pokemon_form_name($raid_pokemon_id,$raid['pokemon_form']);
    $raid_pokemon = $raid_pokemon_id . "-" . $raid_pokemon_form_name;

    if(!is_array($chats)) $chats = [$chats];
    foreach($chats as $chat_id) {
        // Send location.
        if ($config->RAID_LOCATION) {
            // Send location.
            $msg_text = !empty($raid['address']) ? $raid['address'] : $raid['pokemon'];
            // Sending venue together with raid poll can't be multicurled since they would appear to the chat in random order
            send_venue($chat_id, $raid['lat'], $raid['lon'], '', $msg_text, false, false, $raid_id);
            send_message($chat_id, $text['full'], $keys, ['disable_web_page_preview' => 'true'], false, $raid_id);
        }else {
            if($config->RAID_PICTURE && $raid['event_hide_raid_picture'] == 0 && !in_array($raid_level, $raid_picture_hide_level) && !in_array($raid_pokemon, $raid_picture_hide_pokemon) && !in_array($raid_pokemon_id, $raid_picture_hide_pokemon)) {
                if($config->RAID_PICTURE_AUTOEXTEND) {
                    send_photo($chat_id, $picture_url, '', [], [], false, $raid_id);
                    send_message($chat_id, $text['short'], $keys, ['disable_web_page_preview' => 'true'], false, $raid_id);
                } else {
                    $tg_json[] = send_photo($chat_id, $picture_url, $text['short'], $keys, ['disable_web_page_preview' => 'true'], true, $raid_id);
                }
            } else {
                $tg_json[] = send_message($chat_id, $text['full'], $keys, ['disable_web_page_preview' => 'true'], true, $raid_id);
            }
        }
    }
    return $tg_json;
}
?>