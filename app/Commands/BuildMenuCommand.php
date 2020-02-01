<?php
namespace App\Commands;

class BuildMenuCommand extends BaseCommand {

  private $id = 0;
  private $menu = [];

  public function execute($args) {
    echo "Starting building menu from schema.jsom...\n";
    $schema = json_decode(file_get_contents(APP_DIR.'/schema.json'));
    if ($schema == NULL) {
      echo "Invalid json. Building menu failed.\n";
      return;
    }

    $this->eachMenu($schema);
    file_put_contents(APP_DIR.'/menu.json', json_encode($this->menu, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "Building menu finished. Saved to menu.json\n";
  }

  private function eachMenu($item, $parent_id = -1) {

    if(!isset($item->id)) {
      $item->id = $this->id++;
    }

    if($parent_id > -1) {
      $item->parent_id = $parent_id;
    }

    if(isset($item->id)) {
      $item_data = [];
      if(isset($item->childs)) {
        $elems = [];
        $template = [];
        foreach ($item->childs as $child) {
          if(!isset($child->id)) {
            $child->id = $this->id++;
          }
          $meta = [];
          if(isset($child->id)) {
            $meta['menu_id'] = $child->id;
          }

          $row = $child->row;
          $col = $child->col;

          if(!isset($template[$row])) {
            $template[$row] = [];
          }
          $template[$row][$col] = $child->title;


          // if($parent_id >= 0) {
          //   $meta['parent_id'] = $parent_id;
          // }
          $elems[$child->title] = $meta;
        }
        if($item->id > 0) {
          $template[] = ['Назад 🔙'];
        }

        $item_data['menu'] = $elems;
        $item_data['template'] = $template;
      }

      $item_data['id'] = $item->id;
      $item_data['title'] = $item->title;

      if(isset($item->message)) {
        $item_data['message'] = $item->message;
      }

      if(isset($item->parent_id)) {
        $item_data['parent_id'] = $item->parent_id;
      }

      if(isset($item->handler)) {
        $item_data['handler'] = $item->handler;
      }

      if(isset($item->data)) {
        $item_data['data'] = $item->data;
      }

      $this->menu[$item->id] = $item_data;
      if(isset($item->childs)) {
        foreach ($item->childs as $child) {
          $this->eachMenu($child, $item->id);
        }
      }
    }
  }
} ?>
