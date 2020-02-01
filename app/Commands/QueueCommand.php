<?php
namespace App\Commands;

use GuzzleHttp\Exception\RequestException;
use App\Modules\Minter_API;
use App\Models\Transaction;
use App\Models\User;
use Minter\SDK\MinterTx;
use Minter\SDK\MinterCoins\MinterSendCoinTx;
use Minter\SDK\MinterCoins\MinterSellAllCoinTx;
use Minter\SDK\MinterCoins\MinterMultiSendTx;

class QueueCommand extends BaseCommand {

  public function execute($args) {
    $transactions = Transaction::limit(10);

    foreach ($transactions as $transaction) {
      $user = User::get($transaction->user_id);
      $nonce = Minter_API::getNonce($user->wallet_address);
      $data = json_decode($transaction->data, true);
      $coin = 'MINTERPAY';

      if($transaction->tx_type == MinterSellAllCoinTx::TYPE) {
        $coin = $data['coinToSell'];
      }

      $tx = new MinterTx([
        'nonce' => $nonce,
        'chainId' => MinterTx::MAINNET_CHAIN_ID, // or MinterTx::TESTNET_CHAIN_ID
        'gasPrice' => 1,
        'gasCoin' => $coin,
        'type' => $transaction->tx_type,
        'data' => $data,
        'payload' => '',
        'serviceData' => '',
        'signatureType' => MinterTx::SIGNATURE_SINGLE_TYPE // or SIGNATURE_MULTI_TYPE
      ]);

      $sign = $tx->sign($user->wallet_private_key);

      try {
        $res = Minter_API::send($sign);
        $transaction->delete();
        var_dump($res);
      } catch (RequestException $e) {
        var_dump($e->getResponse()->getBody()->getContents());
        continue;
      }
      sleep(5);
    }
  }

}
 ?>
