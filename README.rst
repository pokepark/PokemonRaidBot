
PokemonRaidBot
==============

|docs|

Telegram webhook bot for organizing raids in Pokemon Go. Developers & Admins are welcome to join https://t.me/PokeParkSupport

Documentation
-------------

This README only contains very limited information. For detailed configuration and installation help see the `full documentation <https://pokemonraidbot.readthedocs.io>`_.

Example screenshots
-------------------

These are very old screenshots, they'll be replaced with prettier modern ones soon!

*With the ex-raid notice:*


.. image:: /screens/raid-poll-example-with-ex-raid-message.png?raw=true
   :target: /screens/raid-poll-example-with-ex-raid-message.png?raw=true
   :alt: Example raid poll


*Showing the users teams & levels (if they've set it), status (late, cancel and done), attend times and preferred pokemons (if raid boss is still a raid egg) the users voted for:*


.. image:: /screens/raid-poll-example-with-late.png?raw=true
   :target: /screens/raid-poll-example-with-late.png?raw=true
   :alt: Example raid poll


.. image:: /screens/raid-poll-example-with-cancel.png?raw=true
   :target: /screens/raid-poll-example-with-cancel.png?raw=true
   :alt: Example raid poll


.. image:: /screens/raid-poll-example-with-done.png?raw=true
   :target: /screens/raid-poll-example-with-done.png?raw=true
   :alt: Example raid poll


Installation and configuration
==============================

We recommend Docker for novice admins since it's the most controlled environment but it also still has many pitfalls associated. The `full documentation <https://pokemonraidbot.readthedocs.io>`_ has more installation options including fully manual into any LAMP webhosting solution.

Docker
------

* Official images are provided as GitHub Packages: https://github.com/orgs/pokepark/packages
* The official image contains only an apache2 hosting the php, it's up to you to also provide:

  * a MariaDB server (any image will work fine)
  * an SSL encryption reverse proxy (For example Traefik with Lets Encrypt or a traditional reverse proxy)
  * Task scheduler (such as Ofelia or plain old cron) for overview updates & cleanup.

Basic operation with the Docker image
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* You can use the optional env variable ``TAIL_LOGS`` to set which logs will be directed to stderr of the container main process. By default only PHP errors are so adding in ``info`` is recommended for any troubleshooting. This only controls what logs are forwarded, your ``config.json`` still needs to actually enable the logging!
* Volume mount in your config AND the pokemon image folders!
* Don't override default log paths in your config.json, it will break the stderr forwarding.
* IF YOU DO NOT PERSIST THE POKEMON IMAGE FOLDERS GITHUB MAY BAN YOU (temporarily, and only if you restart the container too often).
* Pokemon images are downloaded / refreshed in the background on container start which will take a long time the first time around! But if you persisted the directories then the next refresh will only take seconds.
* Apache is started on port 80, so forward that whereever you need to. Do note that the final output towards Telegram needs to be either from port 443 or 8443 and SSL encrypted.
* To refresh pokemon images, just restart the container!

.. code-block::

   mkdir /path/to/persistent/images/pokemon_PokeMiners # set your own path where you want these stored
   mkdir /path/to/persistent/images/pokemon_ZeChrales # same here, and use these paths in the command below
   mkdir /path/to/persistent/access # same here, build your access files under here
   docker run \
     -e TAIL_LOGS         = "info" \
     -e TZ                = "Europe/Helsinki" \
     -e TAIL_LOGS         = "info"                 
     -e TEMPLATE_PHP_INI  = "production" \
     -e PHP_INI_EXTENSION = "gd" \
     -v /path/to/persistent/config.json:/var/www/html/config/config.json \
     -v /path/to/persistent/access:/var/www/html/access \
     -v /path/to/persistent/images/pokemon_PokeMiners:/var/www/html/images/pokemon_PokeMiners \
     -v /path/to/persistent/images/pokemon_ZeChrales:/var/www/html/images/pokemon_ZeChrales \
     -p 8088:80
     -it ghcr.io/pokepark/PokemonRaidBot:latest

SSL with Docker
^^^^^^^^^^^^^^^

* The next step is to add some sort of SSL layer on top, this is mandatory due to the requirements of the Telegram API.
* There are dozens of ways to do that, but the recommended ways are either a classic reverse proxy on the normal Hostsytem or adding a reverse proxy container (like the `companion container <https://github.com/JrCs/docker-letsencrypt-nginx-proxy-companion>`_ or using `traefik <https://docs.traefik.io/>`_\ ).
* The Raidbot container listens on port 80 and with the above example is exposed at port ``8088`` on the host.

Task scheduling
^^^^^^^^^^^^^^^

* Overview refreshes & cleanup are not baked into the base Docker image.
* The image does have a cron daemon available but since the calls have raw json in them, quoting can be tricky to get right.
* The easiest way will be to follow the normal guidance for setting up the crons since they can be run from anywhere, not just within the container.
* A sample Ofelia setup can be seen in the Nomad & Composer orchestration examples discussed below.

Orchestration
^^^^^^^^^^^^^

* The raw docker run example above is only provided as an example and using some orchestration system is highly recommended in the long run.
* A sample ``docker-compose.yml`` can be found in the ``docker/`` directory. This is a full example with Ofelia & MariaDB containers included.
* A sample Nomad job can be found at ``docker/pokemonraidbot.hcl``\ , it also includes labels for Traefik & Ofelia integration but does not include the jobs for them.

.. |docs| image:: https://readthedocs.org/projects/pokemonraidbot/badge/?version=latest
  :target: https://pokemonraidbot.readthedocs.io/en/latest/?badge=latest
  :alt: Documentation Status
