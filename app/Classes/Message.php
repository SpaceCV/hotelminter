<?php
namespace App\Classes;

class Message {

  private $text = '';
  private $keyboard = NULL;
  private $photo_id = NULL;

  public function __construct($text, $keyboard = NULL) {
    $this->text = $text;
    $this->keyboard = $keyboard;
  }

  public function setKeyboard($keyboard) {
    $this->keyboard = $keyboard;
  }

  public function setText($text) {
    $this->text = $text;
  }

  public function setPhoto($photo_id) {
    $this->photo_id = $photo_id;
  }

  public function getPhoto() {
    return $this->photo_id;
  }

  public function hasPhoto() {
    return !!$this->photo_id;
  }

  public function text() {
    return $this->text;
  }

  public function keyboard() {
    return $this->keyboard;
  }
} ?>
