<?php

/**
 * Raid access check.
 * @param $update
 * @param $data
 * @return bool
 */
function raid_access_check($update, $data, $permission, $return_result = false)
{
    // Default: Deny access to raids
    $raid_access = false;

    // Build query.
    $rs = my_query(
        "
        SELECT    user_id
        FROM      raids
        WHERE     id = {$data['id']}
        "
    );

    $raid = $rs->fetch_assoc();

    // Check permissions
    if ($update['callback_query']['from']['id'] != $raid['user_id']) {
        // Check "-all" permission
        $permission = $permission . '-all';
        $raid_access = bot_access_check($update, $permission, $return_result);
    } else {
        // Check "-own" permission
        $permission = $permission . '-own';
        $raid_access = bot_access_check($update, $permission, $return_result);
    }

    // Return result
    return $raid_access;
}


/**
 * Active raid duplication check.
 * @param $gym_id
 * @return string
 */
function active_raid_duplication_check($gym_id)
{
    debug_log('Running duplication check');

    // Build query.
    $rs = my_query(
        "
        SELECT id, count(gym_id) AS active_raid
        FROM   raids
        WHERE  end_time > (UTC_TIMESTAMP() + INTERVAL 10 MINUTE)
        AND    gym_id = {$gym_id}
        "
    );

    // Get row.
    $raid = $rs->fetch_assoc();
    $active_counter = $raid['active_raid'];

    // Return 0 or raid id
    if ($active_counter > 0) {
        return $raid['id'];
    } else {
        return 0;
    }
}

/**
 * Insert gym.
 * @param $gym_name
 * @param $latitude
 * @param $longitude
 * @param $address
 */
function insert_gym($name, $lat, $lon, $address)
{
    global $db;

    // Build query to check if gym is already in database or not
    $rs = my_query(
        "
        SELECT    COUNT(*)
        FROM      gyms
          WHERE   gym_name = '{$name}'
         "
        );

    $row = $rs->fetch_row();

    // Gym already in database or new
    if (empty($row['0'])) {
        // Build query for gyms table to add gym to database
        debug_log('Gym not found in database gym list! Adding gym "' . $name . '" to the database gym list.');
        $rs = my_query(
            "
            INSERT INTO   gyms
            SET           lat = '{$lat}',
                              lon = '{$lon}',
                              gym_name = '{$db->real_escape_string($name)}',
                              address = '{$db->real_escape_string($address)}'
            "
        );
    } else {
        // Update gyms table to reflect gym changes.
        debug_log('Gym found in database gym list! Updating gym "' . $name . '" now.');
        $rs = my_query(
            "
            UPDATE        gyms
            SET           lat = '{$lat}',
                              lon = '{$lon}',
                              address = '{$db->real_escape_string($address)}'
               WHERE      gym_name = '{$name}'
            "
        );
    }
}

/**
 * Disable raids for level.
 * @param $id
 * @return array
 */
function disable_raid_level($id)
{
    // Get gym from database
    $rs = my_query(
            "
            UPDATE    pokemon
            SET       raid_level = '0'
            WHERE     raid_level IN ({$id})
            "
        );
}

/**
 * Get raid level of a pokemon.
 * @param $pokedex_id
 * @return string
 */
function get_raid_level($pokedex_id)
{
    // Split pokedex_id and form
    $dex_id_form = explode('-',$pokedex_id);
    $dex_id = $dex_id_form[0];
    $dex_form = $dex_id_form[1];

    // Make sure $dex_id is numeric
    if(is_numeric($dex_id)) {
        // Get raid level from database
        $rs = my_query(
                "
                SELECT    raid_level
                FROM      pokemon
                WHERE     pokedex_id = {$dex_id}
                AND       pokemon_form = '{$dex_form}'
                "
            );

        $raid_level = '0';
        while ($level = $rs->fetch_assoc()) {
            $raid_level = $level['raid_level'];
        }
    } else {
        $raid_level = '0';
    }

    return $raid_level;
}

/**
 * Get raid data.
 * @param $raid_id
 * @return array
 */
function get_raid($raid_id)
{
    // Get the raid data by id.
    $rs = my_query(
        "
        SELECT     raids.*,
                   gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym, gyms.gym_note,
                   users.name,
                   TIME_FORMAT(TIMEDIFF(end_time, UTC_TIMESTAMP()) + INTERVAL 1 MINUTE, '%k:%i') AS t_left
        FROM       raids
        LEFT JOIN  gyms
        ON         raids.gym_id = gyms.id
        LEFT JOIN  users
        ON         raids.user_id = users.user_id
        WHERE      raids.id = {$raid_id}
        "
    );

    // Get the row.
    $raid = $rs->fetch_assoc();

    debug_log($raid);

    return $raid;
}

/**
 * Get last 20 active raids.
 * @return array
 */
function get_active_raids()
{
    // Get last 20 active raids data.
    $rs = my_query(
        "
        SELECT     raids.*,
                   gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym, gyms.gym_note,
                   start_time, end_time,
                   TIME_FORMAT(TIMEDIFF(end_time, UTC_TIMESTAMP()) + INTERVAL 1 MINUTE, '%k:%i') AS t_left
        FROM       raids
        LEFT JOIN  gyms
        ON         raids.gym_id = gyms.id
        WHERE      end_time>UTC_TIMESTAMP()
        ORDER BY   end_time ASC LIMIT 20
        "
    );

    // Get the raids.
    $raids = $rs->fetch_assoc();

    debug_log($raids);

    return $raids;
}

/**
 * Get pokedex id by name of pokemon.
 * @param $pokemon_name
 * @return string
 */
function get_pokemon_id_by_name($pokemon_name)
{
    // Init id and write name to search to log.
    $pokemon_id = 0;
    $pokemon_form = 'normal';
    debug_log($pokemon_name,'P:');

    // Explode pokemon name in case we have a form too.
    $delimiter = '';
    if(strpos($pokemon_name, ' ') !== false) {
        $delimiter = ' ';
    } else if (strpos($pokemon_name, '-') !== false) {
        $delimiter = '-';
    } else if (strpos($pokemon_name, ',') !== false) {
        $delimiter = ',';
    }
    
    // Explode if delimiter was found.
    $poke_name = $pokemon_name;
    if($delimiter != '') {
        $pokemon_name_form = explode($delimiter,$pokemon_name,2);
        $poke_name = trim($pokemon_name_form[0]);
        $poke_name = strtolower($poke_name);
        $poke_form = trim($pokemon_name_form[1]);
        $poke_form = strtolower($poke_form);
        debug_log($poke_name,'P NAME:');
        debug_log($poke_form,'P FORM:');
    }

    // Set language
    $language = USERLANGUAGE;

    // Make sure file exists, otherwise use English language as fallback.
    if(!is_file(CORE_LANG_PATH . '/pokemon_' . strtolower($language) . '.json')) {
        $language = 'EN';
    }

    // Get translation file
    $str = file_get_contents(CORE_LANG_PATH . '/pokemon_' . strtolower($language) . '.json');
    $json = json_decode($str, true);

    // Search pokemon name in json
    $key = array_search(ucfirst($poke_name), $json);
    if($key !== FALSE) {
        // Index starts at 0, so key + 1 for the correct id!
        $pokemon_id = $key + 1;
    } else {
        // Try English language as fallback to get the pokemon id.
        $str = file_get_contents(CORE_LANG_PATH . '/pokemon_' . strtolower(DEFAULT_LANGUAGE) . '.json');
        $json = json_decode($str, true);
    
        // Search pokemon name in json
        $key = array_search(ucfirst($poke_name), $json);
        if($key !== FALSE) {
            // Index starts at 0, so key + 1 for the correct id!
            $pokemon_id = $key + 1;
        } else {
            // Debug log.
            debug_log('Error! Pokedex ID could not be found for pokemon with name: ' . $poke_name);
        }
    }

    // Get form.
    // Works like this: Search form in language file via language, e.g. 'DE' and local form translation, e.g. 'Alola' for 'DE'.
    // In additon we are searching the DEFAULT_LANGUAGE and the key name for the form name.
    // Once we found the key name, e.g. 'pokemon_form_attack', get the form name 'attack' from it via str_replace'ing the prefix 'pokemon_form'.
    if($pokemon_id != 0 && isset($poke_form) && !empty($poke_form) && $poke_form != 'normal') {
        debug_log('Searching for pokemon form: ' . $poke_form);

        // Get forms translation file
        $str_form = file_get_contents(CORE_LANG_PATH . '/pokemon_forms.json');
        $json_form = json_decode($str_form, true);

        // Search pokemon form in json
        foreach($json_form as $key_form => $jform) {
            // Stop search if we found it.
            if ($jform[$language] === ucfirst($poke_form)) {
                $pokemon_form = str_replace('pokemon_form_','',$key_form);
                debug_log('Found pokemon form by user language: ' . $language);
                break;

            // Try DEFAULT_LANGUAGE too.
            } else if ($jform[DEFAULT_LANGUAGE] === ucfirst($poke_form)) {
                $pokemon_form = str_replace('pokemon_form_','',$key_form);
                debug_log('Found pokemon form by default language: ' . DEFAULT_LANGUAGE);
                break;

            // Try key name.
            } else if ($key_form === ('pokemon_form_' . $poke_form)) {
                $pokemon_form = str_replace('pokemon_form_','',$key_form);
                debug_log('Found pokemon form by json key name: pokemon_form_' . $key_form);
                break;
            }
        }
    }

    // Write to log.
    debug_log($pokemon_id,'P:');
    debug_log($pokemon_form,'P:');

    // Set pokemon form.
    $pokemon_id = $pokemon_id . '-' . $pokemon_form;

    // Return pokemon_id
    return $pokemon_id;
}

/**
 * Get local name of pokemon.
 * @param $pokemon_id_form
 * @param $override_language
 * @return string
 */
function get_local_pokemon_name($pokemon_id_form, $override_language = false)
{
    // Split pokedex_id and form
    $dex_id_form = explode('-',$pokemon_id_form);
    $pokedex_id = $dex_id_form[0];
    $pokemon_form = $dex_id_form[1];

    debug_log('Pokemon_form: ' . $pokemon_form);

    // Get translation type
    if($override_language == true) {
        $getTypeTranslation = 'getPublicTranslation';
    } else {
        $getTypeTranslation = 'getTranslation';
    }
    // Init pokemon name and define fake pokedex ids used for raid eggs
    $pokemon_name = '';
    $eggs = $GLOBALS['eggs'];

    // Get eggs from normal translation.
    if(in_array($pokedex_id, $eggs)) {
        $pokemon_name = $getTypeTranslation('egg_' . substr($pokedex_id, -1));
    } else if ($pokemon_form != 'normal') { 
        $pokemon_name = $getTypeTranslation('pokemon_id_' . $pokedex_id) . SP . $getTypeTranslation('pokemon_form_' . $pokemon_form);
    } else { 
        $pokemon_name = $getTypeTranslation('pokemon_id_' . $pokedex_id);
    }

    // Fallback 1: Valid pokedex id or just a raid egg?
    if($pokedex_id === "NULL" || $pokedex_id == 0) {
        $pokemon_name = $getTypeTranslation('egg_0');

    // Fallback 2: Get original pokemon name from database
    } else if(empty($pokemon_name)) {
        $rs = my_query(
                "
                SELECT    pokemon_name
                FROM      pokemon
                WHERE     pokedex_id = {$pokedex_id}
                AND       pokemon_form = '{$pokemon_form}'
                "
            );

        while ($pokemon = $rs->fetch_assoc()) {
            $pokemon_name = $pokemon['pokemon_name'];
        }
    }

    return $pokemon_name;
}

/**
 * Get gym.
 * @param $id
 * @return array
 */
function get_gym($id)
{
    // Get gym from database
    $rs = my_query(
            "
            SELECT    *
            FROM      gyms
	    WHERE     id = {$id}
            "
        );

    $gym = $rs->fetch_assoc();

    return $gym;
}

/**
 * Get gym by telegram id.
 * @param $id
 * @return array
 */
function get_gym_by_telegram_id($id)
{
    // Get gym from database
    $rs = my_query(
            "
            SELECT    *
            FROM      gyms
            WHERE     gym_name = '{$id}'
            ORDER BY  id DESC
            LIMIT     1
            "
        );

    $gym = $rs->fetch_assoc();

    return $gym;
}

/**
 * Delete gym.
 * @param $id
 * @return array
 */
function delete_gym($id)
{
    // Get gym from database
    $rs = my_query(
            "
            DELETE FROM gyms
	    WHERE     id = {$id}
            "
        );
}

/**
 * Get gym details.
 * @param $gym
 * @param $extended
 * @return string
 */
function get_gym_details($gym, $extended = false)
{
    // Add gym name to message.
    $msg = '<b>' . getTranslation('gym_details') . ':</b>' . CR . CR;
    $msg .= '<b>ID = ' . $gym['id'] . '</b>' . CR;
    $msg .= getTranslation('gym') . ':' . SP;
    $ex_raid_gym_marker = (strtolower(RAID_EX_GYM_MARKER) == 'icon') ? EMOJI_STAR : '<b>' . RAID_EX_GYM_MARKER . '</b>';
    $msg .= ($gym['ex_gym'] ? $ex_raid_gym_marker . SP : '') . '<b>' . $gym['gym_name'] . '</b>';
    $msg .= CR;
    // Add maps link to message.
    if (!empty($gym['address'])) {
        $msg .= '<a href="https://maps.google.com/?daddr=' . $gym['lat'] . ',' . $gym['lon'] . '">' . $gym['address'] . '</a>' . CR;
    } else {
        // Get the address.
        $addr = get_address($gym['lat'], $gym['lon']);
        $address = format_address($addr);

        //Only store address if not empty
        if(!empty($address)) {
            //Use new address
            $msg .= '<a href="https://maps.google.com/?daddr=' . $gym['lat'] . ',' . $gym['lon'] . '">' . $address . '</a>' . CR;
        } else {
            //If no address is found show maps link
            $msg .= '<a href="http://maps.google.com/maps?q=' . $gym['lat'] . ',' . $gym['lon'] . '">http://maps.google.com/maps?q=' . $gym['lat'] . ',' . $gym['lon'] . '</a>' . CR;
        }
    }

    // Add or hide gym note.
    if(!empty($gym['gym_note'])) {
        $msg .= EMOJI_INFO . SP . $gym['gym_note'];
    }

    // Get extended gym details?
    if($extended == true) {
        $msg .= CR . '<b>' . getTranslation('extended_gym_details') . '</b>';
        // Normal gym?
        if($gym['ex_gym'] == 1) {
            $msg .= CR . '-' . SP . getTranslation('ex_gym');
        }

        // Hidden gym?
        if($gym['show_gym'] == 1 && $gym['ex_gym'] == 0) {
            $msg .= CR . '-' . SP . getTranslation('normal_gym');
        } else if($gym['show_gym'] == 0) {
            $msg .= CR . '-' . SP . getTranslation('hidden_gym');
        }
    }

    return $msg;
}

