<?php
// Check defines
if($config->DB_HOST && $config->DB_NAME && $config->DB_USER && $config->DB_PASSWORD) {
    // Establish PDO connection
    $dbh = new PDO("mysql:host=" . $config->DB_HOST . ";dbname=" . $config->DB_NAME . ";charset=utf8mb4", $config->DB_USER, $config->DB_PASSWORD, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
    $dbh->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
    $dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} else {
    $error = "Failed to connect to Database! Make sure DB_HOST, DB_NAME, DB_USER and DB_PASSWORD are defined and that you've provided a config/config.json in the first place!";
    error_log($error);
    http_response_code(409);
    die($error);
}
