<?php

/**
 * Verify a GET param apikey and return a provided update Array
 * @return Array
 */
function get_verified_update(){
  global $config;

  // Get api key from get parameters.
  if(isset($_GET['apikey'])) {
      $apiKey = $_GET['apikey'];
  // Get api key from argv.
  } elseif(!empty($argv[1])) {
      $apiKey = $argv[1];
  } else {
      debug_log('Called without apikey, returning empty content.');
      http_response_code(204); // HTTP 204: No Content
      exit();
  }

  // Check if hashed api key is matching config.
  if (hash('sha512', $apiKey) == strtolower($config->APIKEY_HASH)) {
      // Set constants.
      define('API_KEY', $apiKey);
  } else {
      info_log('Incorrect apikey provided! This is most likely a misconfiguration you should fix.');
      http_response_code(403); // HTTP 403: Forbidden
      exit();
  }

  // Get content from POST data.
  $update = null;
  $content = file_get_contents('php://input');

  if($content) {
    // Decode the json string.
    $update = json_decode($content, true);
  } elseif(!empty($argv[2])) {
    $arg_content = addslashes($argv[2]);
    $update = json_decode($argv[2], true);
  }

  if ($update) {
    debug_log_incoming($update, '<');
  }

  return $update;
}
