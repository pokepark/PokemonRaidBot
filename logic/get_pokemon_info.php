<?php
/**
 * Get pokemon info as formatted string.
 * @param $pokemon_id
 * @param $pokemon_form_id
 * @return array
 */
function get_pokemon_info($pokemon_id, $pokemon_form_id)
{
    /** Example:
     * Raid boss: Mewtwo (#ID)
     * Weather: Icons
     * CP: CP values (Boosted CP values)
    */
    $info = '';
    $info .= getTranslation('raid_boss') . ': <b>' . get_local_pokemon_name($pokemon_id, $pokemon_form_id) . ' (#' . $pokemon_id . ')</b>' . CR . CR;
    $poke_raid_level = get_raid_level($pokemon_id, $pokemon_form_id);
    $poke_cp = get_formatted_pokemon_cp($pokemon_id, $pokemon_form_id);
    $poke_weather = get_pokemon_weather($pokemon_id, $pokemon_form_id);
    $info .= getTranslation('pokedex_raid_level') . ': ' . getTranslation($poke_raid_level . 'stars') . CR;
    $info .= (empty($poke_cp)) ? (getTranslation('pokedex_cp') . CR) : $poke_cp . CR;
    $info .= getTranslation('pokedex_weather') . ': ' . get_weather_icons($poke_weather) . CR . CR;

    return $info;
}

?>
