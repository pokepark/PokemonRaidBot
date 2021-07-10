<?php
// Write to log.
debug_log('SETCONFIG()');

// For debug.
// debug_log($update);
// debug_log($data);

// Check access.
bot_access_check($update, 'config-set');

// Get config name and value.
$input = trim(substr($update['message']['text'], 4));

// Get delimiter count.
$count = substr_count($input, " ");

// Get allowed telegram configs.
$allowed = explode(',', $config->ALLOWED_TELEGRAM_CONFIG);

// Get config restrictions for boolean input
$allowed_bool = explode(',', $config->ALLOW_ONLY_TRUE_FALSE);

// Get config restrictions for numeric input
$allowed_numbers = explode(',', $config->ALLOW_ONLY_NUMBERS);

// Write to log.
debug_log('User submitted a telegram config change');
debug_log('Allowed telegram configs: ' . $config->ALLOWED_TELEGRAM_CONFIG);
debug_log('Allow only boolean input: ' . $config->ALLOW_ONLY_TRUE_FALSE);
debug_log('Allow only numeric input: ' . $config->ALLOW_ONLY_NUMBERS);

// 0 means we reset config option value to ""
if($count == 0) {
    // Upper input.
    $config_name = strtoupper($input);
    //$config_value = '"" (' . getTranslation('no_value') . ' / ' . getTranslation('resetted') . ')';
    $config_value = "";
    debug_log('Reset for the config value ' . $config_name . ' was requested by the user');

// 1 means we set the config option to the given value
} else if($count >= 1) {
    // Config name and value.
    $cfg_name_value = explode(' ', $input, 2);
    $config_name = strtoupper($cfg_name_value[0]);
    $config_value = $cfg_name_value[1];
    debug_log('Change for the config option ' . $config_name . ' was requested by the user');

// Set config_name to avoid undefined variable for if clause below.
} else {
    $config_name = 'not_supported';
}

// Config
$cfile = CONFIG_PATH . '/config.json';
if(is_file($cfile)) {
    $str = file_get_contents($cfile);
    $json = json_decode($str, true);
}

// Real config name or alias?
$alias = '';
$afile = CONFIG_PATH . '/alias.json';
if(is_file($afile)) {
    debug_log('Checking alias for config option ' . $config_name);
    $astr = file_get_contents($afile);
    // We compare always uppercase, so change str to upper
    $astr = strtoupper($astr);
    $ajson = json_decode($astr, true);
    $alias = array_search($config_name, $ajson);
    // Check for alias
    if ($alias !== false) {
        debug_log('Config option ' . $config_name . ' is an alias for ' . $alias);
        $help = $config_name;
        $config_name = strtoupper($alias);
        $alias = $help;
    } else {
        debug_log('No alias found. Seems ' . $config_name . ' is the config option name');
    }
}

// Assume restrictions.
$restrict = 'yes';

// Init additional error info.
$msg_error_info = '';

// Make sure it's allowed to update the value via telegram.
if(in_array($config_name, $allowed)) {
    // Only bool?
    if(in_array($config_name, $bool_only)) {
        if(strcasecmp($config_value, true) == 0 || strcasecmp($config_value, false) == 0) {
            $config_value = strtolower($config_value);
            $restrict = 'no';
        } else if($config_value == 0 || $config_value == 1) {
            $restrict = 'no';
        } else {
            debug_log('Boolean value expected. Got this value: ' . $config_value);
            $msg_error_info .= getTranslation('help_bool_expected');
        }
    

    // Only numbers?
    } else if(in_array($config_name, $numbers_only)) {
        if(is_numeric($config_value)) {
            $restrict = 'no';
        } else {
            debug_log('Number expected. Got this value: ' . $config_value);
            $msg_error_info .= getTranslation('help_number_expected');
        }

    // No restriction on input type.
    } else {
        $restrict = 'no';
    }
}

// Update config.
if(in_array($config_name, $allowed) && $restrict == 'no') {
    // Prepare data, replace " with '
    $config_value = str_replace('"', "'", $config_value);
    $old_value = $json[$config_name];
    $json[$config_name] = $config_value;
    $json = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
    debug_log($config_name, 'CONFIG NAME:');
    debug_log($config_value, 'CONFIG VALUE:');

    // Write to file.
    if(!(is_array($json) && is_string(json_decode($json, true)) && (json_last_error() === JSON_ERROR_NONE))) {
        file_put_contents(CONFIG_PATH . '/config.json', $json);
        $msg = getTranslation('config_updated') . ':' . CR . CR;
        $msg .= '<b>' . (empty($alias) ? $config_name : $alias) . '</b>' . CR;
        $msg .= getTranslation('old_value') . SP . $old_value . CR;
        $msg .= getTranslation('new_value') . SP . $config_value . CR;
        debug_log('Changed the config value for ' . $config_name . ' from ' . $old_value . ' to ' . $config_value);
    } else {
        $msg_error_info = getTranslation('internal_error');
        $msg = '<b>' . getTranslation('invalid_input') . '</b>' . (!empty($msg_error_info) ? (CR . $msg_error_info) : '') . CR . CR;
    }

// Tell user how to set config and what is allowed to be set by config.
} else {
    $msg = '<b>' . getTranslation('invalid_input') . '</b>' . (!empty($msg_error_info) ? (CR . $msg_error_info) : '') . CR . CR;
    $msg .= '<b>' . getTranslation('config') . ':</b>' . CR;
    // Any configs allowed?
    if(!empty($config->ALLOWED_TELEGRAM_CONFIG)) {
        $msg .= '<code>/setconfig' . SP . getTranslation('option_value') . '</code>' . CR;
        foreach($json as $cfg_name => $cfg_value) {
            // Only allowed configs
            if(in_array($cfg_name, $allowed)) {
                // Is alias set?
                $alias = '';
                if(isset($ajson[$cfg_name])){
                    $alias = $ajson[$cfg_name];
                }
                // Config name / Alias + value
                $msg .= '<code>/set</code>' . SP . (empty($alias) ? $cfg_name : $alias) . SP . (empty($cfg_value) ? '<i>' . getTranslation('no_value') . '</i>' : $cfg_value);

                // Only bool?
                if(in_array($cfg_name, $allowed_bool)) {
                    $msg .= SP . '<i>(' . getTranslation('help_only_bool') . ')</i>' . CR;

                // Only numbers?
                } else if(in_array($cfg_name, $allowed_numbers)) {
                    $msg .= SP . '<i>(' . getTranslation('help_only_numbers') . ')</i>' . CR;

                // Any type
                } else {
                    $msg .= CR;
                }
            }
        }
    } else {
        $msg .= getTranslation('not_supported');
    }
    debug_log('Unsupported request for a telegram config change: ' . $input);
}

// Send message.
sendMessage($update['message']['chat']['id'], $msg);

?>
