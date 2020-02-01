<?php
namespace App\Models;

use \JsonSerializable;
use App\Modules\DB;

class BaseModel implements JsonSerializable {

  protected static $table = '';
  protected static $props = [];
  protected static $fillable = [];
  protected static $hidden = [];
  protected static $relations = [];

  public function __construct($values = []) {
    // $this->db = app()->db;
    foreach (static::$props as $prop) {
      if(!property_exists($this, $prop)) {
        $this->{$prop} = array_key_exists($prop, $values) ? $values[$prop] : null;
      }
    }
  }

  public static function table($table_name = false) {
    if(!$table_name) {
      $table_name = static::$table;
    }
    return '%prefix%'.$table_name;
  }

  public static function query($params) {

    $table = self::table();

    $select_columns = '*';

    $limit = '';
    $order = '';

    $props = static::$props;

    if(isset($params['columns'])) {
      $select_columns = implode(', ', $params['columns']);
    }

    if(isset($params['order'])) {
      $cols = [];

      // foreach ($params['order'] as $key => $value) {
      //   $cols[] = $key.' '.$value;
      // }
      $cols = implode(', ', $params['order']);
      $order = 'ORDER BY '.$cols;
    }

    if(isset($params['limit'])) {
      $offset = $params['limit']['offset'];
      $len = $params['limit']['length'];

      $limit = "LIMIT $len OFFSET $offset";
    }

    // if(isset($params['conditions'])) {
    //
    // }

    $sql = "SELECT $select_columns FROM $table $order $limit;";
    return DB::getRows($sql, [], get_called_class());
  }

  public static function all() {
    $table_name = self::table();
    return DB::getRows("SELECT * FROM $table_name", [], get_called_class());
  }

  public static function get($id) {
    $table_name = self::table();
    return DB::getRow("SELECT * FROM $table_name WHERE id = :id", ['id' => $id], get_called_class());
  }

  public static function limit($limit, $offset = 0) {
    $table_name = self::table();
    $limit = (int) $limit;
    $offset = (int) $offset;
    return DB::getRows(
      "SELECT * from $table_name LIMIT :limit OFFSET :offset;",
      ['limit' => $limit, 'offset' => $offset],
      get_called_class());
  }

  public static function has($id) {
    $has = (bool) static::get($id);
    return $has;
  }

  public static function count() {
    $table_name = self::table();
    return DB::getValue("SELECT COUNT(*) FROM $table_name;");
  }

  public function jsonSerialize()
  {
    $hidden = static::$hidden;
    $visible = static::$props;
    return array_filter(get_object_vars($this), function ($key) use ($visible, $hidden) {
          return in_array($key, $visible) && !in_array($key, $hidden);
      },
      ARRAY_FILTER_USE_KEY
    );
  }

  public static function makeVisible($field) {
    if (($key = array_search($field, static::$hidden)) !== false) {
      unset(static::$hidden[$key]);
    }
  }

  public static function makeHidden($field) {
    if(!in_array($field, static::$hidden)) {
      array_push(static::$hidden, $field);
    }
  }

  public static function fields() {
    return static::$props;
  }

  public static function fillable_fields() {
    return static::$fillable;
  }

  public static function visible_fields() {
    $fields = static::$props;
    $hidden = static::$hidden;
    return array_diff($fields, $hidden);
  }

  public function save() {
    $table = self::table();

    $visible = static::$props;

    $values = array_filter(get_object_vars($this), function ($key) use ($visible) {
          return in_array($key, $visible);
      },
      ARRAY_FILTER_USE_KEY
    );

    $keys = array_keys($values);
    $fields = implode(', ', $keys);
    $binds = ':'.implode(', :', $keys);

    $update_bind = [];
    foreach ($keys as $key) {
      if($key != 'id') {
        array_push($update_bind, $key.'=:'.$key);
      }
    }
    $update_bind = implode(', ', $update_bind);

    $stmt = DB::prepare("INSERT INTO $table ($fields) VALUES($binds) ON DUPLICATE KEY UPDATE $update_bind");
    $stmt->execute($values);
    if(array_key_exists('id',$values) && $values['id'] == NULL) {
      $this->id = DB::lastInsertId();
    }
  }

  public function delete($with_relation = true) {
    if(!isset($this->id)) {
      return;
    }
    $table = self::table();
    if($with_relation) {
      $relations = static::$relations;
      foreach ($relations as $model => $meta) {
        $rel_table = self::table($meta['table']);
        $column = $meta['column'];
        DB::run("DELETE FROM $rel_table WHERE $column = :id;", ['id' => $this->{$column}]);
      }
    }
    DB::run("DELETE FROM $table where id = :id;", ['id' => $this->id]);
  }
}


 ?>
