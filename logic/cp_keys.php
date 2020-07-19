<?php
/**
 * CP keys.
 * @param $pokedex_id
 * @param $action
 * @param $arg
 * @return array
 */
function cp_keys($pokedex_id, $action, $arg)
{
    // Get the type, level and cp
    $data = explode("-", $arg);
    $cp_type_level = $data[0] . '-' . $data[1];
    $cp_add = $data[0] . '-' . $data[1] . '-' . $data[2] . '-';
    $old_cp = $data[3];

    // Save and reset values
    $save_arg = $cp_type_level . '-save-' . $old_cp;
    $reset_arg = $cp_add . '0';

    // Init empty keys array.
    $keys = [];

    // Max CP is 9999 and no the value 999 is not a typo!
    // Keys will be shown up to 999 and when user is adding one more number we exceed 999, so we remove the keys then
    // This means we do not exceed a Max CP of 9999 :)
    if($old_cp <= 999) {

        // Add keys 0 to 9
        /**
         * 7 8 9
         * 4 5 6
         * 1 2 3
         * 0
        */

        // 7 8 9
        for ($i = 7; $i <= 9; $i = $i + 1) {
            // Set new cp
            $new_cp = $cp_add . ($old_cp == 0 ? '' : $old_cp) . $i;

            // Set keys.
            $keys[] = array(
                'text'          => $i,
                'callback_data' => $pokedex_id . ':' . $action . ':' . $new_cp
            );
        }

        // 4 5 6
        for ($i = 4; $i <= 6; $i = $i + 1) {
            // Set new cp
            $new_cp = $cp_add . ($old_cp == 0 ? '' : $old_cp) . $i;

            // Set keys.
            $keys[] = array(
                'text'          => $i,
                'callback_data' => $pokedex_id . ':' . $action . ':' . $new_cp
            );
        }

        // 1 2 3
        for ($i = 1; $i <= 3; $i = $i + 1) {
            // Set new cp
            $new_cp = $cp_add . ($old_cp == 0 ? '' : $old_cp) . $i;

            // Set keys.
            $keys[] = array(
                'text'          => $i,
                'callback_data' => $pokedex_id . ':' . $action . ':' . $new_cp
            );
        }

        // 0
        if($old_cp != 0) {
            // Set new cp
            $new_cp = $cp_add . $old_cp . '0';
        } else {
            $new_cp = $reset_arg;
        }

        // Set keys.
        $keys[] = array(
            'text'          => '0',
            'callback_data' => $pokedex_id . ':' . $action . ':' . $new_cp
        );
    }

    // Save
    $keys[] = array(
        'text'          => EMOJI_DISK,
        'callback_data' => $pokedex_id . ':' . $action . ':' . $save_arg
    );

    // Reset
    $keys[] = array(
        'text'          => getTranslation('reset'),
        'callback_data' => $pokedex_id . ':' . $action . ':' . $reset_arg
    );

    // Get the inline key array.
    $keys = inline_key_array($keys, 3);

    return $keys;
}

?>
