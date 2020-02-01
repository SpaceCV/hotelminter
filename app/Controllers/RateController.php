<?php
namespace App\Controllers;

use App\Modules\MinterExplorerAPI;
use App\Modules\Router;
use App\Classes\Message;
use App\Classes\Keyboard;

use App\Modules\Minter_API;
use App\Modules\Config;

class RateController extends BaseController {

  // public $currencys = [
  //   'RUB',
  //   'UAH'
  // ];

  // public $rates = [];


  // public function __construct() {
  //   foreach ($this->currencys as $currency) {
  //     $val = getenv("BIP_${currency}_RATE");
  //     if ($val !== false) {
  //       $this->rates[$currency] = $val;
  //     }
  //   }
  // }

  public function rate($message, $photos, $text, $keyboard) {
    $coin = MinterExplorerAPI::coins('MINTERPAY')[0];

    $last_item = Router::last_item();
    $title = $last_item['title'];

    $mint_bip = $coin->reserveBalance / $coin->volume;
		$mint_bip = $mint_bip / $coin->crr;
		$mint_bip = $mint_bip*100;
		$mint_bip = number_format($mint_bip, 6, '.', '');
		$mint_bip = bcdiv(10, $mint_bip, 0);

		$bip = 10*getenv('BIP_RUB_RATE');
		$text = "$title
================
10 bip = $bip ₽
10 bip = $mint_bip minterpay
================";

    $response = new Message($text, $keyboard);
    return $response;
  }

  public static function convert_to_minterpay($amount, $currency) {
    $coin = MinterExplorerAPI::coins('MINTERPAY')[0];
    $mint_bip = $coin->reserveBalance / $coin->volume;
		$mint_bip = $mint_bip / $coin->crr;
		$mint_bip = $mint_bip*100;
		$mint_bip = number_format($mint_bip, 6, '.', '');
		$mint_bip = bcdiv(1, $mint_bip, 0);

    $rate = bcdiv($amount, Config::get_rate($currency), 2);

    return $rate * $mint_bip;
  }

  public static function commission($value = 0.01) {
    $rate = Minter_API::estimateCoinSell('BIP', 1, 'MINTERPAY')->result->will_get;
    return $rate * $value;
  }
} ?>
