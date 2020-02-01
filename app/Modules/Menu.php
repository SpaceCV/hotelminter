<?php
namespace App\Modules;

class Menu {
  private static $schema = [];

  public function __construct() {
    if(file_exists(APP_DIR.'/menu.json')) {
      self::$schema = json_decode(file_get_contents(APP_DIR.'/menu.json'), true);
    }
  }

  public static function get_item($id) {
    if(isset(self::$schema[$id])) {
      return self::$schema[$id];
    }
    return NULL;
  }
} ?>
