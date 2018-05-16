<?php
// Write to log.
debug_log('WILLOW()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access - user must be admin!
bot_access_check($update, BOT_ADMINS);

// Get all available quests from database.
$rs = my_query(
        "
        SELECT     *
        FROM       questlist
        "
    );

// Init empty questlist message.
$msg_questlist = '';

// Add key for quest
while ($questlist = $rs->fetch_assoc()) {
    // Quest action: Singular or plural?
    $quest_action = explode(":", getTranslation('quest_action_' . $questlist['quest_action']));
    $quest_action_singular = $quest_action[0];
    $quest_action_plural = $quest_action[1];
    $qty_action = $questlist['quest_quantity'] . SP . (($questlist['quest_quantity'] > 1) ? ($quest_action_plural) : ($quest_action_singular));

    // Build questlist message.
    $msg_questlist .= '<b>ID: ' . $questlist['id'] . '</b> — '; 
    $msg_questlist .= getTranslation('quest_type_'. $questlist['quest_type']) . SP . $qty_action . CR . CR;
}

// Set keys.
$keys = [];

if(empty($msg_questlist)) {
    // Set the message.
    $msg = '<b>' . getTranslation('no_quests_today') . '</b>' . CR;
} else {
    // Add header.
    $msg = '<b>' . getTranslation('quests_today') . '</b>' . CR;
    $msg .= $msg_questlist;
}

// Send the message.
send_message($update['message']['chat']['id'], $msg, $keys, ['disable_web_page_preview' => 'true']);

exit();
