<?php
/**
 * Get raid data with pokemon.
 * @param $raid_id
 * @return array
 */
function get_raid_with_pokemon($raid_id)
{
    // Remove all non-numeric characters
    $raidid = preg_replace( '/[^0-9]/', '', $raid_id );

    // Get the raid data by id.
    $rs = my_query(
        "
        SELECT     raids.*,
                   gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym, gyms.gym_note, gyms.gym_id, gyms.img_url,
                   pokemon.pokedex_id, pokemon.pokemon_name, pokemon.pokemon_form_name, pokemon.raid_level, pokemon.min_cp, pokemon.max_cp, pokemon.min_weather_cp, pokemon.max_weather_cp, pokemon.weather, pokemon.shiny, pokemon.pokemon_form_id, pokemon.asset_suffix,
                   users.name,
                   TIME_FORMAT(TIMEDIFF(end_time, UTC_TIMESTAMP()) + INTERVAL 1 MINUTE, '%k:%i') AS t_left
        FROM       raids
        LEFT JOIN  gyms
        ON         raids.gym_id = gyms.id
        LEFT JOIN  pokemon
        ON         pokemon.pokedex_id = raids.pokemon
        AND        if(raids.pokemon_form = 0, 1, pokemon.pokemon_form_id=raids.pokemon_form)
        LEFT JOIN  users
        ON         raids.user_id = users.user_id
        WHERE      raids.id = {$raidid}
        "
    );

    // Get the row.
    $raid = $rs->fetch();

    debug_log($raid);

    return $raid;
}

?>
