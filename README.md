# About

Telegram bot for organizing raids in Pokemon Go. Developers are welcome to join https://t.me/PokemonBotSupport

# Screenshots

#### Example raid poll with the ex-raid notice:
![Example raid poll](/screens/raid-poll-example-with-ex-raid-message.png?raw=true "Example raid poll")

#### Example raid poll showing the users teams & levels (if they've set it), status (late, cancel and done), attend times and preferred pokemons (if raid boss is still a raid egg) the users voted for:
![Example raid poll](/screens/raid-poll-example-with-late.png?raw=true "Example raid poll")
![Example raid poll](/screens/raid-poll-example-with-cancel.png?raw=true "Example raid poll")
![Example raid poll](/screens/raid-poll-example-with-done.png?raw=true "Example raid poll")

# Installation and configuration

## Webserver

Preferrably:
- Apache2
- PHP7
- MySQL5 or MariaDB10
- Curl
- SSL Certificate ( https://www.letsencrypt.org )

The following apache packages need to be installed:
- PDO_mysql (ubuntu: php-mysql)
- PHP_curl (ubuntu: php-curl)

## Git clone
For git 2.13 and above:
`git clone --recurse-submodules https://github.com/florianbecker/PokemonRaidBot.git`

If you're running an older version of git use the deprecated recursive command:
`git clone --recursive https://github.com/florianbecker/PokemonRaidBot.git`

## Bot token

Start chat with https://t.me/BotFather and create bot token.

Bot Settings: 
 - Enable Inline mode
 - Allow Groups
   - Group Privacy off

## Database

Create a new mysql database and user for your bot.

Only allow localhost access.

Import `pokemon-raid-bot.sql` as default DB structure and `raid-boss-pokedex.sql` for the current raid bosses. You can find these files in the sql folder.

Command DB structure: `mysql -u USERNAME -p DATABASENAME < sql/pokemon-raid-bot.sql`

Command raid bosses: `mysql -u USERNAME -p DATABASENAME < sql/raid-boss-pokedex.sql`

To get the latest raid bosses via the GOHub API, you can use getGOHubDB.php which will update the sql/gohub-raid-boss-pokedex.sql file. Import is possible too:

Command gohub raid bosses: `mysql -u USERNAME -p DATABASENAME < sql/gohub-raid-boss-pokedex.sql`

Important: The raid level is NOT set when importing the raid bosses from the gohub sql file! Set them via the /pokedex command, explained below in this readme.

## Config

Copy config.php.example to config.php and edit (values explained further).

Enter the details for the database connection to the config.php file.

## General config and log files

Set `DEBUG` to true, to enable the debug logfile.

Set `CONFIG_LOGFILE` to the location of the logfile, e.g. /var/log/tg-bots/dev-raid-bot.log. Make sure to create the log dir, e.g. /var/log/tg-bots/ and set it writeable by webserver.

Set `CONFIG_HASH` to the hashed value of your bot token (preferably lowercase) using a hash generator, e.g. https://www.miniwebtool.com/sha512-hash-generator/ 

Set `DDOS_MAXIMUM` to the amount of callback queries each user is allowed to do each minute. If the amount is reached, e.g. 10, any further callback query is rejected by the DDOS check.

Set `BRIDGE_MODE` to true when you're using the PokemonBotBridge. If you're not using the PokemonBotBridge keep the default false. PokemonBotBridge: https://github.com/florianbecker/PokemonBotBridge

## Proxy

Set `CURL_USEPROXY` to `true` in case you are running the bot behind a proxy server.

Set `CURL_PROXYSERVER` to the proxy server address and port.

Authentication against the proxy server by username and password is currently not supported!

## Webhooks

Set Telegram webhook via webhook.html, e.g. https://yourdomain.com/botdir/webhook.html

## Languages

You can set several languages for the bot. Available languages are (A-Z):
 - DE (German)
 - EN (English)
 - FR (French)
 - IT (Italian)
 - NL (Dutch)
 - NO (Norwegian)
 - PT-BR (Brazilian Portugese)
 - RU (Russian)

Set `LANGUAGE` for the prefered language the bot will answer users when they chat with them. Leave blank that the bot will answer in the users language. If the users language is not supported, e.g. ZH-CN (Chinese), the bot will always use EN (English) as fallback language.

Set `RAID_POLL_LANGUAGE` to the prefered language for raid polls.

So if you want to have the bot communication based on the users Telegram language, e.g. Russian, and show the raid polls in German for example:

`define('LANGUAGE', '');`
`define('RAID_POLL_LANGUAGE', 'DE');`

## Timezone and Google maps API

Set `TIMEZONE` to the timezone you wish to use for the bot. Predefined value from the example config is "Europe/Berlin".

Optionally you can you use Google maps API to lookup addresses of gyms based on latitude and longitude

Therefore get a Google maps API key and set it as `GOOGLE_API_KEY` in your config.

To get a new API key, navigate to https://console.developers.google.com/apis/credentials and create a new API project, e.g. PokemonRaidBot 

Once the project is created select "API key" from the "Create credentials" dropdown menu - a new API key is created.

After the key is created, you need to activate it for both: Geocoding and Timezone API

Therefore go to "Dashboard" on the left navigation pane and afterwards hit "Enable APIs and services" on top of the page.

Search for Geocoding and Timezone API and enable them. Alternatively use these links to get to Geocoding and Timezone API services:

https://console.developers.google.com/apis/library/timezone-backend.googleapis.com

https://console.developers.google.com/apis/library/geocoding-backend.googleapis.com

Finally check the dashboard again and make sure Google Maps Geocoding API and Google Maps Time Zone API are listed as enabled services.

## Raid creation

There are several options to customize the creation of raid polls:

Set `RAID_VIA_LOCATION` to true to allow raid creation from a location shared with the bot.

Set `RAID_EGG_DURATION` to the maximum amount of minutes a user can select for the egg hatching phase.

Set `RAID_POKEMON_DURATION_SHORT` to the maximum amount of minutes a user can select as raid duration for already running/active raids.

Set `RAID_POKEMON_DURATION_LONG` to the maximum amount of minutes a user can select as raid duration for not yet hatched raid eggs.

Set `RAID_DURATION_CLOCK_STYLE` to customize the default style for the raid start time selection. Set to true, the bot will show the time in clocktime style, e.g. "18:34" as selection when the raid will start. Set to false the bot will show the time until the raid starts in minutes, e.g. "0:16" (similar to the countdown in the gyms). Users can switch between both style in the raid creation process.

## Raid times

There are several options to configure the times related to the raid polls:

Set `RAID_LOCATION` to true to send back the location as message in addition to the raid poll.

Set `RAID_SLOTS` to the amount of minutes which shall be between the voting slots.

Set `RAID_DIRECT_START` to the first slot directly after hatching.

Set `RAID_FIRST_START` to the amount of minutes required to add an earlier first start time before the first regular voting slot.

Set `RAID_LAST_START` to the minutes for the last start option before the a raid ends.

Set `RAID_ANYTIME` to true to allow attendance of the raid at any time. If set to false, users have to pick a specific time.

## Raid poll design and layout

There are several options to configure the design and layout of the raid polls:

Set `RAID_VOTE_ICONS` to true to show the icons for the status vote buttons.

Set `RAID_VOTE_TEXT` to true to show the text for the status vote buttons.

Set `RAID_LATE_MSG` to true to enable the message hinting that some participants are late.

Set `RAID_LATE_TIME` to the amount of minutes the local community will may be wait for the late participants.

Set `RAID_POLL_UI_ORDER` to the customize the order of the buttons rows for the raid polls. The default is 'extra,teamlvl,time,pokemon,status' but can be changed to any other order, e.g. 'time,pokemon,extra,status,teamlvl'.

Set `RAID_EX_GYM_MARKER` to set the marker for ex-raid gyms. You can use a predefined icon using the value 'icon' or any own marker, e.g. 'EX'.

## Raid sharing

You can share raid polls with any chat in Telegram via a share button.

Sharing raid polls can be restricted, so only moderators or users or both can be allowed to share a raid poll.

Therefore it is possible, via a comma-separated list, to specify the chats the raid polls can be shared with.

For the ID of a chat either forward a message from the chat to a bot like @RawDataBot or search the web for another method ;)

