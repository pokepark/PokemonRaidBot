<?php
// Write to log.
debug_log('importal()');
require_once(LOGIC_PATH . '/download_Portal_Image.php');
require_once(LOGIC_PATH . '/get_gym.php');
require_once(LOGIC_PATH . '/get_gym_details.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('portal-import');
function escape($value){

  $search = array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
  $replace = array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");

  return str_replace($search, $replace, $value);
}

// Import allowed?
if(!$config->PORTAL_IMPORT) {
  $msg = getTranslation('bot_access_denied');
  $keys = [];
  send_message($update['message']['chat']['id'], $msg, $keys, ['disable_web_page_preview' => 'true']);
  exit;
}

// Process message for portal information.
require_once(CORE_BOT_PATH . '/importal.php');
// Gym name.
$gym_name = $portal;
if(empty($portal)) {
  $gym_name = '#' . $update['message']['from']['id'];
}

// Gym image.
if($config->RAID_PICTURE_STORE_GYM_IMAGES_LOCALLY) {
  $no_spaces_gym_name = str_replace(array(' ', '\''), array('_', ''), $gym_name) . '.png';
  $gym_image = download_Portal_Image($portal_image, PORTAL_IMAGES_PATH, $no_spaces_gym_name);
  if($gym_image) {
    $gym_image = "file://" . $gym_image;
  }
} else {
  $gym_image = $portal_image;
}

$gym_name_no_spec = escape($portal); // Convert special characters in gym name
// Build query to check if gym is already in database or not
// First check if gym is found by portal id
$gym_statement = my_query('SELECT id FROM gyms WHERE gym_id = :gym_id LIMIT 1', ['gym_id' => $portal_id]);
if($gym_statement->rowCount() == 1) {
  $row = $gym_statement->fetch();
  $update_where_condition = 'gym_id = :gym_id';
  $update_values = '';
}else {
  // If portal id wasn't found, check by gym name
  $gym_statement_by_name = my_query('SELECT id FROM gyms WHERE gym_name = :gym_name LIMIT 1', ['gym_name' => $gym_name_no_spec]);
  $row = $gym_statement_by_name->fetch();
  $update_where_condition = 'gym_name = :gym_name';
  $update_values = 'gym_id = :gym_id, ';
}

// Gym already in database or new
if (empty($row['id'])) {
  // insert gym in table.
  debug_log('Gym not found in database gym list! Inserting gym "' . $gym_name . '" now.');
  $query = '
  INSERT INTO gyms (gym_name, lat, lon, address, show_gym, img_url, gym_id)
  VALUES (:gym_name, :lat, :lon, :address, 0, :gym_image, :gym_id)
  ';
  $msg = getTranslation('gym_added');

} else {
  // Update gyms table to reflect gym changes.
  debug_log('Gym found in database gym list! Updating gym "' . $gym_name . '" now.');
  $query = '
    UPDATE    gyms
    SET       lat = :lat,
            lon = :lon,
            gym_name = :gym_name,
            address = :address,
            ' . $update_values . '
            img_url = :gym_image
    WHERE     ' . $update_where_condition . '
    ';
  $msg = getTranslation('gym_updated');
  $gym_id = $row['id'];
}

// Insert / Update.
$statement = my_query(
  $query,[
  'gym_name' => $gym_name_no_spec,
  'lat' => $lat,
  'lon' => $lon,
  'address' => $address,
  'gym_image' => $gym_image,
  'gym_id' => $portal_id
]);


// Get last insert id.
if (empty($row['id'])) {
  $gym_id = $dbh->lastInsertId();
}

// Gym details.
if($gym_id > 0) {
  $gym = get_gym($gym_id);
  $msg .= CR . CR . get_gym_details($gym);
}

// Gym photo.
if($config->RAID_PICTURE_STORE_GYM_IMAGES_LOCALLY && $gym_image) {
  $msg .= EMOJI_CAMERA . SP . $no_spaces_gym_name;
}

// Set keys.
$keys[][] = button(getTranslation('delete'), ['gym_delete', 'fl' => $gym_name[0], 'g' =>  $gym_id, 'c' => 0]);
$keys[][] = button(getTranslation('show_gym'), ['gym_edit_details', 'g' => $gym_id, 'a' => 'show', 'v' => 1]);
$keys[][] = button(getTranslation('done'), ['exit', 'd' => '1']);

// Send the message.
send_message($update['message']['chat']['id'], $msg, $keys, ['disable_web_page_preview' => 'true']);
