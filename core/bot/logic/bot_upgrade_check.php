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
 * @param $dbh
 * @return bool: if an upgrade is needed
*/
function bot_upgrade_check($current, $latest, $dbh)
{
  global $config;
    // Get upgrade sql files.
    $upgrade_files = array();
    $upgrade_files = str_replace(UPGRADE_PATH . '/','', glob(UPGRADE_PATH . '/*.sql'));

    // Remove dots from current and latest version for easier comparison.
    $nodot_current = str_replace('.', '', $current);
    $nodot_latest = str_replace('.', '', $latest);

    // Same version?
    if($nodot_current == $nodot_latest) {
        // No upgrade needed.
        return false;
    } else {
        // Check if upgrade files exists.
        if(is_array($upgrade_files) && count($upgrade_files) > 0) {
            $require_upgrade = false;
            // Check each sql filename.
            foreach ($upgrade_files as $ufile)
            {
                // Skip every older sql file from array.
                $nodot_ufile = str_replace('.', '', str_replace('.sql', '', $ufile));
                if($nodot_ufile <= $nodot_current) {
                    continue;
                } else {
                  if ($config->UPGRADE_SQL_AUTO){
                    debug_log('PERFORMING AUTO SQL UPGRADE:' . UPGRADE_PATH . '/' . $ufile, '!');
                    require_once('sql_utils.php');
                    if (run_sql_file(UPGRADE_PATH . '/' . $ufile)) {
                      upgrade_config_version(basename($ufile, '.sql'));
                    } else {
                      $require_upgrade = true;
                      info_log('AUTO UPGRADE FAILED:' . UPGRADE_PATH . '/' . $ufile, '!');
                    }
                  }
                }
            }
            // Upgrade required.
            return $require_upgrade;
        } else {
            // No upgrade files found! Return false as versions did not match but no upgrades are required!
            debug_log('NO SQL UPGRADE FILES FOUND', '!');
            return false;
        }
    }
}


?>