/**
 * Get pokemon info as formatted string.
 * @param $pokemon_id_form
 * @return array
 */
function get_pokemon_info($pokemon_id_form)
{
    // Split pokedex_id and form
    $dex_id_form = explode('-',$pokemon_id_form);
    $pokedex_id = $dex_id_form[0];
    $pokemon_form = $dex_id_form[1];

    /** Example:
     * Raid boss: Mewtwo (#ID)
     * Weather: Icons
     * CP: CP values (Boosted CP values)
    */
    $info = '';
    $info .= getTranslation('raid_boss') . ': <b>' . get_local_pokemon_name($pokemon_id_form) . ' (#' . $pokedex_id . ')</b>' . CR . CR;
    $poke_raid_level = get_raid_level($pokemon_id_form);
    $poke_cp = get_formatted_pokemon_cp($pokemon_id_form);
    $poke_weather = get_pokemon_weather($pokemon_id_form);
    $info .= getTranslation('pokedex_raid_level') . ': ' . getTranslation($poke_raid_level . 'stars') . CR;
    $info .= (empty($poke_cp)) ? (getTranslation('pokedex_cp') . CR) : $poke_cp . CR;
    $info .= getTranslation('pokedex_weather') . ': ' . get_weather_icons($poke_weather) . CR . CR;

    return $info;
}

/**
 * Get pokemon cp values.
 * @param $pokemon_id_form
 * @return array
 */
function get_pokemon_cp($pokemon_id_form)
{
    // Split pokedex_id and form
    $dex_id_form = explode('-',$pokemon_id_form);
    $pokedex_id = $dex_id_form[0];
    $pokemon_form = $dex_id_form[1];

    // Get gyms from database
    $rs = my_query(
            "
            SELECT    min_cp, max_cp, min_weather_cp, max_weather_cp
            FROM      pokemon
            WHERE     pokedex_id = {$pokedex_id}
            AND       pokemon_form = '{$pokemon_form}'
            "
        );

    $cp = $rs->fetch_assoc();

    return $cp;
}

/**
 * Get formatted pokemon cp values.
 * @param $pokemon_id_form
 * @param $override_language
 * @return string
 */
function get_formatted_pokemon_cp($pokemon_id_form, $override_language = false)
{
    // Split pokedex_id and form
    $dex_id_form = explode('-',$pokemon_id_form);
    $pokedex_id = $dex_id_form[0];
    $pokemon_form = $dex_id_form[1];

    // Init cp text.
    $cp20 = '';
    $cp25 = '';

    // Valid pokedex id?
    if($pokedex_id !== "NULL" && $pokedex_id != 0) {
        // Get gyms from database
        $rs = my_query(
                "
                SELECT    min_cp, max_cp, min_weather_cp, max_weather_cp
                FROM      pokemon
                WHERE     pokedex_id = {$pokedex_id}
                AND       pokemon_form = '{$pokemon_form}'
                "
            );

        while($row = $rs->fetch_assoc()) {
            // CP
            $cp20 .= ($row['min_cp'] > 0) ? $row['min_cp'] : '';
            $cp20 .= (!empty($cp20) && $cp20 > 0) ? ('/' . $row['max_cp']) : ($row['max_cp']);

            // Weather boosted CP
            $cp25 .= ($row['min_weather_cp'] > 0) ? $row['min_weather_cp'] : '';
            $cp25 .= (!empty($cp25) && $cp25 > 0) ? ('/' . $row['max_weather_cp']) : ($row['max_weather_cp']);
        }
    }

    // Combine CP and weather boosted CP
    $text = ($override_language == true) ? (getPublicTranslation('pokedex_cp')) : (getTranslation('pokedex_cp'));
    $cp = (!empty($cp20)) ? ($text . ' <b>' . $cp20 . '</b>') : '';
    $cp .= (!empty($cp25)) ? (' (' . $cp25 . ')') : '';

    return $cp;
}

/**
 * Get pokemon weather.
 * @param $pokemon_id_form
 * @return string
 */
function get_pokemon_weather($pokemon_id_form)
{
    // Split pokedex_id and form
    $dex_id_form = explode('-',$pokemon_id_form);
    $pokedex_id = $dex_id_form[0];
    $pokemon_form = $dex_id_form[1];

    if($pokedex_id !== "NULL" && $pokedex_id != 0) {
        // Get pokemon weather from database
        $rs = my_query(
                "
                SELECT    weather
                FROM      pokemon
                WHERE     pokedex_id = {$pokedex_id}
                AND       pokemon_form = '{$pokemon_form}'
                "
            );

        // Fetch the row.
        $ww = $rs->fetch_assoc();

        return $ww['weather'];
    } else {
        return 0;
   }
}

/**
 * Get weather icons.
 * @param $weather_value
 * @return string
 */
function get_weather_icons($weather_value)
{
    if($weather_value > 0) {
        // Get length of arg and split arg
        $weather_value_length = strlen((string)$weather_value);
        $weather_value_string = str_split((string)$weather_value);

        // Init weather icons string.
        $weather_icons = '';

        // Add icons to string.
        for ($i = 0; $i < $weather_value_length; $i = $i + 1) {
            // Get weather icon from constants
            $weather_icons .= $GLOBALS['weather'][$weather_value_string[$i]];
            $weather_icons .= ' ';
        }

        // Trim space after last icon
        $weather_icons = rtrim($weather_icons);
    } else {
        $weather_icons = '';
    }

    return $weather_icons;
}

/**
 * Get user.
 * @param $user_id
 * @return message
 */
function get_user($user_id)
{
    // Get user details.
    $rs = my_query(
        "
        SELECT    * 
                FROM      users
                  WHERE   user_id = {$user_id}
        "
    );

    // Fetch the row.
    $row = $rs->fetch_assoc();

    // Build message string.
    $msg = '';

    // Add name.
    $msg .= 'Name: <a href="tg://user?id=' . $row['user_id'] . '">' . htmlspecialchars($row['name']) . '</a>' . CR;

    // Unknown team.
    if ($row['team'] === NULL) {
        $msg .= 'Team: ' . $GLOBALS['teams']['unknown'] . CR;

    // Known team.
    } else {
        $msg .= 'Team: ' . $GLOBALS['teams'][$row['team']] . CR;
    }

    // Add level.
    if ($row['level'] != 0) {
        $msg .= 'Level: ' . $row['level'] . CR;
    }

    return $msg;
}

/**
 * Moderator keys.
 * @param $limit
 * @param $action
 * @return array
 */
function edit_moderator_keys($limit, $action)
{
    // Number of entries to display at once.
    $entries = 10;

    // Number of entries to skip with skip-back and skip-next buttons
    $skip = 50;

    // Module for back and next keys
    $module = "mods";

    // Init empty keys array.
    $keys = [];

    // Get moderators from database
    if ($action == "list" || $action == "delete") {
        $rs = my_query(
                "
                SELECT    *
                FROM      users
                WHERE     moderator = 1 
	        ORDER BY  name
	        LIMIT     $limit, $entries
                "
            );

	// Number of entries
        $cnt = my_query(
                "
                SELECT    COUNT(*)
                FROM      users
                WHERE     moderator = 1 
                "
            );
    } else if ($action == "add") {
        $rs = my_query(
                "
                SELECT    *
                FROM      users
                WHERE     (moderator = 0 OR moderator IS NULL)
                ORDER BY  name
                LIMIT     $limit, $entries
                "
            );

	// Number of entries
        $cnt = my_query(
                "
                SELECT    COUNT(*)
                FROM      users
                WHERE     (moderator = 0 OR moderator IS NULL)
                "
            );
    }

    // Number of database entries found.
    $sum = $cnt->fetch_row();
    $count = $sum['0'];

    // List users / moderators
    while ($mod = $rs->fetch_assoc()) {
        $keys[] = array(
            'text'          => $mod['name'],
            'callback_data' => '0:mods_' . $action . ':' . $mod['user_id']
        );
    }

    // Empty backs and next keys
    $keys_back = [];
    $keys_next = [];

    // Add back key.
    if ($limit > 0) {
        $new_limit = $limit - $entries;
        $empty_back_key = [];
        $back = universal_key($empty_back_key, $new_limit, $module, $action, getTranslation('back') . " (-" . $entries . ")");
        $keys_back[] = $back[0][0];
    }

    // Add skip back key.
    if ($limit - $skip > 0) {
        $new_limit = $limit - $skip - $entries;
        $empty_back_key = [];
        $back = universal_key($empty_back_key, $new_limit, $module, $action, getTranslation('back') . " (-" . $skip . ")");
        $keys_back[] = $back[0][0];
    }

    // Add next key.
    if (($limit + $entries) < $count) {
        $new_limit = $limit + $entries;
        $empty_next_key = [];
        $next = universal_key($empty_next_key, $new_limit, $module, $action, getTranslation('next') . " (+" . $entries . ")");
        $keys_next[] = $next[0][0];
    }

    // Add skip next key.
    if (($limit + $skip + $entries) < $count) {
        $new_limit = $limit + $skip + $entries;
        $empty_next_key = [];
        $next = universal_key($empty_next_key, $new_limit, $module, $action, getTranslation('next') . " (+" . $skip . ")");
        $keys_next[] = $next[0][0];
    }

    // Exit key
    $empty_exit_key = [];
    $key_exit = universal_key($empty_exit_key, "0", "exit", "0", getTranslation('abort'));

    // Get the inline key array.
    $keys = inline_key_array($keys, 1);
    $keys_back = inline_key_array($keys_back, 2);
    $keys_next = inline_key_array($keys_next, 2);
    $keys = array_merge($keys_back, $keys);
    $keys = array_merge($keys, $keys_next);
    $keys = array_merge($keys, $key_exit);

    return $keys;
}

/**
 * Raid edit start keys.
 * @param $gym_id
 * @param $gym_first_letter
 * @param $admin
 * @return array
 */
function raid_edit_raidlevel_keys($gym_id, $gym_first_letter, $admin = false)
{
    // Get all raid levels from database
    $rs = my_query(
            "
            SELECT    raid_level, COUNT(*) AS raid_level_count
            FROM      pokemon
            WHERE     raid_level != '0'
            GROUP BY  raid_level
            ORDER BY  FIELD(raid_level, '5', '4', '3', '2', '1', 'X')
            "
        );

    // Init empty keys array.
    $keys = [];

    // Add key for each raid level
    while ($level = $rs->fetch_assoc()) {
        // Continue if user is not part of the BOT_ADMINS and raid_level is X
        if($level['raid_level'] == 'X' && $admin === false) continue;

        // Add key for pokemon if we have just 1 pokemon for a level
        if($level['raid_level_count'] == 1) {
            // Raid level and aciton
            $raid_level = $level['raid_level'];

            // Get pokemon from database
            $rs_rl = my_query(
                "
                SELECT    pokedex_id, pokemon_form
                FROM      pokemon
                WHERE     raid_level = '{$raid_level}'
                "
            );

            // Add key for pokemon
            while ($pokemon = $rs_rl->fetch_assoc()) {
                $keys[] = array(
                    'text'          => get_local_pokemon_name($pokemon['pokedex_id'] . '-' . $pokemon['pokemon_form']),
                    'callback_data' => $gym_id . ',' . $gym_first_letter . ':edit_starttime:' . $pokemon['pokedex_id'] . '-' . $pokemon['pokemon_form']
                );
            }
        } else {
            // Add key for raid level
            $keys[] = array(
                'text'          => getTranslation($level['raid_level'] . 'stars'),
                'callback_data' => $gym_id . ',' . $gym_first_letter . ':edit_pokemon:' . $level['raid_level']
            );
        }
    }

    // Get the inline key array.
    $keys = inline_key_array($keys, 3);

    return $keys;
}

/**
 * Raid gym first letter selection
 * @param $action
 * @param $hidden
 * @return array
 */
function raid_edit_gyms_first_letter_keys($action = 'raid_by_gym', $hidden = false)
{
    // Special/Custom gym letters?
    $case = '';
    if(defined('RAID_CUSTOM_GYM_LETTERS') && !empty(RAID_CUSTOM_GYM_LETTERS)) {
        // Explode special letters.
        $special_keys = explode(',', RAID_CUSTOM_GYM_LETTERS);
        foreach($special_keys as $id => $letter)
        {
            $letter = trim($letter);
            debug_log($letter, 'Special gym letter:');
            $length = strlen($letter);
            $case .= SP . "WHEN UPPER(LEFT(gym_name, " . $length . ")) = '" . $letter . "' THEN UPPER(LEFT(gym_name, " . $length . "))" . SP;
        }
    }

    // Show hidden gyms?
    if($hidden == true) {
        $show_gym = 0;
    } else {
        $show_gym = 1;
    }

    // Case or not?
    if(!empty($case)) {
        // Get gyms from database
        $rs = my_query(
                "
                SELECT CASE $case
                ELSE UPPER(LEFT(gym_name, 1)) 
                END       AS first_letter
                FROM      gyms
                WHERE     show_gym = {$show_gym}
                GROUP BY  1
                ORDER BY  gym_name
                "
            );
    } else {
        // Get gyms from database
        $rs = my_query(
                "
                SELECT UPPER(LEFT(gym_name, 1)) AS first_letter
                FROM      gyms
                WHERE     show_gym = {$show_gym} 
                GROUP BY LEFT(gym_name, 1)
                ORDER BY  gym_name
                "
            );
    }

    // Init empty keys array.
    $keys = [];

    while ($gym = $rs->fetch_assoc()) {
	// Add first letter to keys array
        $keys[] = array(
            'text'          => $gym['first_letter'],
            'callback_data' => $show_gym . ':' . $action . ':' . $gym['first_letter']
        );
    }

    // Get the inline key array.
    $keys = inline_key_array($keys, 4);

    // Add back navigation key.
    if($hidden == false) {
        $nav_keys = [];
        $nav_keys[] = universal_inner_key($keys, '0', 'exit', '0', getTranslation('abort'));

        // Get the inline key array.
        $keys[] = $nav_keys;
    }

    return $keys;
}

/**
 * Raid edit gym keys with active raids marker.
 * @param $first
 * @param $warn
 * @param $action
 * @param $delete
 * @param $hidden
 * @return array
 */
