<?php
namespace App\Controllers;

use App\Modules\Menu;
use App\Modules\Auth;
use App\Classes\Message;
use App\Classes\Keyboard;

class CommandController extends BaseController {

  public function start($message, $photos, $text, $keyboard) {
    $user = Auth::user();
    $text = "<b>Здравствуйте, $user->first_name!</b>\nВыберите услугу которой хотите воспользоваться.";
    $keyboard = Keyboard::fromTemplate(Menu::get_item(0)['template']);
    $user->current_page = 0;
    $user->current_sub_page = 0;
    $user->save();
    $response = new Message($text, $keyboard);
    return $response;
  }

  public function back($message, $photos, $text, $keyboard) {
    $user = Auth::user();
    $item = Menu::get_item($user->current_page);
    if(isset($item['parent_id'])) {
      $parent_item = Menu::get_item($item['parent_id']);
      $text = isset($parent_item['message']) ? $parent_item['message'] : $parent_item['title'];
      $keyboard = Keyboard::fromTemplate($parent_item['template']);
      $user->current_page = $parent_item['id'];
      $user->current_sub_page = 0;
      $user->save();
    }
    $response = new Message($text, $keyboard);
    return $response;
  }

} ?>
