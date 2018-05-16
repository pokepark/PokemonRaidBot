<?php
// Write to log.
debug_log('quest_save()');

// For debug.
//debug_log($update);
//debug_log($data);

// Set the user id.
$userid = $update['callback_query']['from']['id'];

// Pokestop and quest id.
$stop_quest = explode(",", $data['id']);
$pokestop_id = $stop_quest[0];
$quest_id = $stop_quest[1];

// Reward id.
$reward_id = $data['arg'];

// Check if quest already exists for this pokestop.
$quest_in_db = quest_duplication_check($pokestop_id);

// Quest already in database or new
if (!$quest_in_db) {
    debug_log('Saving quest to database.');

    // Insert quest.
    my_query(
        "
        INSERT INTO   quests
        SET           user_id = {$update['callback_query']['from']['id']},
                      quest_date = CURDATE(),
                      pokestop_id = {$pokestop_id},
                      quest_id = {$quest_id},
                      reward_id = {$reward_id}
        "
    );
    // Get last insert id from db.
    $id = my_insert_id();

    // Write to log.
    debug_log('Saved Quest ID: ' . $id);

    // Set message.
    $msg = '<b>' . getTranslation('quest_saved') . '</b>' . CR . CR;
    $quest = get_quest($id);
    $msg .= get_formatted_quest($quest);

    // Init keys.
    $keys = array();
    $keys_share = array();
    $keys_delete = array();

    // Add keys to delete and share.
    $keys_delete = universal_key($keys, $id, 'quest_delete', '0', getTranslation('delete'));
    $keys_share = share_quest_keys($id, $userid);
    $keys = array_merge($keys_delete, $keys_share);
} else {
    // Quest already in the database for this pokestop.
    $msg = EMOJI_WARN . '<b> ' . getTranslation('quest_already_submitted') . ' </b>' . EMOJI_WARN . CR . CR;
    $quest = get_quest($answer['id']);
    $msg .= get_formatted_quest($quest);

    // Empty keys.
    $keys = [];
}

// Edit message.
edit_message($update, $msg, $keys, ['disable_web_page_preview' => 'true']);

// Build callback message string.
$callback_response = 'OK';

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

exit();
