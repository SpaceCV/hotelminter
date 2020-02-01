<?php
namespace App\Controllers;

use App\Modules\Router;
use App\Modules\Auth;
use App\Modules\TelegramApi;
use App\Models\Review;
use App\Classes\Message;
use App\Classes\Keyboard;
use App\Classes\Button;

class ReviewController extends BaseController {

  public function write($message, $photos, $text, $keyboard) {
    $last_item = Router::last_item();
    $user = Auth::user();

    $response = new Message($text, $keyboard);

    if($user->current_page != $last_item['id']) {
      $text = "<b>📝 Написать отзыв</b>\nВы пополнял через нас какую-либо услугу и вам понравилось, как мы сработали, черкните пару строк. Что-то не понравилось тоже пишите, нам важно знать ваше мнение и да, мы публикуем все отзывы. Написать отзыв! 👇";
      $keyboard = new Keyboard();
      $keyboard->addButtonLine([Button::$CANCEL]);
      $user->current_page = $last_item['id'];
      $user->save();
      $response->setText($text);
      $response->setKeyboard($keyboard);
    } else {
      $review = new Review([
        'chat_id' => $user->chat_id,
        'username' => $user->username,
        'data' => $message,
        'activ' => 0
      ]);
      $review->save();
      $response = [];
      $response[] = new Message('<b>Ваш отзыв отправлен на модерацию!</b>\n🕓 <i>Дождитесь подтверждения.</i>');
      // TelegramApi::sendMessage($user->chat_id, '<b>Ваш отзыв отправлен на модерацию!</b>\n🕓 <i>Дождитесь подтверждения.</i>', []);

      $notify_message = new Message("Пользователь $user->first_name $user->last_name добавил отзыв:\n\n$message");
      $notify_keyboard = new Keyboard(true);

      $notify_keyboard->addButtonLine([Button::inline('Принять', 'Review', 'acpt', $review->id), Button::inline('Отклонить', 'Review', 'dec', $review->id)]);
      $notify_message->setKeyboard($notify_keyboard);

      app()->notify($notify_message);
      
      $response[] = (new CommandController())->back('',NULL,'',[]);

    }

    return $response;
  }

  public function read($message, $photos, $text, $keyboard) {
    $review = Review::random();
    if($review) {
      $text = $review->data."\n\n💬 Автор: @".$review->username;
    } else {
      $text = "Отзывов нет";
    }

    $response = new Message($text, $keyboard);
    return $response;
  }
}
?>
