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

//GET GYM picture
$query = $db->prepare("SELECT gym_name,img_url,ex_gym FROM gyms WHERE id = ?");
$query->bind_param('s',$_GET['gym']);
$query->execute();
$res = $query->get_result();
$gym = $res-> fetch_assoc();
$offset = 0;
$img_gym = imagecreatefromjpeg($gym['img_url']);

//If img_url is empty load a default picture.
if (empty($gym['img_url'])) {
    $img_gym = imagecreatefromjpeg(RAID_DEFAULT_PICTURE);
}

// Get the width and height of the gym picture
$gym_w = imagesx($img_gym);
$gym_h= imagesy($img_gym);

// Creating an empty canvas
$canvas = imagecreatetruecolor(700,356);
imagesavealpha($canvas,true);

// Define the gray backgroundcolor for the picture
$grey = imagecolorallocate($canvas,$bg_r,$bg_b, $bg_g);	

$new_w = 300;
$new_h = 300;

if($gym_w>$gym_h) {
	$size = $gym_h;
	$crop_x = (($gym_w/2)-($gym_h/2)+$offset);
	$crop_y = 0;
}else {
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
if($gym['ex_gym']==1) {
	// Load ex gym icon
	$img_exgym = imagecreatefrompng("images/exgym.png");
	// Save transparency
	imagesavealpha($img_exgym,true);
	// Get icon's size
	$exgym_w = imagesx($img_exgym);
	$exgym_h= imagesy($img_exgym);
	
	// Copy icon into canvas
	imagecopy($canvas,$img_exgym,20,20,0,0,$exgym_w,$exgym_h);
}

// Preparing to get the pokemon icon
if($_GET['pokemon']!="ended") {
	// Split pokedex_id and form
	$dex_id_form = explode('-',$_GET['pokemon']);
	$pokedex_id_short = $dex_id_form[0];
	$pokemon_form_text = ($dex_id_form[1]=="")?"normal":$dex_id_form[1];

//Normal alolan or galarian
switch($pokemon_form_text) {
        case "normal":
            $pokemon_form = "00";        
        break;
        case "alolan":
            // Selection for alolan/normal
            $pokemon_form = "61";
        break;
        case "galarian":
            // Selection for galarian/normal
            $pokemon_form = "31";
        break;
    }

	// OR if Deoxys...
	if($pokedex_id_short == "386") {
		switch($pokemon_form_text) {
			case "normal":
				$pokemon_form = 11;
			break;
			case "attack":
				$pokemon_form = 12;
			break;
			case "defense":
				$pokemon_form = 13;
			break;
			case "speed":
				$pokemon_form = 14;
			break;
		}
	}
	// OR if Giratina...
	if($pokedex_id_short == "487") {
		switch($pokemon_form_text) {
			case "altered":
				$pokemon_form = 11;
			break;
			case "origin":
				$pokemon_form = 12;
			break;
		}
	}
	// OR if Burmy  -_-
	if($pokedex_id_short == "412") {
		switch($pokemon_form_text) {
			case "plant":
				$pokemon_form = 11;
			break;
			case "sandy":
				$pokemon_form = 12;
			break;
			case "trash":
				$pokemon_form = 13;
			break;
		}
	}
	if($pokedex_id_short == "150") {
		switch($pokemon_form_text) {
			case "armored":
				$pokemon_form = 11;
			break;
		}
	}
	// Formatting the id from 1 digit to 3 digit (1 -> 001)
	$zeroes="";
	if($pokedex_id_short<9990) {
		for($i=0;$i<(3-strlen($pokedex_id_short));$i++){
			$zeroes.="0";
		}
	}
	$pokemon_id = $zeroes.$pokedex_id_short;

	// Getting the actual icon
	$img_pokemon	= imagecreatefrompng("images/pokemon_icons/pokemon_icon_".$pokemon_id."_".$pokemon_form.".png");
	imagesavealpha($img_pokemon,true);

	//Position the picture of a pokemon or raid egg
	if($pokemon_id>9990) {
		//Is egg
		imagecopyresampled($canvas,$img_pokemon,150,150,0,0,200,200,128,128);
	}else{
		//Is Pokemon
		imagecopyresampled($canvas,$img_pokemon,100,100,0,0,256,256,256,256);
	}
}else {
	$raidwon_img = imagecreatefrompng("images/raidwon.png");
	imagesavealpha($raidwon_img,true);
	$won_width = 150;
	imagecopyresampled($canvas,$raidwon_img,160,160,0,0,$won_width,$won_width/444*512,444,512);
}

// Adding the gym name to the image
$font_gym = "images/calibrib.ttf";					// Path to the font file
$font_text = "images/calibri.ttf";					// Path to the font file
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
$text_lines = explode(PHP_EOL,wordwrap(trim($gym['gym_name']),22,PHP_EOL));

// Go through every line...
for($y=0;$y<count($text_lines);$y++){
	// ...and draw them to image
	$gym_text_top = ($top+($y*($gym_text_size+$spacing)));
	$box = imagettfbbox($gym_text_size,$angle,$font_gym,$text_lines[$y]);
	$gym_left = $left-(($box[2]-$box[0])/2); 
	imagettftext($canvas,$gym_text_size,$angle,$gym_left,$gym_text_top,$font_color,$font_gym,$text_lines[$y]);
}

// Preparing to add raid info to image
$raid_id = preg_replace("/\D/","",$_GET['raid']);
if($_GET['raid']!="") {
	$raid_query = $db->prepare("
	SELECT 
		UNIX_TIMESTAMP(start_time + INTERVAL TIMESTAMPDIFF(HOUR,UTC_TIMESTAMP(),NOW()) hour)		AS ts_start,
		UNIX_TIMESTAMP(end_time + INTERVAL TIMESTAMPDIFF(HOUR,UTC_TIMESTAMP(),NOW()) hour)		AS ts_end,
		UNIX_TIMESTAMP(NOW())			as ts_now
	 FROM raids 
	 WHERE id = ?");
	$raid_query ->bind_param('s',$_GET['raid']);
	$raid_query ->execute();
	$raid = $raid_query ->get_result();
	$raid_info = $raid-> fetch_assoc();
	if($raid_info['ts_end']>$raid_info['ts_now']) {
		$raid_start = date("H:i",$raid_info['ts_start']);
		$raid_end = date("H:i",$raid_info['ts_end']);
		$time_text = getTranslation('raid'). " " .$raid_start." - ".$raid_end;
	}else {
		$time_text = getTranslation('raid');
	}
	imagettftext($canvas,$text_size,$angle,$left_tab,200,$font_color,$font_text,$time_text);
}

// Preparing to add pokemon name and CP info into image
$pokemon_query = $db->prepare("SELECT pokemon_name,pokemon_form,min_cp,max_cp,min_weather_cp,max_weather_cp,weather FROM pokemon WHERE pokedex_id = ? AND pokemon_form = ?");
$pokemon_query->bind_param('ss',$pokedex_id_short,$pokemon_form_text);
$pokemon_query->execute();
$pokemon_res = $pokemon_query->get_result();
$pokemon = $pokemon_res-> fetch_assoc();

$poke_text_top = 300;

$pokemon_name = ($pokemon_form_text!="normal" && $pokemon_form_text!="")?$pokemon['pokemon_name']." - ".$pokemon_form_text:$pokemon['pokemon_name'];
imagettftext($canvas,$text_size,$angle,$left_tab,$poke_text_top,$font_color,$font_text,$pokemon_name);

if($pokedex_id_short<9990 && $_GET['pokemon']!="ended") {
	$cp_text_top = $poke_text_top;
	$cp_text = $pokemon['min_cp']." - ".$pokemon['max_cp'];
	$cp_text2 =  "(".$pokemon['min_weather_cp']." - ".$pokemon['max_weather_cp'].")";
	imagettftext($canvas,$text_size,$angle,$cp_text_left,$cp_text_top,$font_color,$font_text,$cp_text);
	imagettftext($canvas,$text_size,$angle,$cp_text_left,$cp_text_top+$text_size+$spacing,$font_color,$font_text,$cp_text2);

	for($i=0;$i<strlen($pokemon['weather']);$i++) {
		$we = substr($pokemon['weather'],$i,1);
		$weather_icon = imagecreatefrompng("images/".$we.".png"); // 64x64
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
