Prometheus Metrics
==================

The Bot provides telemetry data of it's state as Prometheus compatible metrics.
While they can be useful for the curious, or for troubleshooting issues, the best value is gained when the metrics are ingested into Prometheus so you can
draw time series graphs and set up alerting for anything anomalous. There aren't too many metrics available yet and the names are not yet set in stone, but this feature
will mature over time.

Example Graphs
--------------

<insert examples here once we have decent ones from long-term data.>

Requirements & Installation
---------------------------

* The official Docker image takes care of all prerequisites, all you need to do is specify a token for protecting them and enable the feature.
* If installing manually:

  * You need to enable the PHP ``APCu`` extension. This is used for persisting metrics between individual calls.
  * You will need PHP Composer installed and run ``composer install`` to generate the ``vendor/`` folder that contains further dependencies.

* With any installation method you need to enable two config options:

  * ``METRICS=true``
  * ``METRICS_BEARER_TOKEN=your_super_secret_token_goes_here``

 .. warning::
    Choose a very secure bearer token. Otherwise anyone on the internet could query it.

Pulling metrics data manually
-----------------------------

Getting the data manually only gives you a snapshot of the data right now and is mostly useful for curiosity or checking on current state.

* Any web client capable of adding a custom header can be used. For example, **curl** which is available in most Linux & MacOS installations.
* The bearer token header is formatted as ``Authorization: Bearer your_token_here``
* The endpoint is available on the same port and path as your Bot's normal API. So if you've submitted the webhook with ``https://bot.example.com/`` then the metrics endpoint would be ``https://bot.example.com/metrics/``
* If you're accessing the metrics from the same machine where the bot is running, you don't need to use the domain and can for example just use ``http://localhost``.

.. code-block:: shell

   curl -L --header 'Authorization: Bearer the_token_from_above_goes_here' https://bot.example.com/metrics/

    # HELP php_info Information about the PHP environment.
    # TYPE php_info gauge
    php_info{version="7.4.26"} 1
    # HELP pokemonraidbot_ddos_last_update Last known update_id
    # TYPE pokemonraidbot_ddos_last_update gauge
    pokemonraidbot_ddos_last_update 159748283
    # HELP pokemonraidbot_ddos_state current DDoS values
    # TYPE pokemonraidbot_ddos_state gauge
    pokemonraidbot_ddos_state{user_id="123456789"} 1
    # HELP pokemonraidbot_requests_total total requests served
    # TYPE pokemonraidbot_requests_total counter
    pokemonraidbot_requests_total{endpoint="/"} 1314
    pokemonraidbot_requests_total{endpoint="raidpicture"} 29
    # HELP pokemonraidbot_uptime_seconds Seconds since metrics collection started
    # TYPE pokemonraidbot_uptime_seconds gauge
    pokemonraidbot_uptime_seconds 7441
    # HELP pokemonraidbot_webhook_raids_accepted_total Total raids received & accepted via webhook
    # TYPE pokemonraidbot_webhook_raids_accepted_total counter
    pokemonraidbot_webhook_raids_accepted_total 73
    # HELP pokemonraidbot_webhook_raids_posted_total Total raids posted automatically
    # TYPE pokemonraidbot_webhook_raids_posted_total counter
    pokemonraidbot_webhook_raids_posted_total 14
    # HELP pokemonraidbot_webhook_raids_received_total Total raids received via webhook
    # TYPE pokemonraidbot_webhook_raids_received_total counter
    pokemonraidbot_webhook_raids_received_total 4304

.. note::
    If you don't receive any data, check your bot's info log (By default ``/var/logs/tg-bots/raid-bot.log``). For security reasons the endpoint doesn't provide any errors to the user but they are logged for the admin to help debug.


Ingesting the data into Prometheus
----------------------------------

With Prometheus you can automatically pull the data and display it over time or evaluate it for alerts.
Operating prometheus is out of scope for this document, but this example shows how to provide the bearer token. For further exploring you may wish to graph the data from Prometheus with for example Grafana. The example graphs have been done in this way.

.. code-block:: yaml

   job_name: 'pokemonraidbot'
       scrape_interval: 10s
       metrics_path: /metrics/
       bearer_token: 'the_same_super_secret_token_goes_here'
       static_configs:
           - targets: [ '127.0.0.1:8088' ]
