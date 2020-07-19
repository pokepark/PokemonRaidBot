<?php
/**
 * Get formatted pokemon cp values.
 * @param $pokemon_id_form
 * @param $override_language
 * @return string
 */
function get_formatted_pokemon_cp($pokemon_id_form, $override_language = false)
{
    // Split pokedex_id and form
    $dex_id_form = explode('-',$pokemon_id_form);
    $pokedex_id = $dex_id_form[0];
    $pokemon_form = $dex_id_form[1];

    // Init cp text.
    $cp20 = '';
    $cp25 = '';

    // Valid pokedex id?
    if($pokedex_id !== "NULL" && $pokedex_id != 0) {
        // Get gyms from database
        $rs = my_query(
                "
                SELECT    min_cp, max_cp, min_weather_cp, max_weather_cp
                FROM      pokemon
                WHERE     pokedex_id = {$pokedex_id}
                AND       pokemon_form = '{$pokemon_form}'
                "
            );

        while($row = $rs->fetch_assoc()) {
            // CP
            $cp20 .= ($row['min_cp'] > 0) ? $row['min_cp'] : '';
            $cp20 .= (!empty($cp20) && $cp20 > 0) ? ('/' . $row['max_cp']) : ($row['max_cp']);

            // Weather boosted CP
            $cp25 .= ($row['min_weather_cp'] > 0) ? $row['min_weather_cp'] : '';
            $cp25 .= (!empty($cp25) && $cp25 > 0) ? ('/' . $row['max_weather_cp']) : ($row['max_weather_cp']);
        }
    }

    // Combine CP and weather boosted CP
    $text = ($override_language == true) ? (getPublicTranslation('pokedex_cp')) : (getTranslation('pokedex_cp'));
    $cp = (!empty($cp20)) ? ($text . ' <b>' . $cp20 . '</b>') : '';
    $cp .= (!empty($cp25)) ? (' (' . $cp25 . ')') : '';

    return $cp;
}

?>
