<?php
// Paths
define('ROOT_PATH', __DIR__);
define('TRANSLATION_PATH', ROOT_PATH . '/tr');

// Carriage return.
define('CR',  "\n");
define('CR2', "\n");
// Space.
define('SP', " ");

// Languages.
$languages = array(
    'nl' => 'NL',
    'de' => 'DE',
    'en-US' => 'EN',
    'pt' => 'PT-BR'
);

// Icons.
define('TEAM_B',        iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F499)));
define('TEAM_R',        iconv('UCS-4LE', 'UTF-8', pack('V', 0x2764)));
define('TEAM_Y',        iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F49B)));
define('TEAM_CANCEL',   iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F494)));
define('TEAM_DONE',     iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F4AA)));
define('TEAM_UNKNOWN',  iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F680)));
define('EMOJI_REFRESH', iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F504)));
define('EMOJI_HERE',    iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F4CD)));
define('EMOJI_LATE',    iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F40C)));
define('EMOJI_GROUP',   iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F465)));
define('EMOJI_WARN',    iconv('UCS-4LE', 'UTF-8', pack('V', 0x26A0)));
define('EMOJI_DISK',    iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F4BE)));

// Weather Icons.
define('EMOJI_W_SUNNY',            iconv('UCS-4LE', 'UTF-8', pack('V', 0x2600)));
define('EMOJI_W_CLEAR',            iconv('UCS-4LE', 'UTF-8', pack('V', 0x2728)));
define('EMOJI_W_RAIN',             iconv('UCS-4LE', 'UTF-8', pack('V', 0x2614)));
define('EMOJI_W_PARTLY_CLOUDY',    iconv('UCS-4LE', 'UTF-8', pack('V', 0x26C5)));
define('EMOJI_W_CLOUDY',           iconv('UCS-4LE', 'UTF-8', pack('V', 0x2601)));
define('EMOJI_W_WINDY',            iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F32A)));
define('EMOJI_W_SNOW',             iconv('UCS-4LE', 'UTF-8', pack('V', 0x26C4)));
define('EMOJI_W_FOG',              iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F32B)));

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
    '9995',  // Level 5
    '9994',  // Level 4
    '9993',  // Level 3
    '9992',  // Level 2
    '9991'   // Level 1
);
