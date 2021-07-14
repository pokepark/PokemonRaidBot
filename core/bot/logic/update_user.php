<?php
/**
 * Update user
 * @param $update
*/
function update_user($update)
{
    global $ddos_count;

    // Check DDOS count
    if ($ddos_count < 2) {
        // Update the user.
        $userUpdate = update_userdb($update);

        // Write to log.
        debug_log('Update user: ' . $userUpdate);
    }
}


?>
