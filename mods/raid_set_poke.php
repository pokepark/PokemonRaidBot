<?php
// Write to log.
debug_log('raid_set_poke()');

// For debug.
//debug_log($update);
//debug_log($data);

// Access check.
raid_access_check($update, $data, 'pokemon');

// Set the id.
$id = $data['id'];
$pokemon_id_form = get_pokemon_by_table_id($data['arg']);

// Update pokemon in the raid table.
my_query(
    "
    UPDATE    raids
    SET       pokemon = '{$pokemon_id_form['pokedex_id']}',
              pokemon_form = '{$pokemon_id_form['pokemon_form_id']}'
      WHERE   id = {$id}
    "
);

// Get raid times.
$raid = get_raid($data['id']);

// Create the keys.
$keys = [];

// Build message string.
$msg = '';
$msg .= getTranslation('raid_saved') . CR;
$msg .= show_raid_poll_small($raid);

// Build callback message string.
$callback_response = getTranslation('raid_boss_saved');

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Get raid poll messages to be updated from cleanup.
$rs = my_query(
    "
    SELECT    *
    FROM      cleanup
      WHERE   raid_id = {$id}
    "
);

// Update the shared raid polls.
require_once(LOGIC_PATH .'/update_raid_poll.php');
$tg_json = update_raid_poll($id, $raid, false, $tg_json, false);

// Alert users.
$tg_json = alarm($raid, $update['callback_query']['from']['id'], 'new_boss', '', $tg_json);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();
