<?php
/**
 * Check if the user has completed the tutorial
 * @param $user_id
 * @return bool
 */
function new_user($user_id) {
    global  $dbh;
	debug_log("Checking for new user: ".$user_id);
    try {
        $query = "SELECT tutorial FROM users WHERE user_id = :user_id LIMIT 1";
        $statement = $dbh->prepare( $query );
        $statement->execute([":user_id"=>$user_id]);
        $res = $statement->fetch();
        debug_log("Result: ".$res['tutorial']);
        if($res['tutorial'] == 0) {
            return true;
        }        
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        $dbh = null;
        exit;
    }
	return false;
}
?>