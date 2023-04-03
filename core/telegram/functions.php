<?php
/**
 * Check chat & message id pairs for validity
 * @param $chat_id
 * @param $message_id
 */
function is_valid_target($chat_id, $message_id, $no_chat = false, $no_message = false){
  if($chat_id === null && isset($message_id)) {
  debug_log("Inline message id received, skipping chat id check: {$message_id}");
  return true;
  }
  debug_log("Checking for validity chat_id:{$chat_id} and message_id:{$message_id}");
  // First check that both are numbers, if they are required
  if(!($no_chat || is_numeric($chat_id))) return false;
  if(!($no_message || is_numeric($message_id))) return false;
  // if both were numbers and are non-zero, that's valid
  if($chat_id != 0 && $message_id != 0) return true;
  // if both are zero, that's invalid
  if($chat_id == 0 && $message_id == 0) return false;
  // If allowed, having only one non-zero is fine:
  if($chat_id != 0 && $no_message) return true;
  if($message_id != 0 && $no_chat) return true;
  // Fall back to an error but let the call through
  info_log("chat_id:{$chat_id}, message_id:{$message_id}", 'ERROR: Unhandled pair of chat_id & message_id, this is a bug:');
  return true;
}

/**
 * Send message.
 * @param int $chat_id
 * @param string $text
 * @param mixed $inline_keyboard
 * @param array|bool $merge_args
 * @param array|bool $multicurl
 * @param int|string $identifier
 * @return mixed
*/
function send_message($chat_id, $text = [], $inline_keyboard = false, $merge_args = [], $multicurl = false, $identifier = false)
{
  // Create response content array.
  $reply_content = [
    'method'     => 'sendMessage',
    'chat_id'    => $chat_id,
    'parse_mode' => 'HTML',
    'text'       => $text
  ];
  if(!is_valid_target($chat_id, null, false, true)){
    info_log($chat_id, 'ERROR: Cannot send to invalid chat id:');
    info_log($reply_content, 'ERROR: data would have been:');
    exit();
  }

  // Write to log.
  debug_log('KEYS');
  debug_log($inline_keyboard);

  if ($inline_keyboard != false) {
    $reply_content['reply_markup'] = ['inline_keyboard' => $inline_keyboard];
  }

  if (is_array($merge_args) && count($merge_args)) {
    $reply_content = array_merge_recursive($reply_content, $merge_args);
  }

  // Encode data to json.
  $reply_json = json_encode($reply_content);

  // Set header to json.
  header('Content-Type: application/json');

  // Write to log.
  debug_log($reply_json, '>');

  // Send request to telegram api.
  return curl_request($reply_json, $multicurl, $identifier);
}

/**
 * Send venue.
 * @param $chat_id
 * @param $lat
 * @param $lon
 * @param $title
 * @param $address
 * @param bool $inline_keyboard
 * @param $multicurl
 * @param $identifier
 * @return mixed
 */
function send_venue($chat_id, $lat, $lon, $title, $address, $inline_keyboard = false, $multicurl = false, $identifier = false)
{
  // Create reply content array.
  $reply_content = [
    'method'    => 'sendVenue',
    'chat_id'   => $chat_id,
    'latitude'  => $lat,
    'longitude' => $lon,
    'title'     => $title,
    'address'   => $address
  ];
  if(!is_valid_target($chat_id, null, false, true)){
    info_log($chat_id, 'ERROR: Cannot send to invalid chat id:');
    info_log($reply_content, 'ERROR: data would have been:');
    exit();
  }

  // Write to log.
  debug_log('KEYS');
  debug_log($inline_keyboard);

  if (is_array($inline_keyboard)) {
    $reply_content['reply_markup'] = ['inline_keyboard' => $inline_keyboard];
  }

  // Encode data to json.
  $reply_json = json_encode($reply_content);

  // Set header to json.
  header('Content-Type: application/json');

  // Write to log.
  debug_log($reply_json, '>');

  // Send request to telegram api and return response.
  return curl_request($reply_json, $multicurl, $identifier);
}

