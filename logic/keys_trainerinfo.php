<?php
/**
 * Keys trainer info.
 * @show bool
 * @return array
 */
function keys_trainerinfo($show = false)
{
  global $config;
  // Toggle state.
  $status = 'show';
  if(!$show || $config->TRAINER_BUTTONS_TOGGLE) {
    // Key to show/hide trainer info.
    $keys[][] = button(getPublicTranslation('trainerinfo'), ['vote_level', 'a' => 'trainer', 's' => $status]);
    return $keys;
  }
  // Always show buttons?
  if(($show == true && !$config->TRAINER_BUTTONS_TOGGLE) || $config->TRAINER_BUTTONS_TOGGLE) {
    $status = 'hide';
  }

  // Keys to set team and level
  $keys[0][0] = button(getPublicTranslation('trainerinfo'), ['vote_level', 'a' => 'trainer', 's' => $status]);
  $keys[1][0] = button(getPublicTranslation('team') . SP . TEAM_B, ['vote_team', 'a' => 'trainer', 't' => 'mystic']);
  $keys[1][1] = button(getPublicTranslation('team') . SP . TEAM_R, ['vote_team', 'a' => 'trainer', 't' => 'valor']);
  $keys[1][2] = button(getPublicTranslation('team') . SP . TEAM_Y, ['vote_team', 'a' => 'trainer', 't' => 'instinct']);
  $keys[2][0] = button(getPublicTranslation('level') . ' +', ['vote_level', 'a' => 'trainer', 'l' => 'up']);
  $keys[2][1] = button(getPublicTranslation('level') . ' -', ['vote_level', 'a' => 'trainer', 'l' => 'down']);

  return $keys;
}
