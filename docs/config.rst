Configuration
==============

Inside the config folder, copy the example config.json.example to your own config.json and edit the values (explained further). The example only contains the most common values people want to change, refer to defaults-config.json for all available values. Any value set in config.json will override the default from defaults-config.json.

Don't forget to change the file permissions of your config file to 0600 (e.g. ``chmod 0600 config.json``\ ) afterwards. You need to change the ownerchip of all files to the webserver user - otherwise the config is not readable. Normally this: ``chown www-data:www-data -R *``

Some values are missing as the bot has default values. If you like to change those, you need to add and define them in your config.json file, e.g. ``"DDOS_MAXIMUM":"10"``.

Referring to groups, channels and users
---------------------------------------

The most reliable and secure way to refer to individuals, channels, groups and supergroups private or public is with a numeric ID.
While in some contexts public groups, channels and supergroups could use their public id (e.g. @PublicGroup) this wouldn't work for every call.

*Example IDs:*

.. list-table::
   :header-rows: 1

   * - Type
     - Example
     - Notes
   * - User
     - 12345678
     - Positive number, no set length
   * - Group
     - -998877665
     - Negative number, no set length
   * - Channel
     - -1001122334455
     - Negative number padded to 13 characters (prepend -100)
   * - Supergroup
     - -1001122334455
     - Negative number padded to 13 characters (prepend -100)


Finding public IDs
^^^^^^^^^^^^^^^^^^

Counterintuitively getting the ID of a public user, group, channel or supergroup is more difficult since most ways will replace the @name where a numeric ID would be visible. These methods will also work for private versions but will cause spam to the chat. The easiest way is via @RawDataBot:

**Group or Supergroup:**

Add @RawDataBot the the group which will cause it to report data about the group. Use the id value as-is,
it's already prepended to the right length.

.. code-block::

   *snip*
           "chat": {
               "id": -1002233445566,
               "title": "Pokemon Group",
               "username": "PokemonGroup",
               "type": "supergroup"
           },
   *snip*

**Channel or user:**

Forward a message to @RawDataBot which will get you a data package:

*Channel:*

.. code-block::

   *snip*
           "forward_from_chat": {
               "id": -1001122334455,
               "title": "Pokemon Channel",
               "username": "PokemonChannel",
               "type": "channel"
           },

*User:*

.. code-block::

   *snip*
           "forward_from": {
               "id": 112233445,
               "is_bot": false,
               "first_name": "Edwin",
               "last_name": "Example"
           },
   *snip*

How to make a Supergroup
^^^^^^^^^^^^^^^^^^^^^^^^


* Some features will only work with Supergroups (and Channels) since they enable more features needed for example for automatic cleanup. If in doubt use Supergroups.
* Every created group starts out as a normal Group but once you enable certain features it will get converted automatically to a Supergroup. For example enabling new users to see message history will convert it!
* Once a group has been converted to a Supergroup it cannot go back to a normal Group, even if you change back the option that caused it to convert.
* Be aware that the group ID will change completely when the group gets converted so you'll need to find it again!

Database connection
-------------------

Enter the details for the database connection to the config.json file via ``DB_HOST``\ , ``DB_NAME``\ , ``DB_USER`` and ``DB_PASSWORD``.

General config and log files
----------------------------

Set ``DEBUG`` to true, to enable the debug logfile.

Set ``DEBUG_LOGFILE`` to the location of the logfile, e.g. /var/log/tg-bots/dev-raid-bot.log. Make sure to create the log dir, e.g. /var/log/tg-bots/ and set it writeable by webserver.

Set ``APIKEY_HASH`` to the hashed value of your bot token (preferably lowercase) using a hash generator, e.g. https://www.miniwebtool.com/sha512-hash-generator/

Set ``DDOS_MAXIMUM`` to the amount of callback queries each user is allowed to do each minute. If the amount is reached any further callback query is rejected by the DDOS check. Default value: 10.

Set ``BRIDGE_MODE`` to true when you're using the PokemonBotBridge. If you're not using the PokemonBotBridge the default value of false is used. PokemonBotBridge: https://github.com/pokepark/PokemonBotBridge

More config options
-------------------

Proxy
^^^^^

Set ``CURL_USEPROXY`` with a value of ``true`` in case you are running the bot behind a proxy server.

Set ``CURL_PROXYSERVER`` to the proxy server address and port, for example:

.. code-block::

   "CURL_USEPROXY":"false",
   "CURL_PROXYSERVER":"http://your.proxyserver.com:8080",

Authentication against the proxy server by username and password is currently not supported!

Languages
^^^^^^^^^

You can set several languages for the bot. Available languages are (A-Z):


* DE (German)
* EN (English)
* FI (Finnish)
* FR (French)
* IT (Italian)
* NL (Dutch)
* NO (Norwegian)
* PT-BR (Brazilian Portugese)
* RU (Russian)
* PL (Polish)

Set ``LANGUAGE_PRIVATE`` for the prefered language the bot will answer users when they chat with them. Leave blank that the bot will answer in the users language. If the users language is not supported, e.g. ZH-CN (Chinese), the bot will always use EN (English) as fallback language.

Set ``LANGUAGE_PUBLIC`` to the prefered language for raid polls. Default value: EN

So if you want to have the bot communication based on the users Telegram language, e.g. Russian, and show the raid polls in German for example:

.. code-block::

   "LANGUAGE_PRIVATE":"",
   "LANGUAGE_PUBLIC":"DE",

Timezone, Google maps API and OpenStreetMap API
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Timezone
~~~~~~~~

Set ``TIMEZONE`` to the timezone you wish to use for the bot. Predefined value from the example config is "Europe/Berlin".

Google maps API
~~~~~~~~~~~~~~~

Optionally you can you use Google maps API to lookup addresses of gyms based on latitude and longitude. Therefore get a Google maps API key.

To get a new API key, navigate to https://console.developers.google.com/apis/credentials and create a new API project, e.g. PokemonRaidBot

Once the project is created select "API key" from the "Create credentials" dropdown menu - a new API key is created.

After the key is created, you need to activate it for both: Geocoding and Timezone API

Therefore go to "Dashboard" on the left navigation pane and afterwards hit "Enable APIs and services" on top of the page.

Search for Geocoding and Timezone API and enable them. Alternatively use these links to get to Geocoding and Timezone API services:

https://console.developers.google.com/apis/library/timezone-backend.googleapis.com

https://console.developers.google.com/apis/library/geocoding-backend.googleapis.com

