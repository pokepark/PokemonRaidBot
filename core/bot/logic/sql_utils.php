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
    $statement = $dbh->prepare( $query );
    $statement->execute();
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
 * @param $query
 * @param binds
 * @return PDOStatement
 */
function my_query($query, $binds=null)
{
    global $dbh;
    global $config;

    if($config->DEBUG_SQL) {
        debug_log($query, '?');
    }
    $stmt = $dbh->prepare($query);
    // If the query fails we let it burn to the ground and get logged by the global exception handler.
    if ($stmt && $stmt->execute($binds)) {
        debug_log_sql('Query success', '$');
    }

    return $stmt;
}
