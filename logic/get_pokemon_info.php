<?php
/**
 * Get pokemon info as formatted string.
 * @param $pokemon_id_form
 * @return array
 */
function get_pokemon_info($pokemon_id_form)
{
    // Split pokedex_id and form
    $dex_id_form = explode('-',$pokemon_id_form);
    $pokedex_id = $dex_id_form[0];
    $pokemon_form = $dex_id_form[1];

    /** Example:
     * Raid boss: Mewtwo (#ID)
     * Weather: Icons
     * CP: CP values (Boosted CP values)
    */
    $info = '';
    $info .= getTranslation('raid_boss') . ': <b>' . get_local_pokemon_name($pokemon_id_form) . ' (#' . $pokedex_id . ')</b>' . CR . CR;
    $poke_raid_level = get_raid_level($pokemon_id_form);
    $poke_cp = get_formatted_pokemon_cp($pokemon_id_form);
    $poke_weather = get_pokemon_weather($pokemon_id_form);
    $info .= getTranslation('pokedex_raid_level') . ': ' . getTranslation($poke_raid_level . 'stars') . CR;
    $info .= (empty($poke_cp)) ? (getTranslation('pokedex_cp') . CR) : $poke_cp . CR;
    $info .= getTranslation('pokedex_weather') . ': ' . get_weather_icons($poke_weather) . CR . CR;

    return $info;
}

?>