Finally check the dashboard again and make sure Google Maps Geocoding API and Google Maps Time Zone API are listed as enabled services.

Set ``MAPS_LOOKUP`` to true and put the API key in ``MAPS_API_KEY`` in your config.

OpenStreetMap API
~~~~~~~~~~~~~~~~~

To use OpenStreetMap's Nominatim API to lookup addresses of gyms, set ``OSM_LOOKUP`` to ``true`` and  ``MAPS_LOOKUP`` to ``false``.

Quote from `Nominatim documentation <https://nominatim.org/release-docs/latest/api/Reverse/>`_\ :

``The reverse geocoding API does not exactly compute the address for the coordinate it receives. It works by finding the closest suitable OSM object and returning its address information. This may occasionally lead to unexpected results.``

Raid creation options
^^^^^^^^^^^^^^^^^^^^^

There are several options to customize the creation of raid polls:

Set ``RAID_VIA_LOCATION`` to true to allow raid creation from a location shared with the bot. Use together with ``RAID_VIA_LOCATION_FUNCTION``.

Set ``RAID_VIA_LOCATION_FUNCTION`` to select which action to perform with the shared location. ``create`` (default) to create a permanent gym, which can later be edited, ``list`` to list all active raids nearby the location, ``remote`` to create a temporary remote raid gym.

Set ``RAID_EGG_DURATION`` to the maximum amount of minutes a user can select for the egg hatching phase.

Set ``RAID_DURATION`` to the maximum amount of minutes a user can select as raid duration for already running/active raids.

Set ``RAID_HOUR`` to true to enable the raid hour. Enabling the raid hour superseds the normal raid duration. Note that the raid hour takes precedence over the raid day. Make sure to disable the raid hour to get the raid day.

Set ``RAID_HOUR_DURATION`` to the maximum amount of minutes a user can select as raid duration if the ``RAID_HOUR`` is enabled. Per default max. 60 minutes.

Set ``RAID_HOUR_CREATION_LIMIT`` to the maximum amount of raids a user can create if the ``RAID_HOUR`` is enabled. Per default 1 raid.

Set ``RAID_DAY`` to true to enable the raid day. Enabling the raid day superseds the normal raid duration. Note that the raid hour takes precedence over the raid day. Make sure to disable the raid hour to get the raid day.

Set ``RAID_DAY_DURATION`` to the maximum amount of minutes a user can select as raid duration if the ``RAID_DAY`` is enabled. Per default max. 180 minutes.

Set ``RAID_DAY_CREATION_LIMIT`` to the maximum amount of raids a user can create if the ``RAID_DAY`` is enabled. Per default 1 raid.

Set ``RAID_DURATION_CLOCK_STYLE`` to customize the default style for the raid start time selection. Set to true, the bot will show the time in clocktime style, e.g. "18:34" as selection when the raid will start. Set to false the bot will show the time until the raid starts in minutes, e.g. "0:16" (similar to the countdown in the gyms). Users can switch between both style in the raid creation process.

Set ``RAID_CUSTOM_GYM_LETTERS`` to further split gyms by their first letter. For example if you have a lot of gyms starting with 'St' as there are a lot of churches like St. Helen, St. Jospeh, etc. in your area and the gym list under the letter 'S' is too long, you can tell the bot to put the gyms starting with 'St' under 'St' and exclude them from the letter 'S'. There is no limitation in length, so even 'Berlin' would work to split gyms, but the recommendation is to use as less chars as possible to split the gyms. You can add multiple custom gym letters, just separate them by comma. Example: ``"RAID_CUSTOM_GYM_LETTERS":"Ber,Sch,St,Wi"``

Set ``RAID_EXCLUDE_EXRAID_DUPLICATION`` to true to exclude ex-raids from the duplication check which allows to create an ex-raid and a normal raid.

Raid time customization
^^^^^^^^^^^^^^^^^^^^^^^

There are several options to configure the times related to the raid polls:

Set ``RAID_LOCATION`` to true to send back the location as message in addition to the raid poll.

Set ``RAID_SLOTS`` to the amount of minutes which shall be between the voting slots.

Set ``RAID_DIRECT_START`` to the first slot directly after hatching.

Set ``RAID_FIRST_START`` to the amount of minutes required to add an earlier first start time before the first regular voting slot.

Set ``RAID_LAST_START`` to the minutes for the last start option before the a raid ends.

Set ``RAID_ANYTIME`` to true to allow attendance of the raid at any time. If set to false, users have to pick a specific time.

Raid poll design and layout
^^^^^^^^^^^^^^^^^^^^^^^^^^^

There are several options to configure the design and layout of the raid polls:

Set ``RAID_VOTE_ICONS`` to true to show the icons for the status vote buttons.

Set ``RAID_VOTE_TEXT`` to true to show the text for the status vote buttons.

Set ``RAID_LATE_MSG`` to true to enable the message hinting that some participants are late.

Set ``RAID_LATE_TIME`` to the amount of minutes the local community will may be wait for the late participants.

Set ``RAID_POLL_HIDE_USERS_TIME`` to the amount of minutes when a previous raid slot should be hidden. For example if there are 2 slots, 18:00 and 18:15, and you set the time to 10 minutes the first group of participants from 18:00 will be hidden once we reach 18:10. This helps to keep the raid poll message smaller and clearer if there are multiple groups. Set the value to 0 to always show all slots.

Edit ``RAID_POLL_UI_TEMPLATE`` to customize the order of the buttons for the raid polls. Supported elementa are ``alone, extra, extra_alien, remote, inv_plz, can_inv, ex_inv, teamlvl, time, pokemon, refresh, alarm, here, late, done, cancel``. Some elements may be hidden by some other config values even if they are set in the template.

Set ``RAID_POLL_HIDE_BUTTONS_RAID_LEVEL`` to the raid levels (1-5) for which the voting buttons under the raid poll should be hidden. For example a level 1 raid can be done by a single player, but it is maybe interesting to be shared as some pokemon are only available in raids.

Set ``RAID_POLL_HIDE_BUTTONS_POKEMON`` to the pokedex IDs (e.g. '1' for Bulbasaur) or pokedex ID and form combined by a minus sign (e.g. '386-normal' for Deoxys Normal form or '386-attack' for Deoxys Attack form) for which the voting buttons under the raid poll should be hidden.

Set ``RAID_POLL_HIDE_DONE_CANCELED`` to true to hide the users which are done with the raid or canceled and do not longer attend the raid.

