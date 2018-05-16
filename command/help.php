<?php

$msg = '
<b>EN Guide on how to create a raid poll raid bot</b>
1) make sure the raid hasn\'t been posted yet in the chat
2) check how much time is left for the raid
3) open new PM with @RaidPokemonBot
4) send your location to the bot (make sure you send the location of where the gym is located)
5) choose the type of raid boss and the time left
6) to ensure an easier way to locate the gym in game/chat, it\'s recommended to use the bot function /gym <code>(name of the gym and/or description of it)</code>
7) press share and choose yourRaid channel
8) wait until the option with the boss name appears and select it
';
$msg = 'This is a private bot.'; // temp
sendMessage($update['message']['from']['id'], $msg);
