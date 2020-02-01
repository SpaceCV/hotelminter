<?php
namespace App\Controllers;

use App\Classes\Message;
use App\Classes\Keyboard;
use App\Classes\Button;
use App\Classes\Utils;

use App\Modules\Auth;
use App\Modules\Router;
use App\Modules\Menu;
use App\Modules\Shtrafs;
use App\Modules\TelegramApi;

use App\Models\Settings;
use App\Models\User;
use App\Models\Operation;

class PaymentController extends BaseController {

  public function payment($message, $photos, $text, $keyboard) {

    $user = Auth::user();
    $last_item = Router::last_item();

    if(!isset($last_item)) {
      $last_item = Menu::get_item($user->current_page);
    }

    $currency = 'RUB';
    $photo_available = false;

    if (isset($last_item['data'])) {
      if(isset($last_item['data']['currency'])) {
        $currency = $last_item['data']['currency'];
      }

      if(isset($last_item['data']['foto_available'])) {
        $photo_available = $last_item['data']['foto_available'];
      }
    }

    if($user->current_page != $last_item['id']) {
      $response = [];

      $response[] = new Message($last_item['title'], Keyboard::fromTemplate([[Button::$BACK]]));

      $user->current_page = $last_item['id'];
      // $text = $message;
      // $text = "<i>Выберите номер из списка либо введите вручную</i> <b>\nНапример: \nMTC 894124058</b>";
      $keyboard = new Keyboard(true);

      if(isset($last_item['data']) && isset($last_item['data']['id'])) {
        $data_id = $last_item['data']['id'];
        $sets = $user->settings($data_id);

        foreach ($sets as $val) {
          $keyboard->addButtonLine([['text' => $val->value, 'callback_data' => json_encode(['Payment', 'sel', $val->id])]]);
        }
      }

      $user->save();

      $response[] = new Message($text, $keyboard);
    } else {
      if($user->current_sub_page == 0) {

        if(isset($last_item['data']) && isset($last_item['data']['subscription'])) {
          $text = 'Выберите период на который вы хотите оплатить подписку:';

          $subs = $last_item['data']['subscription'];
          $keyboard = new Keyboard();
          foreach ($subs as $sub) {
            $text = $text."\n<i>${sub[title]} — ${sub[price]}</i>";
            $keyboard->addButtonLine([$sub['title']]);
          }
          $keyboard->addButtonLine([Button::$BACK]);
          $response = new Message($text, $keyboard);
        } else {
          $response = new Message("<b>Введите сумму в $currency, на которую хотите пополнить услугу:</b>");
        }
        $user->tmp = $message;

        if($photo_available && isset($photos) && count($photos) > 0) {
          $user->photo_id = $photos[count($photos) - 1]['file_id'];
        }

        $user->current_sub_page = 1;
        $user->save();
      } elseif ($user->current_sub_page == 1) {

        $summ = false;

        if(isset($last_item['data']) && isset($last_item['data']['subscription'])) {
          var_dump($message);
          $subs = $last_item['data']['subscription'];
          foreach ($subs as $sub) {
            if($sub['title'] == $message) {
              $summ = $sub['price'];
              break;
            }
          }
        } else {
          if(is_numeric($message)) {
            $summ = $message;
          }
        }

        if($summ !== false) {

          $min_pay = getenv('MIN_PAY');

          if($summ < $min_pay) {
            $response = new Message("<b>Вы указали буквы, либо сумму меньшую минимального платежа $min_pay руб.</b>\n<i>Повторите ввод!</i>");
          } else {
            $user->current_sub_page = 2;
            $user->save();

            $amount = RateController::convert_to_minterpay($summ, $currency) + RateController::commission(0.01);

            $response = [];

            $response[] = new Message("С вашего счёта будет списано <b>\n$amount minterpay</b>");
            $balance = WalletController::balance();
            if(isset($balance->MINTERPAY)) {
              if($balance->MINTERPAY < $amount) {
                $r = (new CommandController())->back('',NULL,'', new Keyboard());
                $r->setText("На вашем балансе недосточно средств для оплаты. Необходимо внести ещё ".($amount - $balance->MINTERPAY)." MINTERPAY");
                $response[] = $r;
              } else {
                $keyboard = new Keyboard();
                $keyboard->addButtonLine([Button::$ACCEPT]);
                $keyboard->addButtonLine([Button::$CANCEL]);
                $response[] = new Message("Для подтверждения нажмите кнопку 'Подтвердить'", $keyboard);
                $user->tmp = $user->tmp.'|'.$summ;
                $user->save();
              }
            }
          }
        } else {
          $response = new Message("Сообщение не является числом. Необходимо ввести сумму, на которую вы хотите пополнить.");
        }
      } elseif ($user->current_sub_page == 2 && $message == Button::$ACCEPT) {
        $data = explode('|', $user->tmp);
        $amount = RateController::convert_to_minterpay($data[1], $currency);
        WalletController::send(getenv('MAIN_WALLET'), $amount);
        $oper = new Operation([
          'chat_id' => $user->chat_id,
          'username' => $user->username,
          'data' => $data[0],
          'summ' => $data[1],
          'type' => 2,
          'ok' => 0
        ]);
        $oper->save();

        $title_menu = $last_item['title'];

        $notify_message = new Message("$title_menu\nПользователь: $user->first_name $user->last_name\nРеквизиты: $data[0]\nСумма: $data[1] рублей");
        $notify_keyboard = new Keyboard(true);
        $notify_keyboard->addButtonLine([Button::inline('Оплачено', 'Payment', 'compl', $oper->id)]);
        $notify_message->setKeyboard($notify_keyboard);

        if ($photo_available && $user->photo_id) {
          $notify_message->setPhoto($user->photo_id);
        }

        app()->notify($notify_message);

        $amount = $amount + RateController::commission(0.01);
        $response = (new CommandController())->back('',NULL,'', new Keyboard());
        $amount = Utils::format_number($amount);
        $response->setText("С вашего счета будет списано $amount MINTERPAY. После зачисления средств вы получите уведомление.");
      }
    }

    return $response;

  }

  public function countries($message, $text, $keyboard) {
    $text = "Список стран в которых мы пополняем сотовых операторов. Если вашей страны нет, отправьте запрос во вкладке <b>Пожелания</b>.
================
🇧🇾     |   Беларусь +375
🇷🇺     |   Россия +7
🇺🇦     |   Украина +380
================";
    return (new Message($text));
  }

  public function callback_query($data, $message) {
    if(count($data) > 1) {
      $action = $data[0];
      $value = $data[1];

      if($action == 'sel') {
        $s = Settings::get($value);
        $response = $this->payment($s->value, NULL, '', new Keyboard());
      } elseif ($action == 'compl') {
        $oper = Operation::get($value);
        $oper->ok = 1;
        $oper->save();
        TelegramApi::editMessageReplyMarkup($message['chat']['id'], $message['message_id'], new Keyboard(true));
        TelegramApi::sendMessage($oper->chat_id, new Message("Ваш заказ успешно оплачен."));
      }
    }
    if(!isset($response)) {
      $response = new Message('');
    }

    return $response;
  }

  public function taxes($message, $text, $keyboard) {
    $user = Auth::user();
    $last_item = Router::last_item();


  }

} ?>
