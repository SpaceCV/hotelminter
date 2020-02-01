<?php

namespace App\Classes;

class Keyboard {

  private $inline = false;
  private $buttons = [];

  public function __construct($inline = false) {
    $this->inline = $inline;
  }

  public function type() {
    if($this->inline) {
      return 'inline_keyboard';
    } else {
      return 'keyboard';
    }
  }

  public function addButtonLine($line) {
    $this->buttons[] = $line;
  }

  public function buttons() {
    return $this->buttons;
  }

  public static function fromTemplate($template) {
    $keyboard = new Keyboard();
    foreach ($template as $line) {
      $keyboard->addButtonLine($line);
    }
    return $keyboard;
  }


} ?>
