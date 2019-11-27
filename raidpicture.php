<?php

// Parent dir.
$parent = __DIR__;

// Include requirements and perfom initial steps
include_once(__DIR__ . '/core/bot/requirements.php');

// Database connection
include_once(CORE_BOT_PATH . '/db.php');

// Get language
include_once(CORE_BOT_PATH . '/userlanguage.php');

//Background color
$bg_r = 255;
$bg_b = 255;
$bg_g = 255;

// Get raid info with gym and pokemon
$raid_id = preg_replace("/\D/","",$_GET['raid']);
if($_GET['raid']!="") {
    $raid = get_raid_with_pokemon($raid_id);
}

// Offset and gym image
$offset = 0;
$img_gym = imagecreatefromjpeg($raid['img_url']);

// Get the width and height of the gym picture
$gym_w = imagesx($img_gym);
$gym_h = imagesy($img_gym);

// Creating an empty canvas
$canvas = imagecreatetruecolor(700,356);
imagesavealpha($canvas,true);

// Define the gray backgroundcolor for the picture
$grey = imagecolorallocate($canvas,$bg_r,$bg_b, $bg_g);	

$new_w = 300;
$new_h = 300;

if($gym_w > $gym_h) {
    $size = $gym_h;
    $crop_x = (($gym_w/2)-($gym_h/2)+$offset);
    $crop_y = 0;
} else {
    $size = $gym_w;
    $crop_x = 0;
    $crop_y = (($gym_h/2)-($gym_w/2)+$offset);
}

imagefill($canvas, 0, 0, $grey);

//Create mask
$mask = imagecreatetruecolor($new_w,$new_h);

//Fill the mask with grey
$bg = imagecolorallocate($mask,$bg_r,$bg_b, $bg_g);
imagefill($mask,0,0,$bg);

// Create transparent color for the mask
$transparent = imagecolorallocate($mask,0,0,0);
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
    // Load ex gym icon
    $img_exgym = imagecreatefrompng(IMAGES_PATH . "/exgym.png");
    // Save transparency
    imagesavealpha($img_exgym,true);
    // Get icon's size
    $exgym_w = imagesx($img_exgym);
    $exgym_h = imagesy($img_exgym);
	
    // Copy icon into canvas
    imagecopy($canvas,$img_exgym,20,20,0,0,$exgym_w,$exgym_h);
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

    // Getting the actual icon
    $img_pokemon = imagecreatefrompng(IMAGES_PATH . "/pokemon/pokemon_icon_" . $pokemon_id . "_" . $pokemon_form . ".png");
    imagesavealpha($img_pokemon,true);

    //Position the picture of a pokemon or raid egg
    if($pokemon_id > 9990) {
        //Is egg
        imagecopyresampled($canvas,$img_pokemon,150,150,0,0,200,200,128,128);
    }else{
        //Is Pokemon
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
$font_gym = FONTS_PATH . "/calibrib.ttf";					// Path to the font file
$font_text = FONTS_PATH . "/calibri.ttf";					// Path to the font file
$font_color = imagecolorallocate($canvas,0,0,0);	// Font color (white)
$gym_text_size = 26;								// Font size of gym text
$text_size = 18;									// Font size of additional text
$left = 500;										// Position of the text from left
$left_tab = 355;									// Position of the text from left
$cp_text_left = 550;
$top = 60;											// Position of the text from top
$angle = 0;											// Angle of the text
$spacing = 10;										// Spacing between lines

// Wrapping the gym name if too long (to 22 letters)
$text_lines = explode(PHP_EOL,wordwrap(trim($raid['gym_name']),22,PHP_EOL));

// Go through every line...
for($y=0;$y<count($text_lines);$y++){
    // ...and draw them to image
    $gym_text_top = ($top+($y*($gym_text_size+$spacing)));
    $box = imagettfbbox($gym_text_size,$angle,$font_gym,$text_lines[$y]);
    $gym_left = $left-(($box[2]-$box[0])/2); 
    imagettftext($canvas,$gym_text_size,$angle,$gym_left,$gym_text_top,$font_color,$font_gym,$text_lines[$y]);
}

// Raid times
if($time_now < $raid['end_time']) {
    $time_text = get_raid_times($raid, true, false, true);
} else {
    $time_text = getPublicTranslation('raid_done');
}
imagettftext($canvas,$text_size,$angle,$left_tab,200,$font_color,$font_text,$time_text);

// Raid boss
$poke_text_top = 300;
$pokemon_name = get_local_pokemon_name($raid['pokemon'], true);
imagettftext($canvas,$text_size,$angle,$left_tab,$poke_text_top,$font_color,$font_text,$pokemon_name);

// Pokemon CP
if($raid['pokedex_id'] < 9990) {
    $cp_text_top = $poke_text_top;
    $cp_text = $raid['min_cp']." - ".$raid['max_cp'];
    $cp_text2 =  "(".$raid['min_weather_cp']." - ".$raid['max_weather_cp'].")";
    imagettftext($canvas,$text_size,$angle,$cp_text_left,$cp_text_top,$font_color,$font_text,$cp_text);
    imagettftext($canvas,$text_size,$angle,$cp_text_left,$cp_text_top+$text_size+$spacing,$font_color,$font_text,$cp_text2);

    for($i=0;$i<strlen($raid['weather']);$i++) {
        $we = substr($raid['weather'],$i,1);
        $weather_icon = imagecreatefrompng(IMAGES_PATH . "/weather/".$we.".png"); // 64x64
        imagecopyresampled($canvas,$weather_icon,$left_tab+($i*40),$poke_text_top+5,0,0,38,38,64,64);
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
