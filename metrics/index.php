<?php

namespace Pokepark\Pokemonraidbot\Metrics;

use Prometheus\Storage\APC;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

require_once __DIR__ . '/../core/bot/paths.php';
require_once(CORE_BOT_PATH . '/logic/debug.php');
require_once(CORE_BOT_PATH . '/logic/bearer_token.php');
require_once(CORE_BOT_PATH . '/config.php');

// Authentication is done based on a Bearer Token provided as a header
$bearer_token = getBearerToken();

if (file_exists(ROOT_PATH . '/vendor/autoload.php') && $config->METRICS && $config->METRICS_BEARER_TOKEN && $bearer_token == $config->METRICS_BEARER_TOKEN) {
  require_once(ROOT_PATH . '/vendor/autoload.php');
  $registry = new CollectorRegistry(new APC());
  $renderer = new RenderTextFormat();
  $result = $renderer->render($registry->getMetricFamilySamples());

  header('Content-type: ' . RenderTextFormat::MIME_TYPE);
  echo $result;
}
