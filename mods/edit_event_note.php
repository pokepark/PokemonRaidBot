<?php
///////////
// TODO
// - Make the cancel button actually work

// Set the id.
$raid_id = $data['id'];

// Set the arg.
$arg = explode(",",$data['arg']);
$mode = ( isset($arg[1]) ? $arg[1] : "");

// Set the user id.
$userid = $update['callback_query']['from']['id'];

$msg = "";
$keys = [];

$callback_response = "OK";

// Get the raid
$raid = get_raid($raid_id);

$msg = '';
$msg .= getTranslation('raid_saved') . CR;
$msg .= show_raid_poll_small($raid, false) . CR2;

if($mode == "edit") {
    $msg.= getTranslation("event_note_edit") . ": ";
    
    // Create an entry to user_input table
    $modifiers = json_encode(array("id"=>$raid_id)); // Save the raid id
    $handler = "save_event_note";  // call for mods/save_event_note.php after user posts the answer
    
    my_query("INSERT INTO user_input SET user_id='{$userid}', modifiers='{$modifiers}', handler='{$handler}'");
}elseif($mode == "cancel") {
    $note = "";
    if($raid['event_note'] != "") {
        $note = $raid['event_note'];
    }
    my_query("UPDATE raids SET event_note='{$note}' WHERE id='{$raid_id}'");
    $data['arg'] = $arg[0];
    require_once("edit_save.php");
}else {
    $q = my_query("SELECT * FROM events WHERE id='{$raid['event']}'");
    $res = $q->fetch();

    $msg.= getTranslation("event") . ": <b>".$res['name']."</b>".CR;
    $msg.= getTranslation("event_add_note_description");
    
    // Create an entry to user_input table
    $modifiers = json_encode(array("id"=>$raid_id)); // Save the raid id
    $handler = "save_event_note";  // call for mods/save_event_note.php after user posts the answer
    
    my_query("INSERT INTO user_input SET user_id='{$userid}', modifiers='{$modifiers}', handler='{$handler}'");
}
$keys[] = [
            [
                'text'          => getTranslation('cancel'),
                'callback_data' => $raid_id . ':edit_event_note:'. $arg[0] . ',cancel'
            ]
        ];

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

// Edit message.
edit_message($update, $msg, $keys, false);

exit();