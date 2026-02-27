<?php
/**
 * Format utc date from datetime value.
 * @param $datetime_value
 * @return string
 */
function utcdate($datetime_value)
{
  // Create a object with UTC timezone
  $datetime = new DateTime($datetime_value, new DateTimeZone('UTC'));

  return $datetime->format('Y-m-d');
}

/**
 * Get current utc datetime.
 * @param $format
 * @return string
 */
function utcnow($format = 'Y-m-d H:i:s')
{
  // Create a object with UTC timezone
  $datetime = new DateTime('now', new DateTimeZone('UTC'));

  return $datetime->format($format);
}

/**
 * Format utc time from datetime value.
 * @param $datetime_value
 * @param $format
 * @return string
 */
function utctime($datetime_value, $format = 'H:i')
{
  // Create a object with UTC timezone
  $datetime = new DateTime($datetime_value, new DateTimeZone('UTC'));

  return $datetime->format($format);
}

/**
 * Get date from datetime value.
 * @param $datetime_value
 * @param $tz
 * @return string
 */
function dt2date($datetime_value, $tz = NULL)
{
  global $config;
  if($tz == NULL){
    $tz = $config->TIMEZONE;
  }
  // Create a object with UTC timezone
  $datetime = new DateTime($datetime_value, new DateTimeZone('UTC'));

  // Change the timezone of the object without changing it's time
  $datetime->setTimezone(new DateTimeZone($tz));

  return $datetime->format('Y-m-d');
}

/**
 * Get time from datetime value.
 * @param $datetime_value
 * @param $format
 * @param $tz
 * @return string
 */
function dt2time($datetime_value, $format = 'H:i', $tz = NULL)
{
  global $config;
  if($tz == NULL){
    $tz = $config->TIMEZONE;
  }
  // Create a object with UTC timezone
  $datetime = new DateTime($datetime_value, new DateTimeZone('UTC'));

  // Change the timezone of the object without changing it's time
  $datetime->setTimezone(new DateTimeZone($tz));

  return $datetime->format($format);
}

function tz_diff($src = 'UTC', $dst = NULL) {
  global $config;
  if($dst == NULL){
    $dst = $config->TIMEZONE;
  }
  $dateTimeZoneSrc = new DateTimeZone($src);
  $dateTimeZoneDst = new DateTimeZone($dst);
  $dateTimeSrc = new datetime('now',$dateTimeZoneSrc);
  $diff = $dateTimeZoneDst->getOffset($dateTimeSrc);

  $hours = str_pad(floor($diff/60/60),2,'0',STR_PAD_LEFT);
  $minutes = str_pad(floor(($diff-$hours*60*60)/60),2,'0',STR_PAD_LEFT);
  if($diff < 0) {
    return '-'.$hours.':'.$minutes;
  }
  return '+'.$hours.':'.$minutes;
}
