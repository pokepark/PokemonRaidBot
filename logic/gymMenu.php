<?php
require_once(LOGIC_PATH . '/active_raid_duplication_check.php');
$menuActions = [
  'create' => 'edit_raidlevel',
  'list' => 'list_raid',
  'gym' => 'gym_edit_details',
];

function resolveDefaultGymarea($userId) {
  global $config;
  $q = my_query('SELECT gymarea FROM users WHERE user_id = ? LIMIT 1', [$userId]);
  $userGymarea = $q->fetch()['gymarea'];
  return $userGymarea !== NULL ? $userGymarea : $config->DEFAULT_GYM_AREA;
}
/**
 * Raid gym first letter selection
 * @param string $buttonAction Action that is performed by gym letter keys
 * @param bool $showHidden Show only hidden gyms?
 * @param int $stage
 * @param string $firstLetter
 * @param int|false $gymareaId
 * @return array
 */
function gymMenu($buttonAction, $showHidden, $stage, $firstLetter = false, $gymareaId = false) {
  
  global $config, $botUser;
  // Stage 0: Only gym area keys
  // Stage 1: Gym letter keys (or just gym names if 20 or less gyms were found) with areas under them
  // Stage 2: Gym names
  $stage = ($config->ENABLE_GYM_AREAS && $stage == 1 && $gymareaId == false) ? 0 : $stage;
  [$gymareaName, $gymareaKeys, $gymareaQuery] = ($config->ENABLE_GYM_AREAS) ? getGymareas($gymareaId, $stage, $buttonAction) : ['', [], ''];
  if($stage == 2 && $firstLetter != '')
    $gymKeys = createGymListKeysByFirstLetter($firstLetter, $showHidden, $gymareaQuery, $buttonAction, $gymareaId);
  else
    $gymKeys = createGymKeys($buttonAction, $showHidden, $gymareaId, $gymareaQuery, $stage);
  $keys = ($stage == 0) ? [] : $gymKeys[0];
  if($stage == 0) {
    $title = getTranslation('select_gym_area');
  }elseif($stage == 1) {
    if($config->ENABLE_GYM_AREAS) {
      $title = $gymKeys[1] === true ? getTranslation('select_gym_first_letter_or_gym_area') : getTranslation('select_gym_name_or_gym_area');
    }else {
      $title = $gymKeys[1] === true ? getTranslation('select_gym_first_letter') : getTranslation('select_gym_name');
    }
  }else {
    $title = getTranslation('select_gym_name');
  }
  $gymareaTitle = '<b>' . $title . '</b>' . CR;
  $gymareaTitle.= ($gymareaName != '') ? CR . CR . getTranslation('current_gymarea') . ': ' . $gymareaName : '';

  $gymareaTitle.= ($config->RAID_VIA_LOCATION && $buttonAction == 'create' ? (CR . CR .  getTranslation('send_location')) : '');

  if($config->RAID_VIA_LOCATION_FUNCTION == 'remote' && $buttonAction == 'list') {
    $query_remote = my_query('SELECT count(*) as count FROM raids LEFT JOIN gyms on raids.gym_id = gyms.id WHERE raids.end_time > (UTC_TIMESTAMP() - INTERVAL 10 MINUTE) AND temporary_gym = 1');
    if($query_remote->fetch()['count'] > 0) {
      $keys[][] = button(getTranslation('remote_raids'), 'list_remote_gyms');
    }
  }
  // Merge keys.
  if(($stage < 2 or ($stage == 2 && $firstLetter == '')) && $showHidden == 0) {
    $keys = array_merge($keys, inline_key_array($gymareaKeys, 2));
  }
  // Add key for hidden gyms.
  if($buttonAction == 'gym') {
    if($stage == 1 && $showHidden == 0) {
      // Add key for hidden gyms.
      $h_keys[][] = button(getTranslation('hidden_gyms'), ['gymMenu', 'h' => 1, 'a' => 'gym', 'ga' => $gymareaId]);
      $keys = array_merge($h_keys, $keys);
    }
    if($stage == 0 or $stage == 1 && $botUser->accessCheck('gym-add', true)) {
      $keys[][] = button(getTranslation('gym_create'), 'gym_create');
    }
  }
  if((($stage == 1 or ($stage == 2 && $firstLetter == '')) && $config->DEFAULT_GYM_AREA === false)) {
    $backKey = button(getTranslation('back'), ['gymMenu', 'stage' => 0, 'a' => $buttonAction]);
  }elseif($stage == 2 && $firstLetter !== '') {
    $backKey = button(getTranslation('back'), ['gymMenu', 'stage' => 1, 'a' => $buttonAction, 'h' => $showHidden, 'ga' => $gymareaId]);
  }
  $abortKey = button(getTranslation('abort'), 'exit');
  if (isset($backKey)) {
    $keys[] = [$backKey, $abortKey];
  }else{
    $keys[] = [$abortKey];}
  return ['keys' => $keys, 'gymareaTitle' => $gymareaTitle];
}

