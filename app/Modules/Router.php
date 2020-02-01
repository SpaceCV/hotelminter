<?php
namespace App\Modules;

use App\Modules\Menu;
use App\Classes\Button;

class Router {

  // private static $schema = [];
  private static $last_item = NULL;

  public function __construct() {
    // self::$schema = json_decode(file_get_contents(APP_DIR.'/menu.json'), true);
  }

  public static function route($data, $menu_id = 0) {
    $meta = [];
    if($data == NULL) {
      // $item = self::$schema[$menu_id];
      $item = Menu::get_item($menu_id);
      // $meta['menu'] = array_map(function($elem) {return [$elem];},array_keys($item));
    } else {
      if($data == Button::$BACK || $data == Button::$CANCEL) {
        $item['handler'] = 'CommandController@back';
      } else {
        $item = Menu::get_item($menu_id);
        if(isset($item['menu']) && isset($item['menu'][$data])) {
          $item = Menu::get_item(Menu::get_item($menu_id)['menu'][$data]['menu_id']);
        } else {
          $item = false;
        }
      }
      // $item = self::$schema[self::$schema[$menu_id]['menu'][$data]['menu_id']];
    }

    if($item !== false) {
      $meta['title'] = $item['title'];
      if(isset($item['template'])) {
        $meta['page_id'] = $item['id'];
        $meta['menu'] = $item['template'];
      }
      if(isset($item['handler'])) {
        $meta['handler'] = $item['handler'];
      }
      if(isset($item['message'])) {
        $meta['message'] = $item['message'];
      }

      self::$last_item = $item;

      return $meta;
    } else {
      return $item;
    }

  }

  public static function last_item() {
    return self::$last_item;
  }

} ?>
