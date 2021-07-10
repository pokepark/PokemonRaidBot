<?php
// Write to log.
debug_log('GETCONFIG()');

// For debug.
// debug_log($update);
// debug_log($data);

// Check access.
bot_access_check($update, 'config-get');

// Get all allowed configs.
$allowed = explode(',', $config->ALLOWED_TELEGRAM_CONFIG);
$msg = '<b>' . getTranslation('config') . ':</b>' . CR . CR;

// Get config restrictions for boolean input
$allowed_bool = explode(',', $config->ALLOW_ONLY_TRUE_FALSE);

// Get config restrictions for numeric input
$allowed_numbers = explode(',', $config->ALLOW_ONLY_NUMBERS);

// Get config.
$cfile = CONFIG_PATH . '/config.json';
if(is_file($cfile)) {
    $str = file_get_contents($cfile);
    $json = json_decode($str, true);
}

// Get config aliases.
$afile = CONFIG_PATH . '/alias.json';
if(is_file($afile)) {
    $astr = file_get_contents($afile);
    $ajson = json_decode($astr, true);
}

// Write to log.
debug_log('User requested the allowed telegram configs');
debug_log('Allowed telegram configs: ' . $config->ALLOWED_TELEGRAM_CONFIG);
debug_log('Allow only boolean input: ' . $config->ALLOW_ONLY_TRUE_FALSE);
debug_log('Allow only numeric input: ' . $config->ALLOW_ONLY_NUMBERS);

// Any configs allowed?
if(!empty($config->ALLOWED_TELEGRAM_CONFIG)) {
	foreach($json as $cfg_name => $cfg_value) {
        // Only allowed configs
        if(in_array($cfg_name, $allowed)) {
            // Is alias set?
            $alias = '';
            if(isset($ajson[$cfg_name])){
                $alias = $ajson[$cfg_name];
            }
            // Config name / Alias + value
	        $msg .= (empty($alias) ? $cfg_name : $alias) . SP . (empty($cfg_value) ? '<i>' . getTranslation('no_value') . '</i>' : $cfg_value);

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

sendMessage($update['message']['chat']['id'], $msg);

?>
