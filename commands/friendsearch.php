<?php
// Write to log.
debug_log('FRIENDSEARCH()');

// For debug.
//debug_log($update);
//debug_log($data);

$botUser->accessCheck('friendsearch');

// Trim away everything before "/FRIENDSEARCH"
$searchterm = $update['message']['text'];
$searchterm = preg_replace('/[^A-Za-z0-9]/','', substr($searchterm, 14));

debug_log($searchterm, 'SEARCHTERM');

$query = my_query('SELECT user_id, name, team, level, trainername FROM users WHERE trainername LIKE :tn', [':tn' => $searchterm]);
if($query->rowCount() == 1) {
  $result = $query->fetch();
  $msg = ($result['team'] === NULL) ? ($GLOBALS['teams']['unknown'] . ' ') : ($GLOBALS['teams'][$result['team']] . ' ');
  $msg .= ($result['level'] == 0) ? ('<b>00</b> ') : (($result['level'] < 10) ? ('<b>0' . $result['level'] . '</b> ') : ('<b>' . $result['level'] . '</b> '));
  $msg .= '<a href="tg://user?id=' . $result['user_id'] . '">' . $result['name'] . ' - ' . $result['trainername'] . '</a>';
}else {
  $msg = $searchterm.CR. getTranslation('trainer_not_found');
}
send_message($update['message']['chat']['id'], $msg, [], ['reply_markup' => ['selective' => true]]);
