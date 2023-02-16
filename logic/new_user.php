<?php
/**
 * Check if the user is new (has not completed tutorial)
 * @param int $user_id
 * @param bool $ignoreForcePrivilege
 * @return bool
 */
function new_user($user_id, $ignoreForcePrivilege = false) {
  global $config, $botUser;
  if(
    !$config->TUTORIAL_MODE ||
    (!$ignoreForcePrivilege && !in_array('force-tutorial', $botUser->userPrivileges['privileges'])) ||
    user_tutorial($user_id) >= $config->TUTORIAL_LEVEL_REQUIREMENT
  ) {
    return false;
  }
  return true;
}

/**
 * Return the tutorial value from users table
 * @param $user_id
 * @return int
 */
function user_tutorial($user_id) {
  debug_log('Reading user\'s tutorial value: '.$user_id);
  $query = my_query('SELECT tutorial FROM users WHERE user_id = :user_id LIMIT 1', [":user_id"=>$user_id]);
  $res = $query->fetch();
  $result = 0;
  if($query->rowCount() > 0) $result = $res['tutorial'];
  debug_log('Result: '.$result);
  return $result;
}
