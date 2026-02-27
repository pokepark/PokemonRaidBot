<?php
/**
 * run_sql_file, executes the given file path sql and returns true if no errors occurred
 * @param $raid
 * @return bool
 */
function run_sql_file($file) {
  global $dbh, $config;
  if (!$dbh) {
    error_log('No DB handle!');
    return false;
  }
  try {
    $query = file_get_contents($file);
    $dbh->exec( $query );
  }
  catch (PDOException $exception) {
    info_log('DB upgrade failed: ' . $exception->getMessage());
    error_log('PokemonRaidBot ' . $config->BOT_ID . ' DB schema change failed: ' . $exception->getMessage());
    if(!empty($config->MAINTAINER_ID)) sendMessageEcho($config->MAINTAINER_ID, 'DB schema change failed: ' . CR . '<code>' . $exception->getMessage() . '</code>');
    $dbh = null;
    return false;
  }
  return true;
}

/**
 * DB query wrapper that supports binds.
 * @param $query string
 * @param $binds Array
 * @return PDOStatement
 */
function my_query($query, $binds=null)
{
  global $dbh;
  global $config;
  require_once(ROOT_PATH . '/logic/debug.php');

  try {
    $stmt = $dbh->prepare($query);
    $stmt->execute($binds);
  } catch (PDOException $exception) {
    // The message will be output in the global handler, we just need to extract the failing query
    error_log('The following query failed:');
    log_query($stmt, print_r($binds, true), 'error_log');
    throw $exception;
  } finally {
    debug_log_sql('Query success', '$');
    if($config->DEBUG_SQL) {
      log_query($stmt, print_r($binds, true), 'debug_log_sql');
    }
  }
  return $stmt;
}

// Debug log the full statement with parameters
function log_query($stmt, $binds, $logger='debug_log_sql'){
  ob_start();
  $stmt->debugDumpParams();
  $debug = ob_get_contents();
  ob_end_clean();
  $logger($debug);
  $logger('The parameters bound were:');
  if($binds === null) $binds = 'null';
  $logger($binds);
}
