Manual installation
===================

If you wish to install manually (or can't run Docker containers anywhere) we also support manual installation. In theory you can even host on an FTP & PHP enabled webhost, but it will be somewhat more painful than a Linux VPS or server of your own.

Webserver requirements
----------------------

Preferrably:

* Apache2
* PHP7
* MySQL5 or MariaDB10
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

Database
--------

Create a new mysql database and user for your bot, granting full priviledges for the db. For example:

.. code-block:: shell
    mysql -u root -p
    MariaDB [(none)]> CREATE USER 'username'@'localhost' IDENTIFIED BY 'PASSWORD';
    MariaDB [(none)]> CREATE DATABASE databasename;
    MariaDB [(none)]> GRANT ALL PRIVILEGES ON databasename.* TO 'username'@'localhost';
    MariaDB [(none)]> FLUSH PRIVILEGES;
    MariaDB [(none)]> exit

The DB structure is imported the first time the bot is used. If it fails for any reason you can also run it manually:

.. code-block:: shell
    mysql -u username -p databasename < sql/pokemon-raid-bot.sql

Important: To fill the pokemon table with all pokemon currently available in the game and to set their raid level you need to run /pokedex command.
