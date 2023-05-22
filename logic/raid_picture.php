<?php
require_once(LOGIC_PATH . '/download_Portal_Image.php');
require_once(LOGIC_PATH . '/get_raid_times.php');
/**
 * Returns photo contents to post to Telegram. File_id or photo file contents
 * @param array $raid Raid array from get_raid()
 * @param bool $standalone_photo Clear the bottom right corner of the photo from text
 * @param bool $debug Add debug features to the photo
 * @return array [true/false if returned content is photo file, content, cached unique_id for editMessageMedia]
 */
function get_raid_picture($raid, $standalone_photo = false) {
  $binds = [
    ':raid_id' => $raid['id'],
    ':gym_id' => $raid['gym_id'],
    ':pokedex_id' => $raid['pokemon'],
    ':pokemon_form' => $raid['pokemon_form'],
    ':standalone' => $standalone_photo,
    ':ended' => $raid['raid_ended'],
  ];
  $timeQuery = '';
  if($raid['raid_ended'] == 0) {
    $timeQuery = '
      AND start_time = :start_time
      AND end_time = :end_time';
    $binds['start_time'] = $raid['start_time'];
    $binds['end_time'] = $raid['end_time'];
  }
  $query_cache = my_query('
    SELECT id, unique_id
    FROM photo_cache
    WHERE raid_id = :raid_id
    AND gym_id = :gym_id
    AND pokedex_id = :pokedex_id
    AND form_id = :pokemon_form
    ' . $timeQuery . '
    AND ended = :ended
    AND standalone = :standalone
    LIMIT 1', $binds
  );

  if($query_cache->rowCount() > 0) {
    $result = $query_cache->fetch();
    return [false, $result['id'], $result['unique_id']];
  }
  return [true, create_raid_picture($raid, $standalone_photo)];
}

/**
 * Create a raid picture and return it as a string
 * @param array $raid Raid array from get_raid()
 * @param bool $standalone_photo Clear the bottom right corner of the photo from text
 * @param bool $debug Add debug features to the photo
 * @return string
 */
