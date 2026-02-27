Manual installation
===================

If you wish to install manually (or can't run Docker containers anywhere) we also support manual installation. In theory you can even host on an FTP & PHP enabled webhost, but it will be somewhat more painful than a Linux VPS or server of your own.

Webserver requirements
----------------------

Preferably:

* Apache2
* PHP8.1
* MySQL5 or MariaDB10. MariaDB is preferred, MySQL8 will cause some warnings.
* Curl
* SSL Certificate ( https://www.letsencrypt.org )

The following apache packages need to be installed:

* PDO_mysql (ubuntu: php-mysql)
* PHP_curl (ubuntu: php-curl)
* PHP_GD (ubuntu: php-gd) for raid picture mode

Git clone
---------
Clone the repository into your web root, for example ``/var/www/html``.
 You can also clone & chown locally and then copy the files to your webhosting, even over FTP! Just be sure that file ownership is correct.

.. code-block:: shell

    git clone https://github.com/pokepark/PokemonRaidBot.git
    mv PokemonRaidBot /var/www/html/
    chown -R www-data:www-data /var/www/html/PokemonRaidBot


.. note::
    If you intend to run the bot from another server and don't have a webserver installed locally, you may not have the www-data user and chown will fail. In this case instead use a numeric id: ``33:33``. This matches the Debian / Ubuntu default but your hosting provider may use a different one!


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


Configuration
-------------

After install, proceed to :doc:`config` and if you run into trouble, see :doc:`debug`.
