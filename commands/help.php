<?php
// Check access.
bot_access_check($update, 'help');

$msg = 'See https://github.com/florianbecker/PokemonRaidBot for details and how to install your own Raidbot.';
sendMessage($update['message']['from']['id'], $msg);
?>
