<?php
namespace App\Modules;

class Auth {
  private static $user = NULL;

  public function __construct($user) {
    self::$user = $user;
  }

  public static function user() {
    return self::$user;
  }
} ?>
