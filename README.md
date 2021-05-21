# PokemonRaidBot

Telegram webhook bot for organizing raids in Pokemon Go. Developers are welcome to join https://t.me/PokemonBotSupport

## Table of contents

<!--ts-->
   * [PokemonRaidBot](#pokemonraidbot)
      * [Table of contents](#table-of-contents)
      * [Example screenshots](#example-screenshots)
   * [Installation and configuration](#installation-and-configuration)
      * [Webserver](#webserver)
      * [Git clone](#git-clone)
         * [Core module inside bot folder](#core-module-inside-bot-folder)
         * [Core module outside bot folder](#core-module-outside-bot-folder)
      * [Bot token](#bot-token)
      * [Database](#database)
      * [Docker](#docker)
         * [Installation of Docker:](#installation-of-docker)
         * [Raidbot installation:](#raidbot-installation)
         * [SSL with Docker](#ssl-with-docker)
         * [Useful Docker commands](#useful-docker-commands)
         * [Using getPokemonIcons.php with Docker](#using-getpokemoniconsphp-with-docker)
      * [Config](#config)
         * [Referring to groups, channels and users](#referring-to-groups-channels-and-users)
            * [Finding public IDs](#finding-public-ids)
            * [Finding private IDs](#finding-private-ids)
            * [Which group type should I use? / How do I make a group a Supergroup](#which-group-type-should-i-use--how-do-i-make-a-group-a-supergroup)
         * [Database connection](#database-connection)
         * [General config and log files](#general-config-and-log-files)
      * [Installing the Webhook](#installing-the-webhook)
      * [More config options](#more-config-options)
         * [Proxy](#proxy)
         * [Languages](#languages)
         * [Timezone and Google maps API](#timezone-and-google-maps-api)
         * [Raid creation options](#raid-creation-options)
         * [Raid time customization](#raid-time-customization)
         * [Raid poll design and layout](#raid-poll-design-and-layout)
         * [Raid Picture mode](#raid-picture-mode)
            * [Font support](#font-support)
         * [Portal Import](#portal-import)
         * [Raid sharing](#raid-sharing)
            * [Sharing all raids to two chats](#sharing-all-raids-to-two-chats)
            * [Sharing split to channels by level](#sharing-split-to-channels-by-level)
            * [Raids from Webhook](#raids-from-webhook)
            * [Filter Raids from Webhook / geoconfig.json](#filter-raids-from-webhook--geoconfigjson)
            * [Extended Raid-Sharing](#extended-raid-sharing)
      * [Trainer settings](#trainer-settings)
      * [Raid overview](#raid-overview)
      * [Raid Map](#raid-map)
      * [Cleanup](#cleanup)
         * [Examples](#examples)
      * [Access permissions](#access-permissions)
         * [Public access](#public-access)
         * [Access and permissions](#access-and-permissions)
         * [Permissions overview](#permissions-overview)
            * [Examples](#examples-1)
   * [Customization](#customization)
      * [Custom icons](#custom-icons)
      * [Custom translation](#custom-translation)
   * [Bot usage](#bot-usage)
      * [Send your location to the bot](#send-your-location-to-the-bot)
      * [Using inline search of @PortalMapBot or @Ingressportalbot](#using-inline-search-of-portalmapbot-or-ingressportalbot)
      * [Command: /start](#command-start)
      * [Command: /help](#command-help)
      * [Command: /raid](#command-raid)
      * [Command: /pokemon](#command-pokemon)
      * [Command: /pokedex](#command-pokedex)
      * [Command: /list](#command-list)
      * [Command: /listall](#command-listall)
      * [Command: /overview](#command-overview)
      * [Command: /delete](#command-delete)
      * [Command: /team](#command-team)
      * [Command: /trainer](#command-trainer)
      * [Command: /gym](#command-gym)
      * [Command: /addgym](#command-addgym)
      * [Command: /gymname](#command-gymname)
      * [Command: /gymaddress](#command-gymaddress)
      * [Command: /gymgps](#command-gymgps)
      * [Command: /gymnote](#command-gymnote)
      * [Command: /deletegym](#command-deletegym)
   * [Debugging](#debugging)
   * [Updates](#updates)
   * [Config reference](#config-reference)
   * [Development](#development)
      * [Adding new config values](#adding-new-config-values)
      * [Git Hooks](#git-hooks)
         * [pre-commit](#pre-commit)
         * [game-master-raid-boss-pokedex.sql](#game-master-raid-boss-pokedexsql)
      * [Translations](#translations)
         * [translate.py](#translatepy)
            * [Usage](#usage)

<!-- Added by: artanicus, at: Tue Mar 16 21:46:59 EET 2021 -->

<!--te-->

## Example screenshots

*With the ex-raid notice:*

![Example raid poll](/screens/raid-poll-example-with-ex-raid-message.png?raw=true "Example raid poll")

*Showing the users teams & levels (if they've set it), status (late, cancel and done), attend times and preferred pokemons (if raid boss is still a raid egg) the users voted for:*

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

### Core module inside bot folder

For git 2.13 and above:

`git clone --recurse-submodules https://github.com/florianbecker/PokemonRaidBot.git`

If you're running an older version of git use the deprecated recursive command:

`git clone --recursive https://github.com/florianbecker/PokemonRaidBot.git`

### Core module outside bot folder

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

To get raid bosses:
```
mysql -u USERNAME -p DATABASENAME < sql/game-master-raid-boss-pokedex.sql`
```

Important: The raid level is NOT set when importing the raid bosses from the sql file! Set them via the /pokedex command, explained below in this readme.

## Docker

### Installation of Docker:
```
curl -L https://github.com/docker/compose/releases/download/1.25.1-rc1/docker-compose-`uname -s`-`uname -m` -o /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose
service docker start
```

### Raidbot installation:

Go to the directory where you want to install the raidbot. **Make sure to not expose this directory to the internet as it contains sensitive informations!**

You can just copy & paste this to the shell to prepare your docker-deployment:
```
mkdir raidbot-docker && \
cd raidbot-docker && \
mkdir sql && \
mkdir tg-logs && \
touch tg-logs/dev-raid-bot-cleanup.log && \
touch tg-logs/dev-raid-bot.log && \
git clone --recurse-submodules https://github.com/florianbecker/PokemonRaidBot.git && \
cp PokemonRaidBot/sql/pokemon-raid-bot.sql sql/01_pokemon-raid-bot.sql && \
cp PokemonRaidBot/sql/raid-boss-pokedex.sql sql/02_raid-boss-pokedex.sql && \
cp PokemonRaidBot/sql/gohub-raid-boss-pokedex.sql sql/03_gohub-raid-boss-pokedex.sql && \
cp PokemonRaidBot/docker-compose.yml .
```

This will:
1. Create a directory `raidbot-docker`.
2. Create a directory `sql`.
3. Create a directory `tg-logs` and create the two logfiles in it.
4. Clone the Raidbot Repository including the telegram-core.
5. Copy and rename the required SQL files.
6. Copy the docker-compose file.

Your directory should now look like this:

```
├── PokemonRaidBot
│   └── The normal RaidBot Repository
├── raidbot-db
├── sql
│   ├── 01_pokemon-raid-bot.sql
│   ├── 02_raid-boss-pokedex.sql
│   └── 03_gohub-raid-boss-pokedex.sql
├── tg-logs
│   ├── dev-raid-bot-cleanup.log
│   └── dev-raid-bot.log
└── docker-compose.yml
```

- Check the `docker-compose.yml` for adjusting it to your needs. Change the two `CRON_COMMAND` variables and replace `changeme` with either your API key or your cleanup secret. Make sure to also edit the DB credentials at the bottom of the file. Basically, replace every `changeme`.
- Now setup the Raidbot as usual. Change the `config.json` to your needs (remeber to use `raidbot-db` in the `DB_HOST` value field). Maybe modify stuff in `config/telegram.json`, `custom/` or `access/`, etc.

Change the file permissions:

```
find . -type d -exec chmod 755 {} \; && \
find . -type f -exec chmod 644 {} \; && \
chown -R 33:33 tg-logs/ && \
chmod 0600 PokemonRaidBot/config/config.json
```

To deploy the Raidbot and Database containers, you just need to build the Raidbot container and start them by running:

```
docker-compose up --build -d
```

Look at the logs with:

```
docker-compose logs -f raidbot

docker-compose logs -f raidbot-db
```

Make sure that everything is running correctly by inspecting the logs.

### SSL with Docker

The next step is to add some sort of SSL layer on top. There are dozens of ways to do that, but the recommended ways are ether a classic reverse proxy on the normal Hostsytem or adding a reverse proxy container (like the [companion container](https://github.com/JrCs/docker-letsencrypt-nginx-proxy-companion) or using [traefik](https://docs.traefik.io/)) to the docker-stack.yml. The Raidbot container is exposed at port `8088` by default.

### Useful Docker commands

List all running Docker containers:
```
docker ps -a
```

Accessing the Database (remember to change `changeme`):

```
docker exec -it raidbot-docker_raidbot-db_1 mysql -uchangeme -pchangeme raidbot
```

Restart all Docker containers:

```
docker container restart $(docker container ls -aq)
```

Stop and Delete one Docker container:

```
docker rm -f raidbot
```

Or the database container as well:

```
docker rm -f raidbot raidbot_db
```

### Using getPokemonIcons.php with Docker

Connect to the running Raidbot container and run the php command:

```
docker exec -it raidbot-docker_raidbot_1 php getPokemonIcons.php
```

## Config

Inside the config folder, copy the example config.json.example to your own config.json and edit the values (explained further). The example only contains the most common values people want to change, refer to defaults-config.json for all available values. Any value set in config.json will override the default from defaults-config.json.

Don't forget to change the file permissions of your config file to 0600 (e.g. `chmod 0600 config.json`) afterwards. You need to change the ownerchip of all files to the webserver user - otherwise the config is not readable. Normally this: `chown www-data:www-data -R *`

Some values are missing as the bot has default values. If you like to change those, you need to add and define them in your config.json file, e.g. `"DDOS_MAXIMUM":"10"`.

### Referring to groups, channels and users
The most reliable and secure way to refer to individuals, channels, groups and supergroups private or public is with a numeric ID.
While in some contexts public groups, channels and supergroups could use their public id (e.g. @PublicGroup) this wouldn't work for every call.

*Example IDs:*

| Type       | Example        | Notes                                                  |
|------------|----------------|--------------------------------------------------------|
| User       | 12345678       | Positive number, no set length                         |
| Group      | -998877665     | Negative number, no set length                         |
| Channel    | -1001122334455 | Negative number padded to 13 characters (prepend -100) |
| Supergroup | -1001122334455 | Negative number padded to 13 characters (prepend -100) |

#### Finding public IDs
Counterintuitively getting the ID of a public user, group, channel or supergroup is more difficult since most ways will replace the @name where a numeric ID would be visible. These methods will also work for private versions but will cause spam to the group. The easiest way is via @RawDataBot:

**Group or Supergroup:**

Add @RawDataBot the the group which will cause it to report data about the group. Use the id value as-is,
it's already prepended to the right length.
```
{
*snip*
        "chat": {
            "id": -1002233445566,
            "title": "Pokemon Group",
            "username": "PokemonGroup",
            "type": "supergroup"
        },
*snip*
```

**Channel or user:**

Forward a message to @RawDataBot which will get you a data package:

*Channel:*

```
*snip*
        "forward_from_chat": {
            "id": -1001122334455,
            "title": "Pokemon Channel",
            "username": "PokemonChannel",
            "type": "channel"
        },
```

*User:*

```
*snip*
        "forward_from": {
            "id": 112233445,
            "is_bot": false,
            "first_name": "Edwin",
            "last_name": "Example"
        },
*snip*
```

#### Finding private IDs
The above method works fine for private groups as well but if you don't want to spam the group there are less intrusive ways.
One of the least intrusive ways is to use https://web.telegram.org to log in and select the entity in question in your chat list.
The URL displayed in your browser can be converted into a usable ID.

**Examples:**

*Private user:*
```
https://web.telegram.org/#/im?p=u12345678_1122334455667788990011
=> use 12345678
```

*Private group:*
```
https://web.telegram.org/#/im?p=g998877665
=> use -998877665
```

*Private channel:*
```
https://web.telegram.org/#/im?p=c112233445566_1122334455667788990
=> use -1112233445566 (notice the extra 1 prepended to pad to 13)
```

*Private supergroup:*
```
https://web.telegram.org/#/im?p=s1122334455_11223344556677889900
=> use -1001122334455 (notice the extra 100 prepended to pad to 13)
```

#### Which group type should I use? / How do I make a group a Supergroup
- Some features will only work with Supergroups (and Channels) since they enable more features needed for example for automatic cleanup. If in doubt use Supergroups.
- Every created group starts out as a normal Group but once you enable certain features it will get converted automatically to a Supergroup. For example enabling new users to see message history will convert it!
- Once a group has been converted to a Supergroup it cannot go back to a normal Group, even if you change back the option that caused it to convert.
- Be aware that the group ID will change completely when the group gets converted so you'll need to find it again!


### Database connection

Enter the details for the database connection to the config.json file via `DB_HOST`, `DB_NAME`, `DB_USER` and `DB_PASSWORD`.

### General config and log files

Set `DEBUG` to true, to enable the debug logfile.

Set `DEBUG_LOGFILE` to the location of the logfile, e.g. /var/log/tg-bots/dev-raid-bot.log. Make sure to create the log dir, e.g. /var/log/tg-bots/ and set it writeable by webserver.

Set `APIKEY_HASH` to the hashed value of your bot token (preferably lowercase) using a hash generator, e.g. https://www.miniwebtool.com/sha512-hash-generator/

Set `DDOS_MAXIMUM` to the amount of callback queries each user is allowed to do each minute. If the amount is reached any further callback query is rejected by the DDOS check. Default value: 10.

Set `BRIDGE_MODE` to true when you're using the PokemonBotBridge. If you're not using the PokemonBotBridge the default value of false is used. PokemonBotBridge: https://github.com/florianbecker/PokemonBotBridge


## Installing the Webhook

To tie the bot installation to the Telegram bot API it needs to be submitted as a webhook. This is done by visiting the
provided webhook.html form in a web browser.

Navigate to https://yourdomain.com/botdir/webhook.html and fill in the form.
After submit success test your bot by sending it a /help command. If this does not work, see [Debugging](#debugging)

## More config options

### Proxy

Set `CURL_USEPROXY` with a value of `true` in case you are running the bot behind a proxy server.

Set `CURL_PROXYSERVER` to the proxy server address and port, for example:

```
"CURL_USEPROXY":"false",
"CURL_PROXYSERVER":"http://your.proxyserver.com:8080",
```

Authentication against the proxy server by username and password is currently not supported!

### Languages

You can set several languages for the bot. Available languages are (A-Z):
 - DE (German)
 - EN (English)
 - FI (Finnish)
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

### Timezone and Google maps API

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

### Raid creation options

There are several options to customize the creation of raid polls:

Set `RAID_VIA_LOCATION` to true to allow raid creation from a location shared with the bot.

Set `RAID_EGG_DURATION` to the maximum amount of minutes a user can select for the egg hatching phase.

Set `RAID_DURATION` to the maximum amount of minutes a user can select as raid duration for already running/active raids.

Set `RAID_HOUR` to true to enable the raid hour. Enabling the raid hour superseds the normal raid duration. Note that the raid hour takes precedence over the raid day. Make sure to disable the raid hour to get the raid day.

Set `RAID_HOUR_DURATION` to the maximum amount of minutes a user can select as raid duration if the `RAID_HOUR` is enabled. Per default max. 60 minutes.

Set `RAID_HOUR_CREATION_LIMIT` to the maximum amount of raids a user can create if the `RAID_HOUR` is enabled. Per default 1 raid.

Set `RAID_DAY` to true to enable the raid day. Enabling the raid day superseds the normal raid duration. Note that the raid hour takes precedence over the raid day. Make sure to disable the raid hour to get the raid day.

Set `RAID_DAY_DURATION` to the maximum amount of minutes a user can select as raid duration if the `RAID_DAY` is enabled. Per default max. 180 minutes.

Set `RAID_DAY_CREATION_LIMIT` to the maximum amount of raids a user can create if the `RAID_DAY` is enabled. Per default 1 raid.

Set `RAID_DURATION_CLOCK_STYLE` to customize the default style for the raid start time selection. Set to true, the bot will show the time in clocktime style, e.g. "18:34" as selection when the raid will start. Set to false the bot will show the time until the raid starts in minutes, e.g. "0:16" (similar to the countdown in the gyms). Users can switch between both style in the raid creation process.

Set `RAID_CUSTOM_GYM_LETTERS` to further split gyms by their first letter. For example if you have a lot of gyms starting with 'St' as there are a lot of churches like St. Helen, St. Jospeh, etc. in your area and the gym list under the letter 'S' is too long, you can tell the bot to put the gyms starting with 'St' under 'St' and exclude them from the letter 'S'. There is no limitation in length, so even 'Berlin' would work to split gyms, but the recommendation is to use as less chars as possible to split the gyms. You can add multiple custom gym letters, just separate them by comma. Example: `"RAID_CUSTOM_GYM_LETTERS":"Ber,Sch,St,Wi"`

Set `RAID_EXCLUDE_EXRAID_DUPLICATION` to true to exclude ex-raids from the duplication check which allows to create an ex-raid and a normal raid.

### Raid time customization

There are several options to configure the times related to the raid polls:

Set `RAID_LOCATION` to true to send back the location as message in addition to the raid poll.

Set `RAID_SLOTS` to the amount of minutes which shall be between the voting slots.

Set `RAID_DIRECT_START` to the first slot directly after hatching.

Set `RAID_FIRST_START` to the amount of minutes required to add an earlier first start time before the first regular voting slot.

Set `RAID_LAST_START` to the minutes for the last start option before the a raid ends.

Set `RAID_ANYTIME` to true to allow attendance of the raid at any time. If set to false, users have to pick a specific time.

### Raid poll design and layout

There are several options to configure the design and layout of the raid polls:

Set `RAID_VOTE_ICONS` to true to show the icons for the status vote buttons.

Set `RAID_VOTE_TEXT` to true to show the text for the status vote buttons.

Set `RAID_LATE_MSG` to true to enable the message hinting that some participants are late.

Set `RAID_LATE_TIME` to the amount of minutes the local community will may be wait for the late participants.

Set `RAID_POLL_HIDE_USERS_TIME` to the amount of minutes when a previous raid slot should be hidden. For example if there are 2 slots, 18:00 and 18:15, and you set the time to 10 minutes the first group of participants from 18:00 will be hidden once we reach 18:10. This helps to keep the raid poll message smaller and clearer if there are multiple groups. Set the value to 0 to always show all slots.

Set `RAID_POLL_UI_ORDER` to the customize the order of the buttons rows for the raid polls. The default is 'extra,teamlvl,time,pokemon,status' but can be changed to any other order, e.g. 'time,pokemon,extra,status,teamlvl'.

Set `RAID_POLL_HIDE_BUTTONS_RAID_LEVEL` to the raid levels (1-5 and X) for which the voting buttons under the raid poll should be hidden. For example a level 1 raid can be done by a single player, but it is maybe interesting to be shared as some pokemon are only available in raids.

Set `RAID_POLL_HIDE_BUTTONS_POKEMON` to the pokedex IDs (e.g. '1' for Bulbasaur) or pokedex ID and form combined by a minus sign (e.g. '386-normal' for Deoxys Normal form or '386-attack' for Deoxys Attack form) for which the voting buttons under the raid poll should be hidden.

Set `RAID_POLL_HIDE_BUTTONS_TEAM_LVL` to true to hide the team and level+/- buttons below each raid poll. That users are still able to set their team and level when the buttons are hidden, take a look at the '/trainer' command.

Set `RAID_POLL_HIDE_DONE_CANCELED` to true to hide the users which are done with the raid or canceled and do not longer attend the raid.

Set `RAID_EX_GYM_MARKER` to set the marker for ex-raid gyms. You can use a predefined icon using the value 'icon' or any own marker, e.g. 'EX'.

Set `RAID_CREATION_EX_GYM_MARKER` to true to show the marker for ex-raid gyms during raid creation.

### Raid Picture mode

To enable raid announcements as images set `RAID_PICTURE` to true and set the url in `RAID_PICTURE_URL` to the location of raidpicture.php.

You also need to get the Pokemon sprites from known sources and put them in either images/pokemon/ or the images/pokemon_REPO-OWNER/ folder. The images/pokemon/ directory needs to be created manually, the images/pokemon_REPO-OWNER/ folders will be created automatically when by running the special download script mentioned below.

Pokemon Icons / Sprites:
Link: https://github.com/PokeMiners/pogo_assets/tree/master/Images/Pokemon%20-%20256x256
Link: https://github.com/ZeChrales/PogoAssets/tree/master/pokemon_icons

To easily download you can use a special download script on the CLI: `php getPokemonIcons.php`

The script can also be triggered via command line arguments, eg. as cron job.
Argument 1 is the source (either `ZeChrales` or `PokeMiners`), so eg. `php getPokemonIcons.php PokeMiners`
Argument 2 is the destination folder where the images shall be saved. To save the sprites to a different location outside the actual PokemonRaidBot directory, just add that second argument, eg. `php getPokemonIcons.php PokeMiners /var/www/html/pokemon_sprites/`

If you're sharing the pokemon icons with other bots or applications and therefore placed them outside the PokemonRaidBot directory, you can easily replace the images/pokemon with a softlink to that directory. It won't interfere with git status as we adjusted the .gitignore accordingly.

Example to replace the with a symbolic link:
```
cd /var/www/html/PokemonRaidBot/images/
rm -rf pokemon/
ln -sf /var/www/html/pokemon_sprites pokemon
``` 

#### Font support

If we included support for every unicode glyph under the sun the fonts alone would be over 1GB, thus we only ship the base Noto Sans fonts. If you need support for example for CJK glyphs, download a better suited font from [google.com/get/noto](https://www.google.com/get/noto/), place the `Regular` & `Bold` font files in `fonts/` and override them in `config/config.json`, for example:
```
  "RAID_PICTURE_FONT_GYM": "NotoSansCJKjp-Bold.otf",
  "RAID_PICTURE_FONT_EX_GYM": "NotoSansCJKjp-Regular.otf",
  "RAID_PICTURE_FONT_TEXT": "NotoSansCJKjp-Regular.otf"
```

Set `RAID_PICTURE_HIDE_LEVEL` to the raid levels (1-5 and X) for which the raid message is shared without the picture even if `RAID_PICTURE` is set to true.

Set `RAID_PICTURE_HIDE_POKEMON` to the pokedex IDs (e.g. '1' for Bulbasaur) or pokedex ID and form combined by a minus sign (e.g. '386-normal' for Deoxys Normal form or '386-attack' for Deoxys Attack form) for which the raid message is shared without the picture even if `RAID_PICTURE` is set to true.

Set `RAID_PICTURE_BG_COLOR` to an RGB value to specify the background color of the raid picture.

Set `RAID_PICTURE_TEXT_COLOR` to an RGB value to specify the text color of the raid picture.

Set `RAID_PICTURE_STORE_GYM_IMAGES_LOCALLY` to `true` if you want to download and store gym photos in `images/gyms/` instead of fetching them from the cloud every time an image is created.

Set `RAID_PICTURE_ICONS_WHITE` to true to use white weather icons for the raid picture. Especially useful when you defined a dark background color.

Set `RAID_PICTURE_FILE_FORMAT` to either `gif`, `jpeg`, `jpg` or `png` to specify the output format of the raid picture.

Set `RAID_DEFAULT_PICTURE` to the url of a default gym picture in case no gym image url is stored in the database for a gym.

### Portal Import

Set `PORTAL_IMPORT` to `true` to enable the possibility to import portals from Telegram Ingress Bots.

Set `RAID_PICTURE_STORE_GYM_IMAGES_LOCALLY` to `true` to download the portal image from Telegram Ingress Bots. When set to `false` the URL of the portal image is stored in the database.


### Raid sharing

You can share raid polls with any chat in Telegram via a share button.

Sharing raid polls can be restricted, so only specific chats/users can be allowed to share a raid poll - take a look at the permission system!

With a predefined list `SHARE_CHATS` you can specify the chats which should appear as buttons for sharing raid polls.

You can define different chats for specific raid levels using `SHARE_CHATS_LEVEL_` plus the raid level too. Raid levels can be 'X', '5', '4', '3', '2' or '1'.

For the ID of a chat either forward a message from the chat to a bot like @RawDataBot, @getidsbot or search the web for another method ;)

Examples:

#### Sharing all raids to two chats

Predefine sharing all raids to the chats -100111222333 and -100444555666

`"SHARE_CHATS":"-100111222333,-100444555666"`

#### Sharing split to channels by level

Predefine sharing all raids to the chats -100111222333 and -100444555666, except level 5 raids which will be shared to the chat -100999666333

`"SHARE_CHATS":"-100111222333,-100444555666"`
`"SHARE_CHATS_LEVEL_5":"-100444555666"`

#### Raids from Webhook

You can receive Raids from a mapping system such as MAD via Webhook.
For that you need to setup
`"WEBHOOK_CHATS":"-100444555666"`
or by Raidlevel `"WEBHOOK_CHATS_LEVEL_5":"-100444555666"`
All raids will be published in these chats.

#### Filter Raids from Webhook / geoconfig.json

If you have multiple Chats for different Areas you can setup them in
`"WEBHOOK_CHATS_LEVEL_5_0":"-100444555666"` matching with your configuration in the geoconfig.json.
Go to http://geo.jasparke.net/ and create an Area (Geofence), where your gyms are.
When you are finished, click on 'exp' and save the coordinates to your geoconfig.json. And for the ID 0 you use "WEBHOOK_CHATS_LEVEL_5_0", for ID 1 "WEBHOOK_CHATS_LEVEL_5_1" and so on.
The raids will only be posted into the defined chats.

#### Extended Raid-Sharing

If you are using multiple Channel, you can setup one Channel as Main-Channel "SHARE_CHATS_AFTER_ATTENDANCE":"-100444555666" and on votes in different Channel, the Raid will be shared to your Main-Channel. Activate this function with "SHARE_AFTER_ATTENDANCE":true
This is important for Raids from Webhooks. All Raids were posted to one Channel, which can be muted to the users. But if someone votes for a raid, this raid will be posted to a unmuted channel, where all others get a notification.

## Trainer settings

The command '/trainer' allows users of the bot to change their trainer data like team and level. It is also used to share a message that allows trainers to modify their trainer data like team and level to another chat. To share this message, every chat specified in the raid sharing list like SHARE_CHATS are used.

With `TRAINER_CHATS` you can specify additional chats which should appear as buttons too for sharing the trainer message.

Set `TRAINER_BUTTONS_TOGGLE` to true to enable the toggle which shows/hides the team and level+/- buttons under the trainer message. To disable the toggle button and always show the team and level+/- buttons set it to false.

Add additional chats -100999555111 and -100888444222 to share the trainer message

`"TRAINER_CHATS":"-100999555111,-100888444222"`

## Raid overview

The bot allows you to list all raids which got shared with one or more chats as a single raid overview message to quickly get an overview of all raids which are currently running and got shared in each chat. You can view and share raid overviews via the /overview command - but only if some raids are currently active and if these active raids got shared to any chats!

To keep this raid overview always up to date when you have it e.g. pinned inside your raid channel, you can setup a cronjob that updates the message by calling the overview_refresh module.

You can either refresh all shared raid overview messages by calling the following curl command:

`curl -k -d '{"callback_query":{"data":"0:overview_refresh:0"}}' https://localhost/botdir/index.php?apikey=111111111:AABBccddEEFFggHHiijjKKLLmmnnOOPPqq`

To just refresh the raid overview message you've shared with a specific chat (e.g. -100112233445) use:

`curl -k -d '{"callback_query":{"data":"0:overview_refresh:-100112233445"}}' https://localhost/botdir/index.php?apikey=111111111:AABBccddEEFFggHHiijjKKLLmmnnOOPPqq`

To delete a shared raid overview message you can use the `/overview` command too.

With the `RAID_PIN_MESSAGE` in the config you can add a custom message to the bottom of the raid overview messages.

## Raid Map

Set `MAP_URL` to the URL of the PokemonBotMap to add it to each raid poll. PokemonBotMap: https://github.com/florianbecker/PokemonBotMap

## Cleanup

The bot features an automatic cleanup of telegram raid poll messages as well as cleanup of the database (attendance and raids tables).

To activate cleanup you need to [make sure your groups are Supergroups or Channels](#which-group-type-should-i-use--how-do-i-make-a-group-a-supergroup), enable cleanup in the config and create a cronjob to trigger the cleanup process.

1. Set the `CLEANUP` in the config to `true` and define a cleanup secret/passphrase under `CLEANUP_SECRET`.
2. Activate the cleanup of Telegram messages and/or the database for raids by setting `CLEANUP_TELEGRAM` / `CLEANUP_DATABASE` to true.
   - **Do note** that `CLEANUP_TELEGRAM` will not work in groups that are not Supergroups or Channels!
3. Specify the amount of minutes which need to pass by after raid has ended before the bot executes the cleanup.
   - Times are in minutes in `CLEANUP_TIME_TG` for telegram cleanup and `CLEANUP_TIME_DB` for database cleanup.
   - The value for the minutes of the database cleanup `CLEANUP_TIME_DB` must be greater than then one for telegram cleanup `CLEANUP_TIME_TG`. Otherwise cleanup will do nothing and exit due to misconfiguration!
4. Finally set up a cronjob to trigger the cleanup. You can also trigger telegram / database cleanup per cronjob: For no cleanup use 0, for cleanup use 1 and to use your config file use 2 or leave "telegram" and "database" out of the request data array.
   - See the examples below for curl based calls. Any HTTP client capable of a POST request will work.

### Examples

Make sure to replace the URL with yours!

- Cronjob using cleanup values from config.json:
```
curl -k -d '{"cleanup":{"secret":"your-cleanup-secret/passphrase"}}' https://localhost/index.php?apikey=111111111:AABBCCDDEEFFGGHHIIJJKKLLMMNNOOPP123`
```

- Explicitly use config values:
```
curl -k -d '{"cleanup":{"secret":"your-cleanup-secret/passphrase","telegram":"2","database":"2"}}' https://localhost/index.php?apikey=111111111:AABBCCDDEEFFGGHHIIJJKKLLMMNNOOPP123
```

- Clean up telegram raid poll messages only: telegram = 1 and database = 0
```
curl -k -d '{"cleanup":{"secret":"your-cleanup-secret/passphrase","telegram":"1","database":"0"}}' https://localhost/index.php?apikey=111111111:AABBCCDDEEFFGGHHIIJJKKLLMMNNOOPP123
```

- Clean up telegram raid poll messages and database: telegram = 1 and database = 1
```
curl -k -d '{"cleanup":{"secret":"your-cleanup-secret/passphrase","telegram":"1","database":"1"}}' https://localhost/index.php?apikey=111111111:AABBCCDDEEFFGGHHIIJJKKLLMMNNOOPP123
```

- Clean up database and maybe telegram raid poll messages (when specified in config): telegram = 2 and database = 1
```
curl -k -d '{"cleanup":{"secret":"your-cleanup-secret/passphrase","telegram":"2","database":"1"}}' https://localhost/index.php?apikey=111111111:AABBCCDDEEFFGGHHIIJJKKLLMMNNOOPP123
```

## Access permissions

### Public access

When no telegram id, group, supergroup or channel is specified in `BOT_ADMINS` the bot will allow everyone to use it (public access).

Example for public access: `"BOT_ADMINS":""`

### Access and permissions

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

### Permissions overview

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
|            | List all raids `/list` and `/listall`                            | `list`                                   |
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
| Trainer    | Set trainer data `/trainer`                                      | `trainer`                                |
|            | Share trainer data message `/trainer`                            | `trainer-share`                          |
|            | Delete trainer data message `/trainer`                           | `trainer-delete`                         |
|            |                                                                  |                                          |
| Portal     | Import portals via inline search from other bots                 | `portal-import`                          |
|            |                                                                  |                                          |
| Pokedex    | Manage raid pokemon `/pokedex`                                   | `pokedex`                                |
|            |                                                                  |                                          |
| Help       | Show help `/help`                                                | `help`                                   |


#### Examples

*Allow the user 111555999 to create raid polls and share them to the predefined chat list*

Access file: `access\access111555999`

Content of the access file, so the actual permissions:
```
access-bot
create
share-own
```

*Allow the creator and the admins of the channel -100224466889 to create raid polls as well as sharing raid polls created by their own or others to the predefined chat list or any other chat*

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

# Bot usage

## Send your location to the bot

The bot will guide you through the creation of a raid poll based on the settings in the config file.

In case of a raid poll the bot will ask you for the raid level, the pokemon raid boss, the time until the raids starts and the time left for the raid. Afterwards you can set the gym name and gym team by using the /gym and /team commands.

## Using inline search of @PortalMapBot or @Ingressportalbot

You can add new gyms to the bot using the inline search of one of the bots mentioned above. Just search for a portal name, e.g. `Brandenburger Tor`, and select one of the portals shown as result of your search.

On selection the portal information will get posted as a normal message and detected, so a new gym is automatically created from the portal info in that message.

In case the portal is already in your gym list / database, it will get updated with the new info (latitude, longitude and address) from the message.

Example: `@PortalMapBot Brandenburger Tor`

## Command: /start

The bot will guide you through the creation of the raid poll by asking you for the gym, raid level, the pokemon raid boss, the time until the raid starts and the time left for the raid. Afterwards you can set the gym team by using the /team command.

To search for the gym by partial or full name you can use `/start gym name`, e.g. `/start Brandenburger Tor`

Send `/start` to the bot to create a raid by gym selection:

![Command: /start](/screens/command-start.png?raw=true "Command: /start")

Select the gym via the first letter:

![Command: /start](/screens/commands-start-select-gym-first-letter.png?raw=true "Command: /start")
![Command: /start](/screens/commands-start-select-gym-letter-d.png?raw=true "Command: /start")

Select the raid level and raid boss:

![Command: /start](/screens/commands-start-select-raid-level.png?raw=true "Command: /start")
![Command: /start](/screens/commands-start-select-raid-boss.png?raw=true "Command: /start")

Select the start time (clock time or minutes) and the duration of the raid:

![Command: /start](/screens/commands-start-select-starttime-clock.png?raw=true "Command: /start")
![Command: /start](/screens/commands-start-select-starttime-minutes.png?raw=true "Command: /start")
![Command: /start](/screens/commands-start-select-raid-duration.png?raw=true "Command: /start")

Raid poll is created. Delete or share it:

![Command: /start](/screens/commands-start-raid-saved.png?raw=true "Command: /start")

## Command: /help

The bot will give a personal help based on the permissions you have to access and use it.


## Command: /raid

Create a new raid by gomap-notifier or other input. The raid command expects 8 parameters and an optional 9th parameter as input seperated by comma.

Additionally the raid command checks for existing raids, so sending the same command multiple times to the bot will result in an update of the pokemon raid boss and gym team and won't create duplicate raids.

Parameters: Pokemon raid boss id and form (combine with minus), latitude, longitude, raid duration in minutes, gym team, gym name, district or street, district or street, raid pre-hatch egg countdown in minutes (optional)

Example input: `/raid 244-normal,52.516263,13.377755,45,Mystic,Brandenburger Tor,Pariser Platz 1, 10117 Berlin,30`


## Command: /pokemon

Update pokemon of an existing raid poll. With this command you can change the pokemon raid boss from e.g. "Level 5 Egg" to "Lugia" once the egg has hatched.

Based on your access to the bot, you may can only change the pokemon raid boss of raid polls you created yourself and cannot modify the pokemon of raid polls from other bot users.


## Command: /pokedex

Show and update any pokemon raid boss. You can change the raid level (select raid level 0 to disable a raid boss), pokemon CP values and weather information of any pokemon raid boss.

There is also a possibility to import the raids bosses from Pokebattler and disable all raid bosses for all or just a specific raid level which makes raid boss management pretty easy. By default the import will disable eggs for levels that only contain 1 raid boss. To disable this set `POKEBATTLER_IMPORT_DISABLE_REDUNDANT_EGGS` to `false`.

To quickly get to a specific pokemon raid boss, you can use the /pokedex command with the local name of the pokemon to get a list of it's all formes. A few examples:

| Search term  			| Response                                												       |
|-----------------------|----------------------------------------------------------------------------------------------|
| `/pokedex Deoxys`     | `Deoxys`<br/>`Deoxys Attack`<br/>`Deoxys Defense`<br/>`Deoxys Speed`                         |
| `/pokedex Charizard`  | `Charizard`<br/>`Charizard Copy 2019` (clone)<br/>`Charizard Mega X`<br/>`Charizard Mega Y`  |
| `/pokedex Knogga`     | `Knogga`<br/>`Knogga Alola`	     														   |

User's local language is fetched from their Telegram settings.

Beside your local language the bot always is looking at the English language as a fallback.

Manage pokemons / raid bosses via the `/pokedex` command:

![Command: /pokedex](/screens/command-pokedex.png?raw=true "Command: /pokedex")

All raid bosses:

![Command: /pokedex](/screens/commands-pokedex-all-raid-bosses.png?raw=true "Command: /pokedex")

Select and edit a specific pokemon / raid boss:

![Command: /pokedex](/screens/commands-pokedex-list-raid-boss-pokemon.png?raw=true "Command: /pokedex")
![Command: /pokedex](/screens/commands-pokedex-edit-raid-boss-pokemon.png?raw=true "Command: /pokedex")

Edit the raid level:

![Command: /pokedex](/screens/commands-pokedex-set-raid-level.png?raw=true "Command: /pokedex")
![Command: /pokedex](/screens/commands-pokedex-saved-new-raid-level.png?raw=true "Command: /pokedex")

Edit the CP values, e.g. Max CP:

![Command: /pokedex](/screens/commands-pokedex-enter-max-cp.png?raw=true "Command: /pokedex")
![Command: /pokedex](/screens/commands-pokedex-save-max-cp.png?raw=true "Command: /pokedex")
![Command: /pokedex](/screens/commands-pokedex-saved-new-max-cp.png?raw=true "Command: /pokedex")

Edit the weather:

![Command: /pokedex](/screens/commands-pokedex-set-weather.png?raw=true "Command: /pokedex")


## Command: /list

The bot will allow you to get a list of the last 12 active raids and re-share or delete them.

In case more than 12 active raids are happening, the bot will automatically call the /listall command

List existing raid polls with the `/list` command:

![Command: /list](/screens/command-list.png?raw=true "Command: /list")

![Command: /list](/screens/commands-list-active-raids.png?raw=true "Command: /list")


## Command: /listall

The bot will allow you to get all active raids and re-share or delete them. The raids are grouped by gyms and their first letter or custom letters.

## Command: /overview

Share and delete the raid overview message.

Share overview message with all raids shared to channel "Chat-Name" to the channel:

![Command: /overview](/screens/commands-list-share-overview.png?raw=true "Command: /overview")

Delete the shared overview message:

![Command: /overview](/screens/commands-list-delete-overview.png?raw=true "Command: /overview")

## Command: /delete

Delete an existing raid poll. With this command you can delete a raid poll from telegram and the database. Use with care!

Based on your access to the bot, you may can only delete raid polls you created yourself and cannot delete raid polls from other bot users.

Delete an existing raid poll with the `/delete` command:

![Command: /delete](/screens/command-delete.png?raw=true "Command: /delete")
![Command: /delete](/screens/commands-delete-raid-deleted.png?raw=true "Command: /delete")

## Command: /team

The bot will set the team to Mystic/Valor/Instinct for the last created raid based on your input.

Example input: `/team Mystic`

## Command: /trainer

The bot will give you a list of chats to share the trainer message which allows users to set team and level+/- data. You can also delete the shared trainer messages via the `/trainer` command.

## Command: /gym

The bot will show the details of each gym. Additionally you can change the extended gym details to hide/show gyms under `/start` as well as mark/un-mark them as ex-raid gym.

Example input: `/gym`


## Command: /addgym

The bot will add a gym under the coordinates you're submitting. First latitude, then longitude. The gym is added under the name '#YourTelegramID' (e.g. '#111555777') and you need to change the name afterwards using the `/gymname` command. You cannot submit a second gym unless you changed the name of the first gym. In case you submit a second gym without changing the name of the previously submitted gym, the first gym coordinates will be overwritten!

Example input: `/addgym 52.5145434,13.3501189`


## Command: /gymname

The bot will set the name of gym to your input. If you submitted a gym via location sharing you can use it without an id. Otherwise the id of the gym is required.

Example input: `/gymname Siegessäule`

Example input with gym id: `/gymname 34, Siegessäule`


## Command: /gymaddress

The bot will set the address of gym to your input. The id of the gym is required. You can delete the gym address using the keyword 'reset'.

Example input: `/gymaddress 34, Großer Stern, 10557 Berlin`

Example input to delete the gym address: `/gymaddress 34, reset`


## Command: /gymgps

The bot will set the gps coordinates of gym to your input. The id of the gym is required.

Example input: `/gymgps 34, 52.5145434,13.3501189`


## Command: /gymnote

The bot will set the note for gym to your input. The id of the gym is required. You can delete the gym note using the keyword 'reset'.

Example input: `/gymnote 34, Meeting point: Behind the buildung`

Example input to delete the gym note: `/gymnote 34, reset`


## Command: /deletegym

The bot will show all gyms. Select a gym and confirm the deletion to remove it from the database.

Example input: `/deletegym`


# Debugging

Check your bot logfile and other related log files, e.g. apache/httpd log, php log, and so on.

# Updates

The bot has a version system and checks for updates to the database automatically.

The bot will send a message to the MAINTAINER_ID when an upgrade is required. In case the MAINTAINER_ID is not specified an error message is written to the error log of your webserver.

Required SQL upgrades files can be found under the `sql/upgrade` folder and are applied automatically by default.

After any upgrade you need to make sure to change the bot version in your config.json as that version is used for comparison against the latest bot version in the `VERSION` file.

Updates to the config file are NOT checked automatically. Therefore always check for changes to the config.json.example and add any new config variables you want to override to your own config.json. Most new variables should get added to defaults-config.json so you'll get the new default automatically on update.


# Config reference

* For default values, see `config/defaults-config.json`.
* Most values are strings.
* Boolean values should use `true` & `false`, not strings.
* Any lists are given as a comma separated string.
* For raid levels, valid values are 1,2,3,4,5,X where X stands for Ex-Raid.
* If your config is not valid json, the bot will not work. Use a jslinter if in doubt.

| Option | Description |
|--------|------------ |
| APIKEY_HASH | Telegram API key hashed in sha256 |
| BOT_ADMINS| List of admin identifiers (comma separated telegram ids) |
| BOT_ID| One letter ID for the bot used in debug logging. Mostly useful if you run multiple. |
| BOT_NAME| Name of the bot. |
| BRIDGE_MODE| Bool, whether to enable bridge mode. |
| CLEANUP_DATABASE| Bool, whether to clean up finished raids from DB if cleanup is enabled. |
| CLEANUP_LOG | Log cleanup operations in a separate file, quite verbose! |
| CLEANUP_LOGFILE| Full path to Log file where cleanup operations are logged. |
| CLEANUP_SECRET| Plain text passphrase to protect cleanup calls. |
| CLEANUP_TELEGRAM| Bool, whether to clean up raid polls posted by the bot if cleanup is enabled. |
| CLEANUP_TIME_DB| In minutes how old DB entries (past raid end-time) need to be to be eligible for cleanup |
| CLEANUP_TIME_TG| In minutes how old TG posts (past raid end-time) need to be to be eligible for cleanup |
| CLEANUP| Bool, whether to accept cleanup calls |
| CURL_PROXYSERVER| Address of curl proxy |
| CURL_USEPROXY| Bool, enable curl via proxy |
| DB_HOST | Host or ip address of MySQL server |
| DB_NAME | Name of DB |
| DB_PASSWORD | Password of dedicated RaidBot DB user |
| DB_USER | Username of dedicated RaidBot DB user |
| DDOS_MAXIMUM | ? |
| DEBUG | Output helpful debugging messages to `DEBUG_LOGFILE` |
| DEBUG_LOGFILE | Full path to debug logfile |
| DEBUG_INCOMING | Also log details on incoming webhook data to separate file, quite verbose! |
| DEBUG_INCOMING_LOGFILE | Full path to incoming data debug logfile|
| DEBUG_SQL | Also log details on DB queries to separate file, quite verbose! |
| DEBUG_SQL_LOGFILE | Full path to SQL debug logfile|
| DEFAULTS_WARNING | json files don't support comments, this is just a comment warning you not to edit defaults. |
| LANGUAGE_PRIVATE| Language to use in private messages. Leave empty to infer language from users Telegram language |
| LANGUAGE_PUBLIC| Language to use in groups |
| LOGGING_INFO | Log INFO level messages to the file defined by LOGGING_INFO_LOGFILE. Useful for identifying potential issues. |
| LOGGING_INFO_LOGFILE | Path to logfile. |
| MAINTAINER_ID| Telegram ID of main maintainer |
| MAINTAINER| Name of main maintainer |
| MAPS_API_KEY| Google Maps API key for `MAPS_LOOKUP` |
| MAPS_LOOKUP| Boolean, resolve missing gym addresses via Google Maps |
| MAP_URL| ? |
| POKEBATTLER_IMPORT_DISABLE_REDUNDANT_EGGS| Boolean, when importing Pokedex from Pokebattler, disable creating an Egg raid for any level that only has one boss available. |
| PORTAL_IMPORT| Bool, allow importing gyms via portal import Telegram bots |
| RAID_ANYTIME| Bool, enable a final timeslot for attending at any given time. |
| RAID_AUTOMATIC_ALARM | Bool, sign up every attendee to the raid alarm automatically. They will get private messages of new participants as if they had enabled it themselves on the poll. |
| RAID_CODE_POKEMON | List of Pokemon dex IDs in use for private group codes |
| RAID_CREATION_EX_GYM_MARKER| Highlight gyms eligible for Ex-Raids in raid polls |
| RAID_CUSTOM_GYM_LETTERS| List of custom "letters" to include in gym selector, e.g. "St." or "The" |
| RAID_DAY| Bool, enable the raid day. Raid duration will change to RAID_DAY_DURATION |
| RAID_DAY_DURATION| In minutes, default duration of raids during raid day, currently 180min |
| RAID_DAY_CREATION_LIMIT| In raids, max. raids a user can create if RAID_HOUR is enabled. BOT_ADMINS are not affected |
| RAID_DEFAULT_PICTURE| URL of image to use for raids if the portal photo is unknown. Only relevant for `RAID_PICTURE` |
| RAID_DIRECT_START| Bool, Allow voting for starting raids as soon as it opens |
| RAID_DURATION| In minutes, default duration of raids, currently 45min |
| RAID_DURATION_CLOCK_STYLE| Bool, enable showing the time a raid starts vs. duration until start |
| RAID_EGG_DURATION| In minutes the maximum length of the egg phase a user is allowed to give. |
| RAID_EXCLUDE_EXRAID_DUPLICATION| Bool, true to exclude ex-raids from the duplication check which allows to create an ex-raid and a normal raid at the same gym |
| RAID_EX_GYM_MARKER| Enum, "icon" (default value, a star icon) or a custom text/icon to indicate an ex-raid gym in the raid polls |
| RAID_FIRST_START| In minutes what the earliest timeslot is after egg has opened |
| RAID_HOUR| Bool, enable the raid hour. Raid duration will change to RAID_HOUR_DURATION |
| RAID_HOUR_DURATION| In minutes, default duration of raids during raid hour, currently 60min |
| RAID_HOUR_CREATION_LIMIT| In raids, max. raids a user can create if RAID_HOUR is enabled. BOT_ADMINS are not affected |
| RAID_LAST_START| In minutes what the last timeslot is before the raid ends |
| RAID_LATE_MSG| Bool, add a message to the raidpoll if anyone has signaled they are late. |
| RAID_LATE_TIME| How many minutes to advise waiting in `RAID_LATE_MSG` |
| RAID_LOCATION| Bool, Send a separate attached location message in addition to a raid poll |
| RAID_PICTURE | Bool, enable picture based raid polls instead of default text mode |
| RAID_PICTURE_STORE_GYM_IMAGES_LOCALLY | Bool, option to store gym photos in `images/gyms` instead of fetching them from cloud every time |
| RAID_PICTURE_BG_COLOR| List of RGB values for `RAID_PICTURE` poll background color, e.g. "0,0,0" for black |
| RAID_PICTURE_FILE_FORMAT| Format for raid pictures for `RAID_PICTURE`, valid values are gif, jpg, jpeg, png |
| RAID_PICTURE_FONT_GYM | Font used for gym names for regular raids. must match a ttf or otf file under `fonts/`. Probably should be of weight Bold. |
| RAID_PICTURE_FONT_EX_GYM | Font used for gym names for ex-raids. must match a ttf or otf file under `fonts/`. Probably should be of weight Regular. |
| RAID_PICTURE_FONT_TEXT | Font used for most text in raid pictures. must match a ttf or otf file under `fonts/`. Probably should be of weight Regular. |
| RAID_PICTURE_HIDE_LEVEL| List of levels to exclude from `RAID_PICTURE` (will fall back to text mode)|
| RAID_PICTURE_HIDE_POKEMON| List of Pokemon dex IDs to exclude from `RAID_PICTURE` (will fall back to text mode) |
| RAID_PICTURE_ICONS_WHITE| Bool, use white icons in `RAID_PICTURE` instead of black |
| RAID_PICTURE_POKEMON_ICONS| Comma separated list of pokemon icon sources (currently PokeMiners and ZeChrales) |
| RAID_PICTURE_TEXT_COLOR| List of RGB values for `RAID_PICTURE` poll text color, e.g "255,255,255" for white |
| RAID_PICTURE_URL| Fully qualified HTTPS URL to `raidpicture.php`, for example `https://example.com/raidbot/raidpicture.php` |
| RAID_PIN_MESSAGE| Custom message added to the bottom of the raid overview messages |
| RAID_POLL_HIDE_BUTTONS_POKEMON| List of Pokemon dex IDs for which voting buttons are disabled |
| RAID_POLL_HIDE_BUTTONS_RAID_LEVEL| List of raid levels for which voting buttons are disabled |
| RAID_POLL_HIDE_BUTTONS_TEAM_LVL| Bool, true to hide the team and level+/- buttons below each raid poll |
| RAID_POLL_HIDE_DONE_CANCELED| Bool, hide the Done and Cancel buttons from raid polls |
| RAID_POLL_HIDE_USERS_TIME| In minutes, after what time the previous raid slots are hidden from a raid poll |
| RAID_POLL_UI_ORDER| Order of elements in text based raid polls. Valid elements are: `extra, teamll, time, pokemon, status` |
| RAID_REMOTEPASS_USERS | Bool, allow participation to raid polls with a remote pass |
| RAID_REMOTEPASS_USERS_LIMIT | Integer, How many remote participants to allow into a single raid |
| RAID_WANT_INVITE | Bool, allow participants to indicate that they wish to be invited to the raid |
| RAID_SLOTS| Amount of minutes between raid poll voting slots |
| RAID_VIA_LOCATION| Bool, enable creating raids by sharing a location with the bot |
| RAID_VOTE_ICONS| Bool, use icons on raid poll buttons |
| RAID_VOTE_TEXT| Bool, use text on raid poll buttons |
| SHARE_CHATS_LEVEL_1| List of Telegram group IDs available for sharing raids of level 1 |
| SHARE_CHATS_LEVEL_2| List of Telegram group IDs available for sharing raids of level 2 |
| SHARE_CHATS_LEVEL_3| List of Telegram group IDs available for sharing raids of level 3 |
| SHARE_CHATS_LEVEL_4| List of Telegram group IDs available for sharing raids of level 4 |
| SHARE_CHATS_LEVEL_5| List of Telegram group IDs available for sharing raids of level 5 |
| SHARE_CHATS_LEVEL_X| List of Telegram group IDs available for sharing Ex-Raids |
| SHARE_CHATS| List of Telegram group IDs available for sharing any raids |
| TIMEZONE| Timezone definition to use as per [TZ database names](https://www.wikiwand.com/en/List_of_tz_database_time_zones#/List) |
| TRAINER_MAX_LEVEL| Int, Maximum level a trainer can be (currently 50) |
| TRAINER_BUTTONS_TOGGLE| Bool, true to show/hide the team and level+/- buttons below the trainer data setup messages once a users hits the "trainer info" button. False to always show the team and level+/- buttons. |
| TRAINER_CHATS| List of chats where trainer data setup messages can be shared |
| UPGRADE_SQL_AUTO | When a DB schema upgrade is detected, run it automatically and bump config version to match. |
| WEBHOOK_CHATS_LEVEL_1_0| ? |
| WEBHOOK_CHATS_LEVEL_1_1| ? |
| WEBHOOK_CHATS_LEVEL_1| List of Telegram group IDs to autoshare raids of level 1 |
| WEBHOOK_CHATS_LEVEL_2_0| ? |
| WEBHOOK_CHATS_LEVEL_2_1| ? |
| WEBHOOK_CHATS_LEVEL_2| List of Telegram group IDs to autoshare raids of level 2 |
| WEBHOOK_CHATS_LEVEL_3_0| ? |
| WEBHOOK_CHATS_LEVEL_3_1| ? |
| WEBHOOK_CHATS_LEVEL_3| List of Telegram group IDs to autoshare raids of level 3 |
| WEBHOOK_CHATS_LEVEL_4_0| ? |
| WEBHOOK_CHATS_LEVEL_4_1| ? |
| WEBHOOK_CHATS_LEVEL_4| List of Telegram group IDs to autoshare raids of level 4 |
| WEBHOOK_CHATS_LEVEL_5_0| ? |
| WEBHOOK_CHATS_LEVEL_5_1| ? |
| WEBHOOK_CHATS_LEVEL_5| List of Telegram group IDs to autoshare raids of level 5 |
| WEBHOOK_CHATS| List of Telegram group IDs to autoshare raids of any level  |
| WEBHOOK_CREATE_ONLY| Bool, only create raids, don't autoshare them to any group |
| WEBHOOK_CREATOR| Telegram ID of the bot or user to credit as having created webhook raids |
| WEBHOOK_EXCLUDE_POKEMON| List of Pokemon dex IDs to exclude from webhook raid creation |
| WEBHOOK_EXCLUDE_RAID_LEVEL| List of raid levels to exclude from webhook raid creation |
| WEBHOOK_EXCLUDE_UNKOWN| Bool, disable raid creation for gyms with "unknown" gym name. |
| WEBHOOK_EXCLUDE_AUTOSHARE_DURATION| Time in minutes, skip autosharing of raids to chats if raid duration is greater than set value. Raids are still saved to the bot even if they aren't shared. (Default 45)|


# Development

## Adding new config values

 - Any previously undefined config value needs a sane default value in `config/defaults-config.json`. Anyone updating will get that default!
 - If the new config item is something people will likely want to override, add it to `config/config.json.example`.
 - You can access the new config item in code with `$config->CONFIG_ITEM_NAME` but if inside a function, remember to specify `global $config;`
 - Don't break backwards compatibility if you can.

## Git Hooks

In the needed core repository we provide a folder with git hooks which can be used to automate development processes. Copy them to the `.git/hooks/` folder of this bot and make them executable (e.g. `chmod +x .git/hooks/pre-commit`) to use them, or set your local git config to use them directly:
```
git config --local core.hooksPath core/hooks
```

### pre-commit

The pre-commit git hook will automatically update the VERSION file and table of contents whenever you do a `git commit`.

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

The following command is used to create the game-master-raid-boss-pokedex.sql file.

### game-master-raid-boss-pokedex.sql
The following command is used to create the game-master-raid-boss-pokedex.sql file. Normally this file is kept up to date by developers
but if no one has updated it yet, you can generate an updated version (and optionally also create a Pull Request.)
```
php getDB.php
```

## Translations

Translations are mainly stored in `lang/language.json`. Any string marked as `TRANSLATE` hasn't been translated yet. These can be changed by hand but if you want to add a new language or do large scale translation, using translate.py is recommended.

### translate.py

To help in adding a new translation or improving an existing one the `lang/` folder has a tool called `translate.py`
It will add placeholders for a new language and allow you to incrementally and interatively translate strings. All changes are saved as you go.

#### Usage

By default:
  * Translations are read from and saved directly into language.json but any other file(s) can be specified with `--input` and `--output`
  * The current English translation is shown as context. The language can be chosen with `--from_language`
  * Only missing translations are prompted (incremental mode), use `--noincremental` or `--incremental=False` to prompt every string.
  * No default language to translate into is specified, it must be given with `--to <language_code>`

We'll run through an example, for full options see `translate.py --help`

```shell
% cd lang/
% pip3 install -r requirements.txt # install required libraries
% ./translate.py --to=FI # FI here is the language code of the new or existing language
I1130 18:29:47.547245 139886869309248 translate.py:21] Creating placeholders for missing strings for language FI
Press ^D or ^C to stop. Leave a translation empty to skip.
I1130 18:29:47.556554 139886869309248 translate.py:30] Iterating over strings that have not been translated to language FI
raid[EN]: Raid
raid[FI]:
```

Enter translations as long as you want. You can skip translating a string by just leaving it empty, i.e. pressing enter. Press Ctrl-C or Ctrl-D to exit the tool, you won't lose any translations you've already made.