/**
 * Echo message.
 * @param $chat_id
 * @param $text
 */
function sendMessageEcho($chat_id, $text)
{
  // Create reply content array.
  $reply_content = [
    'method'     => 'sendMessage',
    'chat_id'    => $chat_id,
    'parse_mode' => 'HTML',
    'text'       => $text
  ];
  if(!is_valid_target($chat_id, null, false, true)){
    info_log($chat_id, 'ERROR: Cannot send to invalid chat id:');
    info_log($reply_content, 'ERROR: data would have been:');
    exit();
  }

  // Encode data to json.
  $reply_json = json_encode($reply_content);

  // Set header to json.
  header('Content-Type: application/json');

  // Write to log.
  debug_log($reply_json, '>');

  // Echo json.
  echo($reply_json);
}

/**
 * Answer callback query.
 * @param int $query_id
 * @param string $text
 * @param bool $multicurl
 */
function answerCallbackQuery($query_id, $text, $multicurl = false)
{
  // Create response array.
  $response = [
    'method'            => 'answerCallbackQuery',
    'callback_query_id' => $query_id,
    'text'              => $text
  ];

  // Encode response to json format.
  $json_response = json_encode($response);

  // Set header to json.
  header('Content-Type: application/json');

  // Write to log.
  debug_log($json_response, '>');

  // Send request to telegram api.
  return curl_request($json_response, $multicurl);
}

/**
 * Answer inline query.
 * @param $query_id
 * @param $contents
 */
function answerInlineQuery($query_id, $contents)
{
  // Init empty result array.
  $results = [];

  // For each content.
  foreach($contents as $key => $row) {
    $text = $contents[$key]['text'];
    $title = $contents[$key]['title'];
    $desc = $contents[$key]['desc'];
    $inline_keyboard = $contents[$key]['keyboard'];

    // Create input message content array.
    $input_message_content = [
      'parse_mode'                => 'HTML',
      'message_text'              => $text,
      'disable_web_page_preview'  => true
    ];

    // Fill results array.
    $results[] = [
      'type'                  => 'article',
      'id'                    => $query_id . $key,
      'title'                 => $title,
      'description'           => $desc,
      'input_message_content' => $input_message_content,
      'reply_markup'          => [
        'inline_keyboard'   => $inline_keyboard
      ]
    ];
  }

  // Create reply content array.
  $reply_content = [
    'method'          => 'answerInlineQuery',
    'inline_query_id' => $query_id,
    'is_personal'     => true,
    'cache_time'      => 10,
    'results'         => $results
  ];

  // Encode to json
  $reply_json = json_encode($reply_content);

  // Send request to telegram api.
  return curl_request($reply_json);
}

/**
 * Edit message.
 * @param $update
 * @param $message
 * @param $keys
 * @param array|bool $merge_args
 * @param $multicurl
 */
function edit_message($update, $message, $keys, $merge_args = false, $multicurl = false)
{
  if (isset($update['callback_query']['inline_message_id'])) {
    return editMessageText($update['callback_query']['inline_message_id'], $message, $keys, NULL, $merge_args, $multicurl);
  }
  return editMessageText($update['callback_query']['message']['message_id'], $message, $keys, $update['callback_query']['message']['chat']['id'], $merge_args, $multicurl);
}

/**
 * Edit message text.
 * @param $id_val
 * @param $text_val
 * @param $markup_val
 * @param null $chat_id
 * @param mixed $merge_args
 * @param $multicurl
 */
