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

Updating details or raid bosses:

``curl -k -d '{"callback_query":{"data":"0:getdb:0"}}' https://localhost/botdir/index.php?apikey=111111111:AABBccddEEFFggHHiijjKKLLmmnnOOPPqq``

Updating available icons for picture mode:

``php getPokemonIcons.php``
