<?php

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
