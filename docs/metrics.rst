Prometheus Metrics
==================

The Bot provides telemetry data of it's state as Prometheus compatible metrics.
While they can be useful for the curious, or for troubleshooting metrics as-is, the best value is gained when the metrics are ingested into Prometheus so you can
draw time series graphs and set up alerting for anything anomalous. There aren't too many metrics available yet and the names are not yet set in stone, but this feature
will mature over time.

Example Graphs
--------------

<insert examples here once we have decent ones!>

Requirements & Installation
---------------------------

- The official Docker image takes care of all prerequisites, all you need to do is specify a token for proteting them and enable the feature.
- If installing manually:
  - You need to enable the PHP ``APCu`` extension. This is used for persisting metrics between individual calls.
  - You will need PHP Composer installed and run ``composer install`` to generate the ``vendor/`` folder that contains further dependencies.
- With any installation method you need to enable two config options:
  - ``METRICS=true``
    ``METRICS_BEARER_TOKEN=your_super_secret_token_goes_here``

 .. warning::
    Choose a very secure bearer token. Otherwise anyone on the internet could query it.

Pulling metrics data manually
-----------------------------

Getting the data manually only gives you a snapshot of the data right now and is mostly useful for curiosity or checking on current state.

.. code-block::
   curl -L --header 'Authorization: Bearer the_token_from_above_goes_here' http://127.0.0.1:8088/metrics/

.. note::
    If you don't receive any data, check your bot's info log (By default ``/var/logs/tg-bots/raid-bot.log``). For security reasons the endpoint doesn't provide any errors to the user but they are logged for the admin to help debug.


Ingesting the data into Prometheus
----------------------------------

With Prometheus you can automatically pull the data and display it over time or evaluate it for alerts.
Operating prometheus is out of scope for this document, but this example shows how to provide the bearer token:

.. code-block::
   job_name: 'pokemonraidbot'
       scrape_interval: 10s
       metrics_path: /metrics/
       bearer_token: 'the_same_super_secret_token_goes_here'
       static_configs:
           - targets: [ '127.0.0.1:8088' ]

.. warning::
    This step is NOT optional! Telegram will NOT talk to your bot without SSL.
