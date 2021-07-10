<?php
// Carriage return.
defined('CR') or define('CR',  "\n");
defined('CR2') or define('CR2', "\n");

// Space.
defined('SP') or define('SP', " ");

// Default language.
defined('DEFAULT_LANGUAGE') or define('DEFAULT_LANGUAGE', 'EN');

// Telegram language code => Language files.
$languages = array(
    'nl' => 'NL',
    'de' => 'DE',
    'en-US' => 'EN',
    'it' => 'IT',
    'pt' => 'PT-BR',
    'ru' => 'RU',
    'fr' => 'FR',
    'fi' => 'FI'
);

// Icons.
defined('EMOJI_WARN') 		or define('EMOJI_WARN',    iconv('UCS-4LE', 'UTF-8', pack('V', 0x26A0)));
defined('EMOJI_DISK') 		or define('EMOJI_DISK',    iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F4BE)));
defined('EMOJI_NEW') 		or define('EMOJI_NEW',     iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F195)));
defined('EMOJI_CLIPPY')		or define('EMOJI_CLIPPY',  iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F4CE)));

