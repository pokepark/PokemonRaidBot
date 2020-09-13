<?php
/**
 * Get content why curl_exec.
 * @param $url
 * @return string
 */
function curl_get_contents($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, "Googlebot/2.1 (+http://www.google.com/bot.html)" );
    $content = curl_exec($ch);
    curl_close($ch);
    return $content;
}

?>
