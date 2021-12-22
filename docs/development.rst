Development
===========

Adding new config values
------------------------

* Any previously undefined config value needs a sane default value in ``config/defaults-config.json``. Anyone updating will get that default!
* If the new config item is something people will likely want to override, add it to ``config/config.json.example``.
* You can access the new config item in code with ``$config->CONFIG_ITEM_NAME`` but if inside a function, remember to specify ``global $config;``
* Don't break backwards compatibility if you can.

Adding new metrics
------------------

* Adding new metrics is relatively simple, see `core/bot/ddos.php` for many examples of different metrics gathering.
* The ``$metrics`` and ``$prefix`` objects are available in the global scope, so within a function you will need to first call ``global $metrics, $prefix;``
* Metrics should be named per the `Prometheus best practices <https://prometheus.io/docs/practices/naming/>`_
* Only base metrics should be recorded, anything that can be derived from a base metric should not be.

Schema changes
--------------

Schema changes should be done sparingly and grouped into larger updates that are first built in the ``dev`` branch. Once the branch is merged into ``main``
the schema version is final and immutable and any schema changes need to happen in the next version.

* If the schema version has been raised, a matching sql upgrade file must exist, for example ``sql/upgrade/3.sql``
* The ``VERSION`` file contains the schema version required by the code it's checked in with.
* The ``config/config.json`` file is expected to have a config item ``VERSION`` that records the latest schema that the DB has been upgraded to.
* Schema upgrades should be **idempotent** i.e. can be run multiple times with no adverse effects. During development setting your config version to less than the current schema should always work to get you to the latest schema.


Translations
------------

Translations are stored in ``lang/language.json`` and ``core/lang/language.json``. Any string marked as ``TRANSLATE`` hasn't been translated yet. These can be changed by hand but if you want to add a new language or do large scale translation, using translate.py is recommended.

translate.py
^^^^^^^^^^^^

To help in adding a new translation or improving an existing one the ``lang/`` folder has a tool called ``translate.py``
It will add placeholders for a new language and allow you to incrementally and interatively translate strings. All changes are saved as you go.

By default:

* Translations are read from and saved directly into language.json but any other file(s) can be specified with ``--input`` and ``--output``
* The current English translation is shown as context. The language can be chosen with ``--from_language``
* Only missing translations are prompted (incremental mode), use ``--noincremental`` or ``--incremental=False`` to prompt every string.
* No default language to translate into is specified, it must be given with ``--to <language_code>``

We'll run through an example, for full options see ``translate.py --help``

.. code-block:: shell

   % cd lang/
   % pip3 install -r requirements.txt # install required libraries
   % ./translate.py --to=FI # FI here is the language code of the new or existing language
   I1130 18:29:47.547245 139886869309248 translate.py:21] Creating placeholders for missing strings for language FI
   Press ^D or ^C to stop. Leave a translation empty to skip.
   I1130 18:29:47.556554 139886869309248 translate.py:30] Iterating over strings that have not been translated to language FI
   raid[EN]: Raid
   raid[FI]:

Enter translations as long as you want. You can skip translating a string by just leaving it empty, i.e. pressing enter. Press Ctrl-C or Ctrl-D to exit the tool, you won't lose any translations you've already made.
