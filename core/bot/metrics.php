<?php
// Available in global context for metrics use.
// Any metrics interaction should be guarded by `if ($metrics)`
$metrics = NULL;
// Counter used by any endpoint to record requests
$requests_total = NULL;

if ($config->METRICS) {
  if ($config->METRICS_BEARER_TOKEN) {
    if(extension_loaded('apcu') && apcu_enabled()){
      $metrics = new \Prometheus\CollectorRegistry(new Prometheus\Storage\APC());

      // One-time init tasks
      if (IS_INIT){
        info_log('Metrics endpoint enabled at /metrics and protected by a bearer token');
      }

      /* Metrics in the global scope */
      // This should be updated by any unique php endpoint called directly, except /metrics/
      $requests_total = $metrics->registerCounter($namespace, 'requests_total', 'total requests served', ['endpoint']);
      // "uptime" is the lifetime of this instance. It and all the metrics will be reset if the PHP runtime restarts for any reason.
      $uptime_seconds = $metrics->registerGauge($namespace, 'uptime_seconds', 'Seconds since metrics collection started');
      $uptime_seconds->set(time() - apcu_fetch($namespace));
    } else {
      error_log('Metrics are enabled and secured but your PHP installation does not have the APCu extension enabled which is required!');
    }
  } else {
    error_log('Metrics are enabled but no METRICS_BEARER_TOKEN is defined! This would be unsafe and the endpoint is disabled!');
  }
}
