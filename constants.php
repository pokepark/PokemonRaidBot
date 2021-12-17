<?php
// Paths.
define('PORTAL_IMAGES_PATH', IMAGES_PATH . '/gyms');

// raid level constants
define('RAID_LEVEL_ALL', '654321');

// Value used for denoting anytime attendance
define('ANYTIME', '1970-01-01 00:00:00');
define('ANYTIME_TS', preg_replace("/[^0-9]/", "", ANYTIME));

// Ex-raid event ID
defined('EVENT_ID_EX') 		or define('EVENT_ID_EX', '999');

// Icons.
defined('TEAM_B') 			or define('TEAM_B',                   iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F535)));
defined('TEAM_R') 			or define('TEAM_R',                   iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F534)));
defined('TEAM_Y') 			or define('TEAM_Y',                   iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F7E1)));
defined('TEAM_CANCEL') 			or define('TEAM_CANCEL',              iconv('UCS-4LE', 'UTF-8', pack('V', 0x0274C)));
defined('TEAM_DONE') 			or define('TEAM_DONE',                iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F4AA)));
defined('TEAM_UNKNOWN')			or define('TEAM_UNKNOWN',             iconv('UCS-4LE', 'UTF-8', pack('V', 0x02753)));
defined('EMOJI_REFRESH')		or define('EMOJI_REFRESH',            iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F504)));
defined('EMOJI_HERE') 			or define('EMOJI_HERE',               iconv('UCS-4LE', 'UTF-8', pack('V', 0x02705)));
defined('EMOJI_LATE') 			or define('EMOJI_LATE',               iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F40C)));
defined('EMOJI_REMOTE') 		or define('EMOJI_REMOTE',             iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F4E1)));
defined('EMOJI_SINGLE') 		or define('EMOJI_SINGLE',             iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F464)));
defined('EMOJI_GROUP')			or define('EMOJI_GROUP',              iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F465)));
defined('EMOJI_STAR') 			or define('EMOJI_STAR',               iconv('UCS-4LE', 'UTF-8', pack('V', 0x02B50)));
defined('EMOJI_INVITE') 		or define('EMOJI_INVITE',             iconv('UCS-4LE', 'UTF-8', pack('V', 0x02709)));
defined('EMOJI_INFO')			or define('EMOJI_INFO',               iconv('UCS-4LE', 'UTF-8', pack('V', 0x02139)));
defined('EMOJI_EGG')			or define('EMOJI_EGG',                iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F95A)));
defined('EMOJI_CLOCK')			or define('EMOJI_CLOCK',              iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F551)));
defined('EMOJI_CAMERA')			or define('EMOJI_CAMERA',             iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F4F7)));
defined('EMOJI_ALARM')			or define('EMOJI_ALARM',              iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F514)));
defined('EMOJI_NO_ALARM')		or define('EMOJI_NO_ALARM',           iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F515)));
defined('EMOJI_FRIEND')			or define('EMOJI_FRIEND',             iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F91D)));
defined('EMOJI_WANT_INVITE')		or define('EMOJI_WANT_INVITE',        iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F4E5)));
defined('EMOJI_IN_PERSON')		or define('EMOJI_IN_PERSON',          iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F7E0)));
defined('EMOJI_ALIEN')			or define('EMOJI_ALIEN',              iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F47D)));
defined('EMOJI_CAN_INVITE')		or define('EMOJI_CAN_INVITE',         iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F44B)));
defined('EMOJI_TRAINER')		or define('EMOJI_TRAINER',            iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F464)));
defined('EMOJI_LOCATION')		or define('EMOJI_LOCATION',           iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F30D)));
defined('EMOJI_CODEINFO')		or define('EMOJI_CODEINFO',           iconv('UCS-4LE', 'UTF-8', pack('V', 0x02139)));

// Weather Icons.
defined('EMOJI_W_SUNNY') 		or define('EMOJI_W_SUNNY',            iconv('UCS-4LE', 'UTF-8', pack('V', 0x02600)));
defined('EMOJI_W_CLEAR') 		or define('EMOJI_W_CLEAR',            iconv('UCS-4LE', 'UTF-8', pack('V', 0x02728)));
defined('EMOJI_W_RAIN') 		or define('EMOJI_W_RAIN',             iconv('UCS-4LE', 'UTF-8', pack('V', 0x02614)));
defined('EMOJI_W_PARTLY_CLOUDY')	or define('EMOJI_W_PARTLY_CLOUDY',    iconv('UCS-4LE', 'UTF-8', pack('V', 0x026C5)));
defined('EMOJI_W_CLOUDY') 		or define('EMOJI_W_CLOUDY',           iconv('UCS-4LE', 'UTF-8', pack('V', 0x02601)));
defined('EMOJI_W_WINDY') 		or define('EMOJI_W_WINDY',            iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F32A)));
defined('EMOJI_W_SNOW') 		or define('EMOJI_W_SNOW',             iconv('UCS-4LE', 'UTF-8', pack('V', 0x026C4)));
defined('EMOJI_W_FOG') 			or define('EMOJI_W_FOG',              iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F32B)));

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

// Raid eggs.
$eggs = array(
    '9996',  // Level 6 / Mega
    '9995',  // Level 5
    '9994',  // Level 4
    '9993',  // Level 3
    '9992',  // Level 2
    '9991'   // Level 1
);
