<?php
/**
 * Check if the user is new (has not completed tutorial)
 * @param $user_id
 * @return bool
 */
function new_user($user_id) {
    if(user_tutorial($user_id) == 0 ) return true;
    else return false;
}
?>