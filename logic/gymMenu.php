<?php
require_once(LOGIC_PATH . '/active_raid_duplication_check.php');
$menuActions = [
  'create' => 'edit_raidlevel',
  'list' => 'list_raid',
  'gym' => 'gym_edit_details',
];

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
  if($stage == 2)
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
      $keys = array_merge($keys, [[[
        'text'          => getTranslation('remote_raids'),
        'callback_data' => formatCallbackData(['callbackAction' => 'list_remote_raids'])
      ]]]);
    }
  }
  // Merge keys.
  if($stage < 2 && $showHidden == 0) {
    $keys = array_merge($keys, inline_key_array($gymareaKeys, 2));
  }
  // Add key for hidden gyms.
  if($buttonAction == 'gym') {
    if($stage == 1 && $showHidden == 0) {
      // Add key for hidden gyms.
      $h_keys[] = [
        [
          'text'          => getTranslation('hidden_gyms'),
          'callback_data' => formatCallbackData(['callbackAction' => 'gymMenu', 'h' => 1, 'a' => 'gym', 'ga' => $gymareaId])
        ]
      ];
      $keys = array_merge($h_keys, $keys);
    }
    if($stage == 0 && $botUser->accessCheck('gym-add', true)) {
      $keys[] = [
        [
          'text'          => getTranslation('gym_create'),
          'callback_data' => formatCallbackData(['callbackAction' => 'gym_create'])
        ]
      ];
    }
  }
  if($stage == 1 && $config->DEFAULT_GYM_AREA === false) {
    $backKey = [
      'text'          => getTranslation('back'),
      'callback_data' => formatCallbackData(['callbackAction' => 'gymMenu', 'stage' => 0, 'a' => $buttonAction])
    ];
  }elseif($stage == 2) {
    $backKey = [
      'text'          => getTranslation('back'),
      'callback_data' => formatCallbackData(['callbackAction' => 'gymMenu', 'stage' => 1, 'a' => $buttonAction, 'h' => $showHidden, 'ga' => $gymareaId])
    ];
  }
  $abortKey = [
    'text'          => getTranslation('abort'),
    'callback_data' => formatCallbackData(['callbackAction' => 'exit', 'arg' => 0])
  ];
  if(isset($backKey))
    $keys[] = [$backKey, $abortKey];
  else
    $keys[] = [$abortKey];
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
    } else {
      $gymareaKeys[] = [
        'text'          => $area['name'],
        'callback_data' => formatCallbackData(['callbackAction' => 'gymMenu', 'a' => $buttonAction, 'stage' => 1, 'ga' => $area['id']])
      ];
    }
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
 * @return array
 */
