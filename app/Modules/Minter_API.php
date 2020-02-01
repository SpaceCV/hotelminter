<?php
namespace App\Modules;

use Minter\MinterAPI;

class Minter_API {

  private static $api = NULL;

  public function __construct($node_url) {
    self::$api = new MinterAPI($node_url);
  }

  public static function __callStatic($method, $args) {
    return call_user_func_array([self::$api, $method], $args);
  }
} ?>
