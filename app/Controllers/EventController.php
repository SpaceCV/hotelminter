<?php
namespace App\Controllers;

use App\Classes\Message;
use Ap\Classes\Keyboard;

class EventController extends BaseController {

  public function bonus($message, $photos, $text, $keyboard) {
    $text = "Как наверняка вы могли заметить, после оплаты услуг вам на кошелек прилетает бонус в виде нашей монеты MINTERPAY. Бонус начисляется по принципу -  пополняете например на 100bip в ответ получаете 1000 наших монет. В планах раздать 1 000 000 монет, после того, как все будет роздано мы начнем принимать монеты в качестве оплаты за услуги. За это время мы рассчитываем на то, что монета немного окрепнет и наберет веса. Так что не спешите расставаться с бонусом.";

    $response = new Message($text, $keyboard);
    return $response;
  }
} ?>
