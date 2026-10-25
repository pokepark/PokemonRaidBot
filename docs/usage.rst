PokemonRaidBot usage
====================

Send your location to the bot
-----------------------------

If ``RAID_LOCATION`` is set to ``true`` (default), the bot will guide you through the creation of a raid poll based on the settings in the config file. ``RAID_VIA_LOCATION_FUNCTION`` determines the actions taken after location is received.

By default the bot will ask you for the raid level, the pokemon raid boss, the time until the raids starts and the time left for the raid. Afterwards you can set the gym name by using the /gym or /gymname commands.

For further details please refer to :doc:`config#raid-creation-options`

Using inline search of @PortalMapBot or @Ingressportalbot
---------------------------------------------------------

You can add new gyms to the bot using the inline search of one of the bots mentioned above. Just search for a portal name, e.g. ``Brandenburger Tor``\ , and select one of the portals shown as result of your search.

On selection the portal information will get posted as a normal message and detected, so a new gym is automatically created from the portal info in that message.

In case the portal is already in your gym list / database, it will get updated with the new info (latitude, longitude and address) from the message.

Example: ``@PortalMapBot Brandenburger Tor``

Command reference
-----------------

Command: /start
^^^^^^^^^^^^^^^

The bot will guide you through the creation of the raid poll by asking you for the gym, raid level, the pokemon raid boss, the time until the raid starts and the time left for the raid. Afterwards you can set the gym team by using the /team command.

To search for the gym by partial or full name you can use ``/start gym name``\ , e.g. ``/start Brandenburger Tor``

Send ``/start`` to the bot to create a raid by gym selection:


.. image:: /screens/command-start.png
   :target: /screens/command-start.png
   :alt: Command: /start


Select the gym via the first letter:


.. image:: /screens/commands-start-select-gym-first-letter.png
   :target: /screens/commands-start-select-gym-first-letter.png
   :alt: Command: /start


.. image:: /screens/commands-start-select-gym-letter-d.png
   :target: /screens/commands-start-select-gym-letter-d.png
   :alt: Command: /start


Select the raid level and raid boss:


.. image:: /screens/commands-start-select-raid-level.png
   :target: /screens/commands-start-select-raid-level.png
   :alt: Command: /start


.. image:: /screens/commands-start-select-raid-boss.png
   :target: /screens/commands-start-select-raid-boss.png
   :alt: Command: /start


Select the start time (clock time or minutes) and the duration of the raid:


.. image:: /screens/commands-start-select-starttime-clock.png
   :target: /screens/commands-start-select-starttime-clock.png
   :alt: Command: /start


.. image:: /screens/commands-start-select-starttime-minutes.png
   :target: /screens/commands-start-select-starttime-minutes.png
   :alt: Command: /start


.. image:: /screens/commands-start-select-raid-duration.png
   :target: /screens/commands-start-select-raid-duration.png
   :alt: Command: /start


Raid poll is created. Delete or share it:


.. image:: /screens/commands-start-raid-saved.png
   :target: /screens/commands-start-raid-saved.png
   :alt: Command: /start


Command: /help
^^^^^^^^^^^^^^

The bot will give a personal help based on the permissions you have to access and use it.

Command: /pokemon
^^^^^^^^^^^^^^^^^

Update pokemon of an existing raid poll. With this command you can change the pokemon raid boss from e.g. "Level 5 Egg" to "Lugia" once the egg has hatched.

Based on your access to the bot, you may can only change the pokemon raid boss of raid polls you created yourself and cannot modify the pokemon of raid polls from other bot users.

Command: /pokedex
^^^^^^^^^^^^^^^^^

Show and update any pokemon raid boss. You can change the raid level (select raid level 0 to disable a raid boss), pokemon CP values and weather information of any pokemon raid boss.

There is also a possibility to import the raids bosses from Pokebattler and disable all raid bosses for all or just a specific raid level which makes raid boss management pretty easy.

To quickly get to a specific pokemon raid boss, you can use the /pokedex command with the local name of the pokemon to get a list of it's all formes. A few examples:

.. list-table::
   :header-rows: 1

   * - Search term
     - Response
   * - ``/pokedex Deoxys``
     - ``Deoxys``, ``Deoxys Attack``, ``Deoxys Defense``, ``Deoxys Speed``
   * - ``/pokedex Charizard``
     - ``Charizard``, ``Charizard Copy 2019`` (clone), ``Charizard Mega X``, ``Charizard Mega Y``
   * - ``/pokedex Knogga``
     - ``Knogga``, ``Knogga Alola``


User's local language is fetched from their Telegram settings.

Beside your local language the bot always is looking at the English language as a fallback.

Manage pokemons / raid bosses via the ``/pokedex`` command:


