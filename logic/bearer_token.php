<?php

/**
 * Get header Authorization
 * */
function getAuthorizationHeader(){
  if (isset($_SERVER['Authorization'])) {
    return trim($_SERVER["Authorization"]);
  }
  if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
    return trim($_SERVER["HTTP_AUTHORIZATION"]);
  }
  if (function_exists('apache_request_headers')) {
    $requestHeaders = apache_request_headers();
    if (isset($requestHeaders['Authorization'])) {
      return trim($requestHeaders['Authorization']);
    }
  }
  return null;
}

/**
 * get access token from header
 * */
function getBearerToken() {
  $headers = getAuthorizationHeader();
  if (!empty($headers) && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
    return $matches[1];
  }
  return null;
}
