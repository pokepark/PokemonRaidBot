<?php

function simple_filename($f){
  return str_replace(ROOT_PATH . '/', '', $f);
}

function exception_handler($e) {
  global $metrics, $namespace;
  $filename = simple_filename($e->getFile());
  $lineno = $e->getLine();
  error_log("Uncaught exception at {$filename}:{$lineno}: " . $e->getMessage());
  error_log($e->getTraceAsString());
  if ($metrics){
    $uncaught_exceptions_total = $metrics->getOrRegisterCounter($namespace, 'uncaught_exceptions_total', 'total uncaught exceptions', ['filename', 'lineno']);
    $uncaught_exceptions_total->inc([$filename, $lineno]);
  }
}

// Route Errors into Exceptions so we catch those as well in the same framework
function error_handler($severity, $message, $filename, $lineno) {
  debug_log(debug_backtrace());
  throw new ErrorException($message, 0, $severity, $filename, $lineno);
}

set_error_handler('error_handler');
set_exception_handler('exception_handler');
