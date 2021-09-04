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

Create a new mysql database and user for your bot.

Command to access the terminal as root user: ``mysql -u root -p``

Command to create a user with localhost access (Only allow localhost access!): ``CREATE USER 'USERNAME'@'localhost' IDENTIFIED BY 'PASSWORD';``

Command to create a database: ``CREATE DATABASE DATABASENAME;``

Command to create privileges for new user to database: ``GRANT ALL PRIVILEGES ON DATABASENAME.* TO 'USERNAME'@'localhost';``

Flush privileges: ``FLUSH PRIVILEGES;``

Just use exit to logout from database.

Import ``pokemon-raid-bot.sql`` as default DB structure and ``game-master-raid-boss-pokedex.sql`` for the latest data of Pokemon in the game. You can find these files in the sql folder.

Command DB structure: 

.. code-block::

   mysql -u USERNAME -p DATABASENAME < sql/pokemon-raid-bot.sql

Important: To fill the pokemon table with all pokemon currently available in the game and to set their raid level you need to run /pokedex command.
