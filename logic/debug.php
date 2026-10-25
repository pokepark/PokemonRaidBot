<?php

# Ensure PHP is reporting all errors.
error_reporting(E_ALL);

require_once(CORE_BOT_PATH . '/is_init.php');

# Ensure logfile directories exist, otherwise logging will fail.
# If APCu is enabled this will be skipped on subsequent runs.
if(IS_INIT_OR_WHATEVER){
  $logfiles = [
    $config->DEBUG_LOGFILE,
    $config->LOGGING_INFO_LOGFILE,
    $config->DEBUG_LOGFILE,
    $config->DEBUG_INCOMING_LOGFILE,
    $config->DEBUG_SQL_LOGFILE,
    $config->CLEANUP_LOGFILE,
  ];

  # Collect unique paths that house logfiles
  $paths = [];
  foreach($logfiles as $logfile){
    $dirname = pathinfo($logfile, PATHINFO_DIRNAME);
    if(!in_array($dirname, $paths)){
      $paths[] = $dirname;
    }
  }

  # Create the necessary paths
  foreach($paths as $path){
    if (!file_exists($path)) {
      mkdir($path, 770, true);
    }
  }
}
/**
 * Write any log level.
 * @param $val
 * @param string $type
 */
function generic_log($val, $type, $logfile)
{
  $date = @date('Y-m-d H:i:s');
  $usec = microtime(true);
  $date = $date . '.' . str_pad(substr($usec, 11, 4), 4, '0', STR_PAD_RIGHT);

  $bt = debug_backtrace();
  $bl = '';

  // How many calls back to print
  // Increasing this makes it easier to hunt down issues, but increases log line length
  $layers = 1;

  while ($btl = array_shift($bt)) {
    // Ignore generic_log and it's calling function in the call stack
    // Not sure why it works exactly like that, but it does.
    if ($btl['function'] == __FUNCTION__){
      continue;
    }
    --$layers;
    $bl = $bl . '[' . basename($btl['file']) . ':' . $btl['line'] . ']';
    if($layers <= 0) {
      $bl = $bl . ' ';
      break;
    }
  }

  if (gettype($val) != 'string') $val = var_export($val, 1);
  $rows = explode("\n", $val);
  foreach ($rows as $v) {
    error_log('[' . $date . '][' . getmypid() . '] ' . $bl . $type . ' ' . $v . "\n", 3, $logfile);
  }
}

/**
 * Write debug log.
 * @param $val
 * @param string $type
 */
function debug_log($message, $type = '*')
{
  global $config;
  // Write to log only if debug is enabled.
  if ($config->DEBUG === false){
    return;
  }
  generic_log($message, $type, $config->DEBUG_LOGFILE);
}

/**
 * Write cleanup log.
 * @param $message
 * @param string $type
 */
function cleanup_log($message, $type = '*'){
  global $config;
  // Write to log only if cleanup logging is enabled.
  if ($config->CLEANUP_LOG === false){
    return;
  }
  generic_log($message, $type, $config->CLEANUP_LOGFILE);
}

/**
 * Write sql debug log.
 * @param $message
 * @param string $type
 */
function debug_log_sql($message, $type = '%'){
  global $config;
  // Write to log only if debug is enabled.
  if ($config->DEBUG_SQL === false){
    return;
  }
  generic_log($message, $type, $config->DEBUG_SQL_LOGFILE);
}

/**
 * Write incoming stream debug log.
 * @param $message
 * @param string $type
 */
function debug_log_incoming($message, $type = '<'){
  global $config;
  // Write to log only if debug is enabled.
  if ($config->DEBUG_INCOMING === false){
    return;
  }
  generic_log($message, $type, $config->DEBUG_INCOMING_LOGFILE);
}

/**
 * Write INFO level log.
 * @param $message
 * @param string $type
 */
function info_log($message, $type = '[I]'){
  global $config;
  // Write to log only if info logging is enabled.
  if ($config->LOGGING_INFO === false){
    return;
  }
  generic_log($message, $type, $config->LOGGING_INFO_LOGFILE);
}
