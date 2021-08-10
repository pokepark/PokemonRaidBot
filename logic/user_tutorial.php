<?php
/**
 * Return the tutorial value from users table
 * @param $user_id
 * @return int
 */
function user_tutorial($user_id) {
    global $dbh;
    debug_log("Reading user's tutorial value: ".$user_id);
    try {
        $query = "SELECT tutorial FROM users WHERE user_id = :user_id LIMIT 1";
        $statement = $dbh->prepare( $query );
        $statement->execute([":user_id"=>$user_id]);
        $res = $statement->fetch();
        if($statement->rowCount() > 0) $result = $res['tutorial'];
        else $result = 0;
        debug_log("Result: ".$result);
        return $result;
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        $dbh = null;
        exit;
    }
}
?>