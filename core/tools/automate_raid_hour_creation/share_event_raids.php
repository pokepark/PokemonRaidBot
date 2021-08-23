<?php
$file_contents = file_get_contents(__DIR__ .'/config.json');

if(! is_string($file_contents)){
    die('Config file not readable, cannot continue');
}

$config = (Object)json_decode($file_contents, true);

if(json_last_error() !== JSON_ERROR_NONE) {
    die('Config file not valid JSON, cannot continue.');
}

// Establish mysql connection.
$dbh = new PDO('mysql:host=' . $config->DB_HOST . ';dbname=' . $config->DB_NAME . ';charset=utf8mb4', $config->DB_USER, $config->DB_PASSWORD, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
$dbh->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
$dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$pk = $dbh->prepare("
        SELECT       raids.id
        FROM         raids
        LEFT JOIN    cleanup
        ON           raids.id = cleanup.raid_id
        WHERE        event = ".$config->RAID_HOUR_EVENT_ID."
        AND          chat_id IS NULL
        AND          date(raids.start_time) = date(NOW())
    ");
$pk->execute();

$all = $pk->fetchAll();
foreach($all as $raid) {
    $curl = curl_init($config->URL);

    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    $raid_id = $raid['id'];
    $json = json_encode([
         'skip_ddos' => true,
         'callback_query' => ['data' => $raid_id.':post_raid:'.$config->CHAT_ID]
     ]);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
    // Execute curl request.
    curl_exec($curl);
    // Close connection.
    curl_close($curl);
}

$dbh = null;
?>