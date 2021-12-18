<?php

namespace Pokepark\Pokemonraidbot\Metrics;

use Prometheus\Storage\APC;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

require_once __DIR__ . '/../core/bot/paths.php';
require_once(CORE_BOT_PATH . '/logic/debug.php');
require_once(CORE_BOT_PATH . '/config.php');

/**
 * Get header Authorization
 * */
function getAuthorizationHeader(){
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }
/**
 * get access token from header
 * */
function getBearerToken() {
    $headers = getAuthorizationHeader();
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}


$bearer_token = getBearerToken();

if (file_exists(__DIR__ . '/../vendor/autoload.php') && $config->METRICS && $config->METRICS_BEARER_TOKEN && $bearer_token == $config->METRICS_BEARER_TOKEN) {
  require_once __DIR__ . '/../vendor/autoload.php';
  $registry = new CollectorRegistry(new APC());
  $renderer = new RenderTextFormat();
  $result = $renderer->render($registry->getMetricFamilySamples());

  header('Content-type: ' . RenderTextFormat::MIME_TYPE);
  echo $result;
}
