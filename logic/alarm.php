<?php
/**
 * Send raid alerts to user.
 * @param $raid
 * @param $user
 * @param $action
 * @param $info
 */
function alarm($raid, $user, $action, $info = '')
{
    // Name of the user, which executes a status update
    $request = my_query("SELECT * FROM users WHERE user_id = {$user}");
    $answer_quests = $request->fetch();
    $username = $answer_quests['name'];

    // Gym name and raid times
    $request = my_query("SELECT * FROM raids as r left join gyms as g on r.gym_id = g.id WHERE r.id = {$raid}");
    $answer = $request->fetch();
    $gymname = $answer['gym_name'];
    $raidtimes = str_replace(CR, '', str_replace(' ', '', get_raid_times($answer, false, true)));

    // Get attend time.
    $r = my_query("SELECT DISTINCT attend_time FROM attendance WHERE raid_id = {$raid} and user_id = {$user}");
    $a = $r->fetch();
    $attendtime = $a['attend_time'];

    // Adding a guest
    if($action == "extra") {
        debug_log('Alarm additional trainer: ' . $info);
        $color_old = array('mystic', 'valor', 'instinct');
        $color_new = array (TEAM_B, TEAM_R, TEAM_Y);
        $color = str_replace($color_old, $color_new, $info);

        // Sending message
        $msg_text = '<b>' . getTranslation('alert_add_trainer') . '</b>' . CR;
        $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
        $msg_text .= EMOJI_SINGLE . SP . $username . SP . '+' . $color . CR;
        $msg_text .= EMOJI_CLOCK . SP . check_time($attendtime);
        sendalarm($msg_text, $raid, $user);

    // Updating status - here or cancel
    } else if($action == "status") {
        // If trainer changes state (to late or cancelation)
        if($info == 'late') {
            debug_log('Alarm late: ' . $info);
            // Send message.
            $msg_text = '<b>' . getTranslation('alert_later') . '</b>' . CR;
            $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
            $msg_text .= EMOJI_SINGLE . SP . $username . CR;
            $msg_text .= EMOJI_CLOCK . SP . check_time($attendtime);
            sendalarm($msg_text, $raid, $user);
        } else if($info == 'cancel') {
            debug_log('Alarm cancel: ' . $info);
            $msg_text = '<b>' . getTranslation('alert_cancel') . '</b>' . CR;
            $msg_text .= TEAM_CANCEL . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
            $msg_text .= EMOJI_SINGLE . SP . $username . CR;
            $msg_text .= EMOJI_CLOCK . SP . check_time($attendtime);
            sendalarm($msg_text, $raid, $user);
        }

    // Updating pokemon
    } else if($action == "pok_individual") {
        debug_log('Alarm Pokemon: ' . $info);

        // Only a specific pokemon
        if($info != '0') {
            $pokemon = explode("-",$info);
            $poke_name = get_local_pokemon_name($pokemon[0],$pokemon[1]);
            $msg_text = '<b>' . getTranslation('alert_individual_poke') . SP . $poke_name . '</b>' . CR;
            $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
            $msg_text .= EMOJI_SINGLE . SP . $username . CR;
            $msg_text .= EMOJI_CLOCK . SP . check_time($attendtime);
            sendalarm($msg_text, $raid, $user);
        // Any pokemon
        } else {
            $msg_text = '<b>' . getTranslation('alert_every_poke') . SP . $poke_name . '</b>' . CR;
            $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
            $msg_text .= EMOJI_SINGLE . SP . $username . CR;
            $msg_text .= EMOJI_CLOCK . SP . check_time($attendtime);
            sendalarm($msg_text, $raid, $user);
        }

    // Cancel pokemon
    } else if($action == "pok_cancel_individual") {
        debug_log('Alarm Pokemon: ' . $info);
        $pokemon = explode("-",$info);
        $poke_name = get_local_pokemon_name($pokemon[0],$pokemon[1]);
        $msg_text = '<b>' . getTranslation('alert_cancel_individual_poke') . SP . $poke_name . '</b>' . CR;
        $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
        $msg_text .= EMOJI_SINGLE . SP . $username . CR;
        $msg_text .= EMOJI_CLOCK . SP . check_time($attendtime);
        sendalarm($msg_text, $raid, $user);

    // New attendance
    } else if($action == "new_att") {
        debug_log('Alarm new attendance: ' . $info);
        // Will Attend
        $msg_text = '<b>' . getTranslation('alert_new_att') . '</b>' . CR;
        $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
        $msg_text .= EMOJI_SINGLE . SP . $username . CR;
        $msg_text .= EMOJI_CLOCK . SP . check_time($info);
        sendalarm($msg_text, $raid, $user);

    // Attendance time change
    } else if($action == "change_time") {
        debug_log('Alarm changed attendance time: ' . $info);
        // Changes Time
        $msg_text = '<b>' . getTranslation('alert_change_time') . '</b>' . CR;
        $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
        $msg_text .= EMOJI_SINGLE . SP . $username . CR;
        $msg_text .= EMOJI_CLOCK . SP . '<b>' . check_time($info) . '</b>';
        sendalarm($msg_text, $raid, $user);

    // Attendance from remote
    } else if($action == "remote") {
        debug_log('Alarm remote attendance changed: ' . $info);
        // Changes Time
        $msg_text = '<b>' . getTranslation('alert_remote') . '</b>' . CR;
        $msg_text .= EMOJI_REMOTE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
        $msg_text .= EMOJI_SINGLE . SP . $username . CR;
        $msg_text .= EMOJI_CLOCK . SP . '<b>' . check_time($attendtime) . '</b>';
        sendalarm($msg_text, $raid, $user);

    // Attendance no longer from remote
    } else if($action == "no_remote") {
        debug_log('Alarm remote attendance changed: ' . $info);
        // Changes Time
        $msg_text = '<b>' . getTranslation('alert_no_remote') . '</b>' . CR;
        $msg_text .= EMOJI_REMOTE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
        $msg_text .= EMOJI_SINGLE . SP . $username . CR;
        $msg_text .= EMOJI_CLOCK . SP . '<b>' . check_time($attendtime) . '</b>';
        sendalarm($msg_text, $raid, $user);

    // No additional trainer
    } else if($action == "extra_alone") {
        debug_log('Alarm no additional trainers: ' . $info);
        $msg_text = '<b>' . getTranslation('alert_extra_alone') . '</b>' . CR;
        $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
        $msg_text .= EMOJI_SINGLE . SP . $username . CR;
        $msg_text .= EMOJI_CLOCK . SP . check_time($attendtime);
        sendalarm($msg_text, $raid, $user);

    // Group code public
    } else if($action == "group_code_public") {
        debug_log('Alarm for group code: ' . $info);
        $msg_text = '<b>' . getTranslation('alert_raid_starts_now') . CR . getTranslation('alert_raid_get_in') . '</b>' . CR . CR;
        $msg_text .= '<b>' . getTranslation('alert_public_group') . '</b>' . CR;
        $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
        $msg_text .= EMOJI_SINGLE . SP . $username . CR;
        $msg_text .= EMOJI_REMOTE . SP . $info;
        sendcode($msg_text, $raid, $user, 'public');

    // Group code private
    } else if($action == "group_code_private") {
        debug_log('Alarm for group code: ' . $info);
        $msg_text = '<b>' . getTranslation('alert_raid_starts_now') . CR . getTranslation('alert_raid_get_in') . '</b>' . CR . CR;
        $msg_text .= '<b>' . getTranslation('alert_private_group') . '</b>' . CR;
        $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')' . CR;
        $msg_text .= EMOJI_SINGLE . SP . $username . CR;

        // Send code to remote raiders
        $msg_text_remote = $msg_text;
        $msg_text_remote .= EMOJI_REMOTE . SP . '<b>' . $info . '</b>';
        sendcode($msg_text_remote, $raid, $user, 'remote');

        // Send message to local raiders
        $msg_text_local = $msg_text;
        $msg_text_local .= EMOJI_REMOTE . SP . '<b>' . getTranslation('group_code_only_for_remote_raiders') . '</b>';
        sendcode($msg_text_local, $raid, $user, 'local');
    }
}

?>
