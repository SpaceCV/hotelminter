<?php
namespace App\Classes;

class Utils {

  public static function format_number($number, $decs = 2) {
    return number_format($number, $decs, '.', ',');
  }
} ?>
