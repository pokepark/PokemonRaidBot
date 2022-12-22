<?php
// Write to log.
debug_log('pokedex_import()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('pokedex');

$msg = 'Import data from community maintained sources:'.CR;
$msg.= '<a href="https://github.com/ccev/pogoinfo">ccev\'s github repository</a>'.CR;
$msg.= '<a href="https://www.pokebattler.com">Pokebattler</a>';

$keys[][] = button(getTranslation('import') . SP . '(Pokebattler)', 'pokebattler');
$keys[][] = button(getTranslation('import') . SP . getTranslation('upcoming') . SP . '(Pokebattler)', 'import_future_bosses');
$keys[][] = button(getTranslation('import') . SP . getTranslation('shiny') . SP . '(Pokebattler)', 'import_shinyinfo');
$keys[][] = button(getTranslation('import') . SP . '(ccev pogoinfo)', 'pogoinfo');
$keys[][] = button(getTranslation('abort'), 'exit');

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

exit();
