<?php
/**
 * Send response vote.
 * @param $update
 * @param $show
 */
function send_trainerinfo($update, $show = false)
{
    // Get text and keys.
    $msg = show_trainerinfo($update, $show);
    $keys = keys_trainerinfo($show);

    // Write to log.
    // debug_log($keys);

    // Change message string.
    $callback_msg = getPublicTranslation('updated');

    // Telegram JSON array.
    $tg_json = array();

    // Answer the callback.
    $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_msg, true, true);

    // Edit the message.
    $tg_json[] = edit_message($update, $msg, $keys, ['disable_web_page_preview' => 'true'], true);

    // Telegram multicurl request.
    curl_json_multi_request($tg_json);

    // Exit.
    exit();
}

?>
