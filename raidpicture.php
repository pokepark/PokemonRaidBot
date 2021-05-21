<?php

$parent = __DIR__;

// Include requirements and perfom initial steps
include_once(__DIR__ . '/core/bot/requirements.php');
include_once(CORE_BOT_PATH . '/db.php');
include_once(CORE_BOT_PATH . '/userlanguage.php');

// Create GD image object from given URI regardless of file type
function grab_img($uri){
  $img = imagecreatefromstring(file_get_contents($uri));
  if ($img === false) {
    info_log($uri, 'Failed to get image:');
    return false;
  }
  return $img;
}

// Debug switch
$debug = false;
if(isset($_GET['debug']) && $_GET['debug'] == 1) {
    $debug = true;
}

// Raid info
if(array_key_exists('raid', $_GET) && $_GET['raid']!="") {
    $raid_id = preg_replace("/\D/","",$_GET['raid']);
    $raid = get_raid_with_pokemon($raid_id);
} else {
  info_log('Called without a raid id, things will fail');
  $raid = null;
}

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
if($config->RAID_PICTURE_STORE_GYM_IMAGES_LOCALLY) {
    if(substr($raid['img_url'], 0, 7) == 'file://') {
        $gym_image_path = $raid['img_url'];
        info_log($gym_image_path, 'Found an image imported via a portal bot: ');
    }else {
        $file_name = explode('/', $raid['img_url'])[3];
        $gym_image_path = PORTAL_IMAGES_PATH .'/'. $file_name.'.png';
        info_log($gym_image_path, 'Attempting to use locally stored gym image');
        if(!file_exists($gym_image_path)) {
            info_log($raid['img_url'], 'Gym image not found, attempting to downloading it from: ');
            // Get file.
            $data = curl_get_contents($raid['img_url']);

            // Write to file.
            if(empty($data)) {
                info_log($raid['img_url'], 'Error downloading file, no data received!');
            } else {
                $file = fopen($gym_image_path, "w+");
                fwrite($file, $data);
                fflush($file);
                fclose($file);
            }
        }
    }
    $img_gym = grab_img($gym_image_path);
}else {
    $gym_url = $raid['img_url'];
    $img_gym = false;
    if (!empty($gym_url)) {
        $img_gym = grab_img($gym_url);
    }
}
if($img_gym == false) {
    info_log($img_gym, 'Loading the gym image failed, using default gym image');
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
    $crop_x = (($gym_w/2)-($gym_h/2));
    $crop_y = 0;
} else {
    $size = $gym_w;
    $crop_x = 0;
    $crop_y = (($gym_h/2)-($gym_w/2));
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



// Get current time.
$time_now = utcnow();

// Raid running
if($time_now < $raid['end_time']) {
    if(strlen($raid['asset_suffix']) > 2) {
        $icon_suffix = $raid['asset_suffix'];
    }else {
        $pad_zeroes = '';
        for ($o=3-strlen($raid['pokedex_id']);$o>0;$o--) {
            $pad_zeroes .= 0;
        }
        $icon_suffix = $pad_zeroes.$raid['pokedex_id'] . "_" . $raid['asset_suffix'];
    }

    // Raid Egg
    if($raid['pokedex_id'] > 9990) {
        // Getting the actual icon
        $img_pokemon = grab_img(IMAGES_PATH . "/raid_eggs/pokemon_icon_" . $raid['pokedex_id'] . "_00.png");

        // Position and size of the picture
        $dst_x = $dst_y = 150;
        $dst_w = $dst_h = 200;
        $src_w = $src_h = 128;

    //Pokemon
    } else {
        // Formatting the id from 1 digit to 3 digit (1 -> 001)
        $pokemon_id = str_pad($raid['pokedex_id'], 3, '0', STR_PAD_LEFT);

        // Getting the actual icon filename
        $p_icon = "pokemon_icon_" . $icon_suffix;
        if($raid['shiny'] == 1 && $config->RAID_PICTURE_SHOW_SHINY) {
            $p_icon = $p_icon . "_shiny";
        }
        $p_icon = $p_icon . ".png";

        // Check pokemon icon source and create image
        $img_file = null;
        $p_sources = explode(',', $config->RAID_PICTURE_POKEMON_ICONS);
        foreach($p_sources as $p_dir) {
            // Set pokemon icon dir
            $p_img = IMAGES_PATH . "/pokemon_" . $p_dir . "/" . $p_icon;
            
            // Icon dir named 'pokemon'? Then change path to not add '_repo-owner' to icon folder name
            if($p_dir == 'pokemon') {
                $p_img = IMAGES_PATH . "/pokemon/" . $p_icon;
            }
            // Check if file exists in this collection
            if(file_exists($p_img) && filesize($p_img) > 0) {
                $img_file = $p_img;
                break;
            }
        }

        // If no image was found, substitute with a fallback
        if($img_file === null) {
          info_log($p_icon, 'Failed to find an image in any pokemon image collection for:');
          $img_fallback_file = null;
          // If we know the raid level, fallback to egg image
          if(array_key_exists('raid_level', $raid) && $raid['raid_level'] !== null && $raid['raid_level'] != 0) {
            $img_fallback_file = IMAGES_PATH . "/raid_eggs/pokemon_icon_999" . $raid['raid_level'] . "_00.png";
          } else {
            info_log('Unknown raid level, using fallback icon.');
            $img_fallback_file = $config->RAID_PICTURE_POKEMON_FALLBACK;
          }
          $img_file = $img_fallback_file;
        }

        $img_pokemon = grab_img($img_file);

        // Position and size of the picture
        $dst_x = $dst_y = 100;
        $dst_w = $dst_h = $src_w = $src_h = 256;
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
    $dst_h = $dst_w/$src_w*$src_h;
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



// Ex-Raid?
if($raid['raid_level'] == 'X') {
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
$gym_name_word_shortest = min($gym_name_word_lengths);
$gym_name_total_chars = strlen(utf8_decode($gym_name));

// Number of rows based on number of words or total chars
$gym_name_rows = 1;
if(count($gym_name_words) > 1 && $gym_name_total_chars >= 18 && $gym_name_total_chars <= 50)  {
    $gym_name_rows = 2;
} else if($gym_name_total_chars > 50)  {
    $gym_name_rows = 3;
}

// Wrap gym name to multiple lines if too long
$gym_name_lines = explode(PHP_EOL,wordwrap(trim($gym_name),($gym_name_total_chars+$gym_name_word_largest)/$gym_name_rows,PHP_EOL));

debug_log($gym_name_total_chars, 'Gym name length:');
debug_log($gym_name_lines, 'Gym name lines:');

// Target width and height
$targetWidth = imagesx($canvas) - imagesx($mask) - $spacing_right;
$targetHeight = 95;
$targetHeight = $targetHeight/$gym_name_rows;

// Get largest possible fontsize for each gym name line
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
        if($width >= $targetWidth || $height >= $targetHeight){
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
    $textheight = ($max_y - $min_y);
    // Calculate distance from left and top for positioning the gym name text.
    $gym_name_top = (($y+1)*($textheight))+($y*$spacing_gym);
    $gym_name_left = imagesx($mask) + (((imagesx($canvas) - imagesx($mask) - $spacing_right) - $textwidth)/2);
    imagettftext($canvas, $fontsize_gym, $angle, $gym_name_left, $gym_name_top, $font_color, $font_gym, $gym_name_lines[$y]);
}



// Raid times
if($time_now < $raid['end_time']) {
    $time_text = get_raid_times($raid, true, true);
} else {
    $time_text = getPublicTranslation('raid_done');
}

// Adjust margins, font size and raid time text itself
$time_text_lines = array();
if(strpos($time_text, ',') !== false) {
    $time_text_lines[] .= explode(',', $time_text)[0] . ',';
    // Thursday, 18:00 - 18:45
    if(count(explode(SP, explode(',', $time_text, 2)[1])) == 4) {
        $time_top = 150;
        $time_text_size = 35;
        $tmp_time_text_line = explode(SP, explode(',', $time_text)[1]);
        $time_text_lines[] .= $tmp_time_text_line[0] . SP . $tmp_time_text_line[1] . SP . $tmp_time_text_line[2] . SP . $tmp_time_text_line[3];

    // Thursday, 12. December 18:00 - 18:45
    } else {
        $time_top = 140;
        $time_text_size = 30;
        $tmp_time_text_line = explode(SP, explode(',', $time_text)[1]);
        $time_text_lines[] .= $tmp_time_text_line[0] . SP . $tmp_time_text_line[1] . SP . $tmp_time_text_line[2];
        $time_text_lines[] .= $tmp_time_text_line[3] . SP . $tmp_time_text_line[4] . SP . $tmp_time_text_line[5];
    }
} else {
    // 18:00 - 18:45 or raid ended text.
    $time_text_size = 40;
    $time_top = 175;
    $time_text_lines[] .= $time_text;
}
$num_text_lines = count($time_text_lines);

// Go through every line...
for($ya=0;$ya<$num_text_lines;$ya++){
    // ...and draw them to image
    $time_text_top = ($time_top+($ya*($time_text_size+$spacing)));

    // Align text to center between pokemon icon and right edge
    $box = imagettfbbox($time_text_size, $angle, $font_text, $time_text_lines[$ya]);
    $min_x = min(array($box[0], $box[2], $box[4], $box[6]));
    $max_x = max(array($box[0], $box[2], $box[4], $box[6]));
    $textwidth = ($max_x - $min_x);
    $time_left = $left_after_poke + (((imagesx($canvas) - $left_after_poke - $spacing_right) - $textwidth)/2);
    imagettftext($canvas,$time_text_size,$angle,$time_left,$time_text_top,$font_color,$font_text,$time_text_lines[$ya]);
}



// Pokemon raid boss
$pokemon_name = get_local_pokemon_name($raid['pokemon'], $raid['pokemon_form'], true);

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

// Add pokemon name to image
for($pa=0;$pa<$num_pokemon_lines;$pa++){
    // Get text width and height
    $textwidth = ($max_x - $min_x);
    $textheight = ($max_y - $min_y);
    // Position from top
    $poke_text_top = ($poke_text_top+($pa*($fontsize_poke+$spacing)));
    imagettftext($canvas,$fontsize_poke,$angle,$left_after_poke,$poke_text_top,$font_color,$font_text,$pokemon_text_lines[$pa]);
}

// Pokemon CP
if($raid['pokedex_id'] < 9990) {
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



// Define and print picture
// PNG
if($config->RAID_PICTURE_FILE_FORMAT == 'png') {
   header("Content-type: image/png");
   imagepng($canvas);

// JPEG
} else if($config->RAID_PICTURE_FILE_FORMAT == 'jpeg' || $config->RAID_PICTURE_FILE_FORMAT == 'jpg') {
    header("Content-type: image/jpeg");
    imagejpeg($canvas, NULL, 90);

// Use GIF as default - smallest file size without compression
} else {
    header("Content-type: image/gif");
    imagegif($canvas);
}



// Clear memory
imagedestroy($img_gym);
imagedestroy($img_pokemon);
imagedestroy($canvas);
?>

