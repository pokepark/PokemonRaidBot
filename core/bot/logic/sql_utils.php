<?php
/**
 * run_sql_file, executes the given file path sql and returns true if no errors occurred
 * @param $raid
 * @return bool
 */
function run_sql_file($file) {
  global $dbh;
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
    error_log($exception->getMessage());
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
?>
