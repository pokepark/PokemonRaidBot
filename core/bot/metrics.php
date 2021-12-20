<?php
$metrics = NULL;
$prefix = NULL;
$request_counter = NULL;

if ($config->METRICS) {
  if ($config->METRICS_BEARER_TOKEN) {
    if(extension_loaded('apcu') && apcu_enabled()){
      $prefix = 'pokemonraidbot';
      $metrics = new \Prometheus\CollectorRegistry(new Prometheus\Storage\APC());

      // One-time init tasks
      if (!apcu_exists($prefix)){
        info_log('Metrics endpoint enabled at /metrics and protected by a bearer token');
        apcu_store($prefix, time());
      }

      $request_counter = $metrics->registerCounter($prefix, 'request_counter', 'total requests served', ['endpoint']);
      $uptime_counter = $metrics->registerGauge($prefix, 'uptime', 'Seconds since metrics collection started');
      $uptime_counter->incBy(time() - apcu_fetch($prefix));
    } else {
      error_log('Metrics are enabled and secured but your PHP installation does not have the APCu extension enabled which is required!');
    }
  } else {
    error_log('Metrics are enabled but no METRICS_BEARER_TOKEN is defined! This would be unsafe and the endpoint is disabled!');
  }
}
