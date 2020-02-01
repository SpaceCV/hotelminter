<?php
namespace App\Models;

class Transaction extends BaseModel {

  protected static $table = 'transactions';
  protected static $props = ['id', 'user_id', 'tx_type', 'data'];

  public static function await_time() {
    return ceil(self::count() / 10);
  }
} ?>
