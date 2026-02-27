<?php
/**
 * Edit gym keys.
 * @param $update array The global update-variable containing the query from Telegram
 * @param $gym_id int Gym id
 * @param $show_gym bool Current show_gym value of the gym
 * @param $ex_gym int Current ex_gym value of the gym
 * @param $gym_note string Current gym note
 * @param $gym_address string Current gym address
 * @return array
 */
function edit_gym_keys($update, $gym_id, $show_gym, $ex_gym, $gym_note, $gym_address)
{
  global $botUser;
  // Hide gym?
  $text_show_button = ($show_gym == 1) ? getTranslation('hide_gym') : getTranslation('show_gym');
  $arg_show = ($show_gym == 1) ? 0 : 1;

  // Ex-raid gym?
  $text_ex_button = ($ex_gym == 1) ? getTranslation('normal_gym') : getTranslation('ex_gym');
  $arg_ex = ($ex_gym == 1) ? 0 : 1;

  // Add buttons to show/hide the gym and add/remove ex-raid flag
  $keys = [];
  $keys[] = [
    button($text_show_button, ['gym_edit_details', 'g' => $gym_id, 'a' => 'show', 'v' => $arg_show]),
    button($text_ex_button, ['gym_edit_details', 'g' => $gym_id, 'a' => 'ex', 'v' => $arg_ex])
  ];
  if($botUser->accessCheck('gym-name', true)) {
    $keys[][] = button(EMOJI_PENCIL . ' ' . getTranslation('gym_name_edit'), ['gym_edit_details', 'g' => $gym_id, 'a' => 'name']);
  }
  if($botUser->accessCheck('gym-edit', true)) {
    $keys[][] = button(
      EMOJI_INFO . ' ' . (!empty($gym_note) ? getTranslation('edit') : getTranslation('add') ) . ' ' . getTranslation('gym_add_edit_note'),
      ['gym_edit_details', 'g' => $gym_id, 'a' => 'note']
    );
    $keys[][] = button(
      EMOJI_MAP . ' ' . ((!empty($gym_address) && $gym_address != getTranslation('directions')) ? getTranslation('edit') : getTranslation('add')) . ' ' . getTranslation('gym_address'),
      ['gym_edit_details', 'g' => $gym_id, 'a' => 'addr']
    );
    $keys[][] = button(
      EMOJI_HERE . ' ' . getTranslation('gym_edit_coordinates'),
      ['gym_edit_details', 'g' => $gym_id, 'a' => 'gps']
    );
  }
  if($botUser->accessCheck('gym-delete', true)) {
    $keys[][] = button(EMOJI_DELETE . ' ' . getTranslation('gym_delete'), ['gym_delete', 'g' => $gym_id, 'c' => 0]);
  }
  $keys[][] = button(getTranslation('done'), ['exit', 'd' => '1']);

  return $keys;
}