function raid_edit_gym_keys($first, $warn = true, $action = 'edit_raidlevel', $delete = false, $hidden = false)
{
    // Length of first letter.
    $first_length = strlen($first);

    // Special/Custom gym letters?
    $not = '';
    if(defined('RAID_CUSTOM_GYM_LETTERS') && !empty(RAID_CUSTOM_GYM_LETTERS) && $first_length == 1) {
        // Explode special letters.
        $special_keys = explode(',', RAID_CUSTOM_GYM_LETTERS);

        foreach($special_keys as $id => $letter)
        {
            $letter = trim($letter);
            debug_log($letter, 'Special gym letter:');
            $length = strlen($letter);
            $not .= SP . "AND UPPER(LEFT(gym_name, " . $length . ")) != UPPER('" . $letter . "')" . SP;
        }
    }

    // Show hidden gyms?
    if($hidden == true) {
        $show_gym = 0;
    } else {
        $show_gym = 1;
    }

    // Get gyms from database
    $rs = my_query(
        "
        SELECT    gyms.id, gyms.gym_name,
                  CASE WHEN SUM(raids.end_time > UTC_TIMESTAMP() + INTERVAL 15 MINUTE) THEN 1 ELSE 0 END AS active_raid
        FROM      gyms
        LEFT JOIN raids
        ON        raids.gym_id = gyms.id 
        WHERE     UPPER(LEFT(gym_name, $first_length)) = UPPER('{$first}')
        $not
        AND       gyms.show_gym = {$show_gym}
        GROUP BY  gym_name 
        ORDER BY  gym_name
        "
    );

    // Init empty keys array.
    $keys = [];

    while ($gym = $rs->fetch_assoc()) {
        // Add delete argument to keys
        if ($delete == true) {
           $arg = $gym['id'] . '-delete';
        } else {
           $arg = $gym['id'];
        }

        // No active raid
        if($gym['active_raid'] == 0 || $warn = false) {
            $keys[] = array(
                'text'          => $gym['gym_name'],
                'callback_data' => $first . ':' . $action . ':' . $arg
            );
        // Add warning emoji for active raid
        } else {
            $keys[] = array(
                'text'          => EMOJI_WARN . SP . $gym['gym_name'],
                'callback_data' => $first . ':' . $action . ':' . $arg
            );
        }
    }

    // Get the inline key array.
    $keys = inline_key_array($keys, 1);

    return $keys;

}

/**
 * Get gyms by searchterm.
 * @param $searchterm
 * @return bool|array
 */
function raid_get_gyms_list_keys($searchterm)
{
    // Init empty keys array.
    $keys = [];

    // Make sure the search term is not empty
    if(!empty($searchterm)) {
        // Get gyms from database
        $rs = my_query(
                "
                SELECT    id, gym_name
                FROM      gyms
                WHERE     gym_name LIKE '$searchterm%'
                OR        gym_name LIKE '%$searchterm%'
                ORDER BY
                  CASE
                    WHEN  gym_name LIKE '$searchterm%' THEN 1
                    WHEN  gym_name LIKE '%$searchterm%' THEN 2
                    ELSE  3
                  END
                LIMIT     15
                "
            );

        while ($gym = $rs->fetch_assoc()) {
            $first = strtoupper(substr($gym['gym_name'], 0, 1));
	    $keys[] = array(
                'text'          => $gym['gym_name'],
                'callback_data' => $first . ':edit_raidlevel:' . $gym['id']
            );
        }
    }
    
    // Add abort key.
    if($keys) {
        // Get the inline key array.
        $keys = inline_key_array($keys, 1);

        // Add back navigation key.
        $nav_keys = [];
        $nav_keys[] = universal_inner_key($keys, '0', 'exit', '0', getTranslation('abort'));

        // Get the inline key array.
        $keys[] = $nav_keys;
    }

    return $keys;
}


/**
 * Pokedex edit pokemon keys.
 * @param $limit
 * @param $action
 * @return array
 */
function edit_pokedex_keys($limit, $action)
{
    // Number of entries to display at once.
    $entries = 10;

    // Number of entries to skip with skip-back and skip-next buttons
    $skip = 50;

    // Module for back and next keys
    $module = "pokedex";

    // Init empty keys array.
    $keys = [];

    // Get all pokemon from database
    $rs = my_query(
        "
        SELECT    pokedex_id, pokemon_form
        FROM      pokemon
        ORDER BY  pokedex_id, pokemon_form != 'normal', pokemon_form
        LIMIT     $limit, $entries
        "
    );

    // Number of entries
    $cnt = my_query(
        "
        SELECT    COUNT(*)
        FROM      pokemon
        "
    );

    // Number of database entries found.
    $sum = $cnt->fetch_row();
    $count = $sum['0'];

    // List users / moderators
    while ($mon = $rs->fetch_assoc()) {
        $pokemon_name = get_local_pokemon_name($mon['pokedex_id'] . '-' . $mon['pokemon_form']);
        $keys[] = array(
            'text'          => $mon['pokedex_id'] . SP . $pokemon_name,
            'callback_data' => $mon['pokedex_id'] . '-' . $mon['pokemon_form'] . ':pokedex_edit_pokemon:0'
        );
    }

    // Empty backs and next keys
    $keys_back = [];
    $keys_next = [];

    // Add back key.
    if ($limit > 0) {
        $new_limit = $limit - $entries;
        $empty_back_key = [];
        $back = universal_key($empty_back_key, $new_limit, $module, $action, getTranslation('back') . " (-" . $entries . ")");
        $keys_back[] = $back[0][0];
    }

    // Add skip back key.
    if ($limit - $skip > 0) {
        $new_limit = $limit - $skip - $entries;
        $empty_back_key = [];
        $back = universal_key($empty_back_key, $new_limit, $module, $action, getTranslation('back') . " (-" . $skip . ")");
        $keys_back[] = $back[0][0];
    }

    // Add next key.
    if (($limit + $entries) < $count) {
        $new_limit = $limit + $entries;
        $empty_next_key = [];
        $next = universal_key($empty_next_key, $new_limit, $module, $action, getTranslation('next') . " (+" . $entries . ")");
        $keys_next[] = $next[0][0];
    }

    // Add skip next key.
    if (($limit + $skip + $entries) < $count) {
        $new_limit = $limit + $skip + $entries;
        $empty_next_key = [];
        $next = universal_key($empty_next_key, $new_limit, $module, $action, getTranslation('next') . " (+" . $skip . ")");
        $keys_next[] = $next[0][0];
    }

    // Exit key
    $empty_exit_key = [];
    $key_exit = universal_key($empty_exit_key, "0", "exit", "0", getTranslation('abort'));

    // Get the inline key array.
    $keys = inline_key_array($keys, 1);
    $keys_back = inline_key_array($keys_back, 2);
    $keys_next = inline_key_array($keys_next, 2);
    $keys = array_merge($keys_back, $keys);
    $keys = array_merge($keys, $keys_next);
    $keys = array_merge($keys, $key_exit);

    return $keys;
}

/**
 * Pokemon keys.
 * @param $gym_id_plus_letter
 * @param $raid_level
 * @return array
 */
function pokemon_keys($gym_id_plus_letter, $raid_level, $action)
{
    // Init empty keys array.
    $keys = [];

    // Get pokemon from database
    $rs = my_query(
            "
            SELECT    pokedex_id, pokemon_form
            FROM      pokemon
            WHERE     raid_level = '$raid_level'
            "
        );

    // Add key for each raid level
    while ($pokemon = $rs->fetch_assoc()) {
        $keys[] = array(
            'text'          => get_local_pokemon_name($pokemon['pokedex_id'] . '-' . $pokemon['pokemon_form']),
            'callback_data' => $gym_id_plus_letter . ':' . $action . ':' . $pokemon['pokedex_id'] . '-' . $pokemon['pokemon_form']
        );
    }

    // Get the inline key array.
    $keys = inline_key_array($keys, 3);

    return $keys;
}

/**
 * Weather keys.
 * @param $pokedex_id
 * @param $action
 * @param $arg
 * @return array
 */
function weather_keys($pokedex_id, $action, $arg)
{
    // Get the type, level and cp
    $data = explode("-", $arg);
    $weather_add = $data[0] . '-';
    $weather_value = $data[1];

    // Save and reset values
    $save_arg = 'save-' . $weather_value;
    $reset_arg = $weather_add . '0';
    
    // Init empty keys array.
    $keys = [];

    // Max amount of weathers a pokemon raid boss can have is 3 which means 999
    // Keys will be shown up to 99 and when user is adding one more weather we exceed 99, so we remove the keys then
    // This means we do not exceed the max amout of 3 weathers a pokemon can have :)
    // And no, 99 is not a typo if you read my comment above :P
    if($weather_value <= 99) {
        // Get last number from weather array
        end($GLOBALS['weather']);
        $last = key($GLOBALS['weather']);

        // Add buttons for each weather.
        for ($i = 1; $i <= $last; $i = $i + 1) {
            // Get length of arg and split arg
            $weather_value_length = strlen((string)$weather_value);
            $weather_value_string = str_split((string)$weather_value);

            // Continue if weather got already selected
            if($weather_value_length == 1 && $weather_value == $i) continue;
            if($weather_value_length == 2 && $weather_value_string[0] == $i) continue;
            if($weather_value_length == 2 && $weather_value_string[1] == $i) continue;

            // Set new weather.
            $new_weather = $weather_add . ($weather_value == 0 ? '' : $weather_value) . $i;

            // Set keys.
            $keys[] = array(
                'text'          => $GLOBALS['weather'][$i],
                'callback_data' => $pokedex_id . ':' . $action . ':' . $new_weather
            ); 
        }
    }

    // Get the inline key array.
    $keys = inline_key_array($keys, 3);

    // Save and Reset key
    $keys[] = array(
        array(
            'text'          => EMOJI_DISK,
            'callback_data' => $pokedex_id . ':' . $action . ':' . $save_arg
        ),
        array(
            'text'          => getTranslation('reset'),
            'callback_data' => $pokedex_id . ':' . $action . ':' . $reset_arg
        )
    );

    return $keys;
}

/**
 * CP keys.
 * @param $pokedex_id
 * @param $action
 * @param $arg
 * @return array
 */
function cp_keys($pokedex_id, $action, $arg)
{
    // Get the type, level and cp
    $data = explode("-", $arg);
    $cp_type_level = $data[0] . '-' . $data[1];
    $cp_add = $data[0] . '-' . $data[1] . '-' . $data[2] . '-';
    $old_cp = $data[3];

    // Save and reset values
    $save_arg = $cp_type_level . '-save-' . $old_cp;
    $reset_arg = $cp_add . '0';
    
    // Init empty keys array.
    $keys = [];

    // Max CP is 9999 and no the value 999 is not a typo!
    // Keys will be shown up to 999 and when user is adding one more number we exceed 999, so we remove the keys then
    // This means we do not exceed a Max CP of 9999 :)
    if($old_cp <= 999) {

        // Add keys 0 to 9
        /**
         * 7 8 9
         * 4 5 6
         * 1 2 3
         * 0
        */

        // 7 8 9
        for ($i = 7; $i <= 9; $i = $i + 1) {
            // Set new cp
            $new_cp = $cp_add . ($old_cp == 0 ? '' : $old_cp) . $i;

            // Set keys.
            $keys[] = array(
                'text'          => $i,
                'callback_data' => $pokedex_id . ':' . $action . ':' . $new_cp
            );
        }

        // 4 5 6
        for ($i = 4; $i <= 6; $i = $i + 1) {
            // Set new cp
            $new_cp = $cp_add . ($old_cp == 0 ? '' : $old_cp) . $i;

            // Set keys.
            $keys[] = array(
                'text'          => $i,
                'callback_data' => $pokedex_id . ':' . $action . ':' . $new_cp
            );
        }

        // 1 2 3
        for ($i = 1; $i <= 3; $i = $i + 1) {
            // Set new cp
            $new_cp = $cp_add . ($old_cp == 0 ? '' : $old_cp) . $i;

            // Set keys.
            $keys[] = array(
                'text'          => $i,
                'callback_data' => $pokedex_id . ':' . $action . ':' . $new_cp
            );
        }

        // 0
        if($old_cp != 0) {
            // Set new cp
            $new_cp = $cp_add . $old_cp . '0';
        } else {
            $new_cp = $reset_arg;
        }
        
        // Set keys.
        $keys[] = array(
            'text'          => '0',
            'callback_data' => $pokedex_id . ':' . $action . ':' . $new_cp
        );
    }

    // Save
    $keys[] = array(
        'text'          => EMOJI_DISK,
        'callback_data' => $pokedex_id . ':' . $action . ':' . $save_arg
    );

    // Reset
    $keys[] = array(
        'text'          => getTranslation('reset'),
        'callback_data' => $pokedex_id . ':' . $action . ':' . $reset_arg
    );

    // Get the inline key array.
    $keys = inline_key_array($keys, 3);

    return $keys;
}


/**
 * Insert raid cleanup info to database.
 * @param $chat_id
 * @param $message_id
 * @param $raid_id
 */
function insert_cleanup($chat_id, $message_id, $raid_id)
{
    // Log ID's of raid, chat and message
    debug_log('Raid_ID: ' . $raid_id);
    debug_log('Chat_ID: ' . $chat_id);
    debug_log('Message_ID: ' . $message_id);

    if ((is_numeric($chat_id)) && (is_numeric($message_id)) && (is_numeric($raid_id)) && ($raid_id > 0)) {
        global $db;

        // Get raid times.
        $raid = get_raid($raid_id);
    
	// Init found.
	$found = false;

        // Insert cleanup info to database
        if ($raid) {
	    // Check if cleanup info is already in database or not
	    // Needed since raids can be shared to multiple channels / supergroups!
	    $rs = my_query(
                "
		SELECT    *
            	    FROM      cleanup
                    WHERE     raid_id = '{$raid_id}'
                "
            );

	    // Chat_id and message_id equal to info from database
	    while ($cleanup = $rs->fetch_assoc()) {
		// Leave while loop if cleanup info is already in database
		if(($cleanup['chat_id'] == $chat_id) && ($cleanup['message_id'] == $message_id)) {
            	    debug_log('Cleanup preparation info is already in database!');
		    $found = true;
		    break;
		} 
	    }
	}

	// Insert into database when raid found but no cleanup info found
        if ($raid && !$found) {
            // Build query for cleanup table to add cleanup info to database
            debug_log('Adding cleanup info to database:');
            $rs = my_query(
                "
                INSERT INTO   cleanup
                SET           raid_id = '{$raid_id}',
                              chat_id = '{$chat_id}',
                              message_id = '{$message_id}'
                "
            );
	} 
    } else {
        debug_log('Invalid input for cleanup preparation!');
    }
}

/**
 * Run raids cleanup.
 * @param $telegram
 * @param $database
 */
