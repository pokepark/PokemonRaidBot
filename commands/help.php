<?php
// Check access.
$access = bot_access_check($update, 'help', false, true);

// Display help for each permission
if($access && (is_file(ROOT_PATH . '/access/' . $access) || $access == 'BOT_ADMINS')) {
    // Get permissions from file.
    if($access == 'BOT_ADMINS') {
        $permissions = array();
        $permissions[] = 'access-bot';
        $permissions[] = 'create';
        $permissions[] = 'ex-raids';
        $permissions[] = 'raid-duration';
        $permissions[] = 'list';
        $permissions[] = 'overview';
        $permissions[] = 'delete-all';
        $permissions[] = 'pokemon-all';
        $permissions[] = 'gym-details';
        $permissions[] = 'gym-edit';
        $permissions[] = 'gym-name';
        $permissions[] = 'gym-address';
        $permissions[] = 'gym-note';
        $permissions[] = 'gym-add';
        $permissions[] = 'gym-delete';
        $permissions[] = 'config-get';
        $permissions[] = 'config-set';
        $permissions[] = 'pokedex';
        $permissions[] = 'help';
    } else {
        // Get permissions from file.
        $permissions = file(ROOT_PATH . '/access/' . $access);
    }

    // Write to log.
    // debug_log($permissions,'ACCESS: ');

    // Show help.
    debug_log('Showing help to user now');
    $msg = '<b>' . getTranslation('personal_help') . '</b>' . CR . CR;
    foreach($permissions as $id => $p) {
        if($p == 'access-bot' || strpos($p, 'share-') === 0) continue;
        $msg .= getTranslation('help_' . $p) . CR . CR;
    }
// No help for the user.
} else {
    $msg = getTranslation('bot_access_denied');
}

// Send message.
sendMessage($update['message']['from']['id'], $msg);

?>

