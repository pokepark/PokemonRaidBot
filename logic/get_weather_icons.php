<?php
/**
 * Get weather icons.
 * @param $weather_value
 * @return string
 */
function get_weather_icons($weather_value)
{
    if($weather_value > 0) {
        // Get length of arg and split arg
        $weather_value_length = strlen((string)$weather_value);
        $weather_value_string = str_split((string)$weather_value);

        // Init weather icons string.
        $weather_icons = '';

        // Add icons to string.
        for ($i = 0; $i < $weather_value_length; $i = $i + 1) {
            // Get weather icon from constants
            $weather_icons .= $GLOBALS['weather'][$weather_value_string[$i]];
            $weather_icons .= ' ';
        }

        // Trim space after last icon
        $weather_icons = rtrim($weather_icons);
    } else {
        $weather_icons = '';
    }

    return $weather_icons;
}

?>
