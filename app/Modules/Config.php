<?php
namespace App\Modules;

class Config {

  public static function get($key) {
    return getenv(mb_strtoupper($key));
  }

  public static function get_rate($currency) {
    $currency = mb_strtoupper($currency);
    return getenv("BIP_${currency}_RATE");
  }
} ?>
