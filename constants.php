<?php
// Paths.
define('PORTAL_IMAGES_PATH', IMAGES_PATH . '/gyms');

// raid levels constant
define('RAID_LEVEL_ALL', 'X98765431');

// Raid eggs.
$eggs = array(
    '9999',  // Level 9 / Elite raid
    '9998',  // Level 8 / Ultra beast
    '9997',  // Level 7 / Legendary Mega
    '9996',  // Level 6 / Mega
    '9995',  // Level 5
    '9994',  // Level 4
    '9993',  // Level 3
    '9992',  // Level 2
    '9991'   // Level 1
);

// Raid levels limited to local players only
define('RAID_LEVEL_LOCAL_ONLY', [4, 9]);

// Levels available for import at PokeBattler
$pokebattler_levels = array('9', '8', '7', '6', '5', '4', '3', '1');

// Map our raid levels to tier names PokeBattler uses
$pokebattler_level_map = [
    '1' => 1,
    '3' => 3,
    '4' => 4,
    '5' => 5,
    '6' => 'MEGA',
    '7' => 'MEGA_5',
    '8' => 'ULTRA_BEAST',
    '9' => 'ELITE',
];

$pokebattler_pokemon_map = [
    'ZACIAN' => 'ZACIAN_HERO_FORM',
    'ZAMAZENTA' => 'ZAMAZENTA_HERO_FORM',
];

// Limit the tiers of upcoming raid bosses imported from PokeBattler to legendary and mega
$pokebattler_import_future_tiers = [5, 6, 7, 8, 9];

// Value used for denoting anytime attendance
define('ANYTIME', '1970-01-01 00:00:00');
define('ANYTIME_TS', preg_replace("/[^0-9]/", "", ANYTIME));

// Ex-raid event ID
defined('EVENT_ID_EX') 		or define('EVENT_ID_EX', '999');

// Icons.
defined('TEAM_B') 		or define('TEAM_B',        iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F499)));
defined('TEAM_R') 		or define('TEAM_R',        iconv('UCS-4LE', 'UTF-8', pack('V', 0x2764)));
defined('TEAM_Y') 		or define('TEAM_Y',        iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F49B)));
defined('TEAM_CANCEL') 		or define('TEAM_CANCEL',   iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F494)));
defined('TEAM_DONE') 		or define('TEAM_DONE',     iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F4AA)));
defined('TEAM_UNKNOWN')		or define('TEAM_UNKNOWN',  iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F680)));
defined('EMOJI_REFRESH')	or define('EMOJI_REFRESH', iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F504)));
defined('EMOJI_HERE') 		or define('EMOJI_HERE',    iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F4CD)));
defined('EMOJI_LATE') 		or define('EMOJI_LATE',    iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F40C)));
defined('EMOJI_REMOTE') 	or define('EMOJI_REMOTE',  iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F3E0)));
defined('EMOJI_SINGLE') 	or define('EMOJI_SINGLE',  iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F464)));
defined('EMOJI_GROUP') 		or define('EMOJI_GROUP',   iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F465)));
defined('EMOJI_STAR') 		or define('EMOJI_STAR',    iconv('UCS-4LE', 'UTF-8', pack('V', 0x2B50)));
defined('EMOJI_INVITE') 	or define('EMOJI_INVITE',  iconv('UCS-4LE', 'UTF-8', pack('V', 0x2709)));
defined('EMOJI_INFO')		or define('EMOJI_INFO',    iconv('UCS-4LE', 'UTF-8', pack('V', 0x2139)));
defined('EMOJI_DELETE')		or define('EMOJI_DELETE',  iconv('UCS-4LE', 'UTF-8', pack('V', 0x274C)));
defined('EMOJI_MAP')		or define('EMOJI_MAP',     iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F5FA)));
defined('EMOJI_PENCIL')		or define('EMOJI_PENCIL',  iconv('UCS-4LE', 'UTF-8', pack('V', 0x270F)));
defined('EMOJI_EGG')		or define('EMOJI_EGG',     iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F95A)));
defined('EMOJI_CLOCK')		or define('EMOJI_CLOCK',   iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F551)));
defined('EMOJI_CAMERA')		or define('EMOJI_CAMERA',  iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F4F7)));
defined('EMOJI_ALARM')		or define('EMOJI_ALARM',   iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F514)));
defined('EMOJI_NO_ALARM')	or define('EMOJI_NO_ALARM',iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F515)));
defined('EMOJI_FRIEND')	    or define('EMOJI_FRIEND',       iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F91D)));
defined('EMOJI_WANT_INVITE')or define('EMOJI_WANT_INVITE',  iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F4E5)));
defined('EMOJI_IN_PERSON')  or define('EMOJI_IN_PERSON',    iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F9E1)));
defined('EMOJI_ALIEN')      or define('EMOJI_ALIEN',        iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F47D)));
defined('EMOJI_CAN_INVITE')or define('EMOJI_CAN_INVITE',    iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F695)));
defined('EMOJI_SHINY') 		or define('EMOJI_SHINY',    iconv('UCS-4LE', 'UTF-8', pack('V', 0x2728)));

// Weather Icons.
defined('EMOJI_W_SUNNY') 		or define('EMOJI_W_SUNNY',            iconv('UCS-4LE', 'UTF-8', pack('V', 0x2600)));
defined('EMOJI_W_CLEAR') 		or define('EMOJI_W_CLEAR',            iconv('UCS-4LE', 'UTF-8', pack('V', 0x2728)));
defined('EMOJI_W_RAIN') 		or define('EMOJI_W_RAIN',             iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F327)));
defined('EMOJI_W_PARTLY_CLOUDY') 	or define('EMOJI_W_PARTLY_CLOUDY',    iconv('UCS-4LE', 'UTF-8', pack('V', 0x26C5)));
defined('EMOJI_W_CLOUDY') 		or define('EMOJI_W_CLOUDY',           iconv('UCS-4LE', 'UTF-8', pack('V', 0x2601)));
defined('EMOJI_W_WINDY') 		or define('EMOJI_W_WINDY',            iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F32C)));
defined('EMOJI_W_SNOW') 		or define('EMOJI_W_SNOW',             iconv('UCS-4LE', 'UTF-8', pack('V', 0x2744)));
defined('EMOJI_W_FOG') 			or define('EMOJI_W_FOG',              iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F301)));

// Weather.
$weather = array(
    '1' => EMOJI_W_SUNNY,
    '2' => EMOJI_W_CLEAR,
    '3' => EMOJI_W_RAIN,
    '4' => EMOJI_W_PARTLY_CLOUDY,
    '5' => EMOJI_W_CLOUDY,
    '6' => EMOJI_W_WINDY,
    '7' => EMOJI_W_SNOW,
    '8' => EMOJI_W_FOG
);

// Teams.
$teams = array(
    'mystic'    => TEAM_B,
    'valor'     => TEAM_R,
    'instinct'  => TEAM_Y,
    'unknown'   => TEAM_UNKNOWN,
    'cancel'    => TEAM_CANCEL
);
