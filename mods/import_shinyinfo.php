<?php
// Write to log.
debug_log('pokebattler()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'pokedex');

// Get raid levels
$id = $data['id'];

// Exclude pokemon
$arg = $data['arg'];

$link = 'https://fight.pokebattler.com/raids';
$pb_data = curl_get_contents($link);
$pb_data = json_decode($pb_data,true);

// Init empty keys array.
$keys = [];
$msg = '';
$shinydata = [];
foreach($pb_data['tiers'] as $tier) {

    // Raid level and message.
    $rl = str_replace('RAID_LEVEL_','', $tier['tier']);
    if($rl == "MEGA") $raid_level_id = 6; else $raid_level_id = $rl;
    $rl_parts = explode('_', $rl);
    if($rl_parts[count($rl_parts)-1] == 'FUTURE') continue;
    #$msg .= '<b>' . getTranslation('pokedex_raid_level') . SP . $rl . ':</b>' . CR;

    // Get raid bosses for each raid level.
    foreach($tier['raids'] as $raid) {
        if(!isset($raid['pokemon']) || $raid['shiny'] != 'true') continue;
        // Pokemon name ending with "_FORM" ?
        if(substr_compare($raid['pokemon'], '_FORM', -strlen('_FORM')) === 0) {
            debug_log('Pokemon with a special form received: ' . $raid['pokemon']);
            // Remove "_FORM"
            $pokemon = str_replace('_FORM', '', $raid['pokemon']);

            // Get pokemon name and form.
            $name = explode("_", $pokemon, 2)[0];
            $form = explode("_", $pokemon, 2)[1];

            // Fix for MEWTWO_A_FORM
            if($name == 'MEWTWO' && $form == 'A') {
                $form = 'ARMORED';
            }

        // Pokemon name ending with "_MALE" ?
        } else if(substr_compare($raid['pokemon'], '_MALE', -strlen('_MALE')) === 0) {
            debug_log('Pokemon with gender MALE received: ' . $raid['pokemon']);
            // Remove "_MALE"
            $pokemon = str_replace('_MALE', '', $raid['pokemon']);

            // Get pokemon name and form.
            $name = explode("_", $pokemon, 2)[0] . '♂';
            $form = 'normal';

        // Pokemon name ending with "_FEMALE" ?
        } else if(substr_compare($raid['pokemon'], '_FEMALE', -strlen('_FEMALE')) === 0) {
            debug_log('Pokemon with gender FEMALE received: ' . $raid['pokemon']);
            // Remove "_FEMALE"
            $pokemon = str_replace('_FEMALE', '', $raid['pokemon']);

            // Get pokemon name and form.
            $name = explode("_", $pokemon, 2)[0] . '♀';
            $form = 'normal';

        // Mega pokemon ?
        }else if(substr_compare($raid['pokemon'], '_MEGA', -strlen('_MEGA')) === 0 or substr_compare($raid['pokemon'], '_MEGA_X', -strlen('_MEGA_X')) === 0 or substr_compare($raid['pokemon'], '_MEGA_Y', -strlen('_MEGA_Y')) === 0) {
            debug_log('Mega Pokemon received: ' . $raid['pokemon']);

            // Get pokemon name and form.
            $name_form = explode("_", $raid['pokemon'], 2);
            $name = $name_form[0];
            $form = $name_form[1];

        // Normal pokemon without form or gender.
        } else {
            // Fix pokemon like "HO_OH"...
            if(substr_count($raid['pokemon'], '_') >= 1) {
                $pokemon = str_replace('_', '-', $raid['pokemon']);
            } else {
                $pokemon = $raid['pokemon'];
            }
            // Name and form.
            $name = $pokemon;
            $form = 'normal';

            // Fix for GIRATINA as the actual GIRATINA_ALTERED_FORM is just GIRATINA
            if($name == 'GIRATINA' && $form == 'normal') {
                $form = 'ALTERED';
            }
        }
        if($form != 'normal') continue;
        // Get ID and form name used internally.
        debug_log('Getting dex id and form for pokemon ' . $name . ' with form ' . $form);
        $dex_id_form = get_pokemon_id_by_name($name . '-' . $form, true);
        $dex_id = explode('-', $dex_id_form, 2)[0];
        $dex_form = explode('-', $dex_id_form, 2)[1];

        // Make sure we received a valid dex id.
        if(!is_numeric($dex_id) || $dex_id == 0) {
            info_log('Failed to get a valid pokemon dex id: '. $dex_id .' Continuing with next raid boss...');
            continue;
        }

        $shinydata[] = [':dex_id' => $dex_id, ':dex_form' => $dex_form];
    }
    $msg .= CR;
}
        // Back button.
        $keys[] = [
                [
                    'text'          => getTranslation('done'),
                    'callback_data' => '0:exit:1'
                ]
                ];
if(count($shinydata) > 0) {
    $query = $dbh->prepare("UPDATE pokemon SET shiny = 1 WHERE pokedex_id = :dex_id AND pokemon_form_id = :dex_form");
    foreach($shinydata as $row_data) {
        $query->execute($row_data);
    }
}

$msg .= 'Updated '.count($shinydata).' rows'.CR;

// Callback message string.
$callback_response = 'OK';

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();
