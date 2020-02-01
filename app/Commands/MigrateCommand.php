<?php
namespace App\Commands;

use App\Models\User;
use Minter\SDK\MinterWallet;

class MigrateCommand extends BaseCommand {

  public function execute($args) {
    $users = User::all();

    foreach ($users as $user) {

      if(!$user->wallet_address) {
        $wallet = MinterWallet::create();
        $user->wallet_mnemonic = $wallet['mnemonic'];
        $user->wallet_private_key = $wallet['private_key'];
        $user->wallet_address = $wallet['address'];
        $user->save();
      }
    }
  }
} ?>