Set ``RAID_EX_GYM_MARKER`` to set the marker for ex-raid gyms. You can use a predefined icon using the value 'icon' or any own marker, e.g. 'EX'.

Set ``RAID_CREATION_EX_GYM_MARKER`` to true to show the marker for ex-raid gyms during raid creation.

Automatically refreshing raid polls
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

To remove the need for pressing the refresh button on polls, you can set the config value ``AUTO_REFRESH_POLLS`` to true and then update all relevant polls via curl post.
Please note that Telegram has a limit how many queries you can send them per a certain timeperiod, so you might want to limit this feature to most important chats only.

For all chats:

.. code-block::

   curl -k -d '{"callback_query":{"data":"0:refresh_polls:0"}}' https://localhost/botdir/index.php?apikey=111111111:AABBccddEEFFggHHiijjKKLLmmnnOOPPqq

For a specific chat:

.. code-block::

   curl -k -d '{"callback_query":{"data":"[CHAT_ID]:refresh_polls:0"}}' https://localhost/botdir/index.php?apikey=111111111:AABBccddEEFFggHHiijjKKLLmmnnOOPPqq


Raid Picture mode
^^^^^^^^^^^^^^^^^

To enable raid announcements as images set ``RAID_PICTURE`` to true and set the url in ``RAID_PICTURE_URL`` to the location of raidpicture.php.

You also need to get the Pokemon sprites from known sources and put them in either images/pokemon/ or the images/pokemon_REPO-OWNER/ folder. The images/pokemon/ directory needs to be created manually, the images/pokemon_REPO-OWNER/ folders will be created automatically when by running the special download script mentioned below.

Pokemon Icons / Sprites:
Link: https://github.com/PokeMiners/pogo_assets/tree/master/Images/Pokemon%20-%20256x256

To easily download you can use a special download script on the CLI: ``php getPokemonIcons.php``

The script downloads 20 files at a time by default. You can adjust the value by adding the argument ``--chunk=`` and a number.

To save the sprites to a different location outside the actual PokemonRaidBot directory, you can use the argument ``--dir=``\ , eg. ``php getPokemonIcons.php --dir=/var/www/html/pokemon_sprites/``

The script can also be triggered via command line arguments, eg. as cron job.

If you're sharing the pokemon icons with other bots or applications and therefore placed them outside the PokemonRaidBot directory, you can easily replace the images/pokemon with a softlink to that directory. It won't interfere with git status as we adjusted the .gitignore accordingly.

Example to replace the with a symbolic link:

.. code-block::

   cd /var/www/html/PokemonRaidBot/images/
   rm -rf pokemon/
   ln -sf /var/www/html/pokemon_sprites pokemon

Font support
~~~~~~~~~~~~

If we included support for every unicode glyph under the sun the fonts alone would be over 1GB, thus we only ship the base Noto Sans fonts. If you need support for example for CJK glyphs, download a better suited font from `google.com/get/noto <https://www.google.com/get/noto/>`_\ , place the ``Regular`` & ``Bold`` font files in ``fonts/`` and override them in ``config/config.json``\ , for example:

.. code-block::

     "RAID_PICTURE_FONT_GYM": "NotoSansCJKjp-Bold.otf",
     "RAID_PICTURE_FONT_EX_GYM": "NotoSansCJKjp-Regular.otf",
     "RAID_PICTURE_FONT_TEXT": "NotoSansCJKjp-Regular.otf"

Set ``RAID_PICTURE_HIDE_LEVEL`` to the raid levels (1-5 and X) for which the raid message is shared without the picture even if ``RAID_PICTURE`` is set to true.

Set ``RAID_PICTURE_HIDE_POKEMON`` to the pokedex IDs (e.g. '1' for Bulbasaur) or pokedex ID and form combined by a minus sign (e.g. '386-normal' for Deoxys Normal form or '386-attack' for Deoxys Attack form) for which the raid message is shared without the picture even if ``RAID_PICTURE`` is set to true.

Set ``RAID_PICTURE_BG_COLOR`` to an RGB value to specify the background color of the raid picture. (Default: black)

Set ``RAID_PICTURE_TEXT_COLOR`` to an RGB value to specify the text color of the raid picture. (Default: white)

Set ``RAID_PICTURE_STORE_GYM_IMAGES_LOCALLY`` to ``true`` if you want to download and store gym photos in ``images/gyms/`` instead of fetching them from the cloud every time an image is created.

Set ``RAID_PICTURE_ICONS_WHITE`` to ``true`` to use white weather icons for the raid picture. Especially useful when you defined a dark background color. (Default: true)

Set ``RAID_PICTURE_FILE_FORMAT`` to either ``gif``\ , ``jpeg``\ , ``jpg`` or ``png`` to specify the output format of the raid picture.

Set ``RAID_DEFAULT_PICTURE`` to the url of a default gym picture in case no gym image url is stored in the database for a gym.

Set ``RAID_PICTURE_POKEMON_TYPES`` to ``true`` (default true) to display the type icons of the raid boss.

Portal Import
^^^^^^^^^^^^^

Set ``PORTAL_IMPORT`` to ``true`` to enable the possibility to import portals from Telegram Ingress Bots.

Set ``RAID_PICTURE_STORE_GYM_IMAGES_LOCALLY`` to ``true`` to download the portal image from Telegram Ingress Bots. When set to ``false`` the URL of the portal image is stored in the database.

Raid sharing
^^^^^^^^^^^^

You can share raid polls with any chat in Telegram via a share button.

Sharing raid polls can be restricted, so only specific chats/users can be allowed to share a raid poll - take a look at the permission system!

With a predefined list ``SHARE_CHATS`` you can specify the chats which should appear as buttons for sharing raid polls.

You can define different chats for specific raid levels using ``SHARE_CHATS_LEVEL_`` plus the raid level too. Raid levels can be 'X', '5', '4', '3', '2' or '1'.

For the ID of a chat either forward a message from the chat to a bot like @RawDataBot, @getidsbot or search the web for another method ;)

Examples:

Sharing all raids to two chats
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Predefine sharing all raids to the chats -100111222333 and -100444555666

``"SHARE_CHATS":"-100111222333,-100444555666"``

Sharing split to channels by level
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Predefine sharing all raids to the chats -100111222333 and -100444555666, except level 5 raids which will be shared to the chat -100999666333

``"SHARE_CHATS":"-100111222333,-100444555666"``
``"SHARE_CHATS_LEVEL_5":"-100444555666"``

