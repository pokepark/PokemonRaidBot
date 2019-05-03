<?php
// Check access.
bot_access_check($update, 'help');

$msg = '';
$msg .= '<b>Raids</b>' . CR;
$msg .= CR;
$msg .= '/start - Raid anlegen' . CR;
$msg .= CR;
$msg .= '/delete - Raid löschen' . CR;
$msg .= CR;
$msg .= '/pokemon - Pokemon aktualisieren' . CR;
$msg .= CR;
$msg .= '/list - Alle aktiven Raids anzeigen, updaten, <b>erneut teilen</b> und löschen' . CR;
$msg .= CR;
$msg .= CR;
$msg .= '<b>Quests</b>' . CR;
$msg .= CR;
$msg .= '📎  Standort teilen - Quest anlegen' . CR;
$msg .= CR;
$msg .= '/quest <i>Pfalztheater</i> - Quest für Stop "Pfalztheater" anlegen' . CR;
$msg .= CR;
$msg .= '/questdelete - Quest löschen' . CR;
$msg .= CR;
$msg .= '/questlist - Alle heutigen Quests';
sendMessage($update['message']['from']['id'], $msg);
?>

