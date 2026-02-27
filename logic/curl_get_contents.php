<?php
/**
 * Get content why curl_exec.
 * @param $url string URL
 * @return string
 */
function curl_get_contents($url)
{
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)" );
  if(!$content = curl_exec($ch)) {
    info_log(curl_error($ch));
    return false;
  }
  curl_close($ch);
  return $content;
}