Raids from Webhook
~~~~~~~~~~~~~~~~~~

You can receive Raids from a mapping system such as MAD via Webhook.
For that you need to setup ``WEBHOOK_CREATOR``\ , and to automatically share raids to chats, 
``"WEBHOOK_CHATS_ALL_LEVELS":"-100444555666"``
or by Raidlevel ``"WEBHOOK_CHATS_LEVEL_5":"-100444555666"``
All incoming raids will be published in these chats.

If you only want to automatically share a specific Pokemon, you can do that by editing the ``WEBHOOK_CHATS_BY_POKEMON`` json array:


.. code-block::

  "WEBHOOK_CHATS_BY_POKEMON" : [
    {
        "pokemon_id": 744,
        "chats":[chat_id_1, chat_id_2]
    },
    {
        "pokemon_id": 25,
        "form_id": 2678,
        "chats":[chat_id_3]
    }
  ],

``pokemon_id`` and ``chats`` are required objects, ``form_id`` is optional.

Filter Raids from Webhook / geoconfig.json
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you have multiple Chats for different Areas you can setup them in
``"WEBHOOK_CHATS_LEVEL_5_0":"-100444555666"`` matching with your configuration in the geoconfig.json.
Go to http://geo.jasparke.net/ and create an Area (Geofence), where your gyms are.
When you are finished, click on 'exp' and save the coordinates to your geoconfig.json. And for the ID 0 you use "WEBHOOK_CHATS_LEVEL_5_0", for ID 1 "WEBHOOK_CHATS_LEVEL_5_1" and so on.
The raids will only be posted into the defined chats.

Extended Raid-Sharing
~~~~~~~~~~~~~~~~~~~~~

If you are using multiple Channel, you can setup one Channel as Main-Channel "SHARE_CHATS_AFTER_ATTENDANCE":"-100444555666" and on votes in different Channel, the Raid will be shared to your Main-Channel. Activate this function with "SHARE_AFTER_ATTENDANCE":true
This is important for Raids from Webhooks. All Raids were posted to one Channel, which can be muted to the users. But if someone votes for a raid, this raid will be posted to a unmuted channel, where all others get a notification.

Event raids
^^^^^^^^^^^

Users with the proper access rights can choose to create event raids. These can be handy for example on raid hours and raid days. These special raid polls have event specific name, description and poll settings that need to be set in database. Example of a few settings is in ``sql/event-table-example.sql``.

``vote_key_mode`` currently supports 2 modes, 0 and 1. 0 is the standard mode where users vote for a time when they are attending. 1 is a mode with no timeslots, just a button for 'attending'.

With ``time_slots`` you can set event secific time slots for vote keys when ``vote_key_mode`` 0 is selected.

``raid_duration`` is the duration of the raids of that event type.

``hide_raid_picture`` hides the raid picture from these event polls even if ``RAID_PICTURE`` is set to ``true``.

Trainer settings
----------------

The command '/trainer' allows users of the bot to change their trainer data like team, level, trainercode and trainername. It is also used to share a message that allows trainers to modify their trainer data like team and level to another chat. To share this message, every chat specified in the raid sharing list like SHARE_CHATS are used.

With ``TRAINER_CHATS`` you can specify additional chats which should appear as buttons too for sharing the trainer message.

Set ``TRAINER_BUTTONS_TOGGLE`` to true to enable the toggle which shows/hides the team and level+/- buttons under the trainer message. To disable the toggle button and always show the team and level+/- buttons set it to false.

Add additional chats -100999555111 and -100888444222 to share the trainer message

``"TRAINER_CHATS":"-100999555111,-100888444222"``

Set ``CUSTOM_TRAINERNAME`` to true to enable custom trainernames.

Set ``RAID_POLL_SHOW_TRAINERCODE`` to true to enable saving and displaying of trainercodes.

Raid overview
-------------

The bot allows you to list all raids which got shared with one or more chats as a single raid overview message to quickly get an overview of all raids which are currently running and got shared in each chat. You can view and share raid overviews via the /overview command - but only if some raids are currently active and if these active raids got shared to any chats!

To keep this raid overview always up to date when you have it e.g. pinned inside your raid channel, you can setup a cronjob that updates the message by calling the overview_refresh module.

You can either refresh all shared raid overview messages by calling the following curl command:

``curl -k -d '{"callback_query":{"data":"0:overview_refresh:0"}}' https://localhost/botdir/index.php?apikey=111111111:AABBccddEEFFggHHiijjKKLLmmnnOOPPqq``

To just refresh the raid overview message you've shared with a specific chat (e.g. -100112233445) use:

``curl -k -d '{"callback_query":{"data":"0:overview_refresh:-100112233445"}}' https://localhost/botdir/index.php?apikey=111111111:AABBccddEEFFggHHiijjKKLLmmnnOOPPqq``

To delete a shared raid overview message you can use the ``/overview`` command too.

With the ``RAID_PIN_MESSAGE`` in the config you can add a custom message to the bottom of the raid overview messages.

Raid Map
--------

Set ``MAP_URL`` to the URL of your map to add it to each raid poll.

Cleanup
-------

The bot features an automatic cleanup of Telegram raid poll messages as well as cleanup of the database (attendance and raids tables).

To activate cleanup you need to `make sure your groups are Supergroups or Channels <#which-group-type-should-i-use--how-do-i-make-a-group-a-supergroup>`_\ , make your bot an admin in this chat, enable cleanup in the config and create a cronjob to trigger the cleanup process.


#. Set the ``CLEANUP`` in the config to ``true`` and define a cleanup secret/passphrase under ``CLEANUP_SECRET``.
#. Activate the cleanup of Telegram messages and/or the database for raids by setting ``CLEANUP_TELEGRAM`` / ``CLEANUP_DATABASE`` to true.

   * **Do note** that ``CLEANUP_TELEGRAM`` will not work in groups that are not Supergroups or Channels!

#. Specify the amount of minutes which need to pass by after raid has ended before the bot executes the cleanup.

   * Times are in minutes in ``CLEANUP_TIME_TG`` for Telegram cleanup and ``CLEANUP_TIME_DB`` for database cleanup.
   * The value for the minutes of the database cleanup ``CLEANUP_TIME_DB`` must be greater than then one for Telegram cleanup ``CLEANUP_TIME_TG``. Otherwise cleanup will do nothing and exit due to misconfiguration!

