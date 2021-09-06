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
 * Naive DB query without proper param handling.
 * You should prefer doing your own prepare, bindParam & execute!
 * @param $query
 * @return PDOStatement
 */
function my_query($query)
{
    global $dbh;
    global $config;

    if($config->DEBUG_SQL) {
        debug_log($query, '?');
    }
    $stmt = $dbh->prepare($query);
    if ($stmt && $stmt->execute()) {
        debug_log_sql('Query success', '$');
    } else {
        info_log($dbh->errorInfo(), '!');
    }

    return $stmt;
}
?>