function run_cleanup ($telegram = 2, $database = 2) {
    // Check configuration, cleanup of telegram needs to happen before database cleanup!
    if (CLEANUP_TIME_TG > CLEANUP_TIME_DB) {
	cleanup_log('Configuration issue! Cleanup time for telegram messages needs to be lower or equal to database cleanup time!');
	cleanup_log('Stopping cleanup process now!');
	exit;
    }

    /* Check input
     * 0 = Do nothing
     * 1 = Cleanup
     * 2 = Read from config
    */

    // Get cleanup values from config per default.
    if ($telegram == 2) {
	$telegram = (CLEANUP_TELEGRAM == true) ? 1 : 0;
    }

    if ($database == 2) {
	$database = (CLEANUP_DATABASE == true) ? 1 : 0;
    }

    // Start cleanup when at least one parameter is set to trigger cleanup
    if ($telegram == 1 || $database == 1) {
        // Query for telegram cleanup without database cleanup
        if ($telegram == 1 && $database == 0) {
            // Get cleanup info.
            $rs = my_query(
                "
                SELECT    * 
                FROM      cleanup
                  WHERE   chat_id <> 0
                  ORDER BY id DESC
                  LIMIT 0, 250     
                ", true
            );
        // Query for database cleanup without telegram cleanup
        } else if ($telegram == 0 && $database == 1) {
            // Get cleanup info.
            $rs = my_query(
                "
                SELECT    * 
                FROM      cleanup
                  WHERE   chat_id = 0
                  LIMIT 0, 250
                ", true
            );
        // Query for telegram and database cleanup
        } else {
            // Get cleanup info for telegram cleanup.
            $rs = my_query(
                "
                SELECT    * 
                FROM      cleanup
                  WHERE   chat_id <> 0
                  ORDER BY id DESC
                  LIMIT 0, 250
                ", true
            );

            // Get cleanup info for database cleanup.
            $rs_db = my_query(
                "
                SELECT    * 
                FROM      cleanup
                  WHERE   chat_id = 0
                  LIMIT 0, 250
                ", true
            );
        }

        // Init empty cleanup jobs array.
        $cleanup_jobs = [];

	// Fill array with cleanup jobs.
        while ($rowJob = $rs->fetch_assoc()) {
            $cleanup_jobs[] = $rowJob;
        }

        // Cleanup telegram and database?
        if($telegram == 1 && $database == 1) {
	    // Add database cleanup jobs to array.
            while ($rowDBJob = $rs_db->fetch_assoc()) {
                $cleanup_jobs[] = $rowDBJob;
            }
        }

        // Write to log.
        cleanup_log($cleanup_jobs);

        // Init previous raid id.
        $prev_raid_id = "FIRST_RUN";

        foreach ($cleanup_jobs as $row) {
	    // Set current raid id.
	    $current_raid_id = ($row['raid_id'] == 0) ? $row['cleaned'] : $row['raid_id'];

            // Write to log.
            cleanup_log("Cleanup ID: " . $row['id']);
            cleanup_log("Chat ID: " . $row['chat_id']);
            cleanup_log("Message ID: " . $row['message_id']);
            cleanup_log("Raid ID: " . $row['raid_id']);

            // Make sure raid exists
            $rs = my_query(
                "
                SELECT  end_time
                FROM    raids
                  WHERE id = {$current_raid_id}
                ", true
            );

            // Fetch raid data.
            $raid = $rs->fetch_assoc();

            // No raid found - set cleanup to 0 and continue with next raid
            if (!$raid) {
                cleanup_log('No raid found with ID: ' . $current_raid_id, '!');
                cleanup_log('Updating cleanup information.');
                my_query(
                "
                    UPDATE    cleanup
                    SET       chat_id = 0, 
                              message_id = 0 
                    WHERE   id = {$row['id']}
                ", true
                );

                // Continue with next raid
                continue;
            }

	    // Get raid data only when raid_id changed compared to previous run
	    if ($prev_raid_id != $current_raid_id) {
                // Now.
                $now = utcnow('YmdHis');
                $log_now = utcnow();

	        // Set cleanup time for telegram. 
                $cleanup_time_tg = new DateTimeImmutable($raid['end_time'], new DateTimeZone('UTC'));
                $cleanup_time_tg = $cleanup_time_tg->add(new DateInterval("PT".CLEANUP_TIME_TG."M"));
                $clean_tg = $cleanup_time_tg->format('YmdHis');
                $log_clean_tg = $cleanup_time_tg->format('Y-m-d H:i:s');

	        // Set cleanup time for database. 
                $cleanup_time_db = new DateTimeImmutable($raid['end_time'], new DateTimeZone('UTC'));
                $cleanup_time_db = $cleanup_time_db->add(new DateInterval("PT".CLEANUP_TIME_DB."M"));
                $clean_db = $cleanup_time_db->format('YmdHis');
                $log_clean_db = $cleanup_time_db->format('Y-m-d H:i:s');

		// Write times to log.
		cleanup_log($log_now, 'Current UTC time:');
		cleanup_log($raid['end_time'], 'Raid UTC end time:');
		cleanup_log($log_clean_tg, 'Telegram UTC cleanup time:');
		cleanup_log($log_clean_db, 'Database UTC cleanup time:');
	    }

	    // Time for telegram cleanup?
	    if ($clean_tg < $now) {
                // Delete raid poll telegram message if not already deleted
	        if ($telegram == 1 && $row['chat_id'] != 0 && $row['message_id'] != 0) {
		    // Delete telegram message.
                    cleanup_log('Deleting telegram message ' . $row['message_id'] . ' from chat ' . $row['chat_id'] . ' for raid ' . $row['raid_id']);
                    delete_message($row['chat_id'], $row['message_id']);
		    // Set database values of chat_id and message_id to 0 so we know telegram message was deleted already.
                    cleanup_log('Updating telegram cleanup information.');
		    my_query(
    		    "
    		        UPDATE    cleanup
    		        SET       chat_id = 0, 
    		                  message_id = 0 
      		        WHERE   id = {$row['id']}
		    ", true
		    );
	        } else {
		    if ($telegram == 1) {
			cleanup_log('Telegram message is already deleted!');
		    } else {
			cleanup_log('Telegram cleanup was not triggered! Skipping...');
		    }
		}
	    } else {
		cleanup_log('Skipping cleanup of telegram for this raid! Cleanup time has not yet come...');
	    }

	    // Time for database cleanup?
	    if ($clean_db < $now) {
                // Delete raid from attendance table.
	        // Make sure to delete only once - raid may be in multiple channels/supergroups, but only 1 time in database
	        if (($database == 1) && $row['raid_id'] != 0 && ($prev_raid_id != $current_raid_id)) {
		    // Delete raid from attendance table.
                    cleanup_log('Deleting attendances for raid ' . $current_raid_id);
                    my_query(
                    "
                        DELETE FROM    attendance
                        WHERE          raid_id = {$row['raid_id']}
                    ", true
                    );

		    // Set database value of raid_id to 0 so we know attendance info was deleted already
		    // Use raid_id in where clause since the same raid_id can in cleanup more than once
                    cleanup_log('Updating database cleanup information.');
                    my_query(
                    "
                        UPDATE    cleanup
                        SET       raid_id = 0, 
				  cleaned = {$row['raid_id']}
                        WHERE   raid_id = {$row['raid_id']}
                    ", true
                    );
	        } else {
		    if ($database == 1) {
		        cleanup_log('Attendances are already deleted!');
		    } else {
			cleanup_log('Attendance cleanup was not triggered! Skipping...');
		    }
		}

		// Delete raid from cleanup table and raid table once every value is set to 0 and cleaned got updated from 0 to the raid_id
		// In addition trigger deletion only when previous and current raid_id are different to avoid unnecessary sql queries
		if ($row['raid_id'] == 0 && $row['chat_id'] == 0 && $row['message_id'] == 0 && $row['cleaned'] != 0 && ($prev_raid_id != $current_raid_id)) {
		    // Delete raid from raids table.
		    cleanup_log('Deleting raid ' . $row['cleaned'] . ' from database.');
                    my_query(
                    "
                        DELETE FROM    raids
                        WHERE   id = {$row['cleaned']}
                    ", true
                    );
		    
		    // Get all cleanup jobs which will be deleted now.
                    cleanup_log('Removing cleanup info from database:');
		    $rs_cl = my_query(
                    "
                        SELECT *
			FROM    cleanup
                        WHERE   cleaned = {$row['cleaned']}
                    ", true
		    );

		    // Log each cleanup ID which will be deleted.
		    while($rs_cleanups = $rs_cl->fetch_assoc()) {
 			cleanup_log('Cleanup ID: ' . $rs_cleanups['id'] . ', Former Raid ID: ' . $rs_cleanups['cleaned']);
		    }

		    // Finally delete from cleanup table.
                    my_query(
                    "
                        DELETE FROM    cleanup
                        WHERE   cleaned = {$row['cleaned']}
                    ", true
                    );
		} else {
		    if ($prev_raid_id != $current_raid_id) {
			cleanup_log('Time for complete removal of raid from database has not yet come.');
		    } else {
			cleanup_log('Complete removal of raid from database was already done!');
		    }
		}
	    } else {
		cleanup_log('Skipping cleanup of database for this raid! Cleanup time has not yet come...');
	    }
	
	    // Store current raid id as previous id for next loop
            $prev_raid_id = $current_raid_id;
        }

        // Write to log.
        cleanup_log('Finished with cleanup process!');
    }
}

/**
 * Keys vote.
 * @param $raid
 * @return array
 */
