<?php
namespace App\Models;

use App\Modules\DB;
use App\Models\Settings;
use Minter\SDK\MinterWallet;
use Minter\SDK\MinterCoins\MinterSellAllCoinTx;

class User extends BaseModel {
  protected static $table = 'users';
  protected static $props = [
    'id',
    'chat_id',
    'username',
    'first_name',
    'last_name',
    'step',
    'current_page',
    'current_sub_page',
    'time',
    'ban',
    'root',
    'tmp',
    'photo_id',
    'wallet_mnemonic',
    'wallet_private_key',
    'wallet_address'
  ];

  public static function create($params) {
    if(!isset($params['wallet_mnemonic'])) {
      $wallet = MinterWallet::create();
      $params['wallet_mnemonic'] = $wallet['mnemonic'];
      $params['wallet_private_key'] = $wallet['private_key'];
      $params['wallet_address'] = $wallet['address'];
    }
    $user = new User($params);
    $user->save();
    return $user;
  }

  public static function get_by_chat_id($chat_id) {
    $table = self::table();
    return DB::getRow("SELECT * FROM $table WHERE `chat_id` = :chat_id",['chat_id' => $chat_id], get_called_class());
  }

  public function settings($data_id = NULL) {
    $table = Settings::table();
    $params = ['chat_id' => $this->chat_id];
    $query = "SELECT * FROM $table where chat_id = :chat_id";

    if(isset($data_id)) {
      $query = $query." AND data = :data_id";
      $params['data_id'] = $data_id;
    }

    return DB::getRows($query, $params, Settings::class);
  }

  public function active_exchange_transaction_count() {
    $table = self::table('transactions');
    return DB::getValue("SELECT COUNT(*) from $table where tx_type = :tx_type and user_id = :user_id;", ['user_id' => $this->id, 'tx_type' => MinterSellAllCoinTx::TYPE]);
  }
} ?>
