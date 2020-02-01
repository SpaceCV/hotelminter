<?php
namespace App\Models;

class Settings extends BaseModel {

  protected static $table = 'settings';
  protected static $props = ['id', 'chat_id', 'data', 'value'];
  
} ?>