A few examples:

#### Restrict sharing for moderators and users to chats -100111222333 and -100444555666

`define('SHARE_MODERATORS', false);`

`define('SHARE_USERS', false);`

`define('SHARE_CHATS', '-100111222333,-100444555666');`

#### Allow moderators to share with any chat, restrict sharing for users to chat -100111222333

`define('SHARE_MODERATORS', true);`

`define('SHARE_USERS', false);`

`define('SHARE_CHATS', '-100111222333');`

## Raid overview

The bot allows you to list all raids which got shared with one or more chats as a single raid overview message to quickly get an overview of all raids which are currently running and got shared in each chat. You can view and share raid overviews via the /list command - but only if some raids are currently active and if these active raids got shared to any chats!

To keep this raid overview always up to date when you have it e.g. pinned inside your raid channel, you can setup a cronjob that updates the message by calling the overview_refresh module.

You can either refresh all shared raid overview messages by calling the following curl command:

`curl -k -d '{"callback_query":{"data":"0:overview_refresh:0"}}' https://localhost/botdir/index.php?apikey=111111111:AABBccddEEFFggHHiijjKKLLmmnnOOPPqq`

To just refresh the raid overview message you've shared with a specific chat (e.g. -100112233445) use:

`curl -k -d '{"callback_query":{"data":"0:overview_refresh:-100112233445"}}' https://localhost/botdir/index.php?apikey=111111111:AABBccddEEFFggHHiijjKKLLmmnnOOPPqq`

