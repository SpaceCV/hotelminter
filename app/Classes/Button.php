<?php
namespace App\Classes;

class Button {

  public static $CANCEL = 'Отмена 🔙';
  public static $BACK = 'Назад 🔙';
  public static $EDIT = 'Редактировать ✏️';
  public static $DELETE = 'Удалить ⛔️';
  public static $ADD = 'Добавить ➕';
  public static $ACCEPT = 'Подтвердить';

  public static function inline($text, $controller, $action, $data = NULL) {
    $callback_data = [$controller, $action];
    if (isset($data)) {
      $callback_data[] = $data;
    }

    return [
      'text' => $text,
      'callback_data' => json_encode($callback_data)
    ];
  }

} ?>
