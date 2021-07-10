<?php
/**
 * Naive DB query without proper param handling.
 * You should prefer doing your own prepare, bindParam & execute!
 * @param $query
 * @return PDOStatement
 */
function my_query($query, $cleanup_query = false)
{
    global $dbh;
    global $config;

    if($config->DEBUG_SQL) {
        if ($cleanup_query == true) {
            debug_log($query, '?', true);
        } else {
            debug_log($query, '?');
        }
    }
    $stmt = $dbh->prepare($query);
    if ($stmt && $stmt->execute()) {
        if ($cleanup_query == true) {
            debug_log_sql('Query success', '$', true);
        } else {
            debug_log_sql('Query success', '$');
        }
    } else {
        if ($cleanup_query == true) {
            info_log($dbh->errorInfo(), '!', true);
        } else {
            info_log($dbh->errorInfo(), '!');
        }
    }

    return $stmt;
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
    generic_log($message, $type, $logfile = $config->DEBUG_LOGFILE);
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
    generic_log($message, $type, $logfile = $config->CLEANUP_LOGFILE);
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