/**
 * @param int $gymareaId
 * @param int $stage
 * @param string $buttonAction
 * @return array [$gymareaName, $gymareaKeys, $query]
 */
function getGymareas($gymareaId, $stage, $buttonAction) {
  $gymareaKeys = $points = [];
  $gymareaName = '';
  $json = json_decode(file_get_contents(CONFIG_PATH . '/geoconfig_gym_areas.json'), 1);
  foreach($json as $area) {
    if($gymareaId == $area['id']) {
      foreach($area['path'] as $point) {
        $points[] = $point[0].' '.$point[1];
      }
      $gymareaName = $area['name'];
      if($points[0] != $points[count($points)-1]) $points[] = $points[0];
    }
    if ($stage != 0 && $gymareaId == $area['id']) continue;
    $gymareaKeys[] = button($area['name'], ['gymMenu', 'a' => $buttonAction, 'stage' => 1, 'ga' => $area['id']]);

  }
  if(count($gymareaKeys) > 6 && $stage != 0) {
    // If list of area buttons is getting too large, replace it with a key that opens a submenu
    $gymareaKeys[] = button(getTranslation('gymareas'), ['gymMenu', 'a' => $buttonAction, 'stage' => 0]);
  }
  $polygon_string = implode(',', $points);
  $query = count($points) > 0 ? 'AND ST_CONTAINS(ST_GEOMFROMTEXT(\'POLYGON(('.$polygon_string.'))\'), ST_GEOMFROMTEXT(CONCAT(\'POINT(\',lat,\' \',lon,\')\')))' : '';
  return [$gymareaName, $gymareaKeys, $query];
}

/**
 * @param string $buttonAction
 * @param bool $showHidden
 * @param int $gymareaId
 * @param string $gymareaQuery
 * @param int $stage
 * @return array [keyArray, isArrayListOfLetters]
 */
function createGymKeys($buttonAction, $showHidden, $gymareaId, $gymareaQuery, $stage) {
  global $config, $menuActions, $botUser;
  // Show hidden gyms?
  $show_gym = $showHidden ? 0 : 1;

  if ($buttonAction == 'list') {
    // Select only gyms with active raids
    $queryConditions = '
    LEFT JOIN raids
    ON      raids.gym_id = gyms.id
    WHERE   show_gym = ' . $show_gym . '
    AND end_time > UTC_TIMESTAMP() ';
    $eventQuery = 'event IS NULL';
    if($botUser->accessCheck('ex-raids', true)) {
      if($botUser->accessCheck('event-raids', true))
        $eventQuery = '';
      else
        $eventQuery .= ' OR event = ' . EVENT_ID_EX;
    }elseif($botUser->accessCheck('event-raids', true)) {
      $eventQuery = 'event != ' . EVENT_ID_EX .' OR event IS NULL';
    }
    $eventQuery = ($eventQuery == '') ? ' ' : ' AND ('.$eventQuery.') ';
  }else {
    $eventQuery = ' ';
    $queryConditions = ' WHERE show_gym = ' . $show_gym;
  }
  $rs_count = my_query('SELECT COUNT(gym_name) as count FROM gyms ' . $queryConditions . $eventQuery . $gymareaQuery);
  $gym_count = $rs_count->fetch();

  // Found 20 or less gyms, print gym names
  if($gym_count['count'] <= 20) {
    $keys = createGymListKeysByFirstLetter('', $showHidden, $gymareaQuery, $buttonAction, $gymareaId);
    return $keys;
  }

  // If found over 20 gyms, print letters
  $select = 'SELECT DISTINCT UPPER(SUBSTR(gym_name, 1, 1)) AS first_letter';
  $group_order = ' ORDER BY 1';
  // Special/Custom gym letters?
  if(!empty($config->RAID_CUSTOM_GYM_LETTERS)) {
    // Explode special letters.
    $special_keys = explode(',', $config->RAID_CUSTOM_GYM_LETTERS);
    $select = 'SELECT CASE ';
    foreach($special_keys as $letter)
    {
      $letter = trim($letter);
      debug_log($letter, 'Special gym letter:');
      // Fix chinese chars, prior: $length = strlen($letter);
      $length = strlen(utf8_decode($letter));
      $select .= SP . 'WHEN UPPER(LEFT(gym_name, ' . $length . ')) = \'' . $letter . '\' THEN UPPER(LEFT(gym_name, ' . $length . '))' . SP;
    }
    $select .= 'ELSE UPPER(LEFT(gym_name, 1)) END AS first_letter';
    $group_order = ' GROUP BY 1 ORDER BY gym_name';
  }
  $rs = my_query(
    $select .
    ' FROM gyms ' .
    $queryConditions . ' ' .
    $gymareaQuery .
    $group_order
  );
  while ($gym = $rs->fetch()) {
    // Add first letter to keys array
    $keys[] = button($gym['first_letter'], ['gymMenu', 'a' => $buttonAction, 'stage' => $stage+1, 'fl' => $gym['first_letter'], 'ga' => $gymareaId]);
  }

  // Get the inline key array.
  return [inline_key_array($keys, 4), true];
}
/**
 * Raid edit gym keys with active raids marker.
 * @param string $firstLetter
 * @param bool $showHidden
 * @param string $gymareaQuery
 * @param string $buttonAction
 * @return array
 */
