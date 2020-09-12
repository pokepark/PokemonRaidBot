<?php
/**
 * Get overview data to Share or refresh.
 * @param $update
 * @param $chats_active
 * @param $raids_active
 * @param $action - refresh or share
 * @param $chat_id
 */
function get_overview($update, $chats_active, $raids_active, $action = 'refresh', $chat_id = 0)
{
    global $config;
    // Add pseudo array for last run to active chats array
    $last_run = [];
    $last_run['chat_id'] = 'LAST_RUN';
    $chats_active[] = $last_run;

    // Init previous chat_id and raid_id
    $previous = 'FIRST_RUN';
    $previous_raid = 'FIRST_RAID';

    // Current time.
    $now = utcnow();

    // Any active raids currently?
    if (empty($raids_active)) {
        // Init keys.
        $keys = [];

        // Refresh active overview messages with 'no_active_raids_currently' or send 'no_active_raids_found' message to user.
        $rs = my_query(
            "
            SELECT    *
            FROM      overview
            "
        );

        // Refresh active overview messages.
        while ($row_overview = $rs->fetch()) {
            $chat_id = $row_overview['chat_id'];
            $message_id = $row_overview['message_id'];

            $chat_title = get_chat_title($row_overview['chat_id']);

            // Set the message.
            $msg = '<b>' . getPublicTranslation('raid_overview_for_chat') . ' ' . $chat_title . ' '. getPublicTranslation('from') .' '. dt2time('now') . '</b>' .  CR . CR;
            $msg .= getPublicTranslation('no_active_raids');

            //Add custom message from the config.
            if (!empty($config->RAID_PIN_MESSAGE)) {
                $msg .= CR . CR .$config->RAID_PIN_MESSAGE . CR;
            }

            // Edit the message, but disable the web preview!
            debug_log('Updating overview:' . CR . 'Chat_ID: ' . $chat_id . CR . 'Message_ID: ' . $message_id);
            editMessageText($message_id, $msg, $keys, $chat_id, ['disable_web_page_preview' => 'true']);
        }

        // Triggered from user or cronjob?
        if (!empty($update['callback_query']['id'])) {
            // Send no active raids message to the user.
            $msg = getPublicTranslation('no_active_raids');

            // Telegram JSON array.
            $tg_json = array();

            // Answer the callback.
            $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $msg, true);

            // Edit the message, but disable the web preview!
            $tg_json[] = edit_message($update, $msg, $keys, ['disable_web_page_preview' => 'true'], true);

            // Telegram multicurl request.
            curl_json_multi_request($tg_json);
        }

        // Exit here.
        exit;
    }

    // Beyond here we do have specified raids_active
    // Share or refresh each chat.
    foreach ($chats_active as $row) {
        debug_log($row, 'Operating on chat:');
        $current = $row['chat_id'];

        $chat_title = $current;
        if($current != 'LAST_RUN'){
          $chat_title = get_chat_title($current);
        }

        // Telegram JSON array.
        $tg_json = array();

        // Are any raids shared?
        if ($previous == "FIRST_RUN" && $current == "LAST_RUN") {
            // Send no active raids message to the user.
            $msg = getPublicTranslation('no_active_raids_shared');

            // Answer the callback.
            $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $msg, true);

            // Edit the message, but disable the web preview!
            $tg_json[] = edit_message($update, $msg, $keys, ['disable_web_page_preview' => 'true'], true);

            // Telegram multicurl request.
            curl_json_multi_request($tg_json);
        }

        // Telegram JSON array.
        $tg_json = array();

        // Send message if not first run and previous not current
        if ($previous !== 'FIRST_RUN' && $previous !== $current) {
            // Add keys.
	    $keys = [];

            //Add custom message from the config.
            if (!empty($config->RAID_PIN_MESSAGE)) {
                $msg .= $config->RAID_PIN_MESSAGE . CR;
            }

            // Share or refresh?
            if ($action == 'share') {
                // no specific chat_id given?
                if ($chat_id == 0) {
                    // Make sure it's not already shared
                    $rs = my_query(
                        "
                        SELECT    COUNT(*) AS count
                        FROM      overview
                        WHERE      chat_id = '{$previous}'
                        "
                    );

                    $dup_row = $rs->fetch();

                    if (empty($dup_row['count'])) {
                        // Not shared yet - Share button
                        $keys[] = [
                            [
                                'text'          => getTranslation('share_with') . ' ' . $chat_title,
                                'callback_data' => '0:overview_share:' . $previous
                            ]
                        ];
                    } else {
                        // Already shared - refresh button
                        $keys[] = [
                            [
                                'text'          => EMOJI_REFRESH,
                                'callback_data' => '0:overview_refresh:' . $previous
                            ],
                            [
                                'text'          => getTranslation('done'),
                                'callback_data' => '0:exit:1'
                            ]
                        ];
                    }

                    // Send the message, but disable the web preview!
                    $tg_json[] = send_message($update['callback_query']['message']['chat']['id'], $msg, ['inline_keyboard' => $keys], ['disable_web_page_preview' => 'true'], true);

                    // Set the callback message and keys
                    $callback_keys = [];
                    $callback_msg = '<b>' . getTranslation('list_all_overviews') . ':</b>';

                    // Answer the callback.
                    $tg_json[] = answerCallbackQuery($update['callback_query']['id'], 'OK', true);

                    // Edit the message.
                    $tg_json[] = edit_message($update, $callback_msg, $callback_keys, true);

                } else {
                    // Shared overview
                    $keys = [];

                    // Set callback message string.
                    $msg_callback = getTranslation('successfully_shared');

                    // Answer the callback.
                    $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $msg_callback);

                    // Edit the message, but disable the web preview!
                    $tg_json[] = edit_message($update, $msg_callback, $keys, ['disable_web_page_preview' => 'true'], true);

                    // Send the message, but disable the web preview!
                    $tg_json[] = send_message($chat_id, $msg, ['inline_keyboard' => $keys], ['disable_web_page_preview' => 'true'], true);
                }

                // Telegram multicurl request.
                curl_json_multi_request($tg_json);

	    } else {
                // Refresh overview messages.
                $keys = [];

                // Get active overviews
                $rs = my_query(
                    "
                    SELECT    message_id
                    FROM      overview
                    WHERE      chat_id = '{$previous}'
                    "
                );

                // Edit text for all messages, but disable the web preview!
                while ($row_msg_id = $rs->fetch()) {
                    // Set message_id.
                    $message_id = $row_msg_id['message_id'];
                    debug_log('Updating overview:' . CR . 'Chat_ID: ' . $previous . CR . 'Message_ID: ' . $message_id);
                    editMessageText($message_id, $msg, $keys, $previous, ['disable_web_page_preview' => 'true']);
                }

                // Triggered from user or cronjob?
                if (!empty($update['callback_query']['id'])) {
                    // Answer the callback.
                    answerCallbackQuery($update['callback_query']['id'], 'OK');
                }
            }

        }

        // End if last run
        if ($current == 'LAST_RUN') {
            break;
        }

        // Continue with next if previous and current raid id are equal
        if ($previous_raid == $row['raid_id']) {
            continue;
        }

        // Create message for each raid_id
        if($previous !== $current) {
            // Get info about chat for username.
            debug_log('Getting chat object for chat_id: ' . $row['chat_id']);
            $chat_obj = get_chat($row['chat_id']);
            $chat_username = '';

            // Set username if available.
            if ($chat_obj['ok'] == 'true' && isset($chat_obj['result']['username'])) {
                $chat_username = $chat_obj['result']['username'];
                debug_log('Username of the chat: ' . $chat_obj['result']['username']);
            }
            $chat_title = get_chat_title($current);
            $msg = '<b>' . getPublicTranslation('raid_overview_for_chat') . ' ' . $chat_title . ' ' . getPublicTranslation('from') . ' '. dt2time('now') . '</b>' .  CR . CR;
        }

        // Set variables for easier message building.
        $raid_id = $row['raid_id'];
        $pokemon = get_local_pokemon_name($raids_active[$raid_id]['pokemon'], $raids_active[$raid_id]['pokemon_form'], true);
        $gym = $raids_active[$raid_id]['gym_name'];
        $ex_gym = $raids_active[$raid_id]['ex_gym'];
        $ex_raid_gym_marker = (strtolower($config->RAID_EX_GYM_MARKER) == 'icon') ? EMOJI_STAR : '<b>' . $config->RAID_EX_GYM_MARKER . '</b>';
        $start_time = $raids_active[$raid_id]['start_time'];
        $end_time = $raids_active[$raid_id]['end_time'];
        $time_left = $raids_active[$raid_id]['t_left'];

        debug_log($pokemon . '@' . $gym . ' found for overview.');
        // Build message and add each gym in this format - link gym_name to raid poll chat_id + message_id if possible
        /* Example:
         * Raid Overview from 18:18h
         *
         * Train Station Gym
         * Raikou - still 0:24h
         *
         * Bus Station Gym
         * Level 5 Egg 18:41 to 19:26
        */
        // Gym name.
        $msg .= $ex_gym ? $ex_raid_gym_marker . SP : '';
        $msg .= !empty($chat_username) ? '<a href="https://t.me/' . $chat_username . '/' . $row['message_id'] . '">' . htmlspecialchars($gym) . '</a>' : $gym;
        $msg .= CR;

        // Raid has not started yet - adjust time left message
        if ($now < $start_time) {
            $msg .= get_raid_times($raids_active[$raid_id], true);
        // Raid has started already
        } else {
            // Add time left message.
            $msg .= $pokemon . ' — <b>' . getPublicTranslation('still') . SP . $time_left . 'h</b>' . CR;
        }

        if ( $raid_id ) {

        // Count attendances
        $rs_att = my_query(
            "
            SELECT          count(attend_time)          AS count,
                            sum(team = 'mystic')        AS count_mystic,
                            sum(team = 'valor')         AS count_valor,
                            sum(team = 'instinct')      AS count_instinct,
                            sum(team IS NULL)           AS count_no_team,
                            sum(extra_mystic)           AS extra_mystic,
                            sum(extra_valor)            AS extra_valor,
                            sum(extra_instinct)         AS extra_instinct
            FROM            attendance
            LEFT JOIN       users
              ON            attendance.user_id = users.user_id
              WHERE         raid_id = {$raid_id}
                AND         attend_time IS NOT NULL
                AND         raid_done != 1
                AND         cancel != 1
            "
        );

        $att = $rs_att->fetch();

        // Add to message.
        if ($att['count'] > 0) {
            $msg .= EMOJI_GROUP . '<b> ' . ($att['count'] + $att['extra_mystic'] + $att['extra_valor'] + $att['extra_instinct']) . '</b> — ';
            $msg .= ((($att['count_mystic'] + $att['extra_mystic']) > 0) ? TEAM_B . ($att['count_mystic'] + $att['extra_mystic']) . '  ' : '');
            $msg .= ((($att['count_valor'] + $att['extra_valor']) > 0) ? TEAM_R . ($att['count_valor'] + $att['extra_valor']) . '  ' : '');
            $msg .= ((($att['count_instinct'] + $att['extra_instinct']) > 0) ? TEAM_Y . ($att['count_instinct'] + $att['extra_instinct']) . '  ' : '');
            $msg .= (($att['count_no_team'] > 0) ? TEAM_UNKNOWN . $att['count_no_team'] : '');
            $msg .= CR;
        }

        // Add CR to message now since we don't know if attendances got added or not
        $msg .= CR;
        }

        // Prepare next iteration
        $previous = $current;
        $previous_raid = $row['raid_id'];
    }
}

?>