.. image:: /screens/command-pokedex.png
   :target: /screens/command-pokedex.png
   :alt: Command: /pokedex


All raid bosses:


.. image:: /screens/commands-pokedex-all-raid-bosses.png
   :target: /screens/commands-pokedex-all-raid-bosses.png
   :alt: Command: /pokedex


Select and edit a specific pokemon / raid boss:


.. image:: /screens/commands-pokedex-list-raid-boss-pokemon.png
   :target: /screens/commands-pokedex-list-raid-boss-pokemon.png
   :alt: Command: /pokedex


.. image:: /screens/commands-pokedex-edit-raid-boss-pokemon.png
   :target: /screens/commands-pokedex-edit-raid-boss-pokemon.png
   :alt: Command: /pokedex


Edit the raid level:


.. image:: /screens/commands-pokedex-set-raid-level.png
   :target: /screens/commands-pokedex-set-raid-level.png
   :alt: Command: /pokedex


.. image:: /screens/commands-pokedex-saved-new-raid-level.png
   :target: /screens/commands-pokedex-saved-new-raid-level.png
   :alt: Command: /pokedex


Edit the CP values, e.g. Max CP:


.. image:: /screens/commands-pokedex-enter-max-cp.png
   :target: /screens/commands-pokedex-enter-max-cp.png
   :alt: Command: /pokedex


.. image:: /screens/commands-pokedex-save-max-cp.png
   :target: /screens/commands-pokedex-save-max-cp.png
   :alt: Command: /pokedex


.. image:: /screens/commands-pokedex-saved-new-max-cp.png
   :target: /screens/commands-pokedex-saved-new-max-cp.png
   :alt: Command: /pokedex


Edit the weather:


.. image:: /screens/commands-pokedex-set-weather.png
   :target: /screens/commands-pokedex-set-weather.png
   :alt: Command: /pokedex


Command: /list
^^^^^^^^^^^^^^

The bot will allow you to get a list of the last 12 active raids and re-share or delete them.

In case more than 12 active raids are happening, the bot will automatically call the /listall command

List existing raid polls with the ``/list`` command:


.. image:: /screens/command-list.png
   :target: /screens/command-list.png
   :alt: Command: /list



.. image:: /screens/commands-list-active-raids.png
   :target: /screens/commands-list-active-raids.png
   :alt: Command: /list


Command: /listall
^^^^^^^^^^^^^^^^^

The bot will allow you to get all active raids and re-share or delete them. The raids are grouped by gyms and their first letter or custom letters.

Command: /overview
^^^^^^^^^^^^^^^^^^

Share and delete the raid overview message.

Share overview message with all raids shared to channel "Chat-Name" to the channel:


.. image:: /screens/commands-list-share-overview.png
   :target: /screens/commands-list-share-overview.png
   :alt: Command: /overview


Delete the shared overview message:


.. image:: /screens/commands-list-delete-overview.png
   :target: /screens/commands-list-delete-overview.png
   :alt: Command: /overview


Command: /delete
^^^^^^^^^^^^^^^^

Delete an existing raid poll. With this command you can delete a raid poll from Telegram and the database. Use with care!

Based on your access to the bot, you may can only delete raid polls you created yourself and cannot delete raid polls from other bot users.

Delete an existing raid poll with the ``/delete`` command:


.. image:: /screens/command-delete.png
   :target: /screens/command-delete.png
   :alt: Command: /delete


.. image:: /screens/commands-delete-raid-deleted.png
   :target: /screens/commands-delete-raid-deleted.png
   :alt: Command: /delete


Command: /trainer
^^^^^^^^^^^^^^^^^

Users can use this command to set their trainer name, friend code, team, level and if configured, personal bot settings (private language and automatic raid alarms).

For users with proper access rights the bot will also give you a list of chats to share the trainer message which allows users to set team and level+/- data. You can also delete the shared trainer messages via the ``/trainer`` command.

Command: /history
^^^^^^^^^^^^^^^^^

Tool for admins to view history of raids that had at least one person signed up for it.

Command: /events
^^^^^^^^^^^^^^^^

Tool for admins to edit raid events. The UI is very simple and for some stuff you need to refer to this documentation.

Command: /gym
^^^^^^^^^^^^^

The bot will show the details of each gym. Additionally you can change the extended gym details to hide/show gyms under ``/start`` as well as mark/un-mark them as ex-raid gym.

Example input: ``/gym``

Command: /gymname
^^^^^^^^^^^^^^^^^

The bot will set the name of gym to your input. If you submitted a gym via location sharing you can use it without an id. Otherwise the id of the gym is required.

Example input: ``/gymname Siegessäule``

Example input with gym id: ``/gymname 34, Siegessäule``