#. Finally set up a cronjob to trigger the cleanup. For example with curl:

  .. code-block::

     curl -k -d '{"cleanup":{"secret":"your-cleanup-secret/passphrase"}}' https://localhost/index.php?apikey=111111111:AABBCCDDEEFFGGHHIIJJKKLLMMNNOOPP123`

Access permissions
------------------

Public access
^^^^^^^^^^^^^

When no Telegram id, group, supergroup or channel is specified in ``BOT_ADMINS`` the bot will allow everyone to use it (public access).

Example for public access: ``"BOT_ADMINS":""``

Access and permissions
^^^^^^^^^^^^^^^^^^^^^^

The ``MAINTAINER_ID`` is not able to access the bot nor has any permissions as that id is only contacted in case of errors and issues with the bot configuration.

The ``BOT_ADMINS`` have all permissions and can use any feature of the bot.

Telegram Users can only vote on raid polls, but have no access to other bot functions (unless you configured it).

In order to allow Telegram chats to access the bot and use commands/features, you need to create an access file.

It does not matter if a chat is a user, group, supergroup or channel - any kind of chat is supported as every chat has a chat id!

Those access files need to be placed under the subdirectory 'access' and follow a special name scheme.

.. list-table::
   :header-rows: 1

   * - Chat type
     - User role
     - Name of the access file
     - Example
   * - User
     - -
     - ``accessCHAT_ID``
     - ``access111555999``
   * - 
     - 
     - 
     - 
   * - Group, Supergroup, Channel
     - Any role
     - ``accessCHAT_ID``
     - ``access-100224466889``
   * - 
     - Creator
     - ``creatorCHAT_ID``
     - ``creator-100224466889``
   * - 
     - Admin
     - ``adminsCHAT_ID``
     - ``admins-100224466889``
   * - 
     - Member
     - ``membersCHAT_ID``
     - ``members-100224466889``
   * - 
     - Restricted
     - ``restrictedCHAT_ID``
     - ``restricted-100224466889``
   * - 
     - Kicked
     - ``kickedCHAT_ID``
     - ``kicked-100224466889``


As you can see in the table, you can define different permissions for the creator, the admins and the members of a group, supergroup and channel.

You can also create just one access file for groups, supergroups or channels (e.g. ``access-100224466889``\ ) so any user has the same permission regardless of their role in the chat, but this is not recommended (see important note below!).

.. warning::
    Any role means any role - so in addition to roles 'creator', 'administrator' or 'member' this will also grant 'restricted' and 'kicked' users to access the bot with the defined permissions!

To exclude 'restricted' and 'kicked' users when using an access file for any role (e.g. ``access-100224466889``\ ) you can add the permissions ``ignore-restricted`` and ``ignore-kicked`` to the access file!

User with the role 'left' are automatically receiving an 'Access denied' from the bot as they willingly have choosen to leave the chat through which they got access to the bot!**

Every access file allows the access for a particular chat and must include the permissons which should be granted to that chat.

To differ between all those access file you can add any kind of comment to the filename of the access file itself. Just make sure to not use a number (0-9) right after the chat id!

Consider you have 4 channels. One for each district of your town: east, west, south and north. So you could name the access file for example like this:

.. code-block::

   access-100333444555 South-Channel
   access-100444555666+NorthernChannel
   admins-100222333444_West-District
   creator-100111222333-Channel-East-District
   creator-100444555666+NorthernChannel
   members-100111222333-Channel-East-District
   members-100222333444_West-District

Permissions overview
^^^^^^^^^^^^^^^^^^^^

The following table shows the permissions you need to write into an access file (last column) to grant permissions to chats.

In an access file it is **One permission per line** - so not separated by space, comma or any other char!

A few examples for access files can be found below the permission overview table.

.. list-table::
   :header-rows: 1

   * - Access
     - **Action and /command**
     - Permission inside access file
   * - Bot
     - Access the bot itself
     - ``access-bot``
   * - 
     - Deny access to restricted group/supergroup/channel members
     - ``ignore-restricted``
   * - 
     - Deny access to kicked group/supergroup/channel members
     - ``ignore-kicked``
   * - 
     - 
     - 
   * - Raid poll
     - Vote on shared raid poll
     - Not required!
   * - 
     - Create raids ``/start``\ , ``/raid``
     - ``create``
   * - 
     - Create ex-raids ``/start``
     - ``ex-raids``
   * - 
     - Create event raids ``/start``
     - ``event-raids``
   * - 
     - Change raid duration ``/start``
     - ``raid-duration``
   * - 
     - List all raids ``/list`` and ``/listall``
     - ``list``
   * - 
     - Manage overview ``/overview``
     - ``overview``
   * - 
     - Delete OWN raid polls ``/delete``
     - ``delete-own``
   * - 
     - Delete ALL raid polls ``/delete``
     - ``delete-all``
   * - 
     - View raid poll history ``/history``
     - ``history``
   * - 
     - 
     - 
   * - Sharing
     - Share OWN created raids to predefined chats 'SHARE_CHATS'
     - ``share-own``
   * - 
     - Share ALL created raids to predefined chats 'SHARE_CHATS'
     - ``share-all``
   * - 
     - Share OWN created raids to any chat
     - ``share-own`` and ``share-any-chat``
   * - 
     - Share ALL created raids to any chat
     - ``share-all`` and ``share-any-chat``
   * - 
     - 
     - 
   * - Pokemon
     - Update pokemon on OWN raid polls ``/pokemon``
     - ``pokemon-own``
   * - 
     - Update pokemon on ALL raid polls ``/pokemon``
     - ``pokemon-all``
   * - 
     - 
     - 
   * - Gym
     - Get gym details ``/gym``
     - ``gym-details``
   * - 
     - Edit extended gym details ``/gym``
     - ``gym-edit``
   * - 
     - Edit gym name ``/gymname``
     - ``gym-name``
   * - 
     - Edit gym address ``/gymaddress``
     - ``gym-address``
   * - 
     - Edit gym gps coordinates ``/gymgps``
     - ``gym-gps``
   * - 
     - Edit gym note ``/gymnote``
     - ``gym-note``
   * - 
     - Add a gym ``/addgym``
     - ``gym-add``
   * - 
     - 
     - 
   * - Trainer
     - Set trainer data ``/trainer``
     - ``trainer``
   * - 
     - Share trainer data message ``/trainer``
     - ``trainer-share``
   * - 
     - Delete trainer data message ``/trainer``
     - ``trainer-delete``
   * - 
     - 
     - 
   * - Portal
     - Import portals via inline search from other bots
     - ``portal-import``
   * - 
     - 
     - 
   * - Pokedex
     - Manage raid pokemon ``/pokedex``
     - ``pokedex``
   * - 
     - 
     - 
   * - Help
     - Show help ``/help``
     - ``help``
   * - 
     - 
     - 
   * - Tutorial
     - Allow users to access tutorial
     - ``tutorial``
   * - 
     - Force user to complete tutorial before allowing the use of any other command
     - ``force-tutorial``


