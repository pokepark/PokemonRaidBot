<?php

function list_config_chats_by_short_id() {
  global $config;
  if(!isset($config->CHATS_SHARE)) {
    $chat_vars = ['TRAINER_CHATS','SHARE_CHATS','WEBHOOK_CHATS'];
    $chatsTemp = [];
    foreach(array_keys($config) as $var) {
      foreach($chat_vars as $start) {
        if(!strpos($var, $start)) continue;
        if(is_string($config->{$var})) {
          array_merge($chatsTemp, explode(',', $config->{$var}));
          continue;
        }
        array_merge($chatsTemp, $config->{$var});
      }
    }
    $chats = [];
    foreach($chatsTemp as $chat) {
      $chats[] = create_chat_object($chat);
    }
  }else {
    $chats = [];
    if(isset($config->CHATS_SHARE['manual_share'])) {
      foreach($config->CHATS_SHARE['manual_share'] as $chatGroup) {
        foreach($chatGroup as $chat) {
          $chats = add_chat($chats, $chat);
        }
      }
    }
    if(isset($config->CHATS_SHARE['after_attendance'])) {
      foreach($config->CHATS_SHARE['after_attendance'] as $chat) {
        $chats = add_chat($chats, $chat);
      }
    }
    if(isset($config->CHATS_SHARE['webhook'])) {
      if(isset($config->CHATS_SHARE['webhook']['all'])) {
        foreach($config->CHATS_SHARE['webhook']['all'] as $chat) {
          $chats = add_chat($chats, $chat);
        }
      }
      if(isset($config->CHATS_SHARE['webhook']['by_pokemon'])) {
        foreach($config->CHATS_SHARE['webhook']['by_pokemon'] as $chatGroup) {
          foreach($chatGroup['chats'] as $chat) {
            $chats = add_chat($chats, $chat);
          }
        }
      }
      if(isset($config->CHATS_SHARE['webhook']['geofences'])) {
        foreach($config->CHATS_SHARE['webhook']['geofences'] as $geofence) {
          foreach($geofence as $chatGroup) {
            foreach($chatGroup as $chat) {
              $chats = add_chat($chats, $chat);
            }
          }
        }
      }
    }
  }
  return $chats;
}

function add_chat($chats, $chatToAdd) {
  foreach($chats as $chat) {
    if(
      $chat['id'] == $chatToAdd['id'] && !isset($chat['thread']) ||
      $chat['id'] == $chatToAdd['id'] && isset($chat['thread']) && isset($chatToAdd['thread']) && $chat['thread'] == $chatToAdd['thread']
    ) return $chats;
  }
  $chats[] = $chatToAdd;
  return $chats;
}

function get_config_chat_by_short_id($id) {
  $chats = list_config_chats_by_short_id();
  return $chats[$id];
}
