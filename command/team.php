<?php
// Write to log.
debug_log('TEAM()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get the team.
$gym_team = trim(strtolower(substr($update['message']['text'], 5)));

// Match team names.
$teams = array(
    'mystic'    => 'mystic',
    'instinct'  => 'instinct',
    'valor'     => 'valor',
    getTranslation('red')       => 'valor',
    getTranslation('blue')      => 'mystic',
    getTranslation('yellow')      => 'instinct',
    'r'         => 'valor',
    'b'         => 'mystic',
    'y'         => 'instinct',
    'g'         => 'instinct'
);

// Valid team name.
if ($teams[$gym_team]) {
    // Update team in raids table.
    my_query(
        "
        UPDATE    raids
        SET       gym_team = '{$teams[$gym_team]}'
          WHERE   user_id = {$update['message']['from']['id']}
        ORDER BY  id DESC LIMIT 1
        "
    );

    // Send the message.
    sendMessage($update['message']['chat']['id'], getTranslation('gym_team_set_to') . ' ' . ucfirst($teams[$gym_team]));

// Invalid team name.
} else {
    // Send the message.
    sendMessage($update['message']['chat']['id'], getTranslation('invalid_team'));
}
