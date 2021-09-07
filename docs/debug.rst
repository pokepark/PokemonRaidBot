Debugging issues
================

So you did everything correctly and now it doesn't work?
There's many small things that can go wrong so let's step through the ways in which to try to debug the bot.

Checking logs
-------------

  #. Very first thing, did the bot send the admin user in Telegram an error message? This is used for the most serious issues or situations where the logs don't work. Resolving that error will get you further.
  #. Check the bots log itself. This by default is ``/var/log/tg-bots/raid-bot.log``. By default only "useful" things are logged so if it's an issue we've seen before hopefully the log can already help you out.
  #. If the info log didn't help, let's see if PHP is crashing. For that check your webservers error log. This depends on what you're using but the most common examples are:
       * Apache by default uses ``/var/log/apache2/error.log``
       * Nginx will likely use something like ``/var/log/php/php7.4-fpm.log``
       * Any issue found only here should likely be reported as a bug so we can make it easier to detect and fix the issue, or fix the bug that caused it!
  #. As a last resort, set in your config ``DEBUG`` (or if using env configuration ``POKEMONRAIDBOT_DEBUG``) to ``true``. By default this is logged to the same file, i.e. ``/var/log/tg-bots/raid-bot.log`` but can be overriden with ``DEBUG_LOGFILE``.
       * Debug logging is very verbose so it may take some digging to find out what's going on, but it will solve most issues.
  #. If you're troubleshooting something specific to cleanups you may want to turn on ``CLEANUP_LOG`` and check by default ``/var/log/tg-bots/raid-bot-cleanup.log``. Do remember to turn it off afterwards though, the log can fill up quick if you run cleanup often!
  #. If you're troubleshooting something where you're not sure if the bot is receiving data correctly, either from Telegram or from webhook you may want to enable ``DEBUG_INCOMING`` and check by default ``/var/log/tg-bots/raid-bot-incoming.log``
       * This logging is extremely verbose and can be hard to read.
       * One trick is to give it the same log path with ``DEBUG_INCOMING_LOGFILE`` as you have for other logs, thus combining all outout into one superlog. It will go very fast, but you have maximum context for everything in one log.

Asking for help
---------------

If you can't make sense of the logs yourself, pop by our Telegram channel https://t.me/PokeParkSupport.

.. warning::
    If you give us anything from your logs, be sure to remove your Telegram API key from them! The most common mistake is pasting lines from the access.log which has the full URL, including the API key!
