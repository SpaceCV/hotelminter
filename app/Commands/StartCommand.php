<?php
namespace App\Commands;

use App\Modules\TelegramApi;

class StartCommand extends BaseCommand {

  public function execute($args) {
    var_dump(TelegramApi::setWebhook(getenv('WEBHOOK_URL')));
  }

}
?>
