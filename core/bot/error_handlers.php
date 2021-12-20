<?php

function simple_filename($f){
  return str_replace(ROOT_PATH . '/', '', $f);
}

function exception_handler($e) {
  global $metrics, $prefix;
  $filename = simple_filename($e->getFile());
  $lineno = $e->getLine();
  error_log("Uncaught exception at {$filename}:L{$lineno}: " . $e->getMessage());
  if ($metrics){
    $uncaught_exception_counter = $metrics->registerCounter($prefix, 'uncaught_exception_counter', 'total uncaught exceptions', ['filename', 'lineno']);
    $uncaught_exception_counter->inc([$filename, $lineno]);
  }
}


function error_handler($severity, $message, $filename, $lineno) {
  global $metrics, $prefix;
  $filename = simple_filename($filename);
  if ($metrics){
    $php_error_counter = $metrics->registerCounter($prefix, 'php_error_counter', 'total uncaught exceptions', ['filename', 'lineno']);
    $php_error_counter->inc([$filename, $lineno]);
  }
  throw new ErrorException($message, 0, $severity, $filename, $lineno);
}

set_error_handler('error_handler');
set_exception_handler('exception_handler');
