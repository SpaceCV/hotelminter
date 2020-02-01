<?php
namespace App\Models;

class Operation extends BaseModel {

  protected static $table = 'operations';
  protected static $props = ['id', 'chat_id', 'username', 'data', 'summ', 'type', 'ok'];

} ?>
