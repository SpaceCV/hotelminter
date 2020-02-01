<?php
namespace App\Controllers;

use App\Classes\Button;
use App\Classes\Keyboard;
use App\Classes\Message;
use App\Classes\Utils;

use App\Modules\Auth;
use App\Modules\Router;
use App\Modules\TelegramApi;
use App\Modules\Minter_API;

use App\Models\Settings;
use App\Models\User;
use App\Models\Transaction;

class SettingsController extends BaseController {

  public function sets($message, $photos, $text, $keyboard) {
    // $text = '';
    $user = Auth::user();
    $last_item = Router::last_item();
    $data_id = $last_item['data']['id'];

    $response = new Message($text, $keyboard);

    // $text = $user->current_page.' '.$last_item['id'];

    if($user->current_page != $last_item['id']) {

      $user->current_page = $last_item['id'];
      $user->save();
      $response = $this->info_page($message, $photos, $text, $keyboard, $user, $data_id);

    } else {
      if($user->current_sub_page == 0) {
        if($message == Button::$ADD) {
          $response->setText($text);
          $response->setKeyboard(Keyboard::fromTemplate([[Button::$BACK]]));
          $user->current_sub_page = 1;
          $user->save();
        }
      } elseif ($user->current_sub_page == 1) {
        if($message) {
          $set = new Settings(['chat_id' => $user->chat_id, 'data' => $data_id, 'value' => $message]);
          $set->save();
          $user->current_sub_page = 0;
          $user->save();
          $response = (new CommandController())->back('',NULL,'',[]);
          $response->setText('Запись добавлена');
        }
      } elseif ($user->current_sub_page == 2) {
        $set = Settings::get($user->tmp);
        $set->value = $message;
        $set->save();
        $user->current_sub_page = 0;
        $user->save();
        $response = new Message('Шаблон успешно изменён!');
      }
    }

    return $response;
  }

  private function info_page($message, $photos, $text, $keyboard, $user, $data_id) {

    $response = [];

    // var_dump(Keyboard::fromTemplate([[[Button::$ADD]],[[Button::$BACK]]]));

    $response[] = new Message($text, Keyboard::fromTemplate([[Button::$ADD],[Button::$BACK]]));

    $settings = $user->settings($data_id);

    if(count($settings) > 0) {
      foreach ($settings as $val) {
        $edit_action = ['Settings', 'edit', $val->id];
        $del_action = ['Settings', 'delete', $val->id];


        $keyboard = new Keyboard(true);
        $keyboard->addButtonLine([
          ['text' => Button::$EDIT, 'callback_data' => json_encode($edit_action)],
          ['text' => Button::$DELETE, 'callback_data' => json_encode($del_action)]
        ]);

        $response[] = new Message($val->value, $keyboard);
      }
    } else {
      $response[] = new Message('<b>Сохраненных шаблонов в базе еще нет!</b>');
    }

    return $response;
  }

  public function wallet($message, $photos, $text, $keyboard) {
    $user = Auth::user();
    // $last_item = Router::last_item();

    $balance = Minter_API::getBalance($user->wallet_address)->result->balance;

    $response = [];

    $response[] = new Message('💰 Ваш кошелёк: 💰');
    $response[0]->setKeyboard($keyboard);
    $response[] = new Message("<code>$user->wallet_address</code>");
    // $balances = '';
    //
    // foreach ($balance as $coin => $value) {
    //   $balances = $balances."\n".$coin.': '.$value / 1000000000000000000;
    // }

    $amount = 0;

    if(isset($balance->MINTERPAY)) {
      $amount = $balance->MINTERPAY/ 1000000000000000000;
    }


    $amount = Utils::format_number($amount, 4);
    $response[] = new Message("Баланс: $amount MINTERPAY");
    // $keyboard = new Keyboard();
    // $keyboard->addButtonLine(['Я пополнил!']);
    // $keyboard->addButtonLine([Button::$BACK]);
    // $response[0]->setKeyboard($keyboard);

    return $response;
  }

  public function wallet_repl($message, $photos, $text, $keyboard) {
    $user = Auth::user();

    if($user->active_exchange_transaction_count() > 0) {
      $response = (new CommandController())->back('',NULL,'',new Keyboard());
      $response->setText("Баланс обновляется... Пожалуйста ожидайте.");
    } else {
      $needed = WalletController::exchange_all();
      $response = (new CommandController())->back('',NULL,'',new Keyboard());
      if($needed) {
        $await_time = ceil(Transaction::count() / 10);
        $response->setText("Пожалуйста ожидайте. Баланс будет обновлён в течении $await_time минут");
      } else {
        $response->setText("Баланс актуален.");
      }

    }

    return $response;
  }

  public function transfer($message, $photos, $text, $keyboard) {
    // return new Message('В разработке... Скоро добавим :)');
    $user = Auth::user();
    $last_item = Router::last_item();

    if($user->current_page != $last_item['id']) {
      $user->current_page = $last_item['id'];
      $user->current_sub_page = 0;
      $user->save();

      $keyboard = new Keyboard();
      // $keyboard->addButtonLine(['Перевести']);
      $keyboard->addButtonLine([Button::$BACK]);

      $response = new Message($text, $keyboard);
    } else {
      if($user->current_sub_page == 0) {
        if(strlen($message) == 42 && substr($message, 0, 2) === "Mx") {
          $user->current_sub_page = 1;
          $user->tmp = $message;
          $user->save();
          $response = new Message('Введите сумму, которую желаете перевести.');
        } else {
          $response = new Message('Некорректный адрес. Проверьте правильность введённого адреса.');
        }
      } elseif ($user->current_sub_page == 1) {
        if(is_numeric($message)) {
          $balance = WalletController::balance();
          $tx_fee = RateController::commission(0.015);
          $amount = $message + $tx_fee;
          if($balance->MINTERPAY > $amount) {
            $amount = $amount - $tx_fee;
            $comis = $amount / 100 * 10;
            $amount = $amount - $comis;
            $addrs = [$user->tmp, getenv('MAIN_WALLET')];
            $amounts = [$amount, $comis];
            WalletController::send($addrs, $amounts);
            $response = (new CommandController())->back('',NULL,'',new Keyboard());
            $response->setText('Перевод будет выполнен в течении '.Transaction::await_time().' минут');
          } else {
            $response = new Message('На вашем счету недосточно средств. Введите другую сумму');
          }
        } else {
          $response = new Message('Введите число. Повторите ввод.');
        }
      }
    }

    return $response;
  }

  public function callback_query($data, $message) {
    $action = $data[0];
    $val_id = $data[1];
    $user = Auth::user();

    if($action == 'delete') {
      $set = Settings::get($val_id)->delete();
      $response = new Message('Шаблон удалён');
    } elseif ($action == 'edit') {
      $user->tmp = $val_id;
      $user->current_sub_page = 2;
      $user->save();
      $response = new Message('<b>Введите новое значение шаблона:</b>');
    }

    return $response;
  }
} ?>
