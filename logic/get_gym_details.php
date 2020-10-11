<?php
/**
 * Get gym details.
 * @param $gym
 * @param $extended
 * @return string
 */
function get_gym_details($gym, $extended = false)
{
    global $config;
    // Add gym name to message.
    $msg = '<b>' . getTranslation('gym_details') . ':</b>' . CR . CR;
    $msg .= '<b>ID = ' . $gym['id'] . '</b>' . CR;
    $msg .= getTranslation('gym') . ':' . SP;
    $ex_raid_gym_marker = (strtolower($config->RAID_EX_GYM_MARKER) == 'icon') ? EMOJI_STAR : '<b>' . $config->RAID_EX_GYM_MARKER . '</b>';
    $msg .= ($gym['ex_gym'] ? $ex_raid_gym_marker . SP : '') . '<b>' . $gym['gym_name'] . '</b>';
    $msg .= CR;
    // Add maps link to message.
    if (!empty($gym['address'])) {
        $msg .= mapslink($gym) . CR;
    } else {
        // Get the address.
        $addr = get_address($gym['lat'], $gym['lon']);
        $address = format_address($addr);

        //Only store address if not empty
        if(!empty($address)) {
            //Use new address
            $msg .= mapslink($gym,$address) . CR;
        } else {
            //If no address is found show maps link
            $msg .= mapslink($gym,'1') . CR;
        }
    }

    // Add or hide gym note.
    if(!empty($gym['gym_note'])) {
        $msg .= EMOJI_INFO . SP . $gym['gym_note'];
    }

    // Get extended gym details?
    if($extended == true) {
        $msg .= CR . '<b>' . getTranslation('extended_gym_details') . '</b>';
        // Normal gym?
        if($gym['ex_gym'] == 1) {
            $msg .= CR . '-' . SP . getTranslation('ex_gym');
        }

        // Hidden gym?
        if($gym['show_gym'] == 1 && $gym['ex_gym'] == 0) {
            $msg .= CR . '-' . SP . getTranslation('normal_gym');
        } else if($gym['show_gym'] == 0) {
            $msg .= CR . '-' . SP . getTranslation('hidden_gym');
        }
    }

    return $msg;
}

?>
