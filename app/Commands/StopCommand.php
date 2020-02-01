<?php
namespace App\Commands;

use App\Modules\TelegramApi;

class StopCommand extends BaseCommand {

  public function execute($args) {
    var_dump(TelegramApi::setWebhook(''));
  }

}
?>