function keys_vote($raid)
{
    // Init keys_time array.
    $keys_time = [];

    // Get current UTC time and raid UTC times.
    $now = utcnow();
    $end_time = $raid['end_time'];
    $start_time = $raid['start_time'];

    // Write to log.
    debug_log($now, 'UTC NOW:');
    debug_log($end_time, 'UTC END:');
    debug_log($start_time, 'UTC START:');

    // Extra Keys
    $buttons_extra = [
        [
            [
                'text'          => getPublicTranslation('alone'),
                'callback_data' => $raid['id'] . ':vote_extra:0'
            ],
            [
                'text'          => '+ ' . TEAM_B,
                'callback_data' => $raid['id'] . ':vote_extra:mystic'
            ],
            [
                'text'          => '+ ' . TEAM_R,
                'callback_data' => $raid['id'] . ':vote_extra:valor'
            ],
            [
                'text'          => '+ ' . TEAM_Y,
                'callback_data' => $raid['id'] . ':vote_extra:instinct'
            ]
        ]
    ];

    // Team and level keys.
    $buttons_teamlvl = [
        [
            [
                'text'          => 'Team',
                'callback_data' => $raid['id'] . ':vote_team:0'
            ],
            [
                'text'          => 'Lvl +',
                'callback_data' => $raid['id'] . ':vote_level:up'
            ],
            [
                'text'          => 'Lvl -',
                'callback_data' => $raid['id'] . ':vote_level:down'
            ]
        ]
    ];

    // Ex-Raid Invite key
    $button_invite = [
        [
            [
                'text'          => EMOJI_INVITE,
                'callback_data' => $raid['id'] . ':vote_invite:0'
            ]
        ]
    ];


    // Show icon, icon + text or just text.
    // Icon.
    if(RAID_VOTE_ICONS == true && RAID_VOTE_TEXT == false) {
        $text_here = EMOJI_HERE;
        $text_late = EMOJI_LATE;
        $text_done = TEAM_DONE;
        $text_cancel = TEAM_CANCEL;
    // Icon + text.
    } else if(RAID_VOTE_ICONS == true && RAID_VOTE_TEXT == true) {
        $text_here = EMOJI_HERE . getPublicTranslation('here');
        $text_late = EMOJI_LATE . getPublicTranslation('late');
        $text_done = TEAM_DONE . getPublicTranslation('done');
        $text_cancel = TEAM_CANCEL . getPublicTranslation('cancellation');
    // Text.
    } else {
        $text_here = getPublicTranslation('here');
        $text_late = getPublicTranslation('late');
        $text_done = getPublicTranslation('done');
        $text_cancel = getPublicTranslation('cancellation');
    }

    // Status keys.
    $buttons_status = [
        [
            [
                'text'          => EMOJI_REFRESH,
                'callback_data' => $raid['id'] . ':vote_refresh:0'
            ],
            [
                'text'          => $text_here,
                'callback_data' => $raid['id'] . ':vote_status:arrived'
            ],
            [
                'text'          => $text_late,
                'callback_data' => $raid['id'] . ':vote_status:late'
            ],
            [
                'text'          => $text_done,
                'callback_data' => $raid['id'] . ':vote_status:raid_done'
            ],
            [
                'text'          => $text_cancel,
                'callback_data' => $raid['id'] . ':vote_status:cancel'
            ],
        ],
    ];

    // Raid ended already.
    if ($end_time < $now) {
        $keys = [
            [
                [
                    'text'          => getPublicTranslation('raid_done'),
                    'callback_data' => $raid['id'] . ':vote_refresh:0'
                ]
            ]
        ];
    // Raid is still running.
    } else {
        // Attend raid at any time
        if(RAID_ANYTIME == true)
        {
            $keys_time[] = array(
                'text'          => getPublicTranslation('anytime'),
                'callback_data' => $raid['id'] . ':vote_time:0'
            );
        }

        // Get current time.
        $now_helper = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $now_helper = $now_helper->format('Y-m-d H:i') . ':00';
        $dt_now = new DateTimeImmutable($now_helper, new DateTimeZone('UTC'));

        // Get direct start slot
        $direct_slot = new DateTimeImmutable($start_time, new DateTimeZone('UTC'));

        // Get first raidslot rounded up to the next 5 minutes
        // Get minute and convert modulo raidslot
        $five_slot = new DateTimeImmutable($start_time, new DateTimeZone('UTC'));
        $minute = $five_slot->format("i");
        $minute = $minute % 5;

        // Count minutes to next 5 multiple minutes if necessary
        if($minute != 0)
        {
            // Count difference
            $diff = 5 - $minute;
            // Add difference
            $five_slot = $five_slot->add(new DateInterval("PT".$diff."M"));
        }

        // Add RAID_FIRST_START minutes to five minutes slot
        //$five_plus_slot = new DateTime($five_slot, new DateTimeZone('UTC'));
        $five_plus_slot = $five_slot;
        $five_plus_slot = $five_plus_slot->add(new DateInterval("PT".RAID_FIRST_START."M")); 

        // Get first regular raidslot
	// Get minute and convert modulo raidslot
        $first_slot = new DateTimeImmutable($start_time, new DateTimeZone('UTC'));
        $minute = $first_slot->format("i");
        $minute = $minute % RAID_SLOTS;

        // Count minutes to next raidslot multiple minutes if necessary
        if($minute != 0)
        {
            // Count difference
            $diff = RAID_SLOTS - $minute;
            // Add difference
            $first_slot = $first_slot->add(new DateInterval("PT".$diff."M"));
        } 

        // Compare times slots to add them to keys.
        // Example Scenarios:
        // Raid 1: Start = 17:45, RAID_FIRST_START = 10, RAID_SLOTS = 15
        // Raid 2: Start = 17:36, RAID_FIRST_START = 10, RAID_SLOTS = 15
        // Raid 3: Start = 17:35, RAID_FIRST_START = 10, RAID_SLOTS = 15
        // Raid 4: Start = 17:31, RAID_FIRST_START = 10, RAID_SLOTS = 15
        // Raid 5: Start = 17:40, RAID_FIRST_START = 10, RAID_SLOTS = 15
        // Raid 6: Start = 17:32, RAID_FIRST_START = 5, RAID_SLOTS = 5

        // Write slots to log.
        debug_log($direct_slot, 'Direct start slot:');
        debug_log($five_slot, 'Next 5 Minute slot:');
        debug_log($first_slot, 'First regular slot:');

        // Add first slot only, as all slot times are identical
        if($direct_slot == $five_slot && $direct_slot == $first_slot) {
            // Raid 1: 17:45 (17:45 == 17:45 && 17:45 == 17:45)

            // Add first slot
            if($first_slot >= $dt_now) {
                $slot = $first_slot->format('Y-m-d H:i:s');
                $keys_time[] = array(
                    'text'          => dt2time($slot),
                    'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                );
            }

        // Add either five and first slot or only first slot based on RAID_FIRST_START
        } else if($direct_slot == $five_slot && $five_slot < $first_slot) {
            // Raid 3: 17:35 == 17:35 && 17:35 < 17:45
            // Raid 5: 17:40 == 17:40 && 17:40 < 17:45

            // Add next five minutes slot and first regular slot
            if($five_plus_slot <= $first_slot) {
                // Raid 3: 17:35, 17:45 (17:35 + 10min <= 17:45)

                // Add five minutes slot
                if($five_slot >= $dt_now) {
                    $slot = $five_slot->format('Y-m-d H:i:s');
                    $keys_time[] = array(
                        'text'          => dt2time($slot),
                        'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                    );
                }

                // Add first slot
                if($first_slot >= $dt_now) {
                    $slot = $first_slot->format('Y-m-d H:i:s');
                    $keys_time[] = array(
                        'text'          => dt2time($slot),
                        'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                    );
                }

            // Add only first regular slot
            } else {
                // Raid 5: 17:45

                // Add first slot
                if($first_slot >= $dt_now) {
                    $slot = $first_slot->format('Y-m-d H:i:s');
                    $keys_time[] = array(
                        'text'          => dt2time($slot),
                        'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                    );
                }
            }

        // Add direct slot and first slot
        } else if($direct_slot < $five_slot && $five_slot == $first_slot) {
            // Raid 6: 17:32 < 17:35 && 17:35 == 17:35
            // Some kind of special case for a low value of RAID_SLOTS

            // Add direct slot?
            if(RAID_DIRECT_START == true) {
                if($direct_slot >= $dt_now) {
                    $slot = $direct_slot->format('Y-m-d H:i:s');
                    $keys_time[] = array(
                        'text'          => dt2time($slot),
                        'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                    );
                }
            }

            // Add first slot
            if($first_slot >= $dt_now) {
                $slot = $first_slot->format('Y-m-d H:i:s');
                $keys_time[] = array(
                    'text'          => dt2time($slot),
                    'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                );
            }


        // Add either all 3 slots (direct slot, five minutes slot and first regular slot) or
        // 2 slots (direct slot and first slot) as RAID_FIRST_START does not allow the five minutes slot to be added
        } else if($direct_slot < $five_slot && $five_slot < $first_slot) {
            // Raid 2: 17:36 < 17:40 && 17:40 < 17:45
            // Raid 4: 17:31 < 17:35 && 17:35 < 17:45

            // Add all 3 slots
            if($five_plus_slot <= $first_slot) {
                // Raid 4: 17:31, 17:35, 17:45

                // Add direct slot?
                if(RAID_DIRECT_START == true) {
                    if($direct_slot >= $dt_now) {
                        $slot = $direct_slot->format('Y-m-d H:i:s');
                        $keys_time[] = array(
                            'text'          => dt2time($slot),
                            'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                        );
                    }
                }

                // Add five minutes slot
                if($five_slot >= $dt_now) {
                    $slot = $five_slot->format('Y-m-d H:i:s');
                    $keys_time[] = array(
                        'text'          => dt2time($slot),
                        'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                    );
                }

                // Add first slot
                if($first_slot >= $dt_now) {
                    $slot = $first_slot->format('Y-m-d H:i:s');
                    $keys_time[] = array(
                        'text'          => dt2time($slot),
                        'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                    );
                }
            // Add direct slot and first regular slot
            } else {
                // Raid 2: 17:36, 17:45

                // Add direct slot?
                if(RAID_DIRECT_START == true) {
                    if($direct_slot >= $dt_now) {
                        $slot = $direct_slot->format('Y-m-d H:i:s');
                        $keys_time[] = array(
                            'text'          => dt2time($slot),
                            'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                        );
                    }
                }

                // Add first slot
                if($first_slot >= $dt_now) {
                    $slot = $first_slot->format('Y-m-d H:i:s');
                    $keys_time[] = array(
                        'text'          => dt2time($slot),
                        'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                    );
                }
            }

        // We missed all possible cases or forgot to include them in future else-if-clauses :D
        // Try to add at least the direct slot.
        } else {
            // Add direct slot?
            if(RAID_DIRECT_START == true) {
                if($first_slot >= $dt_now) {
                    $slot = $direct_slot->format('Y-m-d H:i:s');
                    $keys_time[] = array(
                        'text'          => dt2time($slot),
                        'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                    );
                }
            }
        }


        // Init last slot time.
        $last_slot = new DateTimeImmutable($start_time, new DateTimeZone('UTC'));

        // Get regular slots
        // Start with second slot as first slot is already added to keys.
        $second_slot = $first_slot->add(new DateInterval("PT".RAID_SLOTS."M"));
        $dt_end = new DateTimeImmutable($end_time, new DateTimeZone('UTC'));
        $regular_slots = new DatePeriod($second_slot, new DateInterval('PT'.RAID_SLOTS.'M'), $dt_end);

        // Add regular slots.
        foreach($regular_slots as $slot){
            $slot_end = $slot->add(new DateInterval('PT'.RAID_LAST_START.'M'));
            // Slot + RAID_LAST_START before end_time?
            if($slot_end < $dt_end) {
                debug_log($slot, 'Regular slot:');
                // Add regular slot.
                if($slot >= $dt_now) {
                    $slot = $slot->format('Y-m-d H:i:s');
                    $keys_time[] = array(
                        'text'          => dt2time($slot),
                        'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                    );

                    // Set last slot for later.
                    $last_slot = new DateTimeImmutable($slot, new DateTimeZone('UTC'));
                } else {
                    // Set last slot for later.
                    $slot = $slot->format('Y-m-d H:i:s');
                    $last_slot = new DateTimeImmutable($slot, new DateTimeZone('UTC'));
                }
            }
        }

        // Add raid last start slot
        // Set end_time to last extra slot, subtract RAID_LAST_START minutes and round down to earlier 5 minutes.
        $last_extra_slot = $dt_end;
        $last_extra_slot = $last_extra_slot->sub(new DateInterval('PT'.RAID_LAST_START.'M'));
        $s = 5 * 60;
        $last_extra_slot = $last_extra_slot->setTimestamp($s * floor($last_extra_slot->getTimestamp() / $s));
        //$time_to_last_slot = $last_extra_slot->diff($last_slot)->format("%a");

        // Last extra slot not conflicting with last slot and time to last regular slot larger than RAID_LAST_START?
        //if($last_extra_slot > $last_slot && $time_to_last_slot > RAID_LAST_START) {

        // Log last and last extra slot.
        debug_log($last_slot, 'Last slot:');
        debug_log($last_extra_slot, 'Last extra slot:');

        // Last extra slot not conflicting with last slot
        if($last_extra_slot > $last_slot) {
            // Add last extra slot
            if($last_extra_slot >= $dt_now) {
                $slot = $last_extra_slot->format('Y-m-d H:i:s');
                $keys_time[] = array(
                    'text'          => dt2time($slot),
                    'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                );
            }
        }

        // Add time keys.
        $buttons_time = inline_key_array($keys_time, 4);

        // Init keys pokemon array.
        $buttons_pokemon = [];

        // Get current pokemon
        $raid_pokemon = $raid['pokemon'];

        // Get raid level
        $raid_level = '0';
        $raid_level = get_raid_level($raid_pokemon);

        // Get participants
        $rs = my_query(
            "
            SELECT    count(attend_time)                  AS count,
                      sum(pokemon = '0')                  AS count_any_pokemon,
                      sum(pokemon = '{$raid_pokemon}')    AS count_raid_pokemon
            FROM      attendance
              WHERE   raid_id = {$raid['id']}
              AND     attend_time IS NOT NULL
              AND     raid_done != 1
              AND     cancel != 1
             "
        );

        $row = $rs->fetch_assoc();

        // Count participants and participants by pokemon
        $count_pp = $row['count'];
        $count_any_pokemon = $row['count_any_pokemon'];
        $count_raid_pokemon = $row['count_raid_pokemon'];

        // Write to log.
        debug_log('Participants for raid with ID ' . $raid['id'] . ': ' . $count_pp);
        debug_log('Participants who voted for any pokemon: ' . $count_any_pokemon);
        debug_log('Participants who voted for ' . $raid_pokemon . ': ' . $count_raid_pokemon);
    
        // Split pokemon id and form
        $raid_pokemon_id = explode('-',$raid_pokemon)[0];

        // Hide keys for specific cases
        $show_keys = true;
        // Make sure raid boss is not an egg
        if(!in_array($raid_pokemon_id, $GLOBALS['eggs'])) {
            // Make sure we either have no participants
            // OR all participants voted for "any" raid boss
            // OR all participants voted for the hatched raid boss 
            // OR all participants voted for "any" or the hatched raid boss
            if($count_pp == 0 || $count_pp == $count_any_pokemon || $count_pp == $count_raid_pokemon || $count_pp == ($count_any_pokemon + $count_raid_pokemon)) {
                $show_keys = false;
            }
        }

        // Add pokemon keys if we found the raid boss
        if ($raid_level != '0' && $show_keys) {
            // Get pokemon from database
            $rs = my_query(
                "
                SELECT    pokedex_id, pokemon_form
                FROM      pokemon
                WHERE     raid_level = '$raid_level'
                "
            );

            // Init counter. 
            $count = 0;

            // Get eggs.
            $eggs = $GLOBALS['eggs'];

            // Add key for each raid level
            while ($pokemon = $rs->fetch_assoc()) {
                if(in_array($pokemon['pokedex_id'], $eggs)) continue;
                $buttons_pokemon[] = array(
                    'text'          => get_local_pokemon_name($pokemon['pokedex_id'] . '-' . $pokemon['pokemon_form'], true),
                    'callback_data' => $raid['id'] . ':vote_pokemon:' . $pokemon['pokedex_id'] . '-' . $pokemon['pokemon_form']
                );

                // Counter
                $count = $count + 1;
            }

            // Add pokemon keys if we have two or more pokemon
            if($count >= 2) {
                // Add button if raid boss does not matter
                $buttons_pokemon[] = array(
                    'text'          => getPublicTranslation('any_pokemon'),
                    'callback_data' => $raid['id'] . ':vote_pokemon:0'
                );

                // Finally add pokemon to keys
                $buttons_pokemon = inline_key_array($buttons_pokemon, 3);
            } else {
                // Reset pokemon buttons.
                $buttons_pokemon = [];
            }
        }

        // Init keys array
        $keys = [];

        // Get UI order from config and apply if nothing is missing!
        $keys_UI_config = explode(',', RAID_POLL_UI_ORDER);
        $keys_default = explode(',', 'extra,teamlvl,time,pokemon,status');

        //debug_log($keys_UI_config);
        //debug_log($keys_default);

        // Add Ex-Raid Invite button for raid level X
        if ($raid_level == 'X') {
                $buttons_teamlvl[0] = array_merge($buttons_teamlvl[0], $button_invite[0]);
        }

        // Compare if arrays have the same key/value pairs
        if(count($keys_UI_config) == count($keys_default) && count(array_diff($keys_UI_config, $keys_default)) == 0){
            // Custom keys order
            foreach ($keys_UI_config as $keyname) {
                $keys = array_merge($keys, ${'buttons_' . $keyname});
            }
        } else {
            // Default keys order
            $keys = array_merge($buttons_extra,$buttons_teamlvl,$buttons_time,$buttons_pokemon,$buttons_status);
        }
    }

    // Return the keys.
    return $keys;
}

/**
 * Send response vote.
 * @param $update
 * @param $data
 * @param bool $new
 */
function send_response_vote($update, $data, $new = false)
{
    // Get the raid data by id.
    $raid = get_raid($data['id']);

    $msg = show_raid_poll($raid);
    $keys = keys_vote($raid);

    // Write to log.
    // debug_log($keys);

    if ($new) {
        $loc = send_location($update['callback_query']['message']['chat']['id'], $raid['lat'], $raid['lon']);

        // Write to log.
        debug_log('location:');
        debug_log($loc);

        // Telegram JSON array.
        $tg_json = array();

        // Send the message.
        $tg_json[] = send_message($update['callback_query']['message']['chat']['id'], $msg . "\n", $keys, ['reply_to_message_id' => $loc['result']['message_id']], true);

        // Answer the callback.
        $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $msg, true);

    } else {
        // Change message string.
        $callback_msg = getTranslation('vote_updated');

        // Telegram JSON array.
        $tg_json = array();

        // Answer the callback.
        $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_msg, true, true);

        // Edit the message.
        $tg_json[] = edit_message($update, $msg, $keys, ['disable_web_page_preview' => 'true'], true);
    }

    // Telegram multicurl request.
    curl_json_multi_request($tg_json);

    // Exit.
    exit();
}

/**
 * Send please vote for a time first.
 * @param $update
 */
function send_vote_time_first($update)
{
    // Set the message.
    $msg = getTranslation('vote_time_first');

    // Answer the callback.
    answerCallbackQuery($update['callback_query']['id'], $msg);

    exit();
}

/**
 * Insert overview.
 * @param $chat_id
 * @param $message_id
 */
function insert_overview($chat_id, $message_id)
{
    global $db;

    // Build query to check if overview details are already in database or not
    $rs = my_query(
        "
        SELECT    COUNT(*)
        FROM      overview
          WHERE   chat_id = '{$chat_id}'
         "
        );

    $row = $rs->fetch_row();

    // Overview already in database or new
    if (empty($row['0'])) {
        // Build query for overview table to add overview info to database
        debug_log('Adding new overview information to database overview list!');
        $rs = my_query(
            "
            INSERT INTO   overview
            SET           chat_id = '{$chat_id}',
                          message_id = '{$message_id}'
            "
        );
    } else {
        // Nothing to do - overview information is already in database.
        debug_log('Overview information is already in database! Nothing to do...');
    }
}

/**
 * Delete overview.
 * @param $chat_id
 * @param $message_id
 */
function delete_overview($chat_id, $message_id)
{
    global $db;

    // Delete telegram message.
    debug_log('Deleting overview telegram message ' . $message_id . ' from chat ' . $chat_id);
    delete_message($chat_id, $message_id);

    // Delete overview from database.
    debug_log('Deleting overview information from database for Chat_ID: ' . $chat_id);
    $rs = my_query(
        "
        DELETE FROM   overview 
        WHERE   chat_id = '{$chat_id}'
        "
    );
}

/**
 * Get overview data to Share or refresh.
 * @param $update
 * @param $chats_active
 * @param $raids_active
 * @param $action - refresh or share
 * @param $chat_id
 */
function get_overview($update, $chats_active, $raids_active, $action = 'refresh', $chat_id = 0)
{
    // Add pseudo array for last run to active chats array
    $last_run = [];
    $last_run['chat_id'] = 'LAST_RUN';
    $chats_active[] = $last_run;

    // Init previous chat_id and raid_id
    $previous = 'FIRST_RUN';
    $previous_raid = 'FIRST_RAID';

    // Current time.
    $now = utcnow();
    
    // Any active raids currently?
    if (empty($raids_active)) {
        // Init keys.
        $keys = [];

        // Refresh active overview messages with 'no_active_raids_currently' or send 'no_active_raids_found' message to user.
        $rs = my_query(
            "
            SELECT    *
            FROM      overview
            "
        );

        // Refresh active overview messages.
        while ($row_overview = $rs->fetch_assoc()) {
            $chat_id = $row_overview['chat_id'];
            $message_id = $row_overview['message_id'];

            // Get info about chat for title.
            debug_log('Getting chat object for chat_id: ' . $row_overview['chat_id']);
            $chat_obj = get_chat($row_overview['chat_id']);
            $chat_title = '';

            // Set title.
            if ($chat_obj['ok'] == 'true') {
                $chat_title = $chat_obj['result']['title'];
                debug_log('Title of the chat: ' . $chat_obj['result']['title']);
            }

            // Set the message.
            $msg = '<b>' . getPublicTranslation('raid_overview_for_chat') . ' ' . $chat_title . ' '. getPublicTranslation('from') .' '. dt2time('now') . '</b>' .  CR . CR;
            $msg .= getPublicTranslation('no_active_raids');

            //Add custom message from the config.
            if (defined('RAID_PIN_MESSAGE') && !empty(RAID_PIN_MESSAGE)) {
                $msg .= CR . CR .RAID_PIN_MESSAGE . CR;
            }

            // Edit the message, but disable the web preview!
            debug_log('Updating overview:' . CR . 'Chat_ID: ' . $chat_id . CR . 'Message_ID: ' . $message_id);
            editMessageText($message_id, $msg, $keys, $chat_id, ['disable_web_page_preview' => 'true']);
        }

        // Triggered from user or cronjob?
        if (!empty($update['callback_query']['id'])) {
            // Send no active raids message to the user.
            $msg = getPublicTranslation('no_active_raids');

            // Telegram JSON array.
            $tg_json = array();

            // Answer the callback.
            $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $msg, true);

            // Edit the message, but disable the web preview!
            $tg_json[] = edit_message($update, $msg, $keys, ['disable_web_page_preview' => 'true'], true);

            // Telegram multicurl request.
            curl_json_multi_request($tg_json);
        }
    
        // Exit here.
        exit;
    }

    // Share or refresh each chat.
    foreach ($chats_active as $row) {
        $current = $row['chat_id'];

        // Telegram JSON array.
        $tg_json = array();

        // Are any raids shared?
        if ($previous == "FIRST_RUN" && $current == "LAST_RUN") {
            // Send no active raids message to the user.
            $msg = getPublicTranslation('no_active_raids_shared');

            // Answer the callback.
            $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $msg, true);

            // Edit the message, but disable the web preview!
            $tg_json[] = edit_message($update, $msg, $keys, ['disable_web_page_preview' => 'true'], true);

            // Telegram multicurl request.
            curl_json_multi_request($tg_json);
        }

        // Telegram JSON array.
        $tg_json = array();

        // Send message if not first run and previous not current
        if ($previous !== 'FIRST_RUN' && $previous !== $current) {
            // Add keys.
	    $keys = [];
        
            //Add custom message from the config.	
            if (defined('RAID_PIN_MESSAGE') && !empty(RAID_PIN_MESSAGE)) {
                $msg .= RAID_PIN_MESSAGE . CR;
            }

            // Share or refresh?
            if ($action == 'share') {
                if ($chat_id == 0) {
                    // Make sure it's not already shared
                    $rs = my_query(
                        "
                        SELECT    COUNT(*)
                        FROM      overview
                        WHERE      chat_id = '{$previous}'
                        "
                    );

                    $row = $rs->fetch_row();

                    if (empty($row['0'])) {
                        // Not shared yet - Share button
                        $keys[] = [
                            [
                                'text'          => getTranslation('share_with') . ' ' . $chat_obj['result']['title'],
                                'callback_data' => '0:overview_share:' . $previous
                            ]
                        ];
                    } else {
                        // Already shared - refresh button
                        $keys[] = [
                            [
                                'text'          => EMOJI_REFRESH,
                                'callback_data' => '0:overview_refresh:' . $previous
                            ],
                            [
                                'text'          => getTranslation('done'),
                                'callback_data' => '0:exit:1'
                            ]
                        ];
                    }

                    // Send the message, but disable the web preview!
                    $tg_json[] = send_message($update['callback_query']['message']['chat']['id'], $msg, $keys, ['disable_web_page_preview' => 'true'], true);

                    // Set the callback message and keys
                    $callback_keys = [];
                    $callback_msg = '<b>' . getTranslation('list_all_overviews') . ':</b>';

                    // Answer the callback.
                    $tg_json[] = answerCallbackQuery($update['callback_query']['id'], 'OK', true);

                    // Edit the message.
                    $tg_json[] = edit_message($update, $callback_msg, $callback_keys, true);

                } else {
                    // Shared overview
                    $keys = [];

                    // Set callback message string.
                    $msg_callback = getTranslation('successfully_shared');

                    // Answer the callback.
                    $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $msg_callback);

                    // Edit the message, but disable the web preview!
                    $tg_json[] = edit_message($update, $msg_callback, $keys, ['disable_web_page_preview' => 'true'], true);

                    // Send the message, but disable the web preview!
                    $tg_json[] = send_message($chat_id, $msg, $keys, ['disable_web_page_preview' => 'true'], true);
                }

                // Telegram multicurl request.
                curl_json_multi_request($tg_json);

	    } else {
                // Refresh overview messages.
                $keys = [];

                // Get active overviews 
                $rs = my_query(
                    "
                    SELECT    message_id
                    FROM      overview
                    WHERE      chat_id = '{$previous}'
                    "
                );

                // Edit text for all messages, but disable the web preview!
                while ($row_msg_id = $rs->fetch_assoc()) {
                    // Set message_id.
                    $message_id = $row_msg_id['message_id'];
                    debug_log('Updating overview:' . CR . 'Chat_ID: ' . $previous . CR . 'Message_ID: ' . $message_id);
                    editMessageText($message_id, $msg, $keys, $previous, ['disable_web_page_preview' => 'true']);
                }

                // Triggered from user or cronjob?
                if (!empty($update['callback_query']['id'])) {
                    // Answer the callback.
                    answerCallbackQuery($update['callback_query']['id'], 'OK');
                }
            }

        }

        // End if last run
        if ($current == 'LAST_RUN') {
            break;
        }

        // Continue with next if previous and current raid id are equal
        if ($previous_raid == $row['raid_id']) {
            continue;
        }

        // Create message for each raid_id
        if($previous !== $current) {
            // Get info about chat for username.
            debug_log('Getting chat object for chat_id: ' . $row['chat_id']);
            $chat_obj = get_chat($row['chat_id']);
            $chat_username = '';

            // Set username if available.
            if ($chat_obj['ok'] == 'true' && isset($chat_obj['result']['username'])) {
                $chat_username = $chat_obj['result']['username'];
                debug_log('Username of the chat: ' . $chat_obj['result']['username']);
            }

            $msg = '<b>' . getPublicTranslation('raid_overview_for_chat') . ' ' . $chat_obj['result']['title'] . ' ' . getPublicTranslation('from') . ' '. dt2time('now') . '</b>' .  CR . CR;
        }

        // Set variables for easier message building.
        $raid_id = $row['raid_id'];
        $pokemon = $raids_active[$raid_id]['pokemon'];
        $pokemon = get_local_pokemon_name($pokemon, true);
        $gym = $raids_active[$raid_id]['gym_name'];
        $ex_gym = $raids_active[$raid_id]['ex_gym'];
        $ex_raid_gym_marker = (strtolower(RAID_EX_GYM_MARKER) == 'icon') ? EMOJI_STAR : '<b>' . RAID_EX_GYM_MARKER . '</b>';
        $start_time = $raids_active[$raid_id]['start_time'];
        $end_time = $raids_active[$raid_id]['end_time'];
        $time_left = $raids_active[$raid_id]['t_left'];

        // Build message and add each gym in this format - link gym_name to raid poll chat_id + message_id if possible
        /* Example:
         * Raid Overview from 18:18h
         *
         * Train Station Gym
         * Raikou - still 0:24h
         *
         * Bus Station Gym
         * Level 5 Egg 18:41 to 19:26
        */
        // Gym name.
        $msg .= $ex_gym ? $ex_raid_gym_marker . SP : '';
        $msg .= !empty($chat_username) ? '<a href="https://t.me/' . $chat_username . '/' . $row['message_id'] . '">' . htmlspecialchars($gym) . '</a>' : $gym;
        $msg .= CR;

        // Raid has not started yet - adjust time left message
        if ($now < $start_time) {
            $msg .= get_raid_times($raids_active[$raid_id], true, true);
        // Raid has started already
        } else {
            // Add time left message.
            $msg .= $pokemon . ' — <b>' . getPublicTranslation('still') . SP . $time_left . 'h</b>' . CR;
        }

        // Count attendances
        $rs_att = my_query(
            "
            SELECT          count(attend_time)          AS count,
                            sum(team = 'mystic')        AS count_mystic,
                            sum(team = 'valor')         AS count_valor,
                            sum(team = 'instinct')      AS count_instinct,
                            sum(team IS NULL)           AS count_no_team,
                            sum(extra_mystic)           AS extra_mystic,
                            sum(extra_valor)            AS extra_valor,
                            sum(extra_instinct)         AS extra_instinct
            FROM            attendance
            LEFT JOIN       users
              ON            attendance.user_id = users.user_id
              WHERE         raid_id = {$raid_id}
                AND         attend_time IS NOT NULL
                AND         raid_done != 1
                AND         cancel != 1
            "
        );

        $att = $rs_att->fetch_assoc();

        // Add to message.
        if ($att['count'] > 0) {
            $msg .= EMOJI_GROUP . '<b> ' . ($att['count'] + $att['extra_mystic'] + $att['extra_valor'] + $att['extra_instinct']) . '</b> — ';
            $msg .= ((($att['count_mystic'] + $att['extra_mystic']) > 0) ? TEAM_B . ($att['count_mystic'] + $att['extra_mystic']) . '  ' : '');
            $msg .= ((($att['count_valor'] + $att['extra_valor']) > 0) ? TEAM_R . ($att['count_valor'] + $att['extra_valor']) . '  ' : '');
            $msg .= ((($att['count_instinct'] + $att['extra_instinct']) > 0) ? TEAM_Y . ($att['count_instinct'] + $att['extra_instinct']) . '  ' : '');
            $msg .= (($att['count_no_team'] > 0) ? TEAM_UNKNOWN . $att['count_no_team'] : '');
            $msg .= CR;
        }

        // Add CR to message now since we don't know if attendances got added or not
        $msg .= CR;

        // Prepare next iteration
        $previous = $current;
        $previous_raid = $row['raid_id'];
    }
}

/**
 * Delete raid.
 * @param $raid_id
 */
function delete_raid($raid_id)
{
    global $db;

    // Delete telegram messages for raid.
    $rs = my_query(
        "
        SELECT    *
            FROM      cleanup
            WHERE     raid_id = '{$raid_id}'
              AND     chat_id <> 0
        "
    );

    // Counter
    $counter = 0;

    // Delete every telegram message
    while ($row = $rs->fetch_assoc()) {
        // Delete telegram message.
        debug_log('Deleting telegram message ' . $row['message_id'] . ' from chat ' . $row['chat_id'] . ' for raid ' . $row['raid_id']);
        delete_message($row['chat_id'], $row['message_id']);
        $counter = $counter + 1;
    }

    // Nothing to delete on telegram.
    if ($counter == 0) {
        debug_log('Raid with ID ' . $raid_id . ' was not found in the cleanup table! Skipping deletion of telegram messages!');
    }

    // Delete raid from cleanup table.
    debug_log('Deleting raid ' . $raid_id . ' from the cleanup table:');
    $rs_cleanup = my_query(
        "
        DELETE FROM   cleanup
        WHERE   raid_id = '{$raid_id}' 
           OR   cleaned = '{$raid_id}'
        "
    );

    // Delete raid from attendance table.
    debug_log('Deleting raid ' . $raid_id . ' from the attendance table:');
    $rs_attendance = my_query(
        "
        DELETE FROM   attendance 
        WHERE  raid_id = '{$raid_id}'
        "
    );

    // Delete raid from raid table.
    debug_log('Deleting raid ' . $raid_id . ' from the raid table:');
    $rs_raid = my_query(
        "
        DELETE FROM   raids 
        WHERE   id = '{$raid_id}'
        "
    );
}


/**
 * Get raid time message.
 * @param $raid
 * @param override_language
 * @param pokemon
 * @return string
 */
function get_raid_times($raid, $override_language = true, $pokemon = false)
{

    // Get translation type
    if($override_language == true) {
        $getTypeTranslation = 'getPublicTranslation';
    } else {
        $getTypeTranslation = 'getTranslation';
    }

    // Init empty message string.
    $msg = '';

    // Now
    $week_now = utcnow('W');
    $year_now = utcnow('Y');

    // Start
    $week_start = utctime($raid['start_time'], 'W');
    $weekday_start = utctime($raid['start_time'], 'N');
    $day_start = utctime($raid['start_time'], 'j');
    $month_start = utctime($raid['start_time'], 'm');
    $year_start = utctime($raid['start_time'], 'Y');

    // Translation for raid day and month
    $raid_day = $getTypeTranslation('weekday_' . $weekday_start);
    $raid_month = $getTypeTranslation('month_' . $month_start);

    // Days until the raid starts
    $dt_now = utcdate('now');
    $dt_raid = utcdate($raid['start_time']);
    $date_now = new DateTime($dt_now, new DateTimeZone('UTC'));
    $date_raid = new DateTime($dt_raid, new DateTimeZone('UTC'));
    $days_to_raid = $date_raid->diff($date_now)->format("%a");

    // Raid times.
    if($pokemon == true) {
        $msg .= get_local_pokemon_name($raid['pokemon'], $override_language) . ':' . SP;
    } else {
        $msg .= $getTypeTranslation('raid') . ':' . SP;
    }
    // Is the raid in the same week?
    if($week_now == $week_start && $date_now == $date_raid) {
        // Output: Raid egg opens up 17:00
        $msg .= '<b>' . dt2time($raid['start_time']);
    } else {
        if($days_to_raid > 6) {
        // Output: Raid egg opens on Friday, 13 April (2018)
            $msg .= '<b>' .  $raid_day . ', ' . $day_start . ' ' . $raid_month . (($year_start > $year_now) ? $year_start : '');
        } else {
            // Output: Raid egg opens on Friday
            $msg .= '<b>' .  $raid_day;
        }
        // Adds 'at 17:00' to the output.
        $msg .= ' ' . $getTypeTranslation('raid_egg_opens_at') . ' ' . dt2time($raid['start_time']);
    }
    // Add endtime
    $msg .= SP . $getTypeTranslation('to') . SP . dt2time($raid['end_time']) . '</b>' . CR;

    return $msg;
}

/**
 * Show raid poll.
 * @param $raid
 * @return string
 */
function show_raid_poll($raid)
{
    // Init empty message string.
    $msg = '';

    // Get raid times.
    $msg .= get_raid_times($raid);

    // Display gym details.
    if ($raid['gym_name'] || $raid['gym_team']) {
        // Add gym name to message.
        if ($raid['gym_name']) {
            $ex_raid_gym_marker = (strtolower(RAID_EX_GYM_MARKER) == 'icon') ? EMOJI_STAR : '<b>' . RAID_EX_GYM_MARKER . '</b>';
            $msg .= getPublicTranslation('gym') . ': ' . ($raid['ex_gym'] ? $ex_raid_gym_marker . SP : '') . '<b>' . $raid['gym_name'] . '</b>';
        }

        // Add team to message.
        if ($raid['gym_team']) {
            $msg .= ' ' . $GLOBALS['teams'][$raid['gym_team']];
        }

        $msg .= CR;
    }

    // Add maps link to message.
    if (!empty($raid['address'])) {
        $msg .= '<a href="https://maps.google.com/?daddr=' . $raid['lat'] . ',' . $raid['lon'] . '">' . $raid['address'] . '</a>' . CR;
    } else {
        // Get the address.
        $addr = get_address($raid['lat'], $raid['lon']);
        $address = format_address($addr);
		
        //Only store address if not empty
        if(!empty($address)) {
            my_query(
	            "
	            UPDATE    gyms
	            SET     address = '{$address}'
	            WHERE   id = {$raid['gym_id']}
	            "
            );    
            //Use new address
	    $msg .= '<a href="https://maps.google.com/?daddr=' . $raid['lat'] . ',' . $raid['lon'] . '">' . $address . '</a>' . CR;
        } else {
            //If no address is found show maps link
            $msg .= '<a href="http://maps.google.com/maps?q=' . $raid['lat'] . ',' . $raid['lon'] . '">http://maps.google.com/maps?q=' . $raid['lat'] . ',' . $raid['lon'] . '</a>' . CR;
        }	
    }

    // Display raid boss name.
    $msg .= getPublicTranslation('raid_boss') . ': <b>' . get_local_pokemon_name($raid['pokemon'], true) . '</b>';

    // Display raid boss weather.
    $pokemon_weather = get_pokemon_weather($raid['pokemon']);
    $msg .= ($pokemon_weather != 0) ? (' ' . get_weather_icons($pokemon_weather)) : '';
    $msg .= CR;

    // Display raid boss CP values.
    $pokemon_cp = get_formatted_pokemon_cp($raid['pokemon'], true);
    $msg .= (!empty($pokemon_cp)) ? ($pokemon_cp . CR) : ''; 

    // Get current time and time left.
    $time_now = utcnow();
    $time_left = $raid['t_left'];

    // Raid has started?
    if ($time_now > $raid['start_time']) {

        // Add raid is done message.
        if ($time_now > $raid['end_time']) {
        //if ($time_left < 0) {
            $msg .= '<b>' . getPublicTranslation('raid_done') . '</b>' . CR;

        // Add time left message.
        } else {
            $msg .= getPublicTranslation('raid') . ' — <b>' . getPublicTranslation('still') . ' ' . $time_left . 'h</b>' . CR;
        }
    }

    // Gym note?
    if(!empty($raid['gym_note'])) {
        $msg .= EMOJI_INFO . SP . $raid['gym_note'] . CR;
    }

    // Add Ex-Raid Message if Pokemon is in Ex-Raid-List.
    $raid_level = get_raid_level($raid['pokemon']);
    if($raid_level == 'X') {
        $msg .= CR . EMOJI_WARN . ' <b>' . getPublicTranslation('exraid_pass') . '</b> ' . EMOJI_WARN . CR;
    }

    // Get counts and sums for the raid
    // 1 - Grouped by attend_time
    $rs_cnt = my_query(
        "
        SELECT DISTINCT DATE_FORMAT(attend_time, '%Y%m%d%H%i%s') AS ts_att, 
                        count(attend_time)          AS count,
                        sum(team = 'mystic')        AS count_mystic,
                        sum(team = 'valor')         AS count_valor,
                        sum(team = 'instinct')      AS count_instinct,
                        sum(team IS NULL)           AS count_no_team,
                        sum(extra_mystic)           AS extra_mystic,
                        sum(extra_valor)            AS extra_valor,
                        sum(extra_instinct)         AS extra_instinct,
                        sum(IF(late = '1', (late = '1') + extra_mystic + extra_valor + extra_instinct, 0)) AS count_late,
                        sum(pokemon = '0')                   AS count_any_pokemon,
                        sum(pokemon = '{$raid['pokemon']}')  AS count_raid_pokemon,
                        attend_time
        FROM            attendance
        LEFT JOIN       users
          ON            attendance.user_id = users.user_id
          WHERE         raid_id = {$raid['id']}
            AND         attend_time IS NOT NULL
            AND         raid_done != 1
            AND         cancel != 1
          GROUP BY      attend_time
          ORDER BY      attend_time, pokemon
        "
    );

    // Init empty count array and count sum.
    $cnt = [];
    $cnt_all = 0;
    $cnt_latewait = 0;

    while ($cnt_row = $rs_cnt->fetch_assoc()) {
        $cnt[$cnt_row['ts_att']] = $cnt_row;
        $cnt_all = $cnt_all + $cnt_row['count'];
        $cnt_latewait = $cnt_latewait + $cnt_row['count_late'];
    }

    // Write to log.
    debug_log($cnt);

    // Add no attendance found message.
    if ($cnt_all > 0) {
        // Get counts and sums for the raid
        // 2 - Grouped by attend_time and pokemon
        $rs_cnt_pokemon = my_query(
            "
            SELECT DISTINCT DATE_FORMAT(attend_time, '%Y%m%d%H%i%s') AS ts_att, 
                            count(attend_time)          AS count,
                            sum(team = 'mystic')        AS count_mystic,
                            sum(team = 'valor')         AS count_valor,
                            sum(team = 'instinct')      AS count_instinct,
                            sum(team IS NULL)           AS count_no_team,
                            sum(extra_mystic)           AS extra_mystic,
                            sum(extra_valor)            AS extra_valor,
                            sum(extra_instinct)         AS extra_instinct,
                            sum(IF(late = '1', (late = '1') + extra_mystic + extra_valor + extra_instinct, 0)) AS count_late,
                            sum(pokemon = '0')                   AS count_any_pokemon,
                            sum(pokemon = '{$raid['pokemon']}')  AS count_raid_pokemon,
                            attend_time,
                            pokemon
            FROM            attendance
            LEFT JOIN       users
              ON            attendance.user_id = users.user_id
              WHERE         raid_id = {$raid['id']}
                AND         attend_time IS NOT NULL
                AND         raid_done != 1
                AND         cancel != 1
              GROUP BY      attend_time, pokemon
              ORDER BY      attend_time, pokemon
            "
        );

        // Init empty count array and count sum.
        $cnt_pokemon = [];

        while ($cnt_rowpoke = $rs_cnt_pokemon->fetch_assoc()) {
            $cnt_pokemon[$cnt_rowpoke['ts_att'] . '_' . $cnt_rowpoke['pokemon']] = $cnt_rowpoke;
        }

        // Write to log.
        debug_log($cnt_pokemon);

        // Get attendance for this raid.
        $rs_att = my_query(
            "
            SELECT      attendance.*,
                        users.name,
                        users.level,
                        users.team,
                        DATE_FORMAT(attend_time, '%Y%m%d%H%i%s') AS ts_att
            FROM        attendance
            LEFT JOIN   users
            ON          attendance.user_id = users.user_id
              WHERE     raid_id = {$raid['id']}
                AND     raid_done != 1
                AND     cancel != 1
              ORDER BY  attend_time,
                        pokemon,
                        users.team,
                        arrived,
                        users.level desc
            "
        );

        // Init previous attend time and pokemon
        $previous_att_time = 'FIRST_RUN';
        $previous_pokemon = 'FIRST_RUN';

        // For each attendance.
        while ($row = $rs_att->fetch_assoc()) {
            // Set current attend time and pokemon
            $current_att_time = $row['ts_att'];
            $dt_att_time = dt2time($row['attend_time']);
            $current_pokemon = $row['pokemon'];

            // Add hint for late attendances.
            if(RAID_LATE_MSG && $previous_att_time == 'FIRST_RUN' && $cnt_latewait > 0) {
                $late_wait_msg = str_replace('RAID_LATE_TIME', RAID_LATE_TIME, getPublicTranslation('late_participants_wait'));
                $msg .= CR . EMOJI_LATE . '<i>' . getPublicTranslation('late_participants') . ' ' . $late_wait_msg . '</i>' . CR;
            }

            // Add section/header for time
            if($previous_att_time != $current_att_time) {
                // Add to message.
                $count_att_time_extrapeople = $cnt[$current_att_time]['extra_mystic'] + $cnt[$current_att_time]['extra_valor'] + $cnt[$current_att_time]['extra_instinct'];
                $msg .= CR . '<b>' . (($current_att_time == 0) ? (getPublicTranslation('anytime')) : ($dt_att_time)) . '</b>' . ' [' . ($cnt[$current_att_time]['count'] + $count_att_time_extrapeople) . ']';

                // Add attendance counts by team.
                if ($cnt[$current_att_time]['count'] > 0) {
                    // Attendance counts by team.
                    $count_mystic = $cnt[$current_att_time]['count_mystic'] + $cnt[$current_att_time]['extra_mystic'];
                    $count_valor = $cnt[$current_att_time]['count_valor'] + $cnt[$current_att_time]['extra_valor'];
                    $count_instinct = $cnt[$current_att_time]['count_instinct'] + $cnt[$current_att_time]['extra_instinct'];
                    $count_late = $cnt[$current_att_time]['count_late'];

                    // Add to message.
                    $msg .= ' — ';
                    $msg .= (($count_mystic > 0) ? TEAM_B . $count_mystic . '  ' : '');
                    $msg .= (($count_valor > 0) ? TEAM_R . $count_valor . '  ' : '');
                    $msg .= (($count_instinct > 0) ? TEAM_Y . $count_instinct . '  ' : '');
                    $msg .= (($cnt[$current_att_time]['count_no_team'] > 0) ? TEAM_UNKNOWN . $cnt[$current_att_time]['count_no_team'] . '  ' : '');
                    $msg .= (($count_late > 0) ? EMOJI_LATE . $count_late . '  ' : '');
                }
                $msg .= CR;
            }

            // Add section/header for pokemon
            if($previous_pokemon != $current_pokemon || $previous_att_time != $current_att_time) {
                // Get counts for pokemons
                $count_all = $cnt[$current_att_time]['count'];
                $count_any_pokemon = $cnt[$current_att_time]['count_any_pokemon'];
                $count_raid_pokemon = $cnt[$current_att_time]['count_raid_pokemon'];

                // Show attendances when multiple pokemon are selected, unless all attending users voted for the raid boss + any pokemon
                if($count_all != ($count_any_pokemon + $count_raid_pokemon)) {
                    // Add pokemon name.
                    $msg .= ($current_pokemon == 0) ? ('<b>' . getPublicTranslation('any_pokemon') . '</b>') : ('<b>' . get_local_pokemon_name($current_pokemon, true) . '</b>');

                    // Attendance counts by team.
                    $current_att_time_poke = $cnt_pokemon[$current_att_time . '_' . $current_pokemon];
                    $count_att_time_poke_extrapeople = $current_att_time_poke['extra_mystic'] + $current_att_time_poke['extra_valor'] + $current_att_time_poke['extra_instinct'];
                    $poke_count_mystic = $current_att_time_poke['count_mystic'] + $current_att_time_poke['extra_mystic'];
                    $poke_count_valor = $current_att_time_poke['count_valor'] + $current_att_time_poke['extra_valor'];
                    $poke_count_instinct = $current_att_time_poke['count_instinct'] + $current_att_time_poke['extra_instinct'];
                    $poke_count_late = $current_att_time_poke['count_late'];

                    // Add to message.
                    $msg .= ' [' . ($current_att_time_poke['count'] + $count_att_time_poke_extrapeople) . '] — ';
                    $msg .= (($poke_count_mystic > 0) ? TEAM_B . $poke_count_mystic . '  ' : '');
                    $msg .= (($poke_count_valor > 0) ? TEAM_R . $poke_count_valor . '  ' : '');
                    $msg .= (($poke_count_instinct > 0) ? TEAM_Y . $poke_count_instinct . '  ' : '');
                    $msg .= (($current_att_time_poke['count_no_team'] > 0) ? TEAM_UNKNOWN . ($current_att_time_poke['count_no_team']) : '');
                    $msg .= (($poke_count_late > 0) ? EMOJI_LATE . $poke_count_late . '  ' : '');
                    $msg .= CR;
                }
            }

            // Add users: ARRIVED --- TEAM -- LEVEL -- NAME -- INVITE -- EXTRAPEOPLE
            $msg .= ($row['arrived']) ? (EMOJI_HERE . ' ') : (($row['late']) ? (EMOJI_LATE . ' ') : '└ ');
            $msg .= ($row['team'] === NULL) ? ($GLOBALS['teams']['unknown'] . ' ') : ($GLOBALS['teams'][$row['team']] . ' ');
            $msg .= ($row['level'] == 0) ? ('<b>00</b> ') : (($row['level'] < 10) ? ('<b>0' . $row['level'] . '</b> ') : ('<b>' . $row['level'] . '</b> '));
            $msg .= '<a href="tg://user?id=' . $row['user_id'] . '">' . htmlspecialchars($row['name']) . '</a> ';
            $msg .= ($raid_level == 'X' && $row['invite']) ? (EMOJI_INVITE . ' ') : '';
            $msg .= ($row['extra_mystic']) ? ('+' . $row['extra_mystic'] . TEAM_B . ' ') : '';
            $msg .= ($row['extra_valor']) ? ('+' . $row['extra_valor'] . TEAM_R . ' ') : '';
            $msg .= ($row['extra_instinct']) ? ('+' . $row['extra_instinct'] . TEAM_Y . ' ') : '';
            $msg .= CR;

            // Prepare next result
            $previous_att_time = $current_att_time;
            $previous_pokemon = $current_pokemon; 
        }
    }

    // Get sums canceled/done for the raid
    $rs_cnt_cancel_done = my_query(
        "
        SELECT DISTINCT sum(raid_done = '1')   AS count_done,
                        sum(cancel = '1')      AS count_cancel,
                        sum(extra_mystic)           AS extra_mystic,
                        sum(extra_valor)            AS extra_valor,
                        sum(extra_instinct)         AS extra_instinct
        FROM            attendance
          WHERE         raid_id = {$raid['id']}
            AND         (raid_done = 1
                        OR cancel = 1)
          GROUP BY      raid_done
          ORDER BY      raid_done
        "
    );

    // Init empty count array and count sum.
    $cnt_cancel_done = [];

    while ($cnt_row_cancel_done = $rs_cnt_cancel_done->fetch_assoc()) {
        // Cancel count
        if($cnt_row_cancel_done['count_cancel'] > 0) {
            $cnt_cancel_done['count_cancel'] = $cnt_row_cancel_done['count_cancel'] + $cnt_row_cancel_done['extra_mystic'] + $cnt_row_cancel_done['extra_valor'] + $cnt_row_cancel_done['extra_instinct'];
        }

        // Done count
        if($cnt_row_cancel_done['count_done'] > 0) {
            $cnt_cancel_done['count_done'] = $cnt_row_cancel_done['count_done'] + $cnt_row_cancel_done['extra_mystic'] + $cnt_row_cancel_done['extra_valor'] + $cnt_row_cancel_done['extra_instinct'];
        }
    }
    
    // Set canceled count to avoid undefined index notices.
    if(!isset($cnt_cancel_done['count_cancel'])) {
        $cnt_cancel_done['count_cancel'] = 0;
    }

    // Set done count to avoid undefined index notices.
    if(!isset($cnt_cancel_done['count_done'])) {
        $cnt_cancel_done['count_done'] = 0;
    }

    // Write to log.
    debug_log($cnt_cancel_done);

    // Canceled or done?
    if((isset($cnt_cancel_done['count_cancel']) && $cnt_cancel_done['count_cancel'] > 0) || (isset($cnt_cancel_done['count_done']) && $cnt_cancel_done['count_done'] > 0)) {
        // Get done and canceled attendances

        $rs_att = my_query(
            "
            SELECT      attendance.*,
                        users.name,
                        users.level,
                        users.team,
                        DATE_FORMAT(attend_time, '%Y%m%d%H%i%s') AS ts_att
            FROM        attendance
            LEFT JOIN   users
            ON          attendance.user_id = users.user_id
              WHERE     raid_id = {$raid['id']}
                AND     (raid_done = 1
                        OR cancel = 1)
              ORDER BY  raid_done,
                        attend_time
            "
        );

        // Init cancel_done value.
        $cancel_done = 'CANCEL';

        // For each canceled / done.
        while ($row = $rs_att->fetch_assoc()) {
            // Attend time.
            $dt_att_time = dt2time($row['attend_time']);

            // Add section/header for canceled
            if($row['cancel'] == 1 && $cancel_done == 'CANCEL') {
                $msg .= CR . TEAM_CANCEL . ' <b>' . getPublicTranslation('cancel') . ': </b>' . '[' . $cnt_cancel_done['count_cancel'] . ']' . CR;
                $cancel_done = 'DONE';
            }

            // Add section/header for canceled
            if($row['raid_done'] == 1 && $cancel_done == 'CANCEL' || $row['raid_done'] == 1 && $cancel_done == 'DONE') {
                $msg .= CR . TEAM_DONE . ' <b>' . getPublicTranslation('finished') . ': </b>' . '[' . $cnt_cancel_done['count_done'] . ']' . CR;
                $cancel_done = 'END';
            }

            // Add users: TEAM -- LEVEL -- NAME -- CANCELED/DONE -- EXTRAPEOPLE
            $msg .= ($row['team'] === NULL) ? ('└ ' . $GLOBALS['teams']['unknown'] . ' ') : ('└ ' . $GLOBALS['teams'][$row['team']] . ' ');
            $msg .= ($row['level'] == 0) ? ('<b>00</b> ') : (($row['level'] < 10) ? ('<b>0' . $row['level'] . '</b> ') : ('<b>' . $row['level'] . '</b> '));
            $msg .= '<a href="tg://user?id=' . $row['user_id'] . '">' . htmlspecialchars($row['name']) . '</a> ';
            $msg .= ($raid_level == 'X' && $row['invite']) ? (EMOJI_INVITE . ' ') : '';
            $msg .= ($row['cancel'] == 1) ? ('[' . (($row['ts_att'] == 0) ? (getPublicTranslation('anytime')) : ($dt_att_time)) . '] ') : '';
            $msg .= ($row['raid_done'] == 1) ? ('[' . (($row['ts_att'] == 0) ? (getPublicTranslation('anytime')) : ($dt_att_time)) . '] ') : '';
            $msg .= ($row['extra_mystic']) ? ('+' . $row['extra_mystic'] . TEAM_B . ' ') : '';
            $msg .= ($row['extra_valor']) ? ('+' . $row['extra_valor'] . TEAM_R . ' ') : '';
            $msg .= ($row['extra_instinct']) ? ('+' . $row['extra_instinct'] . TEAM_Y . ' ') : '';
            $msg .= CR;
        }
    } 

    // Add no attendance found message.
    if ($cnt_all + $cnt_cancel_done['count_cancel'] + $cnt_cancel_done['count_done'] == 0) {
        $msg .= CR . getPublicTranslation('no_participants_yet') . CR;
    }

    //Add custom message from the config.
    if (defined('MAP_URL') && !empty(MAP_URL)) {
        $msg .= CR . MAP_URL ;
    }	
	
    // Display creator.
    $msg .= ($raid['user_id'] && $raid['name']) ? (CR . getPublicTranslation('created_by') . ': <a href="tg://user?id=' . $raid['user_id'] . '">' . htmlspecialchars($raid['name']) . '</a>') : '';

    // Add update time and raid id to message.
    $msg .= CR . '<i>' . getPublicTranslation('updated') . ': ' . dt2time('now', 'H:i:s') . '</i>';
    $msg .= '  ' . substr(strtoupper(BOT_ID), 0, 1) . '-ID = ' . $raid['id']; // DO NOT REMOVE! --> NEEDED FOR CLEANUP PREPARATION!

    // Return the message.
    return $msg;
}

/**
 * Show small raid poll.
 * @param $raid
 * @param $override_language
 * @return string
 */
function show_raid_poll_small($raid, $override_language = false)
{
    // Build message string.
    $msg = '';

    // Gym Name
    if(!empty($raid['gym_name'])) {
        $msg .= '<b>' . $raid['gym_name'] . '</b>' . CR;
    }

    // Address found.
    if (!empty($raid['address'])) {
        $msg .= '<i>' . $raid['address'] . '</i>' . CR2;
    }

    // Pokemon
    if(!empty($raid['pokemon'])) {
        $msg .= '<b>' . get_local_pokemon_name($raid['pokemon']) . '</b> ' . CR;
    }
    // Start time and end time
    if(!empty($raid['start_time']) && !empty($raid['end_time'])) {
        // Get raid times message.
        $msg .= get_raid_times($raid, $override_language);
    }

    // Count attendances
    $rs = my_query(
        "
        SELECT          count(attend_time)          AS count,
                        sum(team = 'mystic')        AS count_mystic,
                        sum(team = 'valor')         AS count_valor,
                        sum(team = 'instinct')      AS count_instinct,
                        sum(team IS NULL)           AS count_no_team,
                        sum(extra_mystic)           AS extra_mystic,
                        sum(extra_valor)            AS extra_valor,
                        sum(extra_instinct)         AS extra_instinct
        FROM            attendance
        LEFT JOIN       users
          ON            attendance.user_id = users.user_id
          WHERE         raid_id = {$raid['id']}
            AND         attend_time IS NOT NULL
            AND         raid_done != 1
            AND         cancel != 1
        "
    );

    $row = $rs->fetch_assoc();

    // Add to message.
    if ($row['count'] > 0) {
        // Count by team.
        $count_mystic = $row['count_mystic'] + $row['extra_mystic'];
        $count_valor = $row['count_valor'] + $row['extra_valor'];
        $count_instinct = $row['count_instinct'] + $row['extra_instinct'];

        // Add to message.
        $msg .= EMOJI_GROUP . '<b> ' . ($row['count'] + $row['extra_mystic'] + $row['extra_valor'] + $row['extra_instinct']) . '</b> — ';
        $msg .= (($count_mystic > 0) ? TEAM_B . $count_mystic . '  ' : '');
        $msg .= (($count_valor > 0) ? TEAM_R . $count_valor . '  ' : '');
        $msg .= (($count_instinct > 0) ? TEAM_Y . $count_instinct . '  ' : '');
        $msg .= (($row['count_no_team'] > 0) ? TEAM_UNKNOWN . $row['count_no_team'] : '');
        $msg .= CR;
    } else {
        $msg .= getTranslation('no_participants') . CR;
    }

    return $msg;
}

/**
 * Raid list.
 * @param $update
 */
function raid_list($update)
{
    // Init empty rows array and query type.
    $rows = [];

    // Init raid id.
    $iqq = 0;
   
    // Botname:raid_id received? 
    if (substr_count($update['inline_query']['query'], ':') == 1) {
        // Botname: received, is there a raid_id after : or not?
        if(strlen(explode(':', $update['inline_query']['query'])[1]) != 0) {
            // Raid ID.
            $iqq = intval(explode(':', $update['inline_query']['query'])[1]);
        }
    }

    // Inline list polls.
    if ($iqq != 0) {

        // Raid by ID.
        $request = my_query(
            "
            SELECT              raids.*,
                                raids.id AS iqq_raid_id,
                                gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym, gyms.gym_note,
                                TIME_FORMAT(TIMEDIFF(end_time, UTC_TIMESTAMP()) + INTERVAL 1 MINUTE, '%k:%i') AS t_left,
                                users.name
		    FROM        raids
                    LEFT JOIN   gyms
                    ON          raids.gym_id = gyms.id
                    LEFT JOIN   users
                    ON          raids.user_id = users.user_id
		      WHERE     raids.id = {$iqq}
                      AND       end_time>UTC_TIMESTAMP()
            "
        );

        while ($answer = $request->fetch_assoc()) {
            $rows[] = $answer;
        }

    } else {
        // Get raid data by user.
        $request = my_query(
            "
            SELECT              raids.*,
                                raids.id AS iqq_raid_id,
                                gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym, gyms.gym_note,
                                TIME_FORMAT(TIMEDIFF(end_time, UTC_TIMESTAMP()) + INTERVAL 1 MINUTE, '%k:%i') AS t_left,
                                users.name
		    FROM        raids
                    LEFT JOIN   gyms
                    ON          raids.gym_id = gyms.id
                    LEFT JOIN   users
                    ON          raids.user_id = users.user_id
		      WHERE     raids.user_id = {$update['inline_query']['from']['id']}
                      AND       end_time>UTC_TIMESTAMP()
		      ORDER BY  iqq_raid_id DESC LIMIT 2
            "
        );

        while ($answer_raids = $request->fetch_assoc()) {
            $rows[] = $answer_raids;
        }

    }

    // Init array.
    $contents = array();

    // For each rows.
    foreach ($rows as $key => $row) {
            // Get raid poll.
            $contents[$key]['text'] = show_raid_poll($row);

            // Set the title.
            $contents[$key]['title'] = get_local_pokemon_name($row['pokemon'], true) . ' ' . getPublicTranslation('from') . ' ' . dt2time($row['start_time'])  . ' ' . getPublicTranslation('to') . ' ' . dt2time($row['end_time']);

            // Get inline keyboard.
            $contents[$key]['keyboard'] = keys_vote($row);

            // Set the description.
            $contents[$key]['desc'] = strval($row['gym_name']);
    }

    debug_log($contents);
    answerInlineQuery($update['inline_query']['id'], $contents);
}

/**
 * Process response from telegram api.
 * @param $json
 * @param $json_response
 * @return mixed
 */
function curl_json_response($json_response, $json)
{
    // Write to log.
    debug_log($json_response, '<-');

    // Decode json response.
    $response = json_decode($json_response, true);

    // Validate response.
    if ($response['ok'] != true || isset($response['update_id'])) {
        // Write error to log.
        debug_log('ERROR: ' . $json . "\n\n" . $json_response . "\n\n");
    } else {
	// Result seems ok, get message_id and chat_id if supergroup or channel message
	if (isset($response['result']['chat']['type']) && ($response['result']['chat']['type'] == "channel" || $response['result']['chat']['type'] == "supergroup")) {
            // Init cleanup_id
            $cleanup_id = 0;

	    // Set chat and message_id
            $chat_id = $response['result']['chat']['id'];
            $message_id = $response['result']['message_id'];

            // Get raid id from $json
            $json_message = json_decode($json, true);

            // Write to log that message was shared with channel or supergroup
            debug_log('Message was shared with ' . $response['result']['chat']['type'] . ' ' . $response['result']['chat']['title']);
            debug_log('Checking input for cleanup info now...');

	    // Check if callback_data is present to get the raid_id and reply_to_message_id is set to filter only raid messages
            if (!empty($json_message['reply_markup']['inline_keyboard']['0']['0']['callback_data']) && !empty($json_message['reply_to_message_id'])) {
                $split_callback_data = explode(':', $json_message['reply_markup']['inline_keyboard']['0']['0']['callback_data']);
	        // Get raid_id, but check for BRIDGE_MODE first
	        if(defined('BRIDGE_MODE') && BRIDGE_MODE == true) {
		    $cleanup_id = $split_callback_data[1];
		    } else {
		    $cleanup_id = $split_callback_data[0];
	        }

            // Check if it's a venue and get raid id
            } else if (!empty($response['result']['venue']['address'])) {
                // Get raid_id from address.
                $cleanup_id = substr(strrchr($response['result']['venue']['address'], substr(strtoupper(BOT_ID), 0, 1) . '-ID = '), 7);

            // Check if it's a text and get raid id
            } else if (!empty($response['result']['text'])) {
                $cleanup_id = substr(strrchr($response['result']['text'], substr(strtoupper(BOT_ID), 0, 1) . '-ID = '), 7);
            }

            // Trigger Cleanup when raid_id was found
            if($cleanup_id != 0) {
                debug_log('Found ID for cleanup preparation from callback_data or venue!');
                debug_log('Cleanup ID: ' . $cleanup_id);
                debug_log('Chat_ID: ' . $chat_id);
                debug_log('Message_ID: ' . $message_id);

	        // Trigger cleanup preparation process when necessary id's are not empty and numeric
	        if (!empty($chat_id) && !empty($message_id) && !empty($cleanup_id)) {
		    debug_log('Calling cleanup preparation now!');
		    insert_cleanup($chat_id, $message_id, $cleanup_id);
	        } else {
		    debug_log('Missing input! Cannot call cleanup preparation!');
		}
            } else {
                debug_log('No cleanup info found! Skipping cleanup preparation!');
            }

            // Check if text starts with getTranslation('raid_overview_for_chat') and inline keyboard is empty
            $translation = defined('LANGUAGE_PUBLIC') ? getPublicTranslation('raid_overview_for_chat') : '';
            $translation_length = strlen($translation);
            $text = !empty($response['result']['text']) ? substr($response['result']['text'], 0, $translation_length) : '';
            // Add overview message details to database.
            if (!empty($text) && !empty($translation) && $text === $translation && empty($json_message['reply_markup']['inline_keyboard'])) {
                debug_log('Detected overview message!');
                debug_log('Text: ' . $text);
                debug_log('Translation: ' . $translation);
                debug_log('Chat_ID: ' . $chat_id);
                debug_log('Message_ID: ' . $message_id);

                // Write raid overview data to database
                debug_log('Adding overview info to database now!');
                insert_overview($chat_id, $message_id);
            }
	}
    }

    // Return response.
    return $response;
}

