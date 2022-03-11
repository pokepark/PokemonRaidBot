<?php
// Write to log.
debug_log('pokedex_import()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'pokedex');

$id = $data['id'];
$arg = $data['arg'];

$msg = "Import data from community maintained sources:".CR;
$msg.= "<a href=\"https://github.com/ccev/pogoinfo\">ccev's github repository</a>".CR;
$msg.= "<a href=\"https://www.pokebattler.com\">Pokebattler</a>";

$keys = [
        [
            [
                'text'          => getTranslation('import') . SP . '(Pokebattler)',
                'callback_data' => '0:pokebattler:0'
            ]
        ],
        [
            [
                'text'          => getTranslation('import') . SP . getTranslation('upcoming') . SP . '(Pokebattler)',
                'callback_data' => '0:import_future_bosses:0'
            ]
        ],
        [
            [
                'text'          => getTranslation('import') . SP . getTranslation('shiny') . SP . '(Pokebattler)',
                'callback_data' => '0:import_shinyinfo:0'
            ]
        ],
        [
            [
                'text'          => getTranslation('import') . SP . '(ccev pogoinfo)',
                'callback_data' => '0:pogoinfo:0'
            ]
        ],
        [
            [
                'text'          => getTranslation('abort'),
                'callback_data' => '0:exit:0'
            ]
        ]
    ];

// Callback message string.
$callback_response = 'OK';

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, ['disable_web_page_preview' => true], true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

$dbh = null;
exit();

?>