function create_raid_picture($raid, $standalone_photo = false, $debug = false) {
  global $config;
  if ($GLOBALS['metrics']){
    $GLOBALS['requests_total']->inc(['raidpicture']);
  }

  // Query missing raid info
  $q_pokemon_info = my_query('
    SELECT
        pokemon_form_name, min_cp, max_cp, min_weather_cp, max_weather_cp, weather, shiny, type, type2,
        (SELECT img_url FROM gyms WHERE id=:gymId LIMIT 1) as img_url
    FROM pokemon
    WHERE pokedex_id = :pokemonId
    AND pokemon_form_id = :pokemonForm LIMIT 1
    ',[
      'gymId' => $raid['gym_id'],
      'pokemonId' => $raid['pokemon'],
      'pokemonForm' => $raid['pokemon_form'],
  ]);
  if($q_pokemon_info->rowCount() == 0) {
    info_log("Something wrong with the raid data provided!");
    info_log(print_r($raid,true));
    exit();
  }
  $raid = array_merge($raid, $q_pokemon_info->fetch());

  // Fonts
  $font_gym = FONTS_PATH . '/' . $config->RAID_PICTURE_FONT_GYM;
  $font_text = FONTS_PATH . '/' . $config->RAID_PICTURE_FONT_TEXT;
  $font_ex_gym = FONTS_PATH . '/' . $config->RAID_PICTURE_FONT_EX_GYM;

  // Canvas size
  $canvas_width = 700;
  $canvas_height = 356;

  // Creating an empty canvas
  $canvas = imagecreatetruecolor($canvas_width,$canvas_height);
  imagesavealpha($canvas,true);

  // Background color
  // Default: White
  $bg_rgb = [255,255,255];
  $config_bg_color = explode(',',$config->RAID_PICTURE_BG_COLOR);
  if(count($config_bg_color) == 3) {
    $bg_rgb = $config_bg_color;
  } else {
    info_log($config->RAID_PICTURE_BG_COLOR, 'Invalid value RAID_PICTURE_BG_COLOR:');
  }
  $bg_color = imagecolorallocate($canvas,$bg_rgb[0],$bg_rgb[1], $bg_rgb[2]);
  imagefill($canvas, 0, 0, $bg_color);

  // Text / Font color
  // Default: Black
  $font_rgb = [0,0,0];
  $config_font_color = explode(',',$config->RAID_PICTURE_TEXT_COLOR);
  if(count($config_font_color) == 3) {
    $font_rgb = $config_font_color;
  } else {
    info_log($config->RAID_PICTURE_TEXT_COLOR, 'Invalid value RAID_PICTURE_TEXT_COLOR:');
  }
  $font_color = imagecolorallocate($canvas,$font_rgb[0],$font_rgb[1],$font_rgb[2]);

  // Defining RBG values that are used to create transparent color
  // Should be different from RAID_PICTURE_BG_COLOR and RAID_PICTURE_TEXT_COLOR
  $transparent_rgb = [0,255,0];

  // Gym image
  $gym_url = $raid['img_url'];
  $gym_image_path = '';
  if($config->RAID_PICTURE_STORE_GYM_IMAGES_LOCALLY && !empty($gym_url)) {
    if(substr($gym_url, 0, 7) == 'file://') {
      $gym_image_path = $gym_url;
      debug_log($gym_image_path, 'Found an image imported via a portal bot: ');
    }else {
      $file_name = explode('/', $gym_url)[3];
      $gym_image_path = PORTAL_IMAGES_PATH .'/'. $file_name.'.png';
      debug_log($gym_image_path, 'Attempting to use locally stored gym image');
      if(!file_exists($gym_image_path)) {
        debug_log($gym_url, 'Gym image not found, attempting to downloading it from: ');
        if(is_writable(PORTAL_IMAGES_PATH)) {
          download_Portal_Image($gym_url, PORTAL_IMAGES_PATH, $file_name . '.png');
        }else {
          $gym_image_path = $gym_url;
          info_log(PORTAL_IMAGES_PATH, 'Failed to write new gym image, incorrect permissions in directory ');
        }
      }
    }
  }else {
    $img_gym = false;
    if (!empty($gym_url)) {
      $gym_image_path = $gym_url;
    }
  }
  $img_gym = $gym_image_path != '' ? grab_img($gym_image_path) : false;
  if($img_gym == false) {
    info_log($gym_image_path, 'Loading the gym image failed, using default gym image');
    if(is_file($config->RAID_DEFAULT_PICTURE)) {
      $img_gym = grab_img($config->RAID_DEFAULT_PICTURE);
    } else {
      info_log($config->RAID_DEFAULT_PICTURE, 'Cannot read default gym image:');
      $img_gym = grab_img(IMAGES_PATH . "/gym_default.png");
    }
  }

  // Get the width and height of the gym picture
  $gym_w = imagesx($img_gym);
  $gym_h = imagesy($img_gym);

  // Crop gym image
  if($gym_w > $gym_h) {
    $size = $gym_h;
    $crop_x = floor((($gym_w/2)-($gym_h/2)));
    $crop_y = 0;
  } else {
    $size = $gym_w;
    $crop_x = 0;
    $crop_y = floor((($gym_h/2)-($gym_w/2)));
  }

  // Create mask
  $new_w = 300;
  $new_h = 300;
  $mask = imagecreatetruecolor($new_w,$new_h);

  // Fill the mask with background color
  $bg = imagecolorallocate($mask,$bg_rgb[0],$bg_rgb[1], $bg_rgb[1]);
  imagefill($mask,0,0,$bg);

  // Define transparent color for the mask
  $transparent = imagecolorallocate($mask,$transparent_rgb[0],$transparent_rgb[1],$transparent_rgb[2]);
  imagecolortransparent($mask,$transparent);

  // Creating the orange circle around the gym photo
  $color_ellipse = imagecolorallocate($mask,254,193,161);
  imagefilledellipse($mask,$new_w/2,$new_h/2,$new_w-9,$new_h-9,$color_ellipse);
  imagefilledellipse($mask,$new_w/2,$new_h/2,$new_w-16,$new_h-16,$bg);

  // Creating a circle that is filled with transparent color
  imagefilledellipse($mask,$new_w/2,$new_h/2,$new_w-30,$new_h-30,$transparent);

  // Merging the desired part of the gym picture with canvas
  imagecopyresampled($canvas,$img_gym,0,0,$crop_x,$crop_y,$new_w,$new_h, $size,$size);

  // Merging the mask with a circular cutout to the canvas
  imagecopymerge($canvas, $mask, 0, 0, 0, 0, $new_w, $new_h, 100);



  // Is ex gym?
  if($raid['ex_gym'] == 1) {
    $ex_text_size = 20;
    $ex_text_angle = 0;
    $corner = 16; // Roundness of the corners
    $extra = $ex_text_size/5+1; // Some extra height

    $ex_mark_bg_color = [94,169,190];
    $ex_mark_text_color = [255,255,255];

    // Get the text with local translation for EX Raid gym
    $ex_raid_gym_text = strtoupper(getPublicTranslation('ex_gym'));
    // Finding out the size of text
    $ex_text_box = imagettfbbox($ex_text_size,$ex_text_angle,$font_ex_gym,$ex_raid_gym_text);

    $ex_logo_width = $ex_text_box[2]+($corner);
    $ex_logo_height = $ex_text_size+$extra;

    // Create the canvas for EX RAID indicator
    $ex_logo = imagecreatetruecolor($ex_logo_width,$ex_logo_height);
    // Defining the transparent color
    $ex_transparent = imagecolorallocate($ex_logo,$transparent_rgb[0],$transparent_rgb[1],$transparent_rgb[2]);
    imagecolortransparent($ex_logo,$ex_transparent);
    // Defining background color
    $ex_logo_bg = imagecolorallocate($mask,$ex_mark_bg_color[0],$ex_mark_bg_color[1], $ex_mark_bg_color[2]);
    $ex_text_color = imagecolorallocate($ex_logo,$ex_mark_text_color[0],$ex_mark_text_color[1],$ex_mark_text_color[2]);

    //Filling the canvas with transparent color
    imagefill($ex_logo,0,0,$ex_transparent);

    // Creating 4 balls, one in each corner
    imagefilledellipse($ex_logo,$corner/2,$corner/2,$corner,$corner,$ex_logo_bg);
    imagefilledellipse($ex_logo,$corner/2,$ex_logo_height-$corner/2,$corner,$corner,$ex_logo_bg);
    imagefilledellipse($ex_logo,$ex_logo_width-$corner/2,$corner/2,$corner,$corner,$ex_logo_bg);
    imagefilledellipse($ex_logo,$ex_logo_width-$corner/2,$ex_logo_height-$corner/2,$corner,$corner,$ex_logo_bg);
    // And two rectangles to fill the rest
    imagefilledrectangle($ex_logo,$corner/2,0,$ex_logo_width-($corner/2),$ex_logo_height,$ex_logo_bg);
    imagefilledrectangle($ex_logo,0,$corner/2,$ex_logo_width,$ex_logo_height-($corner/2),$ex_logo_bg);

    // Draw the text
    imagettftext($ex_logo,$ex_text_size,$ex_text_angle,$corner/2,$ex_text_size+1,$ex_text_color,$font_ex_gym,$ex_raid_gym_text);

    // Copy icon into canvas
    imagecopy($canvas,$ex_logo,20,20,0,0,$ex_logo_width,$ex_logo_height);
  }


  $show_boss_pokemon_types = false;
  // Raid running
  if(!$raid['raid_ended']) {
    // Raid Egg
    if($raid['pokemon'] > 9990) {
      // Getting the actual icon
      $img_pokemon = grab_img(IMAGES_PATH . "/raid_eggs/pokemon_icon_" . $raid['pokemon'] . "_00.png");

      // Position and size of the picture
      $dst_x = $dst_y = 150;
      $dst_w = $dst_h = 200;
      $src_w = $src_h = 128;

    //Pokemon
    } else {
      // Check pokemon icon source and create image
      $img_file = null;
      $uicons = false;
      $p_sources = explode(',', $config->RAID_PICTURE_POKEMON_ICONS);

      $addressable_icon = 'pm'.$raid['pokemon'];
      $addressableFallback = 'pm'.$raid['pokemon'].'.fNORMAL';
      $uicons_icon = $raid['pokemon'];

      if($raid['pokemon_form_name'] != 'normal') {
        $addressable_icon .= '.f'.strtoupper($raid['pokemon_form_name']);
        $uicons_icon .= '_f'.$raid['pokemon_form'];
      }

      // Add costume info for every mon except megas
      if($raid['costume'] != 0 && $raid['pokemon_form'] >= 0) {
        $costume = json_decode(file_get_contents(ROOT_PATH . '/protos/costume.json'), true);
        $addressable_icon .= '.c' . array_search($raid['costume'],$costume);
        $addressableFallback .= '.c' . array_search($raid['costume'],$costume);

        $uicons_icon .= '_c'.$raid['costume'];
      }
      if($raid['shiny'] == 1 && $config->RAID_PICTURE_SHOW_SHINY) {
        $addressable_icon .= '.s';
        $addressableFallback .= '.s';
        $uicons_icon .= '_s';
        $shiny_icon = grab_img(IMAGES_PATH . "/shinystars.png");
      }
      $addressable_icon .= '.icon.png';
      $addressableFallback .= '.icon.png';
      $uicons_icon .= '.png';

      foreach($p_sources as $p_dir) {
        // Icon dir named 'pokemon'? Then change path to not add '_repo-owner' to icon folder name
        if($p_dir == 'pokemon') $asset_dir = 'pokemon'; else $asset_dir = 'pokemon_' . $p_dir;
        // Set pokemon icon dir
        $p_img_base_path = IMAGES_PATH . "/" . $asset_dir;

        // Check if file exists in this collection
        if(file_exists($p_img_base_path . "/" . $addressable_icon) && filesize($p_img_base_path . "/" . $addressable_icon) > 0) {
          $img_file = $p_img_base_path . "/" . $addressable_icon;
          break;
        }else if(file_exists($p_img_base_path . "/" . $addressableFallback) && filesize($p_img_base_path . "/" . $addressableFallback) > 0) {
          $img_file = $p_img_base_path . "/" . $addressableFallback;
          $uicons = true;
          break;
        }else if(file_exists($p_img_base_path . "/" . $uicons_icon) && filesize($p_img_base_path . "/" . $uicons_icon) > 0) {
          $img_file = $p_img_base_path . "/" . $uicons_icon;
          $uicons = true;
          break;
        }
      }

      // If no image was found, substitute with a fallback
      if($img_file === null) {
        info_log($addressable_icon . ' ' . $uicons_icon, 'Failed to find an image in any pokemon image collection for:');
        $img_fallback_file = null;
        // If we know the raid level, fallback to egg image
        if(array_key_exists('level', $raid) && $raid['level'] !== null && $raid['level'] != 0) {
          $img_fallback_file = IMAGES_PATH . "/raid_eggs/pokemon_icon_999" . $raid['level'] . "_00.png";
        } else {
          info_log('Unknown raid level, using fallback icon.');
          $img_fallback_file = $config->RAID_PICTURE_POKEMON_FALLBACK;
        }
        $img_file = $img_fallback_file;
      }

      $img_pokemon = grab_img($img_file);

      // Position and size of the picture
      $dst_x = $dst_y = 100;
      $dst_w = $dst_h = 256;
      $src_w = $src_h = $dst_w;
      if($uicons === true) {
        [$src_w, $src_h] = getimagesize($img_file);
      }

      if($raid['type'] != '') $show_boss_pokemon_types = true;
    }

  // Raid ended
  } else {
    // Raid won image
    $img_pokemon = grab_img(IMAGES_PATH . "/raidwon.png");

    // Position and size of the picture
    $dst_x = $dst_y = 172;
    $src_w = 444;
    $src_h = 512;
    $dst_w = 160;
    $dst_h = floor($dst_w/$src_w*$src_h);
  }

  // Create pokemon image.
  imagesavealpha($img_pokemon,true);

  // Debug - Add border around pokemon image
  if($debug) {
    $im = imagecreate($src_w,$src_h);
    $black = imagecolorallocate($im,0,0,0);
    imagerectangle($img_pokemon,0,0,$src_w-1,$src_h-1,$black);
  }

  // Add pokemon to image
  imagecopyresampled($canvas,$img_pokemon,$dst_x,$dst_y,0,0,$dst_w,$dst_h,$src_w,$src_h);
  if(isset($raid['shadow']) && $raid['shadow']) {
    $img_shadow = grab_img(IMAGES_PATH . '/shadow.png');
    $icon_x = 275;
    imagecopyresampled($canvas,$img_shadow,$icon_x,275,0,0,75,75,55,62);
    $icon_x -= 45;
  }

  // Add pokemon types
  if($config->RAID_PICTURE_POKEMON_TYPES && $show_boss_pokemon_types) {
    $img_type = grab_img(IMAGES_PATH . "/types/".$raid['type'].".png");
    $icon_x = $icon_x ?? 300;
    imagesavealpha($img_type, true);
    if($raid['type2'] != '') {
      $img_type2 = grab_img(IMAGES_PATH . "/types/".$raid['type2'].".png");
      imagesavealpha($img_type2, true);
      imagecopyresampled($canvas,$img_type2,$icon_x,300,0,0,40,40,64,64);
      $icon_x -= 50;
    }
    imagecopyresampled($canvas,$img_type,$icon_x,300,0,0,40,40,64,64);
  }
  if(isset($shiny_icon)) {
    imagesavealpha($shiny_icon,true);
    $light_white = imagecolorallocatealpha($canvas, 255,255,255,50);
    imagefilledellipse($canvas, $icon_x-35 ,320,40,40,$light_white);
    imagecopyresampled($canvas,$shiny_icon,$icon_x-52,301,0,0,35,35,100,100);
  }

  // Ex-Raid?
  if($raid['event'] == EVENT_ID_EX) {
    $img_expass = grab_img(IMAGES_PATH . "/expass.png");
    imagesavealpha($img_expass,true);

    // Debug - Add border around expass image
    if($debug) {
      $im = imagecreate(256,256);
      $black = imagecolorallocate($im,0,0,0);
      imagerectangle($img_expass,0,0,255,255,$black);
    }
    imagecopyresampled($canvas,$img_expass,0,225,0,0,100,100,256,256);
  }



  // Adding the gym name to the image
  $text_size = 23; // Font size of additional text
  $text_size_cp_weather = 20;// Font size of weather cp text
  $left_after_poke = 356; // First left position behind the pokemon icon.
  $angle = 0; // Angle of the text
  $spacing = 10; // Spacing between lines
  $spacing_right = 10; // Empty space on the right for weather icons and CP text



  // Gym name
  // Largest gym name we found so far for testing:
  //$gym_name = 'Zentrum für Junge Erwachsene der Kirche Jesu Christi der Heiligen der Letzten Tage Pfahl Düsseldorf';
  $gym_name = $raid['gym_name'];

  // Get length, the shortest and largest word of the gym name
  $gym_name_words = explode(SP, $gym_name);
  $gym_name_word_lengths = array_map('strlen', array_map('utf8_decode', $gym_name_words));
  $gym_name_word_largest = max($gym_name_word_lengths);
  $gym_name_total_chars = strlen(utf8_decode($gym_name));

  // Number of rows based on number of words or total chars
  $gym_name_rows = 1;
  if(count($gym_name_words) > 1 && $gym_name_total_chars >= 18 && $gym_name_total_chars <= 50)  {
    $gym_name_rows = 2;
  } else if($gym_name_total_chars > 50)  {
    $gym_name_rows = 3;
  }

  // Wrap gym name to multiple lines if too long
  $gym_name_lines = explode(PHP_EOL,wordwrap(trim($gym_name),floor(($gym_name_total_chars+$gym_name_word_largest)/$gym_name_rows),PHP_EOL));

  debug_log($gym_name_total_chars, 'Gym name length:');
  debug_log($gym_name_lines, 'Gym name lines:');

  // Target width and height
  $targetWidth = imagesx($canvas) - imagesx($mask) - $spacing_right;
  $targetHeight = 95;
  $rowHeight = $targetHeight/$gym_name_rows;

  // Get largest possible fontsize for each gym name line
  $fontsize_gym = 0;
  for($l=0; $l<count($gym_name_lines); $l++) {
    for($s=1; $s<70/count($gym_name_lines); $s=$s+0.5){
      $box = imagettfbbox($s, 0, $font_gym, $gym_name_lines[$l]);
      $min_x = min(array($box[0], $box[2], $box[4], $box[6]));
      $max_x = max(array($box[0], $box[2], $box[4], $box[6]));
      $min_y = min(array($box[1], $box[3], $box[5], $box[7]));
      $max_y = max(array($box[1], $box[3], $box[5], $box[7]));
      $width = ($max_x - $min_x);
      $height = ($max_y - $min_y);
      $targetsize = $s;
      // Exit once we exceed width or height
      if($width >= $targetWidth || $height >= $rowHeight){
        break;
      }
    }

    // Gym name font size and spacing
    if($l == 0 || $targetsize < $fontsize_gym) {
      $fontsize_gym = $targetsize;
      $spacing_gym = $height * 0.30;
    }
  }

  // Add gym name to image
  for($y=0;$y<count($gym_name_lines);$y++){
    // Get box around text
    $box = imagettfbbox($fontsize_gym,$angle,$font_gym,$gym_name_lines[$y]);
    // Get min and max positions for x and y
    $min_x = min(array($box[0], $box[2], $box[4], $box[6]));
    $max_x = max(array($box[0], $box[2], $box[4], $box[6]));
    $min_y = min(array($box[1], $box[3], $box[5], $box[7]));
    $max_y = max(array($box[1], $box[3], $box[5], $box[7]));
    // Get text width and height
    $textwidth = ($max_x - $min_x);
    $textheight = floor($fontsize_gym*1.1);
    // Calculate distance from left and top for positioning the gym name text.
    $gym_name_top = floor((($y+1)*($textheight))+($y*$spacing_gym));
    $gym_name_left = floor(imagesx($mask) + (((imagesx($canvas) - imagesx($mask) - $spacing_right) - $textwidth)/2));
    imagettftext($canvas, $fontsize_gym, $angle, $gym_name_left, $gym_name_top, $font_color, $font_gym, $gym_name_lines[$y]);
  }



  // Raid times
  if(!$raid['raid_ended']) {
    $time_text = get_raid_times($raid, true, true);
  } else {
    $time_text = getPublicTranslation('raid_done');
  }

  // Adjust margins, font size and raid time text itself
  $time_text_lines = array();
  if(strpos($time_text, ',') !== false) {
    $time_text_lines[] = explode(',', $time_text)[0] . ',';
    // Thursday, 18:00 - 18:45
    if(count(explode(SP, explode(',', $time_text, 2)[1])) == 4) {
      $time_top = 150;
      $time_text_size = 35;
      $tmp_time_text_line = explode(SP, explode(',', $time_text)[1]);
      $time_text_lines[] = $tmp_time_text_line[0] . SP . $tmp_time_text_line[1] . SP . $tmp_time_text_line[2] . SP . $tmp_time_text_line[3];

    // Thursday, 12. December 18:00 - 18:45
    } else {
      $time_top = 140;
      $time_text_size = 30;
      $tmp_time_text_line = explode(SP, explode(',', $time_text)[1]);
      $time_text_lines[] = $tmp_time_text_line[0] . SP . $tmp_time_text_line[1] . SP . $tmp_time_text_line[2];
      $time_text_lines[] = $tmp_time_text_line[3] . SP . $tmp_time_text_line[4] . SP . $tmp_time_text_line[5];
    }
  } else {
    // 18:00 - 18:45 or raid ended text.
    $time_text_size = 40;
    $time_top = 175;
    $time_text_lines[] = $time_text;
  }
  $num_text_lines = count($time_text_lines);
  // If the photo is sent without caption, we want to keep the bottom right corcer clear of text because Telegram covers it with a timestamp
  if($standalone_photo) $time_top -= 10;

  // Go through every line...
  for($ya=0;$ya<$num_text_lines;$ya++){
    // ...and draw them to image
    $time_text_top = ($time_top+($ya*($time_text_size+$spacing)));

    // Align text to center between pokemon icon and right edge
    $box = imagettfbbox($time_text_size, $angle, $font_text, $time_text_lines[$ya]);
    $min_x = min(array($box[0], $box[2], $box[4], $box[6]));
    $max_x = max(array($box[0], $box[2], $box[4], $box[6]));
    $textwidth = ($max_x - $min_x);
    $time_left = $left_after_poke + floor((((imagesx($canvas) - $left_after_poke - $spacing_right) - $textwidth)/2));
    imagettftext($canvas,$time_text_size,$angle,$time_left,$time_text_top,$font_color,$font_text,$time_text_lines[$ya]);
  }



  // Pokemon raid boss
  $pokemon_name = get_local_pokemon_name($raid['pokemon'], $raid['pokemon_form'], true) . (isset($raid['shadow']) && $raid['shadow'] ? ' ' . getPublicTranslation('shadow') : '');

  // Pokemon name and form?
  $pokemon_text_lines = array($pokemon_name);
  if(strlen($pokemon_name) > 20) {
    $pokemon_text_lines = explode(SP,$pokemon_name);
    if(count($pokemon_text_lines) == 1) {
      // Wrapping the time text if too long (to 20 letters)
      $pokemon_text_lines = explode(PHP_EOL,wordwrap(trim($pokemon_name),20,PHP_EOL));
    }
  }
  $num_pokemon_lines = count($pokemon_text_lines);

  // Target width and height
  $targetWidth = imagesx($canvas) - $left_after_poke - (strlen($raid['weather']) * 42) - $spacing_right;
  $targetHeight = 80;

  // Get largest possible fontsize for each pokemon name and form line
  $fontsize_poke = 0;
  for($p=0; $p<($num_pokemon_lines); $p++) {
    for($s=1; $s<40; $s=$s+0.5){
      $box = imagettfbbox($s, 0, $font_text, $pokemon_text_lines[$p]);
      $min_x = min(array($box[0], $box[2], $box[4], $box[6]));
      $max_x = max(array($box[0], $box[2], $box[4], $box[6]));
      $min_y = min(array($box[1], $box[3], $box[5], $box[7]));
      $max_y = max(array($box[1], $box[3], $box[5], $box[7]));
      $width = ($max_x - $min_x);
      $height = ($max_y - $min_y);
      $targetsize = $s;
      // Exit once we exceed width or height
      if($width >= $targetWidth || $height >= $targetHeight){
        break;
      }
    }

    // Gym name font size and spacing
    if($p == 0 || $targetsize < $fontsize_poke) {
      $fontsize_poke = $targetsize;
    }
  }

  // Pokemon name (and form) in 1 row
  $poke_text_top = 310;

  // Pokemon name and form in one or two lines?
  if($num_pokemon_lines > 1) {
    $poke_text_top = 272;
  }

  // If the photo is sent without caption, we want to keep the bottom right corcer clear of text because Telegram covers it with a timestamp
  if($standalone_photo) $poke_text_top -= 50;

  // Add pokemon name to image
  for($pa=0;$pa<$num_pokemon_lines;$pa++){
    // Get text width and height
    $textwidth = ($max_x - $min_x);
    $textheight = ($max_y - $min_y);
    // Position from top
    $poke_text_top = floor($poke_text_top+($pa*($fontsize_poke+$spacing)));
    imagettftext($canvas,$fontsize_poke,$angle,$left_after_poke,$poke_text_top,$font_color,$font_text,$pokemon_text_lines[$pa]);
  }

  // Pokemon CP
  if($raid['pokemon'] < 9990) {
    $cp_text_top = $poke_text_top+$text_size+$spacing;
    $cp_text = $raid['min_cp']." - ".$raid['max_cp'];
    $cp_text2 =  "(".$raid['min_weather_cp']."-".$raid['max_weather_cp'].")";

    imagettftext($canvas,$text_size,$angle,$left_after_poke,$cp_text_top,$font_color,$font_text,$cp_text);
    $cp_weather_text_box = imagettfbbox($text_size_cp_weather,$angle,$font_text,$cp_text2);
    imagettftext($canvas,$text_size_cp_weather,$angle,($canvas_width-$cp_weather_text_box[2]-$spacing_right),$cp_text_top,$font_color,$font_text,$cp_text2);

    $count_weather = strlen($raid['weather']);
    for($i=0;$i<$count_weather;$i++) {
      $we = substr($raid['weather'],$i,1);
      $weather_icon_path = IMAGES_PATH . "/weather/";
      // Use white icons?
      if($config->RAID_PICTURE_ICONS_WHITE) {
        $weather_icon_path = IMAGES_PATH . "/weather_white/";
      }
      $weather_icon = grab_img($weather_icon_path . $we . ".png"); // 64x64
      imagecopyresampled($canvas,$weather_icon,$canvas_width-$spacing_right-($count_weather-$i)*40,$poke_text_top-30,0,0,38,38,64,64);
    }
  }

  ob_start();
  // Define and print picture
  // PNG
  if($config->RAID_PICTURE_FILE_FORMAT == 'png') {
    imagepng($canvas);

  // JPEG
  } else if($config->RAID_PICTURE_FILE_FORMAT == 'jpeg' || $config->RAID_PICTURE_FILE_FORMAT == 'jpg') {
    imagejpeg($canvas, NULL, 90);

  // Use GIF as default - smallest file size without compression
  } else {
    imagegif($canvas);
  }
  return ob_get_clean();

}

/**
 * Create GD image object from given URI regardless of file type
 * @param string $uri Image uri
 * @return bool|object
 */
function grab_img($uri) {
  try {
    $img = imagecreatefromstring(file_get_contents($uri));
  }catch(Exception $e) {
    info_log($uri, 'Failed to get image:');
    return false;
  }
  return $img;
}