function editMessageText($id_val, $text_val, $markup_val, $chat_id = NULL, $merge_args = false, $multicurl = false)
{
  // Create response array.
  $response = [
    'method'        => 'editMessageText',
    'text'          => $text_val,
    'parse_mode'    => 'HTML',
    'reply_markup'  => [
      'inline_keyboard' => $markup_val
    ]
  ];

  if ($markup_val == false) {
    unset($response['reply_markup']);
    $response['remove_keyboard'] = true;
  }

  // Valid chat id.
  if ($chat_id != null) {
    $response['chat_id']  = $chat_id;
    $response['message_id'] = $id_val;
  } else {
    $response['inline_message_id'] = $id_val;
  }

  // Write to log.
  //debug_log($merge_args, 'K');
  //debug_log($response, 'K');

  if (is_array($merge_args) && count($merge_args)) {
    $response = array_merge_recursive($response, $merge_args);
  }

  if(!is_valid_target($chat_id, $id_val, true, false)){
    info_log("{$chat_id}/{$id_val}", 'ERROR: Cannot edit invalid chat/message id:');
    info_log($response, 'ERROR: data would have been:');
    exit();
  }
  debug_log($response, '<-');

  // Encode response to json format.
  $json_response = json_encode($response);
  // Send request to telegram api.
  return curl_request($json_response, $multicurl);
}

/**
 * Edit caption.
 * @param $update
 * @param $message
 * @param $keys
 * @param bool $merge_args
 * @param $multicurl
 */
function edit_caption($update, $message, $keys, $merge_args = false, $multicurl = false)
{
  if (isset($update['callback_query']['inline_message_id'])) {
    $json_response = editMessageCaption($update['callback_query']['inline_message_id'], $message, $keys, NULL, $merge_args, $multicurl);
  } else {
    $json_response = editMessageCaption($update['callback_query']['message']['message_id'], $message, $keys, $update['callback_query']['message']['chat']['id'], $merge_args, $multicurl);
  }
  return $json_response;
}

/**
 * Edit message caption.
 * @param $id_val
 * @param $text_val
 * @param $markup_val
 * @param null $chat_id
 * @param mixed $merge_args
 * @param $multicurl
 */
function editMessageCaption($id_val, $text_val, $markup_val, $chat_id = NULL, $merge_args = false, $multicurl = false)
{
  // Create response array.
  $response = [
    'method'        => 'editMessageCaption',
    'caption'       => $text_val,
    'parse_mode'    => 'HTML',
    'reply_markup'  => [
      'inline_keyboard' => $markup_val
    ]
  ];

  if ($markup_val == false) {
    unset($response['reply_markup']);
    $response['remove_keyboard'] = true;
  }

  // Valid chat id.
  if ($chat_id != null) {
    $response['chat_id']  = $chat_id;
    $response['message_id'] = $id_val;
  } else {
    $response['inline_message_id'] = $id_val;
  }

  if (is_array($merge_args) && count($merge_args)) {
    $response = array_merge_recursive($response, $merge_args);
  }

  if(!is_valid_target($chat_id, $id_val, true, false)){
    info_log("{$chat_id}/{$id_val}", 'ERROR: Cannot edit invalid chat/message id:');
    info_log($response, 'ERROR: data would have been:');
    exit();
  }
  debug_log($response, '<-');

  // Encode response to json format.
  $json_response = json_encode($response);

  // Send request to telegram api.
  return curl_request($json_response, $multicurl);
}

/**
 * Edit message reply markup.
 * @param $id_val
 * @param $markup_val
 * @param $chat_id
 * @param $multicurl
 */
function editMessageReplyMarkup($id_val, $markup_val, $chat_id, $multicurl = false)
{
  // Create response array.
  $response = [
    'method'        => 'editMessageReplyMarkup',
    'reply_markup'  => [
      'inline_keyboard' => $markup_val
    ]
  ];

  // Valid chat id.
  if ($chat_id != null) {
    $response['chat_id'] = $chat_id;
    $response['message_id'] = $id_val;

  } else {
    $response['inline_message_id'] = $id_val;
  }
  if(!is_valid_target($chat_id, $id_val)){
    info_log("{$chat_id}/{$id_val}", 'ERROR: Cannot edit invalid chat/message id:');
    info_log($response, 'ERROR: data would have been:');
    exit();
  }
  debug_log($response, '->');
   // Encode response to json format.
  $json_response = json_encode($response);

  // Send request to telegram api.
  return curl_request($json_response, $multicurl);
}

