Maintenance
===========
Most things have either been automated or we've provided ways in which you can automate them in a way that best suits your community.

Updates
-------

The bot has a version system and checks for updates to the database automatically. If automatic db upgrades are disabled, the bot will send a message to the MAINTAINER_ID when an upgrade is required. In case the MAINTAINER_ID is not specified an error message is written to the error log of your webserver.

Required SQL upgrades files can be found under the ``sql/upgrade`` folder and are applied automatically by default.

Updates to the config file are NOT checked automatically. Therefore always check for changes to the config.json.example and add any new config variables you want to override to your own config.json. Most new variables should get added to defaults-config.json so you'll get the new default automatically on update. While sometimes new features are enabled by default, this isn't always the case, so check the :doc:`config` document for more details on new features.

Local updates
-------------

To keep local data, such as ``pokemon`` table and Pokemon icons directory, up to date, you can schedule some scripts to be run.

Updating ``pokemon`` table with data from latest game master file:

``curl -k -d '{"callback_query":{"data":"0:getdb:0"}}' https://localhost/botdir/index.php?apikey=111111111:AABBccddEEFFggHHiijjKKLLmmnnOOPPqq``

Updating available icons for picture mode:

``php getPokemonIcons.php``

To automatically keep the raid boss data somewhat up to date, you can schedule the following command:

``curl -k -d '{"callback_query":{"data":"LEVELS:update_bosses:SOURCE"}}' https://localhost/botdir/index.php?apikey=111111111:AABBccddEEFFggHHiijjKKLLmmnnOOPPqq``

Currently supported arguments for LEVELS are raid levels ``1, 3, 5, 6`` in comma separated string, and ``scheduled`` to execute import of scheduled info for tier 5 and 6 raids.

Currently supported arguments for SOURCE are ``pogoinfo``, which is only used when importing specific levels.

For the best results you can use these two commands together:
``curl -k -d '{"callback_query":{"data":"1,3:update_bosses:pogoinfo"}}' https://localhost/botdir/index.php?apikey=111111111:AABBccddEEFFggHHiijjKKLLmmnnOOPPqq
curl -k -d '{"callback_query":{"data":"scheduled:update_bosses:0"}}' https://localhost/botdir/index.php?apikey=111111111:AABBccddEEFFggHHiijjKKLLmmnnOOPPqq``

Because these scripts can't exclude regional raid bosses from the import, you probably want to disable the auto update during these bosses. You can do that by sending the bot this command:

``/set ENABLE_BOSS_AUTO_UPDATE 0``
