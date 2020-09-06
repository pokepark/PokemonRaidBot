<?php
/**
 * Get user.
 * @param $user_id
 * @return message
 */
function get_user($user_id)
{
    global $config;
    // Get user details.
    $rs = my_query(
        "
        SELECT *
        FROM   users
        WHERE  user_id = {$user_id}
        "
    );

    // Fetch the row.
    $row = $rs->fetch();
    // get Username
    $row = check_trainername($row);
    // Build message string.
    $msg = '';

    // Add name.
    $msg .= getTranslation('name') . ': <a href="tg://user?id=' . $row['user_id'] . '">' . htmlspecialchars($row['name']) . '</a>' . CR;

    if($config->RAID_POLL_SHOW_TRAINERCODE){ // is Trainercode enabled?
        // Unknown trainercode.
        if ($row['trainercode'] === NULL) {
            $msg .= getTranslation('trainercode') . ': ' . getTranslation('code_missing') . CR;
        // Known Trainercode.
        } else {
            $msg .= getTranslation('trainercode') . ': ' . $row['trainercode'] . CR;
        }
    }

    // Unknown team.
    if ($row['team'] === NULL) {
        $msg .= getTranslation('team') . ': ' . $GLOBALS['teams']['unknown'] . CR;
    // Known team.
    } else {
        $msg .= getTranslation('team') . ': ' . $GLOBALS['teams'][$row['team']] . CR;
    }

    // Add level.
    if ($row['level'] != 0) {
        $msg .= getTranslation('level') . ': <b>' . $row['level'] . '</b>' . CR;
    }

    return $msg;
}

?>
