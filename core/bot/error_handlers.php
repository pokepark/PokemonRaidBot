<?php

function simple_filename($f){
  return str_replace(ROOT_PATH . '/', '', $f);
}

function exception_handler($e) {
  global $metrics, $namespace;
  $filename = simple_filename($e->getFile());
  $lineno = $e->getLine();
  $error = "Uncaught exception at {$filename}:{$lineno}: " . $e->getMessage() . PHP_EOL . 'Backtrace: ' . $e->getTraceAsString();

  // Unless disabled, we double log to both error & info logs to aid discovery.
  // The official Docker image disables this since it already makes the error log discoverable.
  $disable_double_logging = getenv('DISABLE_DOUBLE_LOGGING');
  error_log($error); // Standard php error handling is good to notify
  if ($disable_double_logging != 'true'){
    info_log($error); // But we also notify the info log since that's enabled by default and easier to discover
  }
  if ($metrics){
    $uncaught_exceptions_total = $metrics->getOrRegisterCounter($namespace, 'uncaught_exceptions_total', 'total uncaught exceptions', ['filename', 'lineno']);
    $uncaught_exceptions_total->inc([$filename, $lineno]);
  }
}

// Route Errors into Exceptions so we catch those as well in the same framework
function error_handler($severity, $message, $filename, $lineno) {
  // We may be crashing before debug logging is available, so fall back to error_log so at least something gets logged
  if(function_exists('debug_log')){
    $logger = 'debug_log';
  } else {
    $logger = 'error_log';
  }
  $logger('Crash incoming, have a detailed backtrace:');
  $logger(print_r(debug_backtrace(), true));
  throw new ErrorException($message, 0, $severity, $filename, $lineno);
}

set_error_handler('error_handler');
set_exception_handler('exception_handler');