function createGymKeys($buttonAction, $showHidden, $gymareaId, $gymareaQuery, $stage) {
  global $config, $menuActions;
  // Show hidden gyms?
  $show_gym = $showHidden ? 0 : 1;
  $collateQuery = ($config->MYSQL_SORT_COLLATE != '') ? ' COLLATE ' . $config->MYSQL_SORT_COLLATE : '';

  // Get the number of gyms to display
  if($buttonAction == 'list') {
    // Select only gyms with active raids
    $queryConditions = '
    LEFT JOIN raids
    ON      raids.gym_id = gyms.id
    WHERE   end_time > UTC_TIMESTAMP()';
  }else {
    $queryConditions = 'WHERE show_gym = ' . $show_gym . ' ';
  }
  $rs_count = my_query('SELECT COUNT(gym_name) as count FROM gyms ' . $queryConditions . ' ' . $gymareaQuery);
  $gym_count = $rs_count->fetch();

  // If found over 20 gyms, print letters
  if($gym_count['count'] > 20) {
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
      $queryConditions .
      $gymareaQuery .
      $group_order .
      $collateQuery
    );
    while ($gym = $rs->fetch()) {
      // Add first letter to keys array
      $keys[] = array(
        'text'          => $gym['first_letter'],
        'callback_data' => formatCallbackData(['callbackAction' => 'gymMenu', 'a' => $buttonAction, 'stage' => $stage+1, 'fl' => $gym['first_letter'], 'ga' => $gymareaId])
      );
    }

    // Get the inline key array.
    return [inline_key_array($keys, 4), true];
  }

  // If less than 20 gyms was found, print gym names
  $rs = my_query('
    SELECT  gyms.id, gyms.gym_name, gyms.ex_gym
    FROM gyms
    ' . $queryConditions . '
    ' . $gymareaQuery . '
    ORDER BY gym_name ' . $collateQuery
  );
  // Init empty keys array.
  $keys = [];

  while ($gym = $rs->fetch()) {
    if($gym['id'] == NULL) continue;
    $active_raid = active_raid_duplication_check($gym['id']);

    $gym_name = $gym['gym_name'];
    // Show Ex-Gym-Marker?
    if($config->RAID_CREATION_EX_GYM_MARKER && $gym['ex_gym'] == 1) {
      $ex_raid_gym_marker = (strtolower($config->RAID_EX_GYM_MARKER) == 'icon') ? EMOJI_STAR : $config->RAID_EX_GYM_MARKER;
      $gym_name = $ex_raid_gym_marker . SP . $gym['gym_name'];
    }
    // Add warning emoji for active raid
    if ($active_raid > 0) {
      $gym_name = EMOJI_WARN . SP . $gym_name;
    }
    $callback = [
      'callbackAction' => $menuActions[$buttonAction],
      'g' => $gym['id'],
      'ga' => $gymareaId,
      'h' => $showHidden,
    ];
    if($buttonAction == 'list') $callback['r'] = $active_raid;
    $keys[] = array(
      'text'          => $gym_name,
      'callback_data' => formatCallbackData($callback)
    );
  }

  // Get the inline key array.
  return [inline_key_array($keys, 1), false];
}
/**
 * Raid edit gym keys with active raids marker.
 * @param string $firstLetter
 * @param bool $showHidden
 * @param string $gymareaQuery
 * @param string $action
 * @return array
 */
function createGymListKeysByFirstLetter($firstLetter, $showHidden, $gymareaQuery = '', $action = '', $gymareaId = false) {
  global $config, $menuActions;
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

  $query_collate = ($config->MYSQL_SORT_COLLATE != '') ? 'COLLATE ' . $config->MYSQL_SORT_COLLATE : '';
  // Get gyms from database
  $rs = my_query('
    SELECT  gyms.id, gyms.gym_name, gyms.ex_gym
    FROM    gyms
    WHERE   UPPER(LEFT(gym_name, ' . $first_length . ')) = UPPER(\'' . $firstLetter . '\')
    ' . $not . '
    ' . $gymareaQuery . '
    AND     show_gym = ?
    ORDER BY  gym_name ' . $query_collate
    , [$show_gym]
  );

  // Init empty keys array.
  $keys = [];

  while ($gym = $rs->fetch()) {
    $active_raid = active_raid_duplication_check($gym['id']);
    if($action == 'list' && $active_raid == 0) continue;
    // Show Ex-Gym-Marker?
    if($config->RAID_CREATION_EX_GYM_MARKER && $gym['ex_gym'] == 1) {
      $ex_raid_gym_marker = (strtolower($config->RAID_EX_GYM_MARKER) == 'icon') ? EMOJI_STAR : $config->RAID_EX_GYM_MARKER;
      $gym_name = $ex_raid_gym_marker . SP . $gym['gym_name'];
    } else {
      $gym_name = $gym['gym_name'];
    }
    // Add warning emoji for active raid
    if ($active_raid > 0) {
      $gym_name = EMOJI_WARN . SP . $gym_name;
    }
    $callback = [
      'callbackAction' => $menuActions[$action],
      'g' => $gym['id'],
      'ga' => $gymareaId,
      'h' => $showHidden,
    ];
    if($action == 'list') $callback['r'] = $active_raid;
    else $callback['fl'] = $firstLetter;
    $keys[] = array(
      'text'          => $gym_name,
      'callback_data' => formatCallbackData($callback)
    );
  }

  // Get the inline key array.
  $keys = inline_key_array($keys, 1);

  return [$keys];

}