/**
 * Edit message keyboard.
 * @param $id_val
 * @param $markup_val
 * @param $chat_id
 * @param $multicurl
 */
function edit_message_keyboard($id_val, $markup_val, $chat_id, $multicurl = false)
{
  // Create response array.
  $response = [
    'method'        => 'editMessageReplyMarkup',
    'reply_markup'  => [
      'inline_keyboard' => $markup_val
    ]
  ];

  // Valid chat id.
  if ($chat_id != null) {
    $response['chat_id'] = $chat_id;
    $response['message_id'] = $id_val;

  } else {
    $response['inline_message_id'] = $id_val;
  }
  if(!is_valid_target($chat_id, $id_val)){
    info_log("{$chat_id}/{$id_val}", 'ERROR: Cannot edit invalid chat/message id:');
    info_log($response, 'ERROR: data would have been:');
    exit();
  }

  // Encode response to json format.
  $json_response = json_encode($response);

  // Write to log.
  debug_log($response, '->');

  // Send request to telegram api.
  return curl_request($json_response, $multicurl);
}

/**
 * Delete message
 * @param $chat_id
 * @param $message_id
 * @param $multicurl
 */
function delete_message($chat_id, $message_id, $multicurl = false)
{
  // Create response content array.
  $reply_content = [
    'method'      => 'deleteMessage',
    'chat_id'     => $chat_id,
    'message_id'  => $message_id,
  ];
  if(!is_valid_target($chat_id, $message_id)){
    info_log("{$chat_id}/{$message_id}", 'ERROR: Cannot delete invalid chat/message id:');
    info_log($reply_content, 'ERROR: data would have been:');
    exit();
  }

  // Encode data to json.
  $reply_json = json_encode($reply_content);

  // Set header to json.
  header('Content-Type: application/json');

  // Write to log.
  debug_log($reply_json, '>');

  // Send request to telegram api.
  return curl_request($reply_json, $multicurl);
}

/**
 * GetChat
 * @param $chat_id
 * @param $multicurl
 */
function get_chat($chat_id, $multicurl = false)
{
  // Create response content array.
  $reply_content = [
    'method'   => 'getChat',
    'chat_id'  => $chat_id,
  ];
  if(!is_valid_target($chat_id, null, false, true)){
    info_log($chat_id, 'ERROR: Cannot get invalid chat id:');
    info_log($reply_content, 'ERROR: data would have been:');
    exit();
  }

  // Encode data to json.
  $reply_json = json_encode($reply_content);

  // Set header to json.
  header('Content-Type: application/json');

  // Write to log.
  debug_log($reply_json, '>');

  // Send request to telegram api.
  return curl_request($reply_json, $multicurl);
}

/**
 * GetChatAdministrators
 * @param $chat_id
 * @param $multicurl
 */
function get_admins($chat_id, $multicurl = false)
{
  // Create response content array.
  $reply_content = [
    'method'   => 'getChatAdministrators',
    'chat_id'  => $chat_id,
  ];
  if(!is_valid_target($chat_id, null, false, true)){
    info_log($chat_id, 'ERROR: Cannot get invalid chat id:');
    info_log($reply_content, 'ERROR: data would have been:');
    exit();
  }

  // Encode data to json.
  $reply_json = json_encode($reply_content);

  // Set header to json.
  header('Content-Type: application/json');

  // Write to log.
  debug_log($reply_json, '>');

  // Send request to telegram api.
  return curl_request($reply_json, $multicurl);
}

/**
 * GetChatMember
 * @param $chat_id
 * @param $user_id
 * @param $multicurl
 */
function get_chatmember($chat_id, $user_id, $multicurl = false)
{
  // Create response content array.
  $reply_content = [
    'method'   => 'getChatMember',
    'chat_id'  => $chat_id,
    'user_id'  => $user_id,
  ];
  if(!is_valid_target($chat_id, null, false, true)){
    info_log($chat_id, 'ERROR: Cannot get invalid chat id:');
    info_log($reply_content, 'ERROR: data would have been:');
    exit();
  }

  // Encode data to json.
  $reply_json = json_encode($reply_content);

  // Set header to json.
  header('Content-Type: application/json');

  // Write to log.
  debug_log($reply_json, '>');

  // Send request to telegram api.
  return curl_request($reply_json, $multicurl);
}

