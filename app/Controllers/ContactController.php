<?php
namespace App\Controllers;

use App\Classes\Message;
use App\Classes\Keyboard;

class ContactController extends BaseController {

  public function chanel($message, $photos, $text, $keyboard) {
    $text = "На нашем канале вы сможете узнать, какие акции у нас проходят наши планы на будущее и дальнейшее развитие и внедрние нашей монеты MINTERPAY в повседневную жизнь. @MinterPay";

    $response = new Message($text, $keyboard);
    return $response;
  }
} ?>
