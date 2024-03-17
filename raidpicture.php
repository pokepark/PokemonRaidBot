<?php

// Include requirements and perfom initial steps
include_once(__DIR__ . '/core/bot/requirements.php');
include_once(CORE_BOT_PATH . '/db.php');
include_once(LOGIC_PATH . '/raid_picture.php');

if ($metrics){
  $requests_total->inc(['raidpicture']);
}

// Define and print picture
// PNG
if($config->RAID_PICTURE_FILE_FORMAT == 'png') {
  header("Content-type: image/png");

// JPEG
} else if($config->RAID_PICTURE_FILE_FORMAT == 'jpeg' || $config->RAID_PICTURE_FILE_FORMAT == 'jpg') {
  header("Content-type: image/jpeg");

// Use GIF as default - smallest file size without compression
} else {
  header("Content-type: image/gif");
}
$standalone_photo = (isset($_GET['sa']) && $_GET['sa'] == 1) ? true : false;

if(isset($_GET['debug']) && $_GET['debug'] == 1) {
  $raid_id = preg_replace("/\D/","",$_GET['raid']);
  if(preg_match("^[0-9]+$^",$raid_id)) $raid = get_raid($raid_id);
  else die("Invalid raid id!");
  echo create_raid_picture($raid, $standalone_photo, true);
  exit;
}

$required_parameters = ['pokemon', 'pokemon_form', 'start_time', 'end_time', 'gym_id', 'ex_raid'];
$failed = [];
// Raid info
foreach($required_parameters as $required) {
  if(!array_key_exists($required, $_GET)) {
    $failed[] = $required;
  }
}
if(count($failed) > 0) {
  info_log('Raidpicture called without '.join(', ',$failed).', ending execution');
  exit();
}
$raid = [];
$raid['pokemon'] = preg_replace("/\D/","",$_GET['pokemon']);
$raid['gym_id'] = preg_replace("/\D/","",$_GET['gym_id']);
$raid['raid_costume'] = false;
$raid['event'] = ($_GET['ex_raid'] == 1) ? EVENT_ID_EX : 0;
if($_GET['start_time'] == 0) {
  $raid['raid_ended'] = true;
}else {
  $raid['raid_ended'] = false;
  $raid['start_time'] = date("Y-M-d H:i:s",preg_replace("/\D/","",$_GET['start_time']));
  $raid['end_time'] = date("Y-M-d H:i:s",preg_replace("/\D/","",$_GET['end_time']));
}
if(in_array($_GET['pokemon_form'], ['-1','-2','-3'])) {
  $raid['pokemon_form'] = $_GET['pokemon_form'];
}else {
  $raid['pokemon_form'] = preg_replace("/\D/","",$_GET['pokemon_form']);
}
$raid['costume'] = 0;
if(array_key_exists('costume', $_GET) && $_GET['costume'] != '') {
  $raid['costume'] = preg_replace("/\D/","",$_GET['costume']);
}
$q_pokemon_info = my_query('
  SELECT pokemon_form_name, min_cp, max_cp, min_weather_cp, max_weather_cp, weather, shiny, type, type2
  FROM pokemon
  WHERE pokedex_id = ?
  AND pokemon_form_id = ?
  LIMIT 1', [$raid['pokemon'], $raid['pokemon_form']])->fetch();
$q_gym_info = my_query('SELECT img_url, gym_name, ex_gym FROM gyms WHERE id = ?', [$raid['gym_id']])->fetch();
$raid = array_merge($raid, $q_pokemon_info, $q_gym_info);

echo create_raid_picture($raid, $standalone_photo);
