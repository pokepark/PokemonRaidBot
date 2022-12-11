<?php
// Write to log.
debug_log('bot_lang()');

// For debug.
//debug_log($update);
//debug_log($data);

// Access check.
$botUser->accessCheck('trainer');

$keys = [];

if(isset($data['l'])) {
  $query = my_query('
    UPDATE  users
    SET     lang_manual = 1,
            lang= :lang
    WHERE   user_id = :user_id
    ',[
      'lang' => $data['l'],
      'user_id' => $update['callback_query']['from']['id'],
    ]);
  $new_lang_internal = $languages[$data['l']];
  $msg = getTranslation('new_lang_saved', $new_lang_internal);
  $keys[] = [
    [
      'text'          => getTranslation('back', $new_lang_internal),
      'callback_data' => 'trainer'
    ],
    [
      'text'          => getTranslation('done', $new_lang_internal),
      'callback_data' => formatCallbackData(['exit', 'd' => '1'])
    ]
  ];
  $callback_msg = $msg;
} else {
  $displayedLanguages = [];
  foreach($languages as $lang_tg => $lang_internal) {
    if(in_array($lang_internal, $displayedLanguages)) continue;
    $keys[][] = [
      'text'          => getTranslation('lang_name', $lang_internal),
      'callback_data' => formatCallbackData(['bot_lang', 'l' => $lang_tg])
    ];
    $displayedLanguages[] = $lang_internal;
  }
  $keys[] = [
    [
      'text'          => getTranslation('back'),
      'callback_data' => 'trainer'
    ],
    [
      'text'          => getTranslation('done'),
      'callback_data' => formatCallbackData(['exit', 'd' => '1'])
    ]
  ];
  $msg = getTranslation('change_lang').':';
  $callback_msg = getTranslation('change_lang');
}

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_msg);

// Edit message.
edit_message($update, $msg, $keys, false);