To delete a shared raid overview message you can use the /list command too.

With the `RAID_PIN_MESSAGE` in the config you can add a custom message to the bottom of the raid overview messages.

## Raid Map

Set `MAP_URL` to the URL of the PokemonBotMap to add it to each raid poll. PokemonBotMap: https://github.com/florianbecker/PokemonBotMap

## Cleanup

The bot features an automatic cleanup of telegram raid poll messages as well as cleanup of the database (attendance and raids tables).

To activate cleanup you need to change the config and create a cronjob to trigger the cleanup process as follows:

Set the `CLEANUP` in the config to `true` and define a cleanup secret/passphrase under `CLEANUP_SECRET`.

Activate the cleanup of telegram messages and/or the database for raids by setting `CLEANUP_TELEGRAM` / `CLEANUP_DATABASE` to true.

Specify the amount of minutes which need to pass by after raid has ended before the bot executes the cleanup. Times are in minutes in `CLEANUP_TIME_TG` for telegram cleanup and `CLEANUP_TIME_DB` for database cleanup. The value for the minutes of the database cleanup `CLEANUP_TIME_DB` must be greater than then one for telegram cleanup `CLEANUP_TIME_TG`. Otherwise cleanup will do nothing and exit due to misconfiguration!

Finally set up a cronjob to trigger the cleanup. You can also trigger telegram / database cleanup per cronjob: For no cleanup use 0, for cleanup use 1 and to use your config file use 2 or leave "telegram" and "database" out of the request data array. Please make sure to always specify the cleanup type which needs to be `raid`.

A few examples for raids - make sure to replace the URL with yours:

#### Cronjob using cleanup values from config.php for raid polls: Just the secret without telegram/database OR telegram = 2 and database = 2

`curl -k -d '{"cleanup":{"type":"raid","secret":"your-cleanup-secret/passphrase"}}' https://localhost/index.php?apikey=111111111:AABBCCDDEEFFGGHHIIJJKKLLMMNNOOPP123`

OR

`curl -k -d '{"cleanup":{"type":"raid","secret":"your-cleanup-secret/passphrase","telegram":"2","database":"2"}}' https://localhost/index.php?apikey=111111111:AABBCCDDEEFFGGHHIIJJKKLLMMNNOOPP123`

#### Cronjob to clean up telegram raid poll messages only: telegram = 1 and database = 0 

`curl -k -d '{"cleanup":{"type":"raid","secret":"your-cleanup-secret/passphrase","telegram":"1","database":"0"}}' https://localhost/index.php?apikey=111111111:AABBCCDDEEFFGGHHIIJJKKLLMMNNOOPP123`

#### Cronjob to clean up telegram raid poll messages and database: telegram = 1 and database = 1

`curl -k -d '{"cleanup":{"type":"raid","secret":"your-cleanup-secret/passphrase","telegram":"1","database":"1"}}' https://localhost/index.php?apikey=111111111:AABBCCDDEEFFGGHHIIJJKKLLMMNNOOPP123`

