<?php
// Available in global context for metrics use in general
$metrics = NULL;
$prefix = NULL;
// Counter used by any endpoint to record requests
$requests_total = NULL;

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

      $requests_total = $metrics->registerCounter($prefix, 'requests_total', 'total requests served', ['endpoint']);
      $uptime_seconds = $metrics->registerGauge($prefix, 'uptime_seconds', 'Seconds since metrics collection started');
      $uptime_seconds->set(time() - apcu_fetch($prefix));
    } else {
      error_log('Metrics are enabled and secured but your PHP installation does not have the APCu extension enabled which is required!');
    }
  } else {
    error_log('Metrics are enabled but no METRICS_BEARER_TOKEN is defined! This would be unsafe and the endpoint is disabled!');
  }
}
