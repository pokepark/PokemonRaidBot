<?php
/**
 * Show trainer info.
 * @param $update
 * @param $show
 * @return string
 */
function show_trainerinfo($update, $show = false)
{
    // Instructions
    $msg = '<b>' . getPublicTranslation('trainerinfo') . ':</b>' . CR;
    $msg .= getPublicTranslation('trainer_set_your_info') . CR . CR;
    $msg .= getPublicTranslation('trainer_set_your_info_done') . CR . CR;

    // Show user info?
    if($show) {
        $msg .= '<b>' . getPublicTranslation('your_trainer_info') . '</b>' . CR;
        $msg .= get_user($update['callback_query']['from']['id']) . CR;
    }

    $msg .= '<i>' . getPublicTranslation('updated') . ': ' . dt2time('now', 'H:i:s') . '</i>';

    return $msg;
}

?>