#### Cronjob to clean up database and maybe telegram raid poll messages (when specified in config): telegram = 2 and database = 1

`curl -k -d '{"cleanup":{"type":"raid","secret":"your-cleanup-secret/passphrase","telegram":"2","database":"1"}}' https://localhost/index.php?apikey=111111111:AABBCCDDEEFFGGHHIIJJKKLLMMNNOOPP123`

# Access permissions

## Public access

When no telegram id, group, supergroup or channel is specified in `BOT_ADMINS` and/or `BOT_ACCESS`, the bot will allow everyone to use it (public access).

Example for public access: `define('BOT_ACCESS', '');`

## Restricted access

With BOT_ADMINS and BOT_ACCESS being used to restrict access, there are several access roles / types. When you do not configure BOT_ACCESS, everyone will have access to your bot (public access).  

Set `BOT_ADMINS` and `BOT_ACCESS` to id (-100123456789) of one or multiple by comma separated individual telegram chat names/ids, groups, supergroups or channels.

Please note, when you're setting groups, supergroups or channels only administrators (not members!) from these chats will gain access to the bot! So make sure this requirement is fulfilled or add their individual telegram usernames/ids instead.

Example for restricted access:  
`define('BOT_ADMINS', '111222333,111555999');`

`define('BOT_ACCESS', '111222333,-100224466889,-100112233445,111555999');`

To allow members from groups, supergroups or channels:

Set `BOT_ALLOW_MEMBERS` to true, so members of a Telegram chat in addition to the administrators are considered during the access check and allowed to use the bot if they are a member of the respective chat.

Set `BOT_ALLOW_MEMBERS_CHAT` to the chats you wish to allow member access for.

Example to allow members of chat groups -100112233445 and -100224466889:
`define('BOT_ALLOW_MEMBERS', true);`

`define('BOT_ALLOW_MEMBERS_CHATS', '-100112233445, -100224466889');`


## Access overview

With your `MAINTAINER_ID` and as a member of `BOT_ADMINS` you have the permissions to do anything. **For performance improvements, it's recommended to add the MAINTAINER and all members of BOT_ADMINS as moderator via /mods command!** 

As a member of `BOT_ACCESS` you can create raid polls, update your own raid polls' pokemon and change the gym team of your last raid poll. `BOT_ACCESS` members who are moderators too, can also change the gym name and update pokemon from other users raid polls. Not that members of `BOT_ACCESS` are not allowed to create polls for ex-raids, only the `MAINTAINER_ID` and the `BOT_ADMINS` have the right to create them.

Telegram Users can only vote on raid polls, but have no access to other bot functions (unless you configured it for public access).


| Access:   |            |                                  | MAINTAINER_ID | BOT_ADMINS | BOT_ACCESS | BOT_ACCESS | Telegram |
|-----------|------------|----------------------------------|---------------|------------|------------|------------|----------|
| Database: |            |                                  |               |            | Moderator  | User       | User     |
|           | **Area**   | **Action and /command**          |               |            |            |            |          |
|           | Raid poll  | Vote                             | Yes           | Yes        | Yes        | Yes        | Yes      |
|           |            | Create `/start`, `/raid`, `/new` | Yes           | Yes        | Yes        | Yes        |          |
|           |            | Create ex-raid `/start`          | Yes           | Yes        |            |            |          |
|           |            | List `/list`                     | Yes           | Yes        | Yes        | Yes        |          |
|           |            | Overview `/list`                 | Yes           | Yes        |            |            |          |
|           |            | Delete ALL raid polls `/delete`  | Yes           | Yes        | Yes        |            |          |
|           |            | Delete OWN raid polls `/delete`  | Yes           | Yes        | Yes        | Yes        |          |
|           |            |                                  |               |            |            |            |          |
|           | Pokemon    | ALL raid polls `/pokemon`        | Yes           | Yes        | Yes        |            |          |
|           |            | OWN raid polls `/pokemon`        | Yes           | Yes        | Yes        | Yes        |          |
|           |            |                                  |               |            |            |            |          |
|           | Gym        | Name `/gym`                      | Yes           | Yes        | Yes        |            |          |
|           |            | Team `/team`                     | Yes           | Yes        | Yes        | Yes        |          |
|           |            |                                  |               |            |            |            |          |
|           | Moderators | List `/mods`                     | Yes           | Yes        |            |            |          |
|           |            | Add `/mods`                      | Yes           | Yes        |            |            |          |
|           |            | Delete `/mods`                   | Yes           | Yes        |            |            |          |
|           |            |                                  |               |            |            |            |          |
|           | Pokedex    | Manage raid pokemon `/pokedex`   | Yes           | Yes        |            |            |          |
|           |            |                                  |               |            |            |            |          |
|           | Help       | Show `/help`                     | Yes           | Yes        | Yes        | Yes        |          |


