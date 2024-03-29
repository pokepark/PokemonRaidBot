Installation with Docker
------------------------

* Official images are provided as GitHub Packages: https://github.com/orgs/pokepark/packages
* The official image contains apache2, php7 and all required PHP dependencies. It's up to you to also provide:

  * a MariaDB server (any image will work fine)
  * ~ 100MB of persistent storage for your config, access files and raid boss icons.
  * an SSL encryption reverse proxy (For example Traefik with Lets Encrypt or a traditional reverse proxy)
  * Task scheduler (such as Ofelia or plain old cron) for overview updates & cleanup.

* We also provide orchestration examples for Docker Compose and Hashicorp Nomad. These can be a good base to build a more "production grade" installation.
* The Docker image is also ready to go for utilizing Prometheus for bot metrics,  see :doc:`metrics` for how to enable it. You will need a Prometheus instance running as well to fully utilize the data.

DB Setup with Docker
^^^^^^^^^^^^^^^^^^^^

The bot will on first use initialize all database tables, assuming the DB provided in configuration exists and the user provided has access to create the tables.
This also allows for easily running the DB on a Docker instance of it's own, meaning no manual action is required to bootstrap a new database.

If you don't wish to use the automation, initialize the tables yourself before running the bot. See :doc:`manual_install` for details on manual DB initialization.

Configuring the bot with Docker
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

There are two main options for providing the configuration:

#. Create and volume mount a ``config.json`` file inside the container to ``/var/www/html/config/config.json``.
#. Set configuration parameters as environment variables that will become an autocreated ``config.json``. Any env variable starting with ``POKEMONRAIDBOT_`` is read, for example to set the config option ``DB_USER`` you would have a variable ``POKEMONRAIDBOT_DB_USER=raidbot``. For a complete example, see the later discussed ``docker/pokemonraidbot.hcl`` Nomad example.

.. warning::
    If a single variable starting with ``POKEMONRAIDBOT_`` is set, the config will be overriden and overwritten! You cannot combine a file based and env based config, but default values will still apply.

Basic operation with the Docker image
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. warning::
    * IF YOU DO NOT PERSIST THE POKEMON IMAGE FOLDERS GITHUB MAY BAN YOU (temporarily, and only if you restart the container too often).
    * It will also take a really long time to download them all, so you want to do the bulk of the downloads only once instead of every restart. :-)

* Pokemon images are downloaded / refreshed in the background on container start which will take a long time the first time around! But if you persisted the directories then the next refresh will only take seconds.
* Also you'll need to volume mount in your ``config.json`` and ``access`` folder.
* Don't override default log paths in your ``config.json``, it will break the stderr forwarding. Feel free to change what's logged though.
* Apache is started on port 80, so forward that whereever you need to. Do note that the final output towards Telegram needs to be either from port 443 or 8443 and SSL encrypted.
* You can use the optional env variable ``TAIL_LOGS`` to set which logs will be directed to stderr of the container main process. By default only PHP errors are so adding in ``info`` is recommended for any troubleshooting. This only controls what logs are forwarded, your ``config.json`` still needs to actually enable the logging!
* To refresh pokemon images, just restart the container!

Example commands to run with just Docker
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

- This basic set of commands allows you to run the bot without learning any orhestration such as Docker Compose or Nomad.
- You will need `Docker Engine <https://docs.docker.com/engine/install/>`_ installed.
- The example uses `/srv/raidbot` as a storage location. Choose any directory you won't forget to back up. :-)
- The example surfaces the bot on port `8088` which you would then route & add SSL via a forward proxy and surface as `443`.

.. code-block::

   mkdir /srv/raidbot/pokemon_PokeMiners # set your own path where you want these stored
   mkdir /srv/raidbot/access # same here, build your access files under here
   cp config/config.json.example /srv/raidbot/config.json # The sample config is a good starting point if you want to configure by editing the file
   nano /srv/raidbot/config.json # Customize the config. Alternatively you can set any config items with env variables
   chmod 600 /srv/raidbot/config.json # make the config safer since it contains your DB credentials and API key
   chown -R www-data:www-data /srv/raidbot # change ownership to www-data so the image can access them
   # Now we finally run the image
   docker run \
     -e TAIL_LOGS         = "info" \
     -e TZ                = "Europe/Helsinki" \
     -v /srv/raidbot/config.json:/var/www/html/config/config.json \
     -v /srv/raidbot/access:/var/www/html/access \
     -v /srv/raidbot/pokemon_PokeMiners:/var/www/html/images/pokemon_PokeMiners \
     -p 8088:80
     -it ghcr.io/pokepark/PokemonRaidBot:latest

SSL with Docker
^^^^^^^^^^^^^^^

.. warning::
    This step is NOT optional! Telegram will NOT talk to your bot without SSL.

* The next step is to add some sort of SSL layer on top, this is mandatory due to the requirements of the Telegram API.
* There are dozens of ways to do that, but the recommended ways are either a classic reverse proxy on the normal Hostsystem or adding a reverse proxy container (like the `companion container <https://github.com/JrCs/docker-letsencrypt-nginx-proxy-companion>`_ or using `traefik <https://docs.traefik.io/>`_\ ).
* The Raidbot container listens on port 80 and with the above example is exposed at port ``8088`` on the host.
* Not only does Telegram mandate SSL but they will only accept it served over ports ``443`` or ``8443`` with a non-wildcard certificate. So in practice it's easiest if you let LetsEncrypt deal with the details of the cert and serve it over port ``443``.

Task scheduling
^^^^^^^^^^^^^^^

* Overview refreshes & cleanup are not baked into the base Docker image. While you can definately live without these features they are quite nice to have.
* The image does have a cron daemon available but since the calls have raw json in them, quoting can be tricky to get right.
* The easiest way will be to follow the normal guidance for setting up the crons since they can be run from anywhere, not just within the container.
* A sample Ofelia setup can be seen in the Nomad & Composer orchestration examples discussed below. Ofelia by default runs the tasks within the raidbot container and it does have curl installed for this purpose.

Orchestration
^^^^^^^^^^^^^

* The raw docker run example above is only provided as an example and using some orchestration system is highly recommended in the long run.
* A sample ``docker-compose.yml`` can be found in the ``docker/`` directory. This is a full example with Ofelia & MariaDB containers included. The simpler of the two options to get started.
* A sample Nomad job can be found at ``docker/pokemonraidbot.hcl`` that also includes labels for Traefik & Ofelia integration but does not include the jobs for them.

.. |docs| image:: https://readthedocs.org/projects/pokemonraidbot/badge/?version=latest
  :target: https://pokemonraidbot.readthedocs.io/en/latest/?badge=latest
  :alt: Documentation Status
