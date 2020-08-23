<?php
/**
 * Raid access check.
 * @param $update
 * @param $data
 * @return bool
 */
function raid_access_check($update, $data, $permission, $return_result = false)
{
    // Default: Deny access to raids
    $raid_access = false;

    // Build query.
    $rs = my_query(
        "
        SELECT    user_id
        FROM      raids
        WHERE     id = {$data['id']}
        "
    );

    $raid = $rs->fetch();

    // Check permissions
    if ($update['callback_query']['from']['id'] != $raid['user_id']) {
        // Check "-all" permission
        debug_log('Checking permission:' . $permission . '-all');
        $permission = $permission . '-all';
        $raid_access = bot_access_check($update, $permission, $return_result);
    } else {
        // Check "-own" permission
        debug_log('Checking permission:' . $permission . '-own');
        $permission_own = $permission . '-own';
        $permission_all = $permission . '-all';
        $raid_access = bot_access_check($update, $permission_own, true);

        // Check "-all" permission if we get "access denied"
        // Maybe necessary if user has only "-all" configured, but not "-own"
        if(!$raid_access) {
            debug_log('Permission check for ' . $permission_own . ' failed! Maybe the access is just granted via ' . $permission . '-all ?');
            debug_log('Checking permission:' . $permission_all);
            $raid_access = bot_access_check($update, $permission_all, $return_result);
        } else {
            $raid_access = bot_access_check($update, $permission_own, $return_result);
        }
    }

    // Return result
    return $raid_access;
}


?>
