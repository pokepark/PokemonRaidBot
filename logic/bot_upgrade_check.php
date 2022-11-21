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
 * Get current code revision from git state, or 'manual' if no git state exists.
 * @return string
 */
function get_rev()
{
  if (!file_exists(ROOT_PATH . '/.git/HEAD')){
    debug_log('No .git/HEAD present, marking revision as manual');
    return 'manual';
  }
  $ref = trim(file_get_contents(ROOT_PATH . '/.git/HEAD'));
  if (ctype_xdigit($ref)){
    // Is already a hex string and thus valid rev
    // Return first 6 digits of the hash
    return substr($ref, 0, 6);
  }
  // strip 'ref: ' to get file path
  $ref_file = ROOT_PATH . '/.git/' . substr($ref, 5);
  if (file_exists($ref_file)){
    // Return first 6 digits of the hash, this matches our naming of docker image tags
    return substr(file_get_contents($ref_file), 0, 6);
  }
  error_log("Git ref found but we cannot resolve it to a revision ({$ref_file}). Was the .git folder mangled?");
  return 'manual';
}

/**
 * Bot upgrade check
 * @param $current
 * @param $latest
 * @return bool: if a manual upgrade is needed
 */
function bot_upgrade_check($current, $latest)
{
  global $config, $metrics, $namespace;
  $orig = $current; // we may have to do multiple upgrades
  if ($metrics){
    // This is the one place where we have full knowledge of version information & upgrades
    debug_log('init upgrade metrics');
    $version_info = $metrics->registerGauge($namespace, 'version_info', 'Schema and revision information', ['current_schema', 'required_schema', 'rev', 'upgraded_timestamp', 'upgraded_from']);
  }

  $upgrade_verdict = null;
  $manual_upgrade_verdict = null;
  // Same version?
  if($current == $latest) {
    return;
  }
  $upgrade_verdict = true;
  if ($metrics && IS_INIT){
    // record initial version even if we don't do upgrades.
    $version_info->set(1, [$current, $latest, get_rev(), null, null]);
  }
  // Check if upgrade files exist.
  $upgrade_files = array();
  $upgrade_files = str_replace(UPGRADE_PATH . '/','', glob(UPGRADE_PATH . '/*.sql'));
  if(!is_array($upgrade_files) or count($upgrade_files) == 0 or !in_array($latest . '.sql', $upgrade_files)) {
    // No upgrade files found! Since the version now would only go up with upgrade files, something is off.
    // It could be the user has bumped the VERSION file manually, or omitted upgrade files.
    $error = 'NO SQL UPGRADE FILES FOUND FOR LATEST SCHEMA, THIS SHOULD NOT HAPPEN';
    throw new Exception($error);
  }
  // Check each sql filename.
  foreach ($upgrade_files as $ufile)
  {
    $target = str_replace('.sql', '', $ufile);
    // Skip every older sql file from array.
    if($target <= $current) continue;

    if (!$config->UPGRADE_SQL_AUTO){
      $manual_upgrade_verdict = true;
      debug_log("There's a schema upgrade to {$target} we could have run, but auto-upgrades have been disabled!");
      break;
    }
    info_log('PERFORMING AUTO SQL UPGRADE: ' . UPGRADE_PATH . '/' . $ufile, '!');
    require_once('sql_utils.php');
    if (!run_sql_file(UPGRADE_PATH . '/' . $ufile)) {
      $manual_upgrade_verdict = true;
      $error = 'AUTO UPGRADE FAILED: ' . UPGRADE_PATH . '/' . $ufile;
      throw new Exception($error);
    }
    $manual_upgrade_verdict = false;
    upgrade_config_version($target);
    if ($metrics){
      $version_info->set(1, [$target, $latest, get_rev(), time(), $current]);
    }
    $current = $target;
  }
  // If previous sql upgrades had to be done and were successful, update also pokemon table
  if($upgrade_verdict === true && $manual_upgrade_verdict === false) {
    require_once(ROOT_PATH . '/mods/getdb.php');
  }

  // Signal whether manual action is required or not.
  if ($manual_upgrade_verdict === true){
    $error = "The bot has pending schema upgrades ({$current} -> {$latest}) but you've disabled automatic upgrades. Nothing will work until you go do the upgrade(s) manually. You'll find them in the dir sql/upgrade/";
    throw new Exception($error);
  }
}
