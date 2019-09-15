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

#### Core module inside bot folder

For git 2.13 and above:

`git clone --recurse-submodules https://github.com/florianbecker/PokemonRaidBot.git`

If you're running an older version of git use the deprecated recursive command:

`git clone --recursive https://github.com/florianbecker/PokemonRaidBot.git`

#### Core module outside bot folder

If you like to keep the core repo outside the bot folder so multiple bots can access the core (e.g. via the [PokemonBotBridge](https://github.com/florianbecker/PokemonBotBridge.git "PokemonBotBridge")) you can do the following:

Clone the bot repo to e.g. `var/www/html`:

`git clone https://github.com/florianbecker/PokemonRaidBot.git`

Clone the core repo to e.g. `var/www/html`:

`git clone https://github.com/florianbecker/php.core.telegram.git`

Change to the bot folder and create a symlink to make core accessible for the bot:
```
cd /var/www/html/PokemonRaidBot
rm -rf core/
ln -sf /var/www/html/php.core.telegram core
```

## Bot token

Start chat with https://t.me/BotFather and create bot token.

Bot Settings: 
 - Enable Inline mode
 - Allow Groups
   - Group Privacy off

## Database

Create a new mysql database and user for your bot.

Command to access the terminal as root user: `mysql -u root -p`

Command to create a user with localhost access (Only allow localhost access!): `CREATE USER 'USERNAME'@'localhost' IDENTIFIED BY 'PASSWORD';`

Command to create a database: `CREATE DATABASE DATABASENAME;`

Command to create privileges for new user to database: `GRANT ALL PRIVILEGES ON DATABASENAME.* TO 'USERNAME'@'localhost';`

Flush privileges: `FLUSH PRIVILEGES;`

Just use exit to logout from database.

Import `pokemon-raid-bot.sql` as default DB structure and `raid-boss-pokedex.sql` for the current raid bosses. You can find these files in the sql folder.

Command DB structure: `mysql -u USERNAME -p DATABASENAME < sql/pokemon-raid-bot.sql`

Command raid bosses: `mysql -u USERNAME -p DATABASENAME < sql/raid-boss-pokedex.sql`

To get the latest raid bosses via the GOHub API, you can use getGOHubDB.php which will update the sql/gohub-raid-boss-pokedex.sql file. Import is possible too:

Command gohub raid bosses: `mysql -u USERNAME -p DATABASENAME < sql/gohub-raid-boss-pokedex.sql`

Important: The raid level is NOT set when importing the raid bosses from the gohub sql file! Set them via the /pokedex command, explained below in this readme.

## Config

Inside the config folder, copy the example config.json.example to your own config.json and edit the values (explained further).

Don't forget to change the file permissions of your config file to 0600 (e.g. `chmod 0600 config.json`) afterwards. You need to change the ownerchip of all files to the webserver user - otherwise the config is not readable. Normally this: `chown www-data:www-data -R *`

Some values are missing as the bot has default values. If you like to change those, you need to add and define them in your config.json file, e.g. `"DDOS_MAXIMUM":"10"`.

## Database connection

Enter the details for the database connection to the config.php file via `DB_HOST`, `DB_NAME`, `DB_USER` and `DB_PASSWORD`.

## General config and log files

Set `DEBUG` to true, to enable the debug logfile.

Set `DEBUG_LOGFILE` to the location of the logfile, e.g. /var/log/tg-bots/dev-raid-bot.log. Make sure to create the log dir, e.g. /var/log/tg-bots/ and set it writeable by webserver.

Set `APIKEY_HASH` to the hashed value of your bot token (preferably lowercase) using a hash generator, e.g. https://www.miniwebtool.com/sha512-hash-generator/ 

Set `DDOS_MAXIMUM` to the amount of callback queries each user is allowed to do each minute. If the amount is reached any further callback query is rejected by the DDOS check. Default value: 10.

Set `BRIDGE_MODE` to true when you're using the PokemonBotBridge. If you're not using the PokemonBotBridge the default value of false is used. PokemonBotBridge: https://github.com/florianbecker/PokemonBotBridge

## Proxy

Set `CURL_USEPROXY` with a value of `true` in case you are running the bot behind a proxy server.

Set `CURL_PROXYSERVER` to the proxy server address and port, for example:

```
"CURL_USEPROXY":"false",
"CURL_PROXYSERVER":"http://your.proxyserver.com:8080",
```

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
 - PL (Polish)

Set `LANGUAGE_PRIVATE` for the prefered language the bot will answer users when they chat with them. Leave blank that the bot will answer in the users language. If the users language is not supported, e.g. ZH-CN (Chinese), the bot will always use EN (English) as fallback language.

Set `LANGUAGE_PUBLIC` to the prefered language for raid polls. Default value: EN

So if you want to have the bot communication based on the users Telegram language, e.g. Russian, and show the raid polls in German for example:

```
"LANGUAGE_PRIVATE":"",
"LANGUAGE_PUBLIC":"DE",
```

## Timezone and Google maps API

Set `TIMEZONE` to the timezone you wish to use for the bot. Predefined value from the example config is "Europe/Berlin".

Optionally you can you use Google maps API to lookup addresses of gyms based on latitude and longitude. Therefore get a Google maps API key. 

To get a new API key, navigate to https://console.developers.google.com/apis/credentials and create a new API project, e.g. PokemonRaidBot 

Once the project is created select "API key" from the "Create credentials" dropdown menu - a new API key is created.

After the key is created, you need to activate it for both: Geocoding and Timezone API

Therefore go to "Dashboard" on the left navigation pane and afterwards hit "Enable APIs and services" on top of the page.

Search for Geocoding and Timezone API and enable them. Alternatively use these links to get to Geocoding and Timezone API services:

https://console.developers.google.com/apis/library/timezone-backend.googleapis.com

https://console.developers.google.com/apis/library/geocoding-backend.googleapis.com

Finally check the dashboard again and make sure Google Maps Geocoding API and Google Maps Time Zone API are listed as enabled services.

Set `MAPS_LOOKUP` to true and put the API key in `MAPS_API_KEY` in your config.

## Raid creation

There are several options to customize the creation of raid polls:

Set `RAID_VIA_LOCATION` to true to allow raid creation from a location shared with the bot.

Set `RAID_EGG_DURATION` to the maximum amount of minutes a user can select for the egg hatching phase.

Set `RAID_POKEMON_DURATION_SHORT` to the maximum amount of minutes a user can select as raid duration for already running/active raids.

Set `RAID_POKEMON_DURATION_LONG` to the maximum amount of minutes a user can select as raid duration for not yet hatched raid eggs.

Set `RAID_DURATION_CLOCK_STYLE` to customize the default style for the raid start time selection. Set to true, the bot will show the time in clocktime style, e.g. "18:34" as selection when the raid will start. Set to false the bot will show the time until the raid starts in minutes, e.g. "0:16" (similar to the countdown in the gyms). Users can switch between both style in the raid creation process.

Set `RAID_CUSTOM_GYM_LETTERS` to further split gyms by their first letter. For example if you have a lot of gyms starting with 'St' as there are a lot of churches like St. Helen, St. Jospeh, etc. in your area and the gym list under the letter 'S' is too long, you can tell the bot to put the gyms starting with 'St' under 'St' and exclude them from the letter 'S'. There is no limitation in length, so even 'Berlin' would work to split gyms, but the recommendation is to use as less chars as possible to split the gyms. You can add multiple custom gym letters, just separate them by comma. Example: `"RAID_CUSTOM_GYM_LETTERS":"Ber,Sch,St,Wi"`

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

Sharing raid polls can be restricted, so only specific chats/users can be allowed to share a raid poll - take a look at the permission system!

With a predefined list `SHARE_CHATS` you can specify the chats which should appear as buttons for sharing raid polls.

You can define different chats for specific raid levels using `SHARE_CHATS_LEVEL_` plus the raid level too. Raid levels can be 'X', '5', '4', '3', '2' or '1'.

For the ID of a chat either forward a message from the chat to a bot like @RawDataBot, @getidsbot or search the web for another method ;)

Examples:

#### Predefine sharing all raids to the chats -100111222333 and -100444555666

`"SHARE_CHATS":"-100111222333,-100444555666"`

#### Predefine sharing all raids to the chats -100111222333 and -100444555666, except level 5 raids which will be shared to the chat -100999666333

`"SHARE_CHATS":"-100111222333,-100444555666"`
`"SHARE_CHATS_LEVEL_5":"-100444555666"`

## Raid overview

The bot allows you to list all raids which got shared with one or more chats as a single raid overview message to quickly get an overview of all raids which are currently running and got shared in each chat. You can view and share raid overviews via the /overview command - but only if some raids are currently active and if these active raids got shared to any chats!

To keep this raid overview always up to date when you have it e.g. pinned inside your raid channel, you can setup a cronjob that updates the message by calling the overview_refresh module.

You can either refresh all shared raid overview messages by calling the following curl command:

`curl -k -d '{"callback_query":{"data":"raid:0:overview_refresh:0"}}' https://localhost/botdir/index.php?apikey=111111111:AABBccddEEFFggHHiijjKKLLmmnnOOPPqq`

To just refresh the raid overview message you've shared with a specific chat (e.g. -100112233445) use:

`curl -k -d '{"callback_query":{"data":"raid:0:overview_refresh:-100112233445"}}' https://localhost/botdir/index.php?apikey=111111111:AABBccddEEFFggHHiijjKKLLmmnnOOPPqq`

To delete a shared raid overview message you can use the `/overview` command too.

With the `RAID_PIN_MESSAGE` in the config you can add a custom message to the bottom of the raid overview messages.

## Raid Map

Set `MAP_URL` to the URL of the PokemonBotMap to add it to each raid poll. PokemonBotMap: https://github.com/florianbecker/PokemonBotMap

## Portal Import

Set `PORTAL_IMPORT` to `true` to enable the possibility to import portals from Telegram Ingress Bots.

## Cleanup

The bot features an automatic cleanup of telegram raid poll messages as well as cleanup of the database (attendance and raids tables).

To activate cleanup you need to change the config and create a cronjob to trigger the cleanup process as follows:

Set the `CLEANUP` in the config to `true` and define a cleanup secret/passphrase under `CLEANUP_SECRET`.

Activate the cleanup of telegram messages and/or the database for raids by setting `CLEANUP_TELEGRAM` / `CLEANUP_DATABASE` to true.

Specify the amount of minutes which need to pass by after raid has ended before the bot executes the cleanup. Times are in minutes in `CLEANUP_TIME_TG` for telegram cleanup and `CLEANUP_TIME_DB` for database cleanup. The value for the minutes of the database cleanup `CLEANUP_TIME_DB` must be greater than then one for telegram cleanup `CLEANUP_TIME_TG`. Otherwise cleanup will do nothing and exit due to misconfiguration!

Finally set up a cronjob to trigger the cleanup. You can also trigger telegram / database cleanup per cronjob: For no cleanup use 0, for cleanup use 1 and to use your config file use 2 or leave "telegram" and "database" out of the request data array.

A few examples for raids - make sure to replace the URL with yours:

#### Cronjob using cleanup values from config.php for raid polls: Just the secret without telegram/database OR telegram = 2 and database = 2

`curl -k -d '{"cleanup":{"secret":"your-cleanup-secret/passphrase"}}' https://localhost/index.php?apikey=111111111:AABBCCDDEEFFGGHHIIJJKKLLMMNNOOPP123`

OR

`curl -k -d '{"cleanup":{"secret":"your-cleanup-secret/passphrase","telegram":"2","database":"2"}}' https://localhost/index.php?apikey=111111111:AABBCCDDEEFFGGHHIIJJKKLLMMNNOOPP123`

#### Cronjob to clean up telegram raid poll messages only: telegram = 1 and database = 0 

`curl -k -d '{"cleanup":{"secret":"your-cleanup-secret/passphrase","telegram":"1","database":"0"}}' https://localhost/index.php?apikey=111111111:AABBCCDDEEFFGGHHIIJJKKLLMMNNOOPP123`

#### Cronjob to clean up telegram raid poll messages and database: telegram = 1 and database = 1

`curl -k -d '{"cleanup":{"secret":"your-cleanup-secret/passphrase","telegram":"1","database":"1"}}' https://localhost/index.php?apikey=111111111:AABBCCDDEEFFGGHHIIJJKKLLMMNNOOPP123`

#### Cronjob to clean up database and maybe telegram raid poll messages (when specified in config): telegram = 2 and database = 1

`curl -k -d '{"cleanup":{"secret":"your-cleanup-secret/passphrase","telegram":"2","database":"1"}}' https://localhost/index.php?apikey=111111111:AABBCCDDEEFFGGHHIIJJKKLLMMNNOOPP123`

# Access permissions

## Public access

When no telegram id, group, supergroup or channel is specified in `BOT_ADMINS` the bot will allow everyone to use it (public access).

Example for public access: `"BOT_ADMINS":""`

## Access and permissions

The `MAINTAINER_ID` is not able to access the bot nor has any permissions as that id is only contacted in case of errors and issues with the bot configuration.

The `BOT_ADMINS` have all permissions and can use any feature of the bot.

Telegram Users can only vote on raid polls, but have no access to other bot functions (unless you configured it).

In order to allow telegram chats to access the bot and use commands/features, you need to create an access file.

It does not matter if a chat is a user, group, supergroup or channel - any kind of chat is supported as every chat has a chat id!

Those access files need to be placed under the subdirectory 'access' and follow a special name scheme.

| Chat type                     | User role      | Name of the access file           | Example                   |
|-------------------------------|----------------|-----------------------------------|---------------------------|
| User                          | -              | `accessCHAT_ID`                   | `access111555999`         |
|                               |                |                                   |                           |
| Group, Supergroup, Channel    | Any role       | `accessCHAT_ID`                   | `access-100224466889`     |
|                               | Creator        | `creatorCHAT_ID`                  | `creator-100224466889`    |
|                               | Admin          | `adminsCHAT_ID`                   | `admins-100224466889`     |
|                               | Member         | `membersCHAT_ID`                  | `members-100224466889`    |
|                               | Restricted     | `restrictedCHAT_ID`               | `restricted-100224466889` |
|                               | Kicked         | `kickedCHAT_ID`                   | `kicked-100224466889`     |

As you can see in the table, you can define different permissions for the creator, the admins and the members of a group, supergroup and channel.

You can also create just one access file for groups, supergroups or channels (e.g. `access-100224466889`) so any user has the same permission regardless of their role in the chat, but this is not recommended (see important note below!).

**Important: Any role means any role - so in addition to roles 'creator', 'administrator' or 'member' this will also grant 'restricted' and 'kicked' users to access the bot with the defined permissions!

To exclude 'restricted' and 'kicked' users when using an access file for any role (e.g. `access-100224466889`) you can add the permissions `ignore-restricted` and `ignore-kicked` to the access file!

User with the role 'left' are automatically receiving an 'Access denied' from the bot as they willingly have choosen to leave the chat through which they got access to the bot!**

Every access file allows the access for a particular chat and must include the permissons which should be granted to that chat.

To differ between all those access file you can add any kind of comment to the filename of the access file itself. Just make sure to not use a number (0-9) right after the chat id!

Consider you have 4 channels. One for each district of your town: east, west, south and north. So you could name the access file for example like this:

```
access-100333444555 South-Channel
access-100444555666+NorthernChannel
admins-100222333444_West-District
creator-100111222333-Channel-East-District
creator-100444555666+NorthernChannel
members-100111222333-Channel-East-District
members-100222333444_West-District
```

## Permissions overview

The following table shows the permissions you need to write into an access file (last column) to grant permissions to chats.

In an access file it is **One permission per line** - so not separated by space, comma or any other char!

A few examples for access files can be found below the permission overview table.


| Access     | **Action and /command**                                          | Permission inside access file            |
|------------|------------------------------------------------------------------|------------------------------------------|
| Bot        | Access the bot itself                                            | `access-bot`                             |
|            | Deny access to restricted group/supergroup/channel members       | `ignore-restricted`                      |
|            | Deny access to kicked group/supergroup/channel members           | `ignore-kicked`                          |
|            |                                                                  |                                          |
| Raid poll  | Vote on shared raid poll                                         | Not required!                            |
|            | Create raids `/start`, `/raid`                                   | `create`                                 |
|            | Create ex-raids `/start`                                         | `ex-raids`                               |
|            | Change raid duration `/start`                                    | `raid-duration`                          |
|            | List all raids `/list`                                           | `list`                                   |
|            | Manage overview `/overview`                                      | `overview`                               |
|            | Delete OWN raid polls `/delete`                                  | `delete-own`                             | 
|            | Delete ALL raid polls `/delete`                                  | `delete-all`                             |
|            |                                                                  |                                          |
| Sharing    | Share OWN created raids to predefined chats 'SHARE_CHATS'        | `share-own`                              |
|            | Share ALL created raids to predefined chats 'SHARE_CHATS'        | `share-all`                              |
|            | Share OWN created raids to any chat                              | `share-own` and `share-any-chat`         |
|            | Share ALL created raids to any chat                              | `share-all` and `share-any-chat`         |
|            |                                                                  |                                          |
| Pokemon    | Update pokemon on OWN raid polls `/pokemon`                      | `pokemon-own`                            |
|            | Update pokemon on ALL raid polls `/pokemon`                      | `pokemon-all`                            |
|            |                                                                  |                                          |
| Gym        | Get gym details `/gym`                                           | `gym-details`                            |
|            | Edit extended gym details `/gym`                                 | `gym-edit`                               |
|            | Edit gym name `/gymname`                                         | `gym-name`                               |
|            | Edit gym address `/gymaddress`                                   | `gym-address`                            |
|            | Edit gym gps coordinates `/gymgps`                               | `gym-gps`                                |
|            | Edit gym note `/gymnote`                                         | `gym-note`                               |
|            | Add a gym `/addgym`                                              | `gym-add`                                |
|            | Delete a gym `/deletegym`                                        | `gym-delete`                             |
|            |                                                                  |                                          |
| Portal     | Import portals via inline search from other bots                 | `portal-import`                          |
|            |                                                                  |                                          |
| Pokedex    | Manage raid pokemon `/pokedex`                                   | `pokedex`                                |
|            |                                                                  |                                          |
| Help       | Show help `/help`                                                | `help`                                   |


#### Example: Allow the user 111555999 to create raid polls and share them to the predefined chat list

Access file: `access\access111555999`

Content of the access file, so the actual permissions:
```
access-bot
create
share-own
```

#### Example: Allow the creator and the admins of the channel -100224466889 to create raid polls as well as sharing raid polls created by their own or others to the predefined chat list or any other chat

Access file for the creator: `access\creator-100224466889`

Access file for the admins: `access\admins-100224466889`

Important: The minus `-` in front of the actual chat id must be part of the name as it's part of the chat id!

Content of the access files, so the actual permissions:
```
access-bot
create
share-all
share-own
share-any-chat
```

# Customization

The bot allows you to customize things and therefore has a folder 'custom' for your customizations.

## Custom icons

In case you do not like some of the predefined icons and might like to change them to other/own icons:
- Create a file named `constants.php` in the custom folder
- Lookup the icon definitions you'd like to change in either the core or bot constants.php (`core/bot/constants.php` and `constants.php`)
- Define your own icons in your custom constants.php
- For example to change the yellow exclamation mark icon to a red exclamation mark put the following in your `custom/constants.php`:

`<?php
defined('EMOJI_WARN')           or define('EMOJI_WARN',    iconv('UCS-4LE', 'UTF-8', pack('V', 0x2757)));
`
- Make sure to not miss the first line which declares the file as php file!
- To get the codes (here: 0x2757) of the icons/emojis, take a look at one of the large emoji databases in the web. They ususally have them mentioned and also show how the icons look like on different systems.

## Custom translation

To change translations you can do the following:
- Create a file named `language.json` in the custom folder
- Find the translation name/id by searching the core and bot language.php files (`core/lang/language.php` and `lang/language.php`)
- Set your own translation in your custom language.json
- For example to change the translation of 'Friday' to a shorter 'Fri' put the following in your `custom/language.json`:

```
{
    "weekday_5":{
        "EN":"Fri"
    }
}
```
- Make sure to create a valid JSON file for your custom translations
- To verify your custom language.json you can use several apps, programs and web services.

# Usage

## Bot commands
### Command: No command - just send your location to the bot

The bot will guide you through the creation of a raid poll based on the settings in the config file.

In case of a raid poll the bot will ask you for the raid level, the pokemon raid boss, the time until the raids starts and the time left for the raid. Afterwards you can set the gym name and gym team by using the /gym and /team commands.

### Command: No command - using inline search of @PortalMapBot or @Ingressportalbot

You can add new gyms to the bot using the inline search of one of the bots mentioned above. Just search for a portal name, e.g. `Brandenburger Tor`, and select one of the portals shown as result of your search.

On selection the portal information will get posted as a normal message and detected, so a new gym is automatically created from the portal info in that message.

In case the portal is already in your gym list / database, it will get updated with the new info (latitude, longitude and address) from the message.

Example: `@PortalMapBot Brandenburger Tor`

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

The bot will give a personal help based on the permissions you have to access and use it.


### Command: /raid

Create a new raid by gomap-notifier or other input. The raid command expects 8 parameters and an optional 9th parameter as input seperated by comma.

Additionally the raid command checks for existing raids, so sending the same command multiple times to the bot will result in an update of the pokemon raid boss and gym team and won't create duplicate raids.

Parameters: Pokemon raid boss id and form (combine with minus), latitude, longitude, raid duration in minutes, gym team, gym name, district or street, district or street, raid pre-hatch egg countdown in minutes (optional)

Example input: `/raid 244-normal,52.516263,13.377755,45,Mystic,Brandenburger Tor,Pariser Platz 1, 10117 Berlin,30`


### Command: /pokemon

Update pokemon of an existing raid poll. With this command you can change the pokemon raid boss from e.g. "Level 5 Egg" to "Lugia" once the egg has hatched.

Based on your access to the bot, you may can only change the pokemon raid boss of raid polls you created yourself and cannot modify the pokemon of raid polls from other bot users.


### Command: /pokedex

Show and update any pokemon raid boss. You can change the raid level (select raid level 0 to disable a raid boss), pokemon CP values and weather information of any pokemon raid boss.

There is also a possibility to import the raids bosses from Pokebattler and disable all raid bosses for all or just a specific raid level which makes raid boss management pretty easy.

To quickly get to a specific pokemon raid boss, you can use the /pokedex command with the local name of the pokemon. A few examples:

Your telegram is set to English and you like to change Deoxys in his Attack form: `/pokedex Deoxys Attack`

Your telegram is set to German and you like to change Marowak (German: Knogga) in his Alolan (German: Alola) form: `/pokedex Knogga Alola`

Beside your local language the bot always is looking at the English language as a fallback.

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

The bot will allow you to get a list of the last 20 active raids and re-share or delete them.


#### Screenshots
#### List existing raid polls with the `/list` command:

![Command: /list](/screens/command-list.png?raw=true "Command: /list")

![Command: /list](/screens/commands-list-active-raids.png?raw=true "Command: /list")


### Command: /overview 

Share and delete the raid overview message.

#### Share overview message with all raids shared to channel "Chat-Name" to the channel:

![Command: /overview](/screens/commands-list-share-overview.png?raw=true "Command: /overview")

#### Delete the shared overview message:

![Command: /overview](/screens/commands-list-delete-overview.png?raw=true "Command: /overview")

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

The bot will show the details of each gym. Additionally you can change the extended gym details to hide/show gyms under `/start` as well as mark/un-mark them as ex-raid gym.

Example input: `/gym`


### Command: /addgym

The bot will add a gym under the coordinates you're submitting. First latitude, then longitude. The gym is added under the name '#YourTelegramID' (e.g. '#111555777') and you need to change the name afterwards using the `/gymname` command. You cannot submit a second gym unless you changed the name of the first gym. In case you submit a second gym without changing the name of the previously submitted gym, the first gym coordinates will be overwritten!

Example input: `/addgym 52.5145434,13.3501189`


### Command: /gymname

The bot will set the name of gym to your input. If you submitted a gym via location sharing you can use it without an id. Otherwise the id of the gym is required.

Example input: `/gymname Siegessäule`

Example input with gym id: `/gymname 34, Siegessäule`


### Command: /gymaddress

The bot will set the address of gym to your input. The id of the gym is required. You can delete the gym address using the keyword 'reset'.

Example input: `/gymaddress 34, Großer Stern, 10557 Berlin`

Example input to delete the gym address: `/gymaddress 34, reset`


### Command: /gymgps

The bot will set the gps coordinates of gym to your input. The id of the gym is required.

Example input: `/gymgps 34, 52.5145434,13.3501189`


### Command: /gymnote

The bot will set the note for gym to your input. The id of the gym is required. You can delete the gym note using the keyword 'reset'.

Example input: `/gymnote 34, Meeting point: Behind the buildung`

Example input to delete the gym note: `/gymnote 34, reset`


### Command: /deletegym

The bot will show all gyms. Select a gym and confirm the deletion to remove it from the database.

Example input: `/deletegym`


# Debugging

Check your bot logfile and other related log files, e.g. apache/httpd log, php log, and so on.

# Updates

The bot has a version system and checks for updates to the database automatically.

The bot will send a message to the MAINTAINER_ID when an upgrade is required. In case the MAINTAINER_ID is not specified an error message is written to the error log of your webserver.

Required SQL upgrades files can be found under the `sql/upgrade` folder and need to be applied manually!

After any upgrade you need to make sure to change the bot version in your config.json as that version is used for comparison against the latest bot version in the `VERSION` file.

Updates to the config file are NOT checked automatically. Therefore always check for changes to the config.json.example and add new config variables to your own config.json then too!

# Git Hooks

In the needed core repository we provide a folder with git hooks which can be used to automate several processes. Copy them to the `.git/hooks/` folder of this bot and make them executable (e.g. `chmod +x .git/hooks/pre-commit`) to use them.

#### pre-commit

The pre-commit git hook will automatically update the VERSION file whenever you do a `git commit`.

The bot version is automatically generated when using the pre-commit hook according to the following scheme consisting of 4 parts separated by dots:
 - Current decade (1 char)
 - Current year (1 char)
 - Current day of the year (up to 3 chars)
 - Number of the commit at the current day of the year (1 or more chars)

To give a little example the bot version `1.9.256.4` means:
 - Decade was 20**1**0-20**1**9
 - Year was 201**9**
 - Day number **256** (from 365 days in 2019) was the 13th September 2019
 - There have been **4** commits at that day

This way it is easy to find out when a bot version was released and how old/new a version is.

# SQL Files

The following commands are used to create the raid-pokemon-bot.sql, raid-boss-pokedex.sql and gohub-raid-boss-pokedex.sql files. Make sure to change to the bot directory first and replace USERNAME and DATABASENAME before executing the commands.

#### pokemon-raid-bot.sql

Export command: `mysqldump -u USERNAME -p --no-data --skip-add-drop-table --skip-add-drop-database --skip-comments DATABASENAME | sed 's/ AUTO_INCREMENT=[0-9]*\b/ AUTO_INCREMENT=100/' > sql/pokemon-raid-bot.sql`

#### raid-boss-pokedex.sql

Export command: `mysqldump -u USERNAME -p --skip-extended-insert --skip-comments DATABASENAME pokemon > sql/raid-boss-pokedex.sql`

#### gohub-raid-boss-pokedex.sql

CLI creation command: `php getGOHubDB.php`
