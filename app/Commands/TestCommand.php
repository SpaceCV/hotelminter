<?php
namespace App\Commands;

use App\Modules\Minter_API;
use App\Models\User;
use App\Controllers\WalletController;
use App\Modules\TelegramApi;

class TestCommand extends BaseCommand {

  public function execute($args) {
    echo "test\n";
  }
} ?>