# Usage

## Bot commands
### Command: No command - just send your location to the bot

The bot will guide you through the creation of a raid poll based on the settings in the config file.

In case of a raid poll the bot will ask you for the raid level, the pokemon raid boss, the time until the raids starts and the time left for the raid. Afterwards you can set the gym name and gym team by using the /gym and /team commands.

### Command: /start

The bot will guide you through the creation of the raid poll by asking you for the gym, raid level, the pokemon raid boss, the time until the raid starts and the time left for the raid. Afterwards you can set the gym team by using the /team command.

To search for the gym by partial or full name you can use `/start gym name`, e.g. `/start Brandenburger Tor`

#### Screenshots
#### Send `/start` to the bot to create a raid by gym selection:
![Command: /start](/screens/command-start.png?raw=true "Command: /start")

#### Select the gym via the first letter:
![Command: /start](/screens/commands-start-select-gym-first-letter.png?raw=true "Command: /start")
![Command: /start](/screens/commands-start-select-gym-letter-d.png?raw=true "Command: /start")

#### Select the raid level and raid boss:
![Command: /start](/screens/commands-start-select-raid-level.png?raw=true "Command: /start")
![Command: /start](/screens/commands-start-select-raid-boss.png?raw=true "Command: /start")

#### Select the start time (clock time or minutes) and the duration of the raid:
![Command: /start](/screens/commands-start-select-starttime-clock.png?raw=true "Command: /start")
![Command: /start](/screens/commands-start-select-starttime-minutes.png?raw=true "Command: /start")

![Command: /start](/screens/commands-start-select-raid-duration.png?raw=true "Command: /start")

#### Raid poll is created. Delete or share it:
![Command: /start](/screens/commands-start-raid-saved.png?raw=true "Command: /start")

### Command: /help

The bot will answer you "This is a private bot" so you can verify the bot is working and accepting input.


### Command: /mods

The bot allows you to set some users as moderators. You can list, add and delete moderators from the bot. Note that when you have restricted the access to your bot via BOT_ADMINS and BOT_ACCESS, you need to add the users as administrators of a chat or their Telegram IDs to either BOT_ADMINS or BOT_ACCESS. Otherwise they won't have access to the bot, even though you have added them as moderators! 


### Command: /raid

Create a new raid by gomap-notifier or other input. The raid command expects 8 parameters and an optional 9th parameter as input seperated by comma.

Additionally the raid command checks for existing raids, so sending the same command multiple times to the bot will result in an update of the pokemon raid boss and gym team and won't create duplicate raids.

Parameters: Pokemon raid boss id, latitude, longitude, raid duration in minutes, gym team, gym name, district or street, district or street, raid pre-hatch egg countdown in minutes (optional)

Example input: `/raid 244,52.516263,13.377755,45,Mystic,Brandenburger Tor,Pariser Platz 1, 10117 Berlin,30`


### Command: /pokemon

Update pokemon of an existing raid poll. With this command you can change the pokemon raid boss from e.g. "Level 5 Egg" to "Lugia" once the egg has hatched.

Based on your access to the bot, you may can only change the pokemon raid boss of raid polls you created yourself and cannot modify the pokemon of raid polls from other bot users.


### Command: /pokedex

Show and update any pokemon raid boss. You can change the raid level (select raid level 0 to disable a raid boss), pokemon CP values and weather information of any pokemon raid boss.

To quickly get to a specific pokemon raid boss, you can use the /pokedex command with the local name of the pokemon. A few examples:

Your telegram is set to English and you like to change Deoxys in his Attack form: `/pokedex Deoxys Attack`

Your telegram is set to German and you like to change Marowak (German: Knogga) in his Alolan (German: Alola) form: `/pokedex Knogga Alola`

