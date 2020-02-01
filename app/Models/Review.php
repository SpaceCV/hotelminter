<?php
namespace App\Models;

use App\Modules\DB;

class Review extends BaseModel {

  protected static $table = 'feedback';

  protected static $props = [
    'id', 'chat_id', 'username', 'data', 'activ'
  ];

  public static function random() {
    $table = self::table();
    return DB::getRow("SELECT * FROM $table where `activ` = 1 ORDER BY RAND() LIMIT 1;",[], get_called_class());
  }

} ?>
