<?php
require_once(LOGIC_PATH . '/curl_get_contents.php');

/**
 * Try to resolve Pokemon's proto name used by Pokebattler to match our DB entry
 * @param string $name Return results in sql insert query instead of text list
 * @return array|bool
 */
function resolvePokebattlerNameToIds($protoName) {

  $hardCoded = [ // Because some things just can't be explained with logic
    'NIDORAN_FEMALE_SHADOW_FORM' => [29, 776],
    'WURMPLE_NOEVOLVE_FORM' => [265, 600],
  ];
  if(isset($hardCoded[$protoName])) return $hardCoded[$protoName];
  $data = createProtoNames();
  if(isset($data[$protoName])) return $data[$protoName];
  return false;
}

function createProtoNames() {
  static $result = [];
  if($result !== []) return $result;

  $megaForms = ['','MEGA','MEGA_X','MEGA_Y','PRIMAL'];
  $data = json_decode(curl_get_contents('https://raw.githubusercontent.com/WatWowMap/Masterfile-Generator/master/master-latest.json'), true);
  foreach($data['pokemon'] as $pokemon) {
    $protoNameBase = str_replace("♂","_MALE",str_replace("♀","_FEMALE",preg_replace("/\s/","_",strtoupper($pokemon['name']))));
    $formCount = count($pokemon['forms']);
    $i = 1;
    foreach($pokemon['forms'] as $formId => $form) {
      if($formId == "0" && $formCount > 1) continue;
      if($formId == (string)$pokemon['default_form_id']) {
        $result[$protoNameBase] = [$pokemon['pokedex_id'], (int)$formId];
        $result[$form['proto'].'_FORM'] = [$pokemon['pokedex_id'], (int)$formId];
      }elseif($i == $formCount-1 && $pokemon['default_form_id'] == 0) { // Some random logic for Xerneas
        $result[$protoNameBase] = [$pokemon['pokedex_id'], (int)$formId];
      }else {
        if($form['proto'] == $protoNameBase.'_NORMAL') $protoName = $protoNameBase;
        else $protoName = $form['proto'].'_FORM';
        $result[$protoName] = [$pokemon['pokedex_id'], (int)$formId];
      }
      $i++;
    }
    if(!isset($pokemon['temp_evolutions'])) continue;
    foreach($pokemon['temp_evolutions'] as $evoId => $evo) {
      $result[$protoNameBase.'_'.$megaForms[(int)$evoId]] = [$pokemon['pokedex_id'], (int)$evoId];
    }
  }
  return $result;
}
