<?php
namespace App\Controllers;

use App\Modules\Minter_API;
use App\Modules\Auth;
use App\Models\Transaction;
use Minter\SDK\MinterTx;
use Minter\SDK\MinterCoins\MinterSendCoinTx;
use Minter\SDK\MinterCoins\MinterSellAllCoinTx;
use Minter\SDK\MinterCoins\MinterMultiSendTx;
use GuzzleHttp\Exception\RequestException;

class WalletController extends BaseController {

  public static function balance($user = NULL) {
    if(!isset($user)) {
      $user = Auth::user();
    }
    $balance = Minter_API::getBalance($user->wallet_address)->result->balance;
    foreach ($balance as $coin => $value) {
      $balance->{$coin} = $value / 1000000000000000000;
    }
    if(!isset($balance->MINTERPAY)) {
      $balance->MINTERPAY = 0;
    }
    return $balance;
  }

  public static function send($address, $amount, $user = NULL) {
    if(!isset($user)) {
      $user = Auth::user();
    }

    $tx_type = MinterSendCoinTx::TYPE;
    $data = [];

    if(is_array($address)) {
      $addresses = array_combine($address, $amount);
      $data['list'] = [];
      $tx_type = MinterMultiSendTx::TYPE;
      foreach ($addresses as $addr => $am) {
        $data['list'][] = [
          'coin' => 'MINTERPAY',
          'to' => $addr,
          'value' => $am
        ];
      }
    } else {
      $data = [
          'coin' => 'MINTERPAY',
          'to' => $address,
          'value' => $amount
      ];
    }

    $transaction = new Transaction([
      'user_id' => $user->id,
      'tx_type' => $tx_type,
      'data' => json_encode($data)
    ]);
    $transaction->save();
    // 1000000000000000000
  }

  public static function exchange_all($user = NULL) {
    if(!isset($user)) {
      $user = Auth::user();
    }
    $balances = Minter_API::getBalance($user->wallet_address)->result->balance;

    $needed = false;

    foreach ($balances as $coin => $amount) {
      if($coin == 'MINTERPAY') {
        continue;
      }

      if($amount > 0) {
        $needed = true;
        $transaction = new Transaction([
          'user_id' => $user->id,
          'tx_type' => MinterSellAllCoinTx::TYPE,
          'data' => json_encode([
            'coinToSell' => $coin,
            'coinToBuy' => 'MINTERPAY',
            'minimumValueToBuy' => 0
          ]),
        ]);
        $transaction->save();
      }

    }
    return $needed;
  }
} ?>
