<?php
namespace App\Controllers;

use App\Modules\Router;
use App\Modules\Auth;
use App\Modules\TelegramApi;

use App\Classes\Message;
use App\Classes\Keyboard;
use App\Classes\Button;

class MainController extends BaseController {

  public function wishes($message, $photos, $text, $keyboard) {
    $user = Auth::user();
    $last_item = Router::last_item();

    if($user->current_page != $last_item['id']) {
      $user->current_page = $last_item['id'];
      $user->save();
      $response = new Message($text, Keyboard::fromTemplate([[Button::$BACK]]));
    } else {
      $username = $user->username;
      $s = "Пользователь $username добавил пожелание.\n\n$message";
      app()->notify(new Message("Пользователь $username добавил пожелание.\n\n$message"));
      $response = (new CommandController())->back('',NULL,'',new Keyboard());
      $response->setText("Спасибо за ваш вклад в развитие нашего сервиса!");
    }
    return $response;
  }

  public function addGame() {
    $user = Auth::user();
    $last_item = Router::last_item();

    if($user->current_page != $last_item['id']) {
      $user->current_page = $last_item['id'];
      $user->save();
      $response = new Message($text, Keyboard::fromTemplate([[Button::$BACK]]));
    } else {
      $username = $user->username;
      app()->notify(new Message("Пользователь $username просит добавить игру:\n$message"));
      $response = (new CommandController())->back('',NULL,'',new Keyboard());
      $response->setText("Спасибо за ваш вклад в развитие нашего сервиса!");
    }
    return $response;
  }

} ?>
