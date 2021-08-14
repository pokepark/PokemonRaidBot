<?php
/**
 * Update user.
 * @param $update
 * @return bool|mysqli_result
 */
function update_userdb($update)
{
    global $dbh;

    $name = '';
    $nick = '';
    $sep = '';
    $lang = '';

    if (isset($update['message']['from'])) {
        $msg = $update['message']['from'];
    }

    if (isset($update['callback_query']['from'])) {
        $msg = $update['callback_query']['from'];
    }

    if (isset($update['inline_query']['from'])) {
        $msg = $update['inline_query']['from'];
    }

    if (!empty($msg['id'])) {
        $id = $msg['id'];

    } else {
        debug_log('No id', '!');
        debug_log($update, '!');
        return false;
    }

    if ($msg['first_name']) {
        $name = $msg['first_name'];
        $sep = ' ';
    }

    if (isset($msg['last_name'])) {
        $name .= $sep . $msg['last_name'];
    }

    if (isset($msg['username'])) {
        $nick = $msg['username'];
    }

    if (isset($msg['language_code'])) {
        $lang = $msg['language_code'];
    }

    // Create or update the user.
    $stmt = $dbh->prepare(
        "
        INSERT INTO users
        SET         user_id = :id,
                    nick    = :nick,
                    name    = :name,
                    lang    = :lang
        ON DUPLICATE KEY
        UPDATE      nick    = :nick,
                    name    = :name,
                    lang    = IF(lang_manual = 1, lang, :lang)
        "
    );
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':nick', $nick);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':lang', $lang);
    $stmt->execute();

    return 'Updated user ' . $nick;
}
?>
