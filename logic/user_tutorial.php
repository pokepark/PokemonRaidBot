<?php
/**
 * Return the tutorial value from users table
 * @param $user_id
 * @return int
 */
function user_tutorial($user_id) {
    global  $dbh;
	debug_log("Checking for new user: ".$user_id);
    try {
        $query = "SELECT tutorial FROM users WHERE user_id = :user_id LIMIT 1";
        $statement = $dbh->prepare( $query );
        $statement->execute([":user_id"=>$user_id]);
        $res = $statement->fetch();
        debug_log("Result: ".$res['tutorial']);
        return $res['tutorial'];
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        $dbh = null;
        exit;
    }
}
?>