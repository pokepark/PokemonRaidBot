<?php
/**
 * Get formatted pokemon cp values.
 * @param array $row Result of get_pokemon_info
 * @param bool $override_language
 * @return string
 */
function get_formatted_pokemon_cp($row, $override_language = false)
{
    // Init cp text.
    $cp20 = '';
    $cp25 = '';

    // CP
    $cp20 .= ($row['min_cp'] > 0) ? $row['min_cp'] : '';
    $cp20 .= (!empty($cp20) && $cp20 > 0) ? ('/' . $row['max_cp']) : ($row['max_cp']);

    // Weather boosted CP
    $cp25 .= ($row['min_weather_cp'] > 0) ? $row['min_weather_cp'] : '';
    $cp25 .= (!empty($cp25) && $cp25 > 0) ? ('/' . $row['max_weather_cp']) : ($row['max_weather_cp']);

    // Combine CP and weather boosted CP
    $text = ($override_language == true) ? (getPublicTranslation('pokedex_cp')) : (getTranslation('pokedex_cp'));
    $cp = (!empty($cp20)) ? ($text . ' <b>' . $cp20 . '</b>') : '';
    $cp .= (!empty($cp25)) ? (' (' . $cp25 . ')') : '';

    return $cp;
}
