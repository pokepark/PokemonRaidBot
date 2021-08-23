<?php
/**
 * Check if the user is new (has not completed tutorial)
 * @param $user_id
 * @return bool
 */
function new_user($user_id) {
    global $config;
    if(!$config->TUTORIAL_MODE || user_tutorial($user_id) < $config->TUTORIAL_LEVEL_REQUIREMENT) return true;
    else return false;
}
?>