/**
 * Send photo.
 * @param $chat_id
 * @param string $media_content content of the media file.
 * @param bool $content_type true = photo file, false = file_id
 * @param string|bool $text
 * @param array|bool $inline_keyboard
 * @param array $merge_args
 * @param array|bool $multicurl
 * @param int|bool $identifier
 */
function send_photo($chat_id, $media_content, $content_type, $text = '', $inline_keyboard = false, $merge_args = [], $multicurl = false, $identifier = false)
{
  // Create response content array.
  $post_contents = [
    'method'        => 'sendPhoto',
    'chat_id'       => $chat_id,
    'reply_markup'  => json_encode(['inline_keyboard' => $inline_keyboard]),
  ];
  if($text != '') {
    $post_contents['caption'] = $text;
    $post_contents['parse_mode'] = 'HTML';
  }
  if(!is_valid_target($chat_id, null, false, true)){
    info_log($chat_id, 'ERROR: Cannot send to invalid chat id:');
    info_log(print_r($post_contents, true), 'ERROR: data would have been:');
    exit();
  }

  $post_contents['photo'] = ($content_type) ? new CURLStringFile($media_content, 'photo') : $media_content;

  debug_log($inline_keyboard, 'KEYS:');

  if (is_array($merge_args) && count($merge_args)) {
    $post_contents = array_merge_recursive($post_contents, $merge_args);
  }

  // Don't log the binary portion
  $log_contents = array_merge(array(), $post_contents);
  $log_contents['photo'] = '[binary data]';
  debug_log(print_r($log_contents, true), '>');

  // Send request to telegram api.
  return curl_request($post_contents, $multicurl, $identifier);
}

/**
 * Edit message media and text.
 * @param $id_val
 * @param $text_val
 * @param string $media_content content of the media file.
 * @param bool $content_type true = photo file, false = file_id/url
 * @param $markup_val
 * @param null $chat_id
 * @param mixed $merge_args
 * @param $multicurl
 */
function editMessageMedia($id_val, $text_val, $media_content, $content_type, $inline_keyboard = false, $chat_id = NULL, $merge_args = false, $multicurl = false, $identifier = false)
{
  // Create response array.
  $post_contents = [
    'method'      => 'editMessageMedia',
    'media'       => [
      'type'      => 'photo',
      'caption'   => $text_val,
      'parse_mode'=> 'HTML'
    ]
  ];

  if ($inline_keyboard !== false) {
    $post_contents['reply_markup'] = json_encode(['inline_keyboard' => $inline_keyboard]);
  }else {
    $post_contents['remove_keyboard'] = true;
  }
  if ($chat_id != null) {
    $post_contents['chat_id']  = $chat_id;
    $post_contents['message_id'] = $id_val;
  } else {
    $post_contents['inline_message_id'] = $id_val;
  }
  if (is_array($merge_args) && count($merge_args)) {
    $post_contents = array_merge_recursive($post_contents, $merge_args);
  }
  if(!is_valid_target($chat_id, $id_val, true, false)){
    info_log("{$chat_id}/{$id_val}", 'ERROR: Cannot edit media of invalid chat/message id:');
    info_log(print_r($post_contents, true), 'ERROR: data would have been:');
    exit();
  }

  // Encode response to json format.
  if($content_type) {
    $post_contents['photo'] = new CURLStringFile($media_content, 'photo');
    $post_contents['media']['media'] = 'attach://photo';
  } else {
    $post_contents['media']['media'] = $media_content;
  }
  $post_contents['media'] = json_encode($post_contents['media']);
  debug_log("Editing message ${post_contents['message_id']} media: ${post_contents['media']}", '->');

  // Send request to telegram api.
  return curl_request($post_contents, $multicurl, $identifier);
}

