<?php
/**
 * Pokedex edit pokemon keys.
 * @param $limit
 * @param $action
 * @return array
 */
function edit_pokedex_keys($limit, $action)
{
    // Number of entries to display at once.
    $entries = 10;

    // Number of entries to skip with skip-back and skip-next buttons
    $skip = 50;

    // Module for back and next keys
    $module = "pokedex";

    // Init empty keys array.
    $keys = [];

    // Get all pokemon from database
    $rs = my_query(
        "
        SELECT    pokedex_id, pokemon_form_id
        FROM      pokemon
        ORDER BY  pokedex_id, pokemon_form_name != 'normal', pokemon_form_name
        LIMIT     $limit, $entries
        "
    );

    // Number of entries
    $cnt = my_query(
        "
        SELECT    COUNT(*) AS count
        FROM      pokemon
        "
    );

    // Number of database entries found.
    $sum = $cnt->fetch();
    $count = $sum['count'];

    // List users / moderators
    while ($mon = $rs->fetch()) {
        $pokemon_name = get_local_pokemon_name($mon['pokedex_id'], $mon['pokemon_form_id']);
        $keys[] = array(
            'text'          => $mon['pokedex_id'] . SP . $pokemon_name,
            'callback_data' => $mon['pokedex_id'] . '-' . $mon['pokemon_form_id'] . ':pokedex_edit_pokemon:0'
        );
    }

    // Empty backs and next keys
    $keys_back = [];
    $keys_next = [];

    // Add back key.
    if ($limit > 0) {
        $new_limit = $limit - $entries;
        $empty_back_key = [];
        $back = universal_key($empty_back_key, $new_limit, $module, $action, getTranslation('back') . " (-" . $entries . ")");
        $keys_back[] = $back[0][0];
    }

    // Add skip back key.
    if ($limit - $skip > 0) {
        $new_limit = $limit - $skip - $entries;
        $empty_back_key = [];
        $back = universal_key($empty_back_key, $new_limit, $module, $action, getTranslation('back') . " (-" . $skip . ")");
        $keys_back[] = $back[0][0];
    }

    // Add next key.
    if (($limit + $entries) < $count) {
        $new_limit = $limit + $entries;
        $empty_next_key = [];
        $next = universal_key($empty_next_key, $new_limit, $module, $action, getTranslation('next') . " (+" . $entries . ")");
        $keys_next[] = $next[0][0];
    }

    // Add skip next key.
    if (($limit + $skip + $entries) < $count) {
        $new_limit = $limit + $skip + $entries;
        $empty_next_key = [];
        $next = universal_key($empty_next_key, $new_limit, $module, $action, getTranslation('next') . " (+" . $skip . ")");
        $keys_next[] = $next[0][0];
    }

    // Exit key
    $empty_exit_key = [];
    $key_exit = universal_key($empty_exit_key, "0", "exit", "0", getTranslation('abort'));

    // Get the inline key array.
    $keys = inline_key_array($keys, 1);
    $keys_back = inline_key_array($keys_back, 2);
    $keys_next = inline_key_array($keys_next, 2);
    $keys = array_merge($keys_back, $keys);
    $keys = array_merge($keys, $keys_next);
    $keys = array_merge($keys, $key_exit);

    return $keys;
}

?>