Examples
~~~~~~~~

*Allow the user 111555999 to create raid polls and share them to the predefined chat list*

Access file: ``access\access111555999``

Content of the access file, so the actual permissions:

.. code-block::

   access-bot
   create
   share-own

*Allow the creator and the admins of the channel -100224466889 to create raid polls as well as sharing raid polls created by their own or others to the predefined chat list or any other chat*

Access file for the creator: ``access\creator-100224466889``

Access file for the admins: ``access\admins-100224466889``

Important: The minus ``-`` in front of the actual chat id must be part of the name as it's part of the chat id!

Content of the access files, so the actual permissions:

.. code-block::

   access-bot
   create
   share-all
   share-own
   share-any-chat

Tutorial mode
-------------

To help with teaching users how to use the bot, you can force them to go through a tutorial (that you must create) before they are able to use any of the bot's commands. This feature is mainly intended to be used in small communities with one small raid group.

To enable this feature:


* Create ``tutorial.php`` in config folder. Use ``tutorial.php.example`` as reference
* Set ``TUTORIAL_MODE`` to ``true`` in ``config.json``
* ``tutorial`` in access config file(s)
* ``force-tutorial`` in access config file(s) to force users to go through the tutorial before they're able to use the bot.

Customization
-------------

The bot allows you to customize things and therefore has a folder 'custom' for your customizations.

Custom icons
^^^^^^^^^^^^

In case you do not like some of the predefined icons and might like to change them to other/own icons:


* Create a file named ``constants.php`` in the custom folder
* Lookup the icon definitions you'd like to change in either the core or bot constants.php (\ ``core/bot/constants.php`` and ``constants.php``\ )
* Define your own icons in your custom constants.php
* For example to change the yellow exclamation mark icon to a red exclamation mark put the following in your ``custom/constants.php``\ :

``<?php
defined('EMOJI_WARN')           or define('EMOJI_WARN',    iconv('UCS-4LE', 'UTF-8', pack('V', 0x2757)));``


* Make sure to not miss the first line which declares the file as php file!
* To get the codes (here: 0x2757) of the icons/emojis, take a look at one of the large emoji databases in the web. They ususally have them mentioned and also show how the icons look like on different systems.

Custom translation
^^^^^^^^^^^^^^^^^^

To change translations you can do the following:


* Create a file named ``language.json`` in the custom folder
* Find the translation name/id by searching the core and bot language.php files (\ ``core/lang/language.php`` and ``lang/language.php``\ )
* Set your own translation in your custom language.json
* For example to change the translation of 'Friday' to a shorter 'Fri' put the following in your ``custom/language.json``\ :

.. code-block::

   {
       "weekday_5":{
           "EN":"Fri"
       }
   }


* Make sure to create a valid JSON file for your custom translations
* To verify your custom language.json you can use several apps, programs and web services.

Config reference
----------------

* For default values, see ``config/defaults-config.json``.
* Most values are strings.
* Boolean values should use ``true`` & ``false``\ , not strings.
* Any lists are given as a comma separated string.
* For raid levels, valid values are 1,2,3,4,5,X where X stands for Ex-Raid.
* If your config is not valid json, the bot will not work. Use a jslinter if in doubt.

