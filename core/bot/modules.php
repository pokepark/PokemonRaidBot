<?php
// Init empty data array.
$data = [];

// Callback data found.
if ($update['callback_query']['data']) {
    // Bridge mode?
    if($config->BRIDGE_MODE) {
        // Split bot folder name away from actual data.
        $botnameData = explode(':', $update['callback_query']['data'], 2);
        $botname = $botnameData[0];
        $thedata = $botnameData[1];
        // Write to log
        debug_log('Bot Name: ' . $botname);
        debug_log('The Data: ' . $thedata);
    } else {
        // Data is just the data.
        $thedata = $update['callback_query']['data'];
    }
    // Split callback data and assign to data array.
    $splitData = explode(':', $thedata);
    $data['id']     = $splitData[0];
    $data['action'] = $splitData[1];
    $data['arg']    = $splitData[2];
}

// Write data to log.
debug_log($data, '* DATA= ');

// Set module path by sent action name.
$module = ROOT_PATH . '/mods/' . basename($data['action']) . '.php';

// Write module to log.
debug_log($module);

// Check if the module file exists.
if (file_exists($module)) {
    // Dynamically include module file and exit.
    include_once($module);
    exit();

// Module file is missing.
} else {
    // Write to log.
    debug_log('No action');
}