/**
 * Send request to telegram api - single or multi?.
 * @param $post_contents
 * @param $multicurl
 * @param $identifier
 * @return mixed
 */
function curl_request($post_contents, $multicurl = false, $identifier = false)
{

  // Send request to telegram api.
  if($multicurl == true) {
    return ['post_contents' => $post_contents, 'identifier' => $identifier];
  } else {
    return curl_json_request($post_contents, $identifier);
  }
}

/**
 * Send request to telegram api.
 * @param $post_contents
 * @param $identifier
 * @return mixed
 */
function curl_json_request($post_contents, $identifier)
{
  global $config;

  // Telegram
  $URL = 'https://api.telegram.org/bot' . API_KEY . '/';
  $curl = curl_init($URL);

  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  if(!is_array($post_contents)) curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $post_contents);
  curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($curl, CURLOPT_TIMEOUT, 10);

  // Use Proxyserver for curl if configured
  if ($config->CURL_USEPROXY && !empty($config->CURL_PROXYSERVER)) {
    curl_setopt($curl, CURLOPT_PROXY, $config->CURL_PROXYSERVER);
  }

  // Write to log.
  if(is_array($post_contents)) debug_log(print_r($post_contents,true), '->');
  else debug_log($post_contents, '->');

  // Execute curl request.
  $json_response = curl_exec($curl);

  if($json_response === false) {
     info_log(curl_error($curl));
  }

  // Close connection.
  curl_close($curl);

  // Process response from telegram api.
  responseHandler($json_response, $post_contents);

  $responseArray = json_decode($json_response, true);
  collectCleanup($responseArray, $post_contents, $identifier);

  // Return response.
  return $responseArray;
}

/**
 * Send multi request to telegram api.
 * @param $json
 * @return mixed
 */
function curl_json_multi_request($json)
{
  global $config;
  // Set URL.
  $URL = 'https://api.telegram.org/bot' . API_KEY . '/';

  // Curl handles.
  $curly = array();

  // Curl response.
  $response = array();

  // Init multi handle.
  $mh = curl_multi_init();

  // Loop through json array, create curl handles and add them to the multi-handle.
  foreach ($json as $id => $data) {
    // Init.
    $curly[$id] = curl_init();

    // Curl options.
    curl_setopt($curly[$id], CURLOPT_URL, $URL);
    curl_setopt($curly[$id], CURLOPT_HEADER, false);
    if(!is_array($data['post_contents'])) curl_setopt($curly[$id], CURLOPT_HTTPHEADER, array("Content-type: application/json"));
    curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curly[$id], CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curly[$id], CURLOPT_TIMEOUT, 10);

    // Use Proxyserver for curl if configured.
    if($config->CURL_USEPROXY && !empty($config->CURL_PROXYSERVER)) {
      curl_setopt($curly[$id], CURLOPT_PROXY, $config->CURL_PROXYSERVER);
    }

    // Curl post.
    curl_setopt($curly[$id], CURLOPT_POST,     true);
    curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $data['post_contents']);

    // Add multi handle.
    curl_multi_add_handle($mh, $curly[$id]);

    // Don't log the binary data of a photo
    $content = $data['post_contents'];
    $log_content = $content;
    if(is_array($content)) {
      if(is_object($content['photo']) or isBinary($content['photo']) ) {
        $log_content = array_merge([],$content);
        $log_content['photo'] = '[binary content]';
      }
    } else {
      if(isBinary($content)) {
        $log_content = '[binary content]';
      }
    }
    if(is_array($log_content)) debug_log(print_r($log_content, true), '->');
    else debug_log($log_content, '->');
  }

  // Get content and remove handles.
  $retry = false;
  $maxRetries = 3;
  $retryCount = 0;
  $sleep = 0;
  do {
    // On the second pass and onwards sleep before executing curls
    if($retry === true) {
    $retry = false;
    $sleep = 0;
    $retryCount++;
    debug_log('Retrying in '.$sleep.' seconds');
    sleep($sleep);
    debug_log('Retry count: '.($retryCount).'...');
    }

    // Execute the handles.
    $running = null;
    do {
    $status = curl_multi_exec($mh, $running);
    curl_multi_select($mh);
    } while($running > 0 && $status === CURLM_OK);

    if ($status != CURLM_OK) {
    info_log(curl_multi_strerror($status));
    }

    foreach($curly as $id => $content) {
    $response[$id] = curl_multi_getcontent($content);
    curl_multi_remove_handle($mh, $content);
    $responseResults = responseHandler($response[$id], $json[$id]['post_contents']);
    // Handle errors
    if(is_array($responseResults) && $responseResults[0] === 'retry') {
      $retry = true;
      // Use the highest sleep value returned by TG
      $sleep = $responseResults[1] > $sleep ? $responseResults[1] : $sleep;
      // Re-add this handle with the same info
      curl_multi_add_handle($mh, $curly[$id]);
      continue;
    }

    unset($curly[$id]);
    }
  }while($retry === true && $retryCount < $maxRetries);

  $responseArrays = [];
  // Process response from telegram api.
  foreach($response as $id => $json_response) {
    $responseArrays[$id] = json_decode($response[$id], true);
    collectCleanup($responseArrays[$id], $json[$id]['post_contents'], $json[$id]['identifier']);
  }

  // Return response.
  return $responseArrays;
}
if($metrics) {
  $tg_response_code = $metrics->registerCounter($namespace, 'tg_response_count', 'Counters of response codes from Telegram', ['code', 'method', 'description']);
}

