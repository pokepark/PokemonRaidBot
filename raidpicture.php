<?php

// Parent dir.
$parent = __DIR__;

// Include requirements and perfom initial steps
include_once(__DIR__ . '/core/bot/requirements.php');

// Database connection
include_once(CORE_BOT_PATH . '/db.php');

// Get language
include_once(CORE_BOT_PATH . '/userlanguage.php');

// Background color
$config_bg_color = explode(',',RAID_PICTURE_BG_COLOR);
if(count($config_bg_color)!=3) {
	$bg_rgb = [255,255,255]; // Default is white
}else {
	$bg_rgb = $config_bg_color;
}
// Text color
$config_font_color = explode(',',RAID_PICTURE_TEXT_COLOR);
if(count($config_font_color)!=3) {
	$font_rgb = [0,0,0];	// Default is black
}else {
	$font_rgb = $config_font_color;
}

// Defining RBG values that are used to create transparent color
// Should be different from RAID_PICTURE_BG_COLOR and RAID_PICTURE_TEXT_COLOR
$transparent_rgb = [0,255,0]; 

//Canvas size
$canvas_width = 700;
$canvas_height = 356;

// Get raid info with gym and pokemon
$raid_id = preg_replace("/\D/","",$_GET['raid']);
if($_GET['raid']!="") {
    $raid = get_raid_with_pokemon($raid_id);
}

// Gym image
//If img_url is empty load a default picture.
if (empty($raid['img_url'])) {
    $img_gym = imagecreatefromjpeg(RAID_DEFAULT_PICTURE);
} else {
    $img_gym = imagecreatefromjpeg($raid['img_url']);
}
// Get the width and height of the gym picture
$gym_w = imagesx($img_gym);
$gym_h = imagesy($img_gym);

// Creating an empty canvas
$canvas = imagecreatetruecolor($canvas_width,$canvas_height);
imagesavealpha($canvas,true);

// Define the backgroundcolor for the picture
$bg_color = imagecolorallocate($canvas,$bg_rgb[0],$bg_rgb[1], $bg_rgb[2]);	
imagefill($canvas, 0, 0, $bg_color);

$new_w = 300;
$new_h = 300;

if($gym_w > $gym_h) {
    $size = $gym_h;
    $crop_x = (($gym_w/2)-($gym_h/2));
    $crop_y = 0;
} else {
    $size = $gym_w;
    $crop_x = 0;
    $crop_y = (($gym_h/2)-($gym_w/2));
}

//Create mask
$mask = imagecreatetruecolor($new_w,$new_h);

//Fill the mask with background color
$bg = imagecolorallocate($mask,$bg_rgb[0],$bg_rgb[1], $bg_rgb[1]);
imagefill($mask,0,0,$bg);

// Define transparent color for the mask
$transparent = imagecolorallocate($mask,$transparent_rgb[0],$transparent_rgb[1],$transparent_rgb[2]);
imagecolortransparent($mask,$transparent);

// Creating a circle that is filled with transparent color
imagefilledellipse($mask,$new_w/2,$new_h/2,$new_w-30,$new_h-30,$transparent);

// Merging the desired part of the gym picture with canvas
imagecopyresampled($canvas,$img_gym,0,0,$crop_x,$crop_y,$new_w,$new_h, $size,$size);

// Merging the mask with a circular cutout to the canvas
imagecopymerge($canvas, $mask, 0, 0, 0, 0, $new_w, $new_h, 100);

// Creating the orange circle around the gym photo
$color_ellipse = imagecolorallocate($img_gym,254,193,161);
imageellipse($canvas,$new_w/2,$new_w/2,$new_w-13,$new_w-13,$color_ellipse);
imageellipse($canvas,$new_w/2,$new_w/2,$new_w-12,$new_w-12,$color_ellipse);
imageellipse($canvas,$new_w/2,$new_w/2,$new_w-11,$new_w-11,$color_ellipse);
imageellipse($canvas,$new_w/2,$new_w/2,$new_w-10,$new_w-10,$color_ellipse);
imageellipse($canvas,$new_w/2,$new_w/2,$new_w-9,$new_w-9,$color_ellipse);

// Is ex gym?
if($raid['ex_gym'] == 1) {
    $font_ex_gym = FONTS_PATH . "/calibri.ttf";			// Path to the font file
    $ex_text_size = 20;
    $ex_text_angle = 0;
    $corner = 16;										// Roundness of the corners
    $extra = $ex_text_size/5+1;							// Some extra height

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
    // Build array to map pokedex_id-form to filenames
    $pokeforms = array(
        '150-armored' => '11',
        '386-normal' => '11',
        '386-attack' => '12',
        '386-defense' => '13',
        '386-speed' => '14',
        '412-plant' => '11',
        '412-sandy' => '12',
        '412-trash' => '13',
        '487-altered' => '11',
        '487-origin' => '12'
    );

    // Map pokemon form for filename
    $pokemon_form = '00';
    if(array_key_exists($raid['pokemon'], $pokeforms)) {
        $pokemon_form = $pokeforms[$raid['pokemon']];
    } else {
        if($raid['pokemon_form'] == 'alolan') {
            $pokemon_form = '61';
        } else if($raid['pokemon_form'] == 'galarian') {
            $pokemon_form = '31';
        }
    }

    // Formatting the id from 1 digit to 3 digit (1 -> 001)
    $zeroes='';
    if($raid['pokedex_id'] < 9990) {
        for($i=0; $i<(3-strlen($raid['pokedex_id'])); $i++){
             $zeroes .= '0';
		}
    }
    $pokemon_id = $zeroes . $raid['pokedex_id'];

    //Position the picture of a pokemon or raid egg
    // Raid Egg
    if($pokemon_id > 9990) {
        // Getting the actual icon
        $img_pokemon = imagecreatefrompng(IMAGES_PATH . "/raid_eggs/pokemon_icon_" . $pokemon_id . "_" . $pokemon_form . ".png");
        imagesavealpha($img_pokemon,true);
 
        imagecopyresampled($canvas,$img_pokemon,150,150,0,0,200,200,128,128);

    //Pokemon
    } else {
        // Getting the actual icon
        $img_pokemon = imagecreatefrompng(IMAGES_PATH . "/pokemon/pokemon_icon_" . $pokemon_id . "_" . $pokemon_form . ".png");
        imagesavealpha($img_pokemon,true);

        imagecopyresampled($canvas,$img_pokemon,100,100,0,0,256,256,256,256);
    }
// Raid ended
} else {
    $raidwon_img = imagecreatefrompng(IMAGES_PATH . "/raidwon.png");
    imagesavealpha($raidwon_img,true);
    $won_width = 150;
    imagecopyresampled($canvas,$raidwon_img,160,160,0,0,$won_width,$won_width/444*512,444,512);
}