function createGymListKeysByFirstLetter($firstLetter, $showHidden, $gymareaQuery = '', $buttonAction = '', $gymareaId = false) {
  global $config, $menuActions, $botUser;
  // Length of first letter.
  // Fix chinese chars, prior: $first_length = strlen($first);
  $first_length = strlen(utf8_decode($firstLetter));

  // Special/Custom gym letters?
  $not = '';
  if(!empty($config->RAID_CUSTOM_GYM_LETTERS) && $first_length == 1) {
    // Explode special letters.
    $special_keys = explode(',', $config->RAID_CUSTOM_GYM_LETTERS);

    foreach($special_keys as $letter)
    {
      $letter = trim($letter);
      debug_log($letter, 'Special gym letter:');
      // Fix chinese chars, prior: $length = strlen($letter);
      $length = strlen(utf8_decode($letter));
      $not .= SP . 'AND UPPER(LEFT(gym_name, ' . $length . ')) != UPPER(\'' . $letter . '\')' . SP;
    }
  }
  $show_gym = $showHidden ? 0 : 1;

  $eventQuery = 'event IS NULL';
  if($botUser->accessCheck('ex-raids', true)) {
    if($botUser->accessCheck('event-raids', true))
      $eventQuery = '';
    else
      $eventQuery .= ' OR event = ' . EVENT_ID_EX;
  }elseif($botUser->accessCheck('event-raids', true)) {
    $eventQuery = 'event != ' . EVENT_ID_EX .' OR event IS NULL';
  }
  $eventQuery = ($eventQuery == '') ? ' ' : ' AND ('.$eventQuery.') ';

  $letterQuery = ($firstLetter != '') ? 'AND UPPER(LEFT(gym_name, ' . $first_length . ')) = UPPER(\'' . $firstLetter . '\')' : '';

  $query_collate = ($config->MYSQL_SORT_COLLATE != '') ? 'COLLATE ' . $config->MYSQL_SORT_COLLATE : '';
  // Get gyms from database
  $rs = my_query('
    SELECT  gyms.id, gyms.gym_name, gyms.ex_gym,
            case when (select 1 from raids where gym_id = gyms.id and end_time > utc_timestamp() '.$eventQuery.' LIMIT 1) = 1 then 1 else 0 end as active_raid
    FROM    gyms
    WHERE   show_gym = ?
    ' . $letterQuery . '
    ' . $not . '
    ' . $gymareaQuery . '
    ORDER BY  gym_name ' . $query_collate
    , [$show_gym]
  );

  // Init empty keys array.
  $keys = [];

  while ($gym = $rs->fetch()) {
    if ($buttonAction == 'list' && $gym['active_raid'] == 0) continue;
    // Show Ex-Gym-Marker?
    if($config->RAID_CREATION_EX_GYM_MARKER && $gym['ex_gym'] == 1) {
      $ex_raid_gym_marker = (strtolower($config->RAID_EX_GYM_MARKER) == 'icon') ? EMOJI_STAR : $config->RAID_EX_GYM_MARKER;
      $gym_name = $ex_raid_gym_marker . SP . $gym['gym_name'];
    } else {
      $gym_name = $gym['gym_name'];
    }
    // Add warning emoji for active raid
    if ($gym['active_raid'] == 1) {
      $gym_name = EMOJI_WARN . SP . $gym_name;
    }
    $callback = [
      $menuActions[$buttonAction],
      'g' => $gym['id'],
      'ga' => $gymareaId,
      'fl' => $firstLetter,
      'h' => $showHidden,
    ];
    $keys[] = button($gym_name, $callback);
  }

  // Get the inline key array.
  $keys = inline_key_array($keys, 1);

  return [$keys, false];

}