/**
 * Determine whether the given value is a binary string by checking to see if it has detectable character encoding.
 * Non-strings are treated as binary.
 *
 * @param string $value
 *
 * @return bool
 */
function isBinary($value): bool
{
  if(is_string($value)){
    return false === mb_detect_encoding((string)$value, null, true);
  }
  return true;
}

/**
 * Process response from Telegram.
 * @param $jsonResponse string JSON string returned by Telegram
 * @param $request string|array The request we sent to Telegram
 * @return mixed
 */
function responseHandler($jsonResponse, $request) {
  global $metrics, $tg_response_code;
  // Write to log.
  debug_log_incoming($jsonResponse, '<-');

  // Decode json objects
  $request_array = is_array($request) ? $request : json_decode($request, true);
  $response = json_decode($jsonResponse, true);
  if ($metrics){
    $code = 200;
    $method = $request_array['method'];
    $description = null;
    if (isset($response['error_code'])) {
      $code = $response['error_code'];
      # We have to also include the description because TG overloads error codes
      $description = $response['description'];
    }
    $tg_response_code->inc([$code, $method, $description]);
  }
  // Validate response.
  if ((isset($response['ok']) && $response['ok'] != true) || isset($response['update_id'])) {
    if(is_array($request)) $json = json_encode($request); else $json = $request;
    // Handle some specific errors
    if($response['description'] == 'Bad Request: message to edit not found' || $response['description'] == 'Bad Request: message to delete not found') {
      // Loop through tables where we store sent messages
      $table = ['cleanup', 'overview', 'trainerinfo'];
      $i = 0;
      do {
        $q = my_query('DELETE FROM '.$table[$i].' WHERE chat_id = :chatId AND message_id = :messageId',['chatId' => $request_array['chat_id'], ':messageId' => $request_array['message_id']]);
        $i++;
      } while($q->rowCount() == 0 && $i < count($table));
      info_log($table[$i-1], 'A message was deleted by someone else than us. Deleting info from database table:');
      info_log($request_array['chat_id'], 'chat_id:');
      info_log($request_array['message_id'], 'message_id:');
      return true;
    }
    if(substr($response['description'], 0, 30) == 'Too Many Requests: retry after') {
      return [
        'retry',
        ($response['parameters']['retry_after'] + 1),
      ];
    }
    info_log("{$json} -> {$jsonResponse}", 'ERROR:');
    // Log unhandled errors
    return false;
  }
  return true;
}