// Adding the gym name to the image
$font_gym = FONTS_PATH . "/calibrib.ttf";			// Path to the font file
$font_text = FONTS_PATH . "/calibri.ttf";			// Path to the font file
$font_color = imagecolorallocate($canvas,$font_rgb[0],$font_rgb[1],$font_rgb[2]);	 // Font color
$gym_text_size = 26;								// Font size of gym text
$text_size = 23;									// Font size of additional text
$left = 500;										// Position of the text from left
$left_tab = 325;									// Position of the text from left
$top = 45;											// Position of the text from top
$angle = 0;											// Angle of the text
$spacing = 10;										// Spacing between lines
$spacing_right = 10;								// Empty space on the right for weather icons and CP text

// Wrapping the gym name if too long (to 22 letters)
$gym_text_lines = explode(PHP_EOL,wordwrap(trim($raid['gym_name']),22,PHP_EOL));

// Go through every line...
if(count($gym_text_lines) == 1) {
    // ...and draw them to image
    $box = imagettfbbox($gym_text_size,$angle,$font_gym,$gym_text_lines[0]);
    imagettftext($canvas,$gym_text_size,$angle,$left_tab,$top,$font_color,$font_gym,$gym_text_lines[0]);
} else {
    for($y=0;$y<count($gym_text_lines);$y++){
        // ...and draw them to image
        $gym_text_top = ($top+($y*($gym_text_size+$spacing)));
        $box = imagettfbbox($gym_text_size,$angle,$font_gym,$gym_text_lines[$y]);
        $gym_left = $left-($box[2]/2);
        imagettftext($canvas,$gym_text_size,$angle,$gym_left,$gym_text_top,$font_color,$font_gym,$gym_text_lines[$y]);
    }
}

// Raid times
if($time_now < $raid['end_time']) {
    $time_text = get_raid_times($raid, true, false, true);
} else {
    $time_text = getPublicTranslation('raid_done');
}

// Wrapping the time text if too long (to 25 letters)
$time_text_lines = explode(PHP_EOL,wordwrap(trim($time_text),25,PHP_EOL));

$num_text_lines = count($time_text_lines);
// Move the text a little bit up if we have 3 or more lines
$time_top = ($num_text_lines>2) ? (175-($num_text_lines-2)*$text_size) : 175;

// Go through every line...
for($ya=0;$ya<$num_text_lines;$ya++){
    // ...and draw them to image
    $time_text_top = ($time_top+($ya*($text_size+$spacing)));
    $box = imagettfbbox($text_size,$angle,$font_gym,$time_text_lines[$ya]);
    $time_left = $left-($box[2]/2);
    imagettftext($canvas,$text_size,$angle,$time_left,$time_text_top,$font_color,$font_text,$time_text_lines[$ya]);
}

// Raid boss
$poke_text_top = 300;
$pokemon_name = get_local_pokemon_name($raid['pokemon'], true);
imagettftext($canvas,$text_size,$angle,$left_tab,$poke_text_top,$font_color,$font_text,$pokemon_name);

// Pokemon CP
if($raid['pokedex_id'] < 9990) {
    $cp_text_top = $poke_text_top+$text_size+$spacing;
    $cp_text = $raid['min_cp']." - ".$raid['max_cp'];
    $cp_text2 =  "(".$raid['min_weather_cp']." - ".$raid['max_weather_cp'].")";

    imagettftext($canvas,$text_size,$angle,$left_tab,$cp_text_top,$font_color,$font_text,$cp_text);
    $cp_text_box = imagettfbbox($text_size,$angle,$font_text,$cp_text2);
    imagettftext($canvas,$text_size,$angle,($canvas_width-$cp_text_box[2]-$spacing_right),$cp_text_top,$font_color,$font_text,$cp_text2);

    $count_weather = strlen($raid['weather']);
    for($i=0;$i<$count_weather;$i++) {
        $we = substr($raid['weather'],$i,1);
        $weather_icon_path = IMAGES_PATH . "/weather/";
        // Use white icons?
        if(RAID_PICTURE_ICONS_WHITE == true) {
            $weather_icon_path = IMAGES_PATH . "/weather_white/";
        }
        $weather_icon = imagecreatefrompng($weather_icon_path . $we . ".png"); // 64x64
        imagecopyresampled($canvas,$weather_icon,$canvas_width-$spacing_right-($count_weather-$i)*40,$poke_text_top-30,0,0,38,38,64,64);
    }
}

// Define and print picture
header("Content-type: image/png");
imagepng($canvas);

// Clear memory
imagedestroy($img_gym);
imagedestroy($img_pokemon);
imagedestroy($canvas);

$db->close();
$db = null;
?>