#### Screenshots
#### Manage pokemons / raid bosses via the `/pokedex` command:

![Command: /pokedex](/screens/command-pokedex.png?raw=true "Command: /pokedex")

#### All raid bosses:

![Command: /pokedex](/screens/commands-pokedex-all-raid-bosses.png?raw=true "Command: /pokedex")

#### Select and edit a specific pokemon / raid boss:

![Command: /pokedex](/screens/commands-pokedex-list-raid-boss-pokemon.png?raw=true "Command: /pokedex")
![Command: /pokedex](/screens/commands-pokedex-edit-raid-boss-pokemon.png?raw=true "Command: /pokedex")

#### Edit the raid level:

![Command: /pokedex](/screens/commands-pokedex-set-raid-level.png?raw=true "Command: /pokedex")
![Command: /pokedex](/screens/commands-pokedex-saved-new-raid-level.png?raw=true "Command: /pokedex")

#### Edit the CP values, e.g. Max CP:

![Command: /pokedex](/screens/commands-pokedex-enter-max-cp.png?raw=true "Command: /pokedex")
![Command: /pokedex](/screens/commands-pokedex-save-max-cp.png?raw=true "Command: /pokedex")
![Command: /pokedex](/screens/commands-pokedex-saved-new-max-cp.png?raw=true "Command: /pokedex")

#### Edit the weather:

![Command: /pokedex](/screens/commands-pokedex-set-weather.png?raw=true "Command: /pokedex")


### Command: /list 

The bot will allow you to get a list of the last 20 active raids, share and delete all raids which got shared to channels as a raid overview.

#### Screenshots
#### List existing raid polls with the `/list` command:

![Command: /list](/screens/command-list.png?raw=true "Command: /list")

![Command: /list](/screens/commands-list-active-raids.png?raw=true "Command: /list")

#### Share overview message with all raids shared to channel "Chat-Name" to the channel:

![Command: /list](/screens/commands-list-share-overview.png?raw=true "Command: /list")

#### Delete the shared overview message:

![Command: /list](/screens/commands-list-delete-overview.png?raw=true "Command: /list")

### Command: /delete

Delete an existing raid poll. With this command you can delete a raid poll from telegram and the database. Use with care!

Based on your access to the bot, you may can only delete raid polls you created yourself and cannot delete raid polls from other bot users.

#### Screenshots
#### Delete an existing raid poll with the `/delete` command:

![Command: /delete](/screens/command-delete.png?raw=true "Command: /delete")

![Command: /delete](/screens/commands-delete-raid-deleted.png?raw=true "Command: /delete")

### Command: /team

The bot will set the team to Mystic/Valor/Instinct for the last created raid based on your input.

Example input: `/team Mystic`


### Command: /gym

The bot will set the name of gym to your input.

Example input: `/gym Siegessäule`

# Debugging

Check your bot logfile and other related log files, e.g. apache/httpd log, php log, and so on.

# Updates

Currently constantly new features, bug fixes and improvements are added to the bot. Since we do not have an update mechanism yet, when updating the bot, please always do the following:
 - Add new config variables which got added to the config.php.example to your own config.php!
 - If new tables and/or columns got added or changed inside raid-pokemon-bot.sql, please add/alter these tables/columns at your existing installation!

# TODO

* New gyms: Adding gyms to database without creating a raid via /raid

# SQL Files

The following commands are used to create the raid-pokemon-bot.sql, raid-boss-pokedex.sql and gohub-raid-boss-pokedex.sql files. Make sure to change to the bot directory first and replace USERNAME and DATABASENAME before executing the commands.

#### pokemon-raid-bot.sql

Export command: `mysqldump -u USERNAME -p --no-data --skip-add-drop-table --skip-add-drop-database --skip-comments DATABASENAME | sed 's/ AUTO_INCREMENT=[0-9]*\b/ AUTO_INCREMENT=100/' > sql/pokemon-raid-bot.sql`

#### raid-boss-pokedex.sql

Export command: `mysqldump -u USERNAME -p --skip-extended-insert --skip-comments DATABASENAME pokemon > sql/raid-boss-pokedex.sql`

#### gohub-raid-boss-pokedex.sql

CLI creation command: `php getGOHubDB.php`
