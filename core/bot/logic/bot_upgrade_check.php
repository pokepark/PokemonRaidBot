<?php
/**
 * Replace current config version string
 * @param $version
 * @return bool
 */
function upgrade_config_version($version)
{
  debug_log('Bumping config.json VERSION to: ' . $version);
  return write_config_array(array("VERSION" => $version), CONFIG_PATH . '/config.json');
}

/**
 * Bot upgrade check
 * @param $current
 * @param $latest
 * @return bool: if a manual upgrade is needed
 */
function bot_upgrade_check($current, $latest)
{
  global $config;
  // Get upgrade sql files.
  $upgrade_files = array();
  $upgrade_files = str_replace(UPGRADE_PATH . '/','', glob(UPGRADE_PATH . '/*.sql'));

  // Remove dots from current and latest version for easier comparison.

  // Same version?
  if($current == $latest) {
    // No upgrade needed.
    return false;
  } else {
    // Check if upgrade files exists.
    if(is_array($upgrade_files) && count($upgrade_files) > 0) {
      $require_upgrade = false;
      // Check each sql filename.
      foreach ($upgrade_files as $ufile)
      {
        $nodot_ufile = str_replace('.sql', '', $ufile);
        // Skip every older sql file from array.
        if($nodot_ufile <= $current) {
          continue;
        } else {
          if ($config->UPGRADE_SQL_AUTO){
            info_log('PERFORMING AUTO SQL UPGRADE:' . UPGRADE_PATH . '/' . $ufile, '!');
            require_once('sql_utils.php');
            if (run_sql_file(UPGRADE_PATH . '/' . $ufile)) {
              upgrade_config_version(basename($ufile, '.sql'));
            } else {
              $require_upgrade = true;
              info_log('AUTO UPGRADE FAILED:' . UPGRADE_PATH . '/' . $ufile, '!');
              break;
            }
          }
        }
      }
    } else {
      // No upgrade files found! Return false as versions did not match but no upgrades are required!
      debug_log('NO SQL UPGRADE FILES FOUND', '!');
      return false;
    }
    // If previous sql upgrades were successfull, update also pokemon table
    if(!$require_upgrade) {
      require_once(ROOT_PATH . '/mods/getdb.php');
    }
    // Signal whether manual action is required or not.
    return $require_upgrade;
  }
}


?>
