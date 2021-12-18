<?php

namespace Pokepark\Pokemonraidbot\Metrics;

use Prometheus\Storage\APC;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;


if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
  require_once __DIR__ . '/../vendor/autoload.php';
  $registry = new CollectorRegistry(new APC());
  $renderer = new RenderTextFormat();
  $result = $renderer->render($registry->getMetricFamilySamples());

  header('Content-type: ' . RenderTextFormat::MIME_TYPE);
  echo $result;
}
