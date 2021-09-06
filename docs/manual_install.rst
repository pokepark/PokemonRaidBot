Manual installation
===================

If you wish to install manually (or can't run Docker containers anywhere) we also support manual installation. In theory you can even host on an FTP & PHP enabled webhost, but it will be somewhat more painful than a Linux VPS or server of your own.

Webserver requirements
----------------------

Preferably:

* Apache2
* PHP7
* MySQL5 or MariaDB10. MariaDB is preferred, MySQL8 will cause some warnings.
* Curl
* SSL Certificate ( https://www.letsencrypt.org )

The following apache packages need to be installed:

* PDO_mysql (ubuntu: php-mysql)
* PHP_curl (ubuntu: php-curl)
* PHP_GD (ubuntu: php-gd) for raid picture mode

Git clone
---------
Clone the repository into your web root, for example ``/var/www/html``

``git clone https://github.com/pokepark/PokemonRaidBot.git``

Bot token
---------

To obtain a new bot token from Telegram, start a chat with https://t.me/BotFather and create a bot token.

Bot Settings:

* Enable Inline mode
* Allow Groups
  * Group Privacy off

Database initialization
-----------------------

There's nothing special here really, all we need is an empty database and a user with full priviledges to it.
If you don't want the automatic table creation you can also run it manually as instructed below.

.. code-block:: shell

    mysql -u username -p databasename < sql/pokemon-raid-bot.sql

.. note::
    To fill the database with all pokemon currently available in the game and to set their raid level you need to run /pokedex command and import from a source of your choosing. For more information, see :doc:`usage`
