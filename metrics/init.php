<?php
$metrics = NULL
$prefix = NULL

if ($config->METRICS && $config->METRICS_BEARER_TOKEN) {
  if ($config->METRICS_BEARER_TOKEN) {
    if(!extension_loaded('apcu') || !ini_get('apcu.enabled')){
      error_log('Metrics are enabled and secured but your PHP installation does not have the APCu extension enabled which is required!');
    } else {
      // One-time log that endpoint should be working
      if !(apcu_exists('initialized')){
        info_log('Metrics endpoint enabled at /metrics and protected by a bearer token');
        apcu_store('initialized', True);
      }
      $metrics = new \Prometheus\CollectorRegistry(new Prometheus\Storage\APC());
      $prefix = 'pokemonraidbot';
      $request_counter = $metrics->registerCounter($prefix, 'request_counter', 'total requests served');
      $request_counter->inc();
    }
  } else {
    error_log('Metrics are enabled but no METRICS_BEARER_TOKEN is defined! This would be unsafe and the endpoint is disabled!')
  }
}
