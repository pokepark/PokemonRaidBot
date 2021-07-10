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
 * @param $chat_id
 * @param array $text
 * @param $multicurl
 */
function sendMessage($chat_id, $text = [], $multicurl = false)
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

    if (isset($inline_keyboard)) {
        $reply_content['reply_markup'] = ['inline_keyboard' => $inline_keyboard];
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
 * Send message.
 * @param $chat_id
 * @param array $text
 * @param mixed $inline_keyboard
 * @param array $merge_args
 * @param $multicurl
 */
function send_message($chat_id, $text = [], $inline_keyboard = false, $merge_args = [], $multicurl = false)
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

    if (isset($inline_keyboard)) {
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
    return curl_request($reply_json, $multicurl);
}

/**
 * Send location.
 * @param $chat_id
 * @param $lat
 * @param $lon
 * @param bool $inline_keyboard
 * @param $multicurl
 * @return mixed
 */
function send_location($chat_id, $lat, $lon, $inline_keyboard = false, $multicurl = false)
{
    // Create reply content array.
    $reply_content = [
        'method'    => 'sendLocation',
        'chat_id'   => $chat_id,
        'latitude'  => $lat,
        'longitude' => $lon
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
    return curl_request($reply_json, $multicurl);
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
 * @return mixed
 */
function send_venue($chat_id, $lat, $lon, $title, $address, $inline_keyboard = false, $multicurl = false)
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
    return curl_request($reply_json, $multicurl);
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
 * @param $query_id
 * @param $text
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
                'inline_keyboard' => $inline_keyboard
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
 * @param bool $merge_args
 * @param $multicurl
 */
function edit_message($update, $message, $keys, $merge_args = false, $multicurl = false)
{
    if (isset($update['callback_query']['inline_message_id'])) {
        $json_response = editMessageText($update['callback_query']['inline_message_id'], $message, $keys, NULL, $merge_args, $multicurl);
    } else {
        $json_response = editMessageText($update['callback_query']['message']['message_id'], $message, $keys, $update['callback_query']['message']['chat']['id'], $merge_args, $multicurl);
    }
    return $json_response;
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
        $response['chat_id']    = $chat_id;
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
        $response['chat_id']    = $chat_id;
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
        'method' => 'editMessageReplyMarkup',
        'reply_markup' => [
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
        'method' => 'editMessageReplyMarkup',
        'reply_markup' => [
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
        'method'     => 'deleteMessage',
        'chat_id'    => $chat_id,
        'message_id' => $message_id,
        'parse_mode' => 'HTML',
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
        'method'     => 'getChat',
        'chat_id'    => $chat_id,
        'parse_mode' => 'HTML',
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
        'method'     => 'getChatAdministrators',
        'chat_id'    => $chat_id,
        'parse_mode' => 'HTML',
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
        'method'     => 'getChatMember',
        'chat_id'    => $chat_id,
        'user_id'    => $user_id,
        'parse_mode' => 'HTML',
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
 * @param $photo_url
 * @param array $text
 * @param mixed $inline_keyboard
 * @param array $merge_args
 * @param array $multicurl
 */
function send_photo($chat_id, $photo_url, $text = array(), $inline_keyboard = false, $merge_args = [], $multicurl = false)
{
    // Create response content array.
    $reply_content = [
        'method'     => 'sendPhoto',
        'chat_id'    => $chat_id,
        'photo'      => $photo_url,
        'parse_mode' => 'HTML',
        'caption'    => $text
    ];
    if(!is_valid_target($chat_id, null, false, true)){
      info_log($chat_id, 'ERROR: Cannot send to invalid chat id:');
      info_log($reply_content, 'ERROR: data would have been:');
      exit();
    }

    debug_log($inline_keyboard, 'KEYS:');

    if (isset($inline_keyboard)) {
        $reply_content['reply_markup'] = ['inline_keyboard' => $inline_keyboard];
    }

    if (is_array($merge_args) && count($merge_args)) {
        $reply_content = array_merge_recursive($reply_content, $merge_args);
    }

    // Encode data to json.
    $reply_json = json_encode($reply_content);
    header('Content-Type: application/json');

    debug_log($reply_json, '>');

    // Send request to telegram api.
    return curl_request($reply_json, $multicurl);
}

/**
 * Edit message text.
 * @param $id_val
 * @param $text_val
 * @param $markup_val
 * @param null $chat_id
 * @param mixed $merge_args
 * @param $multicurl
 * @param $url
 */
function editMessageMedia($id_val, $text_val, $markup_val, $chat_id = NULL, $merge_args = false, $multicurl = false, $url)
{
    // Create response array.
    $response = [
        'method'        => 'editMessageMedia',
        'media'         => [
          'type'      => 'photo',
          'media'     => $url,
          'caption'   => $text_val,
          'parse_mode'=> 'HTML'
        ],
        'reply_markup'  => [
          'inline_keyboard' => $markup_val
        ]
    ];

    if ($markup_val == false) {
        unset($response['reply_markup']);
        $response['remove_keyboard'] = true;
    }
    if ($chat_id != null) {
        $response['chat_id']    = $chat_id;
        $response['message_id'] = $id_val;
    } else {
        $response['inline_message_id'] = $id_val;
    }
    if (is_array($merge_args) && count($merge_args)) {
        $response = array_merge_recursive($response, $merge_args);
    }
    if(!is_valid_target($chat_id, $id_val, true, false)){
      info_log("{$chat_id}/{$id_val}", 'ERROR: Cannot edit media of invalid chat/message id:');
      info_log($response, 'ERROR: data would have been:');
      exit();
    }

    // Encode response to json format.
    $json_response = json_encode($response);
    debug_log($response, '<-');

    // Send request to telegram api.
    return curl_request($json_response, $multicurl);
}

/**
 * Send request to telegram api - single or multi?.
 * @param $json
 * @param $multicurl
 * @return mixed
 */
function curl_request($json, $multicurl = false)
{

    // Send request to telegram api.
    if($multicurl == true) {
        return $json;
    } else {
        return curl_json_request($json);
    }
}

/**
 * Send request to telegram api.
 * @param $json
 * @return mixed
 */
function curl_json_request($json)
{
    global $config;
    // Bridge mode?
    if($config->BRIDGE_MODE) {
        // Add bot folder name to callback data
        debug_log('Adding bot folder name "' . basename(ROOT_PATH) . '" to callback data');
        $search = '"callback_data":"';
        $replace = $search . basename(ROOT_PATH) . ':';
        $json = str_replace($search,$replace,$json);
    }

    // Telegram
    $URL = 'https://api.telegram.org/bot' . API_KEY . '/';
    $curl = curl_init($URL);

    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);

    // Use Proxyserver for curl if configured
    if ($config->CURL_USEPROXY && !empty($config->CURL_PROXYSERVER)) {
      curl_setopt($curl, CURLOPT_PROXY, $config->CURL_PROXYSERVER);
    }

    // Write to log.
    debug_log($json, '->');

    // Execute curl request.
    $json_response = curl_exec($curl);

    // Close connection.
    curl_close($curl);

    // Process response from telegram api.
    $response = curl_json_response($json_response, $json);

    // Return response.
    return $response;
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

    // Init $data as array - since php 5.2 the CURLOPT_POSTFIELDS wants an array
    $data = array();

    // Loop through json array, create curl handles and add them to the multi-handle.
    foreach ($json as $id => $data) {
        // Init.
        $curly[$id] = curl_init();

        // Curl options.
        curl_setopt($curly[$id], CURLOPT_URL, $URL);
        curl_setopt($curly[$id], CURLOPT_HEADER, false);
        curl_setopt($curly[$id], CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curly[$id], CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curly[$id], CURLOPT_TIMEOUT, 10);

        // Use Proxyserver for curl if configured.
        if($config->CURL_USEPROXY && !empty($config->CURL_PROXYSERVER)) {
            curl_setopt($curl, CURLOPT_PROXY, $config->CURL_PROXYSERVER);
        }

        // Bridge mode?
        if($config->BRIDGE_MODE) {
            // Add bot folder name to callback data
            debug_log('Adding bot folder name "' . basename(ROOT_PATH) . '" to callback data');
            $search = '"callback_data":"';
            $replace = $search . basename(ROOT_PATH) . ':';
            array_push($data, str_replace($search,$replace,$data));
        }

        // Curl post.
        curl_setopt($curly[$id], CURLOPT_POST,       true);
        curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $data);

        // Add multi handle.
        curl_multi_add_handle($mh, $curly[$id]);

        // Write to log.
        debug_log($data, '->');
    }

    // Execute the handles.
    $running = null;
    do {
        curl_multi_select($mh);
        curl_multi_exec($mh, $running);
    } while($running > 0);

    // Get content and remove handles.
    foreach($curly as $id => $content) {
        $response[$id] = curl_multi_getcontent($content);
        curl_multi_remove_handle($mh, $content);
    }

    // Close connection.
    curl_multi_close($mh);

    // Process response from telegram api.
    foreach($response as $id => $json_response) {
        // Bot specific funtion to process response from telegram api.
        if (function_exists('curl_json_response')) {
            $response[$id] = curl_json_response($json_response, $response[$id]);
        } else {
            info_log('No function found to process response from Telegram API!', 'ERROR:');
            info_log('Add a function named "curl_json_response" to process them!', 'ERROR:');
            info_log('Arguments of that function need to be the response $json_response and the send data $json.', 'ERROR:');
            info_log('For example: function curl_json_response($json_response, $json)', 'ERROR:');
        }
        debug_log_incoming($json_response, '<-');
    }

    // Return response.
    return $response;
}

