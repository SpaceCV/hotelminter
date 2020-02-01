<?php
namespace App\Modules;

use \GuzzleHttp\Client;
use App\Classes\Keyboard;
use App\Classes\Message;

class TelegramApi {

  private static $http_client = NULL;

  public function __construct($token) {
    self::$http_client = new Client([
        // Base URI is used with relative requests
        // 'base_uri' => "https://api.telegram.org/bot${token}/",
        'base_uri' => getenv('API_URL'),
    ]);
  }

  public static function invoke($method, $params = []) {
    try {
      return json_decode(self::$http_client->request('POST', $method, [
        'json' => $params
      ])->getBody()->getContents());
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        echo "ERROR\n";
        var_dump($e->getResponse()->getBody()->getContents());
    }

  }

  public static function getMe() {
    return self::invoke('getMe');
  }

  public static function setWebhook($url) {
    return self::invoke('setWebhook', ['url' => $url]);
  }

  public static function sendMessage($chat_id, $message, $parse_mode = 'HTML') {

    if(!$message->text()) {
      return false;
    }

    $keyboard = $message->keyboard();

    $data = [
      'chat_id' => $chat_id,
      'text' => $message->text(),
      'parse_mode' => $parse_mode
    ];

    if(isset($keyboard) && count($keyboard->buttons()) > 0) {
      $data['reply_markup'] = [
        $keyboard->type() => $keyboard->buttons(),
        'one_time_keyboard' => false,
        'resize_keyboard' => true
      ];
    }

    return self::invoke('sendMessage', $data);

  }

  public static function sendPhoto ($chat_id, $message) {

    if(!$message->text()) {
      return false;
    }

    $keyboard = $message->keyboard();

    $data = [
      'chat_id' => $chat_id,
      'caption' => $message->text(),
      'photo' => $message->getPhoto()
    ];

    if(isset($keyboard) && count($keyboard->buttons()) > 0) {
      $data['reply_markup'] = [
        $keyboard->type() => $keyboard->buttons(),
        'one_time_keyboard' => false,
        'resize_keyboard' => true
      ];
    }

    return self::invoke('sendPhoto', $data);
  }

  public static function editMessageReplyMarkup($chat_id, $message_id, $keyboard) {

    $data = [
      'chat_id' => $chat_id,
      'message_id' => $message_id,
      'reply_markup' => [
        $keyboard->type() => $keyboard->buttons(),
        'one_time_keyboard' => false,
        'resize_keyboard' => true
      ]
    ];

    return self::invoke('editMessageReplyMarkup', $data);
  }
} ?>
