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
?>