.. list-table::
   :header-rows: 1

   * - Option
     - Description
   * - APIKEY_HASH
     - Telegram API key hashed in sha512
   * - BOT_ADMINS
     - List of admin identifiers (comma separated Telegram ids)
   * - BOT_ID
     - One letter ID for the bot used in debug logging. Mostly useful if you run multiple.
   * - BOT_NAME
     - Name of the bot.
   * - BRIDGE_MODE
     - Bool, whether to enable bridge mode.
   * - CLEANUP_DATABASE
     - Bool, whether to clean up finished raids from DB if cleanup is enabled.
   * - CLEANUP_LOG
     - Log cleanup operations in a separate file, quite verbose!
   * - CLEANUP_LOGFILE
     - Full path to Log file where cleanup operations are logged.
   * - CLEANUP_SECRET
     - Plain text passphrase to protect cleanup calls.
   * - CLEANUP_TELEGRAM
     - Bool, whether to clean up raid polls posted by the bot if cleanup is enabled.
   * - CLEANUP_TIME_DB
     - In minutes how old DB entries (past raid end-time) need to be to be eligible for cleanup
   * - CLEANUP_TIME_TG
     - In minutes how old TG posts (past raid end-time) need to be to be eligible for cleanup
   * - CLEANUP
     - Bool, whether to accept cleanup calls
   * - CURL_PROXYSERVER
     - Address of curl proxy
   * - CURL_USEPROXY
     - Bool, enable curl via proxy
   * - DB_HOST
     - Host or ip address of MySQL server
   * - DB_NAME
     - Name of DB
   * - DB_PASSWORD
     - Password of dedicated RaidBot DB user
   * - DB_USER
     - Username of dedicated RaidBot DB user
   * - ENABLE_DDOS_PROTECTION
     - Bool, enables ddos protection. True by default
   * - DDOS_MAXIMUM
     - Number of actions per minute an user is allowed to perform before getting locked out for ddosing
   * - DEBUG
     - Output helpful debugging messages to ``DEBUG_LOGFILE``
   * - DEBUG_LOGFILE
     - Full path to debug logfile
   * - DEBUG_INCOMING
     - Also log details on incoming webhook data to separate file, quite verbose!
   * - DEBUG_INCOMING_LOGFILE
     - Full path to incoming data debug logfile
   * - DEBUG_SQL
     - Also log details on DB queries to separate file, quite verbose!
   * - DEBUG_SQL_LOGFILE
     - Full path to SQL debug logfile
   * - DEFAULTS_WARNING
     - json files don't support comments, this is just a comment warning you not to edit defaults.
   * - LANGUAGE_PRIVATE
     - Language to use in private messages. Leave empty to infer language from users Telegram language
   * - LANGUAGE_PUBLIC
     - Language to use in chats
   * - LOGGING_INFO
     - Log INFO level messages to the file defined by LOGGING_INFO_LOGFILE. Useful for identifying potential issues.
   * - LOGGING_INFO_LOGFILE
     - Path to logfile.
   * - MAINTAINER_ID
     - Telegram ID of main maintainer
   * - MAINTAINER
     - Name of main maintainer
   * - AUTO_REFRESH_POLLS
     - Bool, enable the auto refresh feature and hides the refresh button from polls. Requires a curl job for refreshing. 
   * - MAPS_API_KEY
     - Google Maps API key for ``MAPS_LOOKUP``
   * - MAPS_LOOKUP
     - Boolean, resolve missing gym addresses via Google Maps
   * - OSM_LOOKUP
     - Boolean, resolve missing gym addresses via OpenStreetMap
   * - MAP_URL
     - URL to your map. This is displayed under every raid poll.
   * - CUSTOM_TRAINERNAME
     - Book, allow users to add custom trainernames via ``/trainer`` command
   * - ENABLE_GYM_AREAS
     - To divide gyms into areas when making selections through ``/start``, ``/listall`` etc. set this to true. Areas are defined in geoconfig_gym_areas.json.
   * - DEFAULT_GYM_AREA": false,
     - ID of default gymarea. Can also be set to false to only display areas.
   * - PORTAL_IMPORT
     - Bool, allow importing gyms via portal import Telegram bots
   * - RAID_ANYTIME
     - Bool, enable a final timeslot for attending at any given time.
   * - RAID_AUTOMATIC_ALARM
     - Bool, if true, force every attendee to sign up for the raid alarm automatically. If false, users can choose to set automatic alarms on via ``/trainer``.
   * - RAID_CODE_POKEMON
     - List of Pokemon dex IDs in use for private group codes
   * - RAID_CREATION_EX_GYM_MARKER
     - Highlight gyms eligible for Ex-Raids in raid polls
   * - RAID_CUSTOM_GYM_LETTERS
     - List of custom "letters" to include in gym selector, e.g. "St." or "The"
   * - RAID_DEFAULT_PICTURE
     - URL of image to use for raids if the portal photo is unknown. Only relevant for ``RAID_PICTURE``
   * - RAID_DIRECT_START
     - Bool, Allow voting for starting raids as soon as it opens
   * - RAID_DURATION
     - In minutes, default duration of raids, currently 45min
   * - RAID_DURATION_CLOCK_STYLE
     - Bool, enable showing the time a raid starts vs. duration until start
   * - RAID_EGG_DURATION
     - In minutes the maximum length of the egg phase a user is allowed to give.
   * - RAID_EXCLUDE_EXRAID_DUPLICATION
     - Bool, true to exclude ex-raids from the duplication check which allows to create an ex-raid and a normal raid at the same gym
   * - RAID_EX_GYM_MARKER
     - Enum, "icon" (default value, a star icon) or a custom text/icon to indicate an ex-raid gym in the raid polls
   * - RAID_FIRST_START
     - In minutes what the earliest timeslot is after egg has opened
   * - RAID_LAST_START
     - In minutes what the last timeslot is before the raid ends
   * - RAID_LATE_MSG
     - Bool, add a message to the raidpoll if anyone has signaled they are late.
   * - RAID_LATE_TIME
     - How many minutes to advise waiting in ``RAID_LATE_MSG``
   * - RAID_LOCATION
     - Bool, Send a separate attached location message in addition to a raid poll
   * - RAID_PICTURE
     - Bool, enable picture based raid polls instead of default text mode
   * - RAID_PICTURE_AUTOEXTEND
     - Bool, send the picture and poll as separate messages
   * - RAID_PICTURE_STORE_GYM_IMAGES_LOCALLY
     - Bool, option to store gym photos in ``images/gyms`` instead of fetching them from cloud every time
   * - RAID_PICTURE_BG_COLOR
     - List of RGB values for ``RAID_PICTURE`` poll background color, e.g. "0,0,0" for black
   * - RAID_PICTURE_FILE_FORMAT
     - Format for raid pictures for ``RAID_PICTURE``\ , valid values are gif, jpg, jpeg, png
   * - RAID_PICTURE_FONT_GYM
     - Font used for gym names for regular raids. must match a ttf or otf file under ``fonts/``. Probably should be of weight Bold.
   * - RAID_PICTURE_FONT_EX_GYM
     - Font used for gym names for ex-raids. must match a ttf or otf file under ``fonts/``. Probably should be of weight Regular.
   * - RAID_PICTURE_FONT_TEXT
     - Font used for most text in raid pictures. must match a ttf or otf file under ``fonts/``. Probably should be of weight Regular.
   * - RAID_PICTURE_HIDE_LEVEL
     - List of levels to exclude from ``RAID_PICTURE`` (will fall back to text mode)
   * - RAID_PICTURE_HIDE_POKEMON
     - List of Pokemon dex IDs to exclude from ``RAID_PICTURE`` (will fall back to text mode)
   * - RAID_PICTURE_ICONS_WHITE
     - Bool, use white icons in ``RAID_PICTURE`` instead of black
   * - RAID_PICTURE_POKEMON_ICONS
     - Comma separated list of pokemon icon sources (currently PokeMiners and ZeChrales)
   * - RAID_PICTURE_TEXT_COLOR
     - List of RGB values for ``RAID_PICTURE`` poll text color, e.g "255,255,255" for white
   * - RAID_PICTURE_POKEMON_TYPES
     - Bool, display the raid boss' typing icons in raid picture
   * - RAID_PICTURE_URL
     - Fully qualified HTTPS URL to ``raidpicture.php``\ , for example ``https://example.com/raidbot/raidpicture.php``
   * - RAID_PIN_MESSAGE
     - Custom message added to the bottom of the raid overview messages
   * - RAID_POLL_HIDE_BUTTONS_POKEMON
     - List of Pokemon dex IDs for which voting buttons are disabled
   * - RAID_POLL_HIDE_BUTTONS_RAID_LEVEL
     - List of raid levels for which voting buttons are disabled
   * - RAID_POLL_HIDE_DONE_CANCELED
     - Bool, hide the Done and Cancel buttons from raid polls
   * - RAID_POLL_HIDE_USERS_TIME
     - In minutes, after what time the previous raid slots are hidden from a raid poll
   * - RAID_POLL_UI_TEMPLATE
     - Array, Order of elements in raid polls. Supported elementa are ``alone, extra, extra_alien, remote, inv_plz, can_inv, ex_inv, teamlvl, time, pokemon, refresh, alarm, here, late, done, cancel``.
   * - RAID_POLL_POKEMON_NAME_FIRST_LINE
     - Shows the Name of the Pokemon instead of ``Raid:`` - Good for Message Preview to see which Pokemon the Raid will be.
   * - RAID_POLL_CALCULATE_MAPS_ROUTE
     - TRUE: Will show the Route to the Gym while clicking onto gym-address - FALSE: Will open Google Maps and only show the gym as a point in the map.
   * - RAID_POLL_SHOW_NICK_OVER_NAME
     - Show users Telegram @username instead of name
   * - RAID_POLL_SHOW_TRAINERCODE
     - With /trainer everyone can set his trainercode and it will be shown on raidpolls, if the trainer chooses everytime (or renamed to invite me) and inside raidalarm messages
   * - RAID_POLL_SHOW_TRAINERNAME_STRING
     - Bool, Print every attendees', who wish to be invited, trainername in copyable search string within the raid poll
   * - RAID_POLL_SHOW_START_LINK
     - Display the ``START``\ -link in raid poll that allows users to send lobby code to other participants.
   * - RAID_POLL_SHOW_CREATOR
     - Display the creator of the raid in the bottom of raid poll.
   * - RAID_POLL_ENABLE_HYPERLINKS_IN_NAMES
     - Enable hyperlinks to user profiles in participant names in raid polls. It's recommended to disable this if you're running the bot in a supergroup and with ``RAID_PICTURE`` mode on.
   * - RAID_POLL_SHOW_NICK_OVER_NAME
     - Bool, If ``CUSTOM_TRAINERNAME`` is ``false``\ , display user's Telegram nickname (@name) instead of name (first name + last name)
   * - RAID_ENDED_HIDE_KEYS
     - Bool, Hide the ``Raid done`` button in raid polls after the raid has ended
   * - RAID_REMOTEPASS_USERS_LIMIT
     - Integer, How many remote participants to allow into a single raid
   * - RAID_SLOTS
     - Amount of minutes between raid poll voting slots
   * - RAID_VIA_LOCATION
     - Bool, enable creating or sharing raids by sharing a location with the bot. Works together with ``RAID_VIA_LOCATION_FUNCTION``.
   * - RAID_VIA_LOCATION_FUNCTION
     - ``create``, ``list`` or ``remote``, which function to perform when user shares a location with the bot. ``create`` to create a permanent gym, which can later be edited, ``list`` to list all active raids nearby the location, ``remote`` to create a temporary remote raid gym.
   * - RAID_VOTE_ICONS
     - Bool, use icons on raid poll buttons
   * - RAID_VOTE_TEXT
     - Bool, use text on raid poll buttons
   * - SHARE_CHATS_LEVEL_1
     - List of Telegram chat IDs available for sharing raids of level 1
   * - SHARE_CHATS_LEVEL_2
     - List of Telegram chat IDs available for sharing raids of level 2
   * - SHARE_CHATS_LEVEL_3
     - List of Telegram chat IDs available for sharing raids of level 3
   * - SHARE_CHATS_LEVEL_4
     - List of Telegram chat IDs available for sharing raids of level 4
   * - SHARE_CHATS_LEVEL_5
     - List of Telegram chat IDs available for sharing raids of level 5
   * - SHARE_CHATS_LEVEL_X
     - List of Telegram chat IDs available for sharing Ex-Raids
   * - SHARE_CHATS
     - List of Telegram chat IDs available for sharing any raids
   * - MYSQL_SORT_COLLATE
     - Charset added to SQL query for sorting gym names
   * - TIMEZONE
     - Timezone definition to use as per `TZ database names <https://www.wikiwand.com/en/List_of_tz_database_time_zones#/List>`_
   * - TRAINER_MAX_LEVEL
     - Int, Maximum level a trainer can be (currently 50)
   * - TRAINER_BUTTONS_TOGGLE
     - Bool, true to show/hide the team and level+/- buttons below the trainer data setup messages once a users hits the "trainer info" button. False to always show the team and level+/- buttons.
   * - TRAINER_CHATS
     - List of chats where trainer data setup messages can be shared
   * - UPGRADE_SQL_AUTO
     - When a DB schema upgrade is detected, run it automatically and bump config version to match.
   * - SHARE_AFTER_ATTENDANCE
     - Bool, enable raid sharing to preset chats after first attending vote
   * - SHARE_CHATS_AFTER_ATTENDANCE
     - ID (only one) of chat to auto-share raids to after first attending vote
   * - WEBHOOK_CHATS_LEVEL_1
     - List of Telegram chat IDs to autoshare raids of level 1
   * - WEBHOOK_CHATS_LEVEL_1_0
     - List of Telegram chat IDs to autoshare raids of level 1 inside geofence ID 0
   * - WEBHOOK_CHATS_LEVEL_1_1
     - List of Telegram chat IDs to autoshare raids of level 1 inside geofence ID 1
   * - WEBHOOK_CHATS_LEVEL_2
     - List of Telegram chat IDs to autoshare raids of level 2
   * - WEBHOOK_CHATS_LEVEL_3
     - List of Telegram chat IDs to autoshare raids of level 3
   * - WEBHOOK_CHATS_LEVEL_4
     - List of Telegram chat IDs to autoshare raids of level 4
   * - WEBHOOK_CHATS_LEVEL_5
     - List of Telegram chat IDs to autoshare raids of level 5
   * - WEBHOOK_CHATS_ALL_LEVELS
     - List of Telegram chat IDs to autoshare raids of any level
   * - WEBHOOK_CHATS_BY_POKEMON
     - Automatically share only specific Pokemon to set chats. See above for further details.
   * - WEBHOOK_CREATE_ONLY
     - Bool, only create raids, don't autoshare them to any chat
   * - WEBHOOK_CREATOR
     - Telegram ID of the bot or user to credit as having created webhook raids
   * - WEBHOOK_EXCLUDE_POKEMON
     - List of Pokemon dex IDs to exclude from webhook raid creation
   * - WEBHOOK_EXCLUDE_RAID_LEVEL
     - List of raid levels to exclude from webhook raid creation
   * - WEBHOOK_EXCLUDE_UNKOWN
     - Bool, disable raid creation for gyms with "unknown" gym name.
   * - WEBHOOK_EXCLUDE_AUTOSHARE_DURATION
     - Time in minutes, skip autosharing of raids to chats if raid duration is greater than set value. Raids are still saved to the bot even if they aren't shared. (Default 45)
