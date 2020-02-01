<?php
namespace App\Modules;

use App\Classes\PrefixPDO;
use \PDO;

class DB {

  private static $data_types = [
    'integer' => PDO::PARAM_INT,
    'string' => PDO::PARAM_STR,
    'boolean' => PDO::PARAM_BOOL,
    'double' => PDO::PARAM_STR,
    'NULL' => PDO::PARAM_NULL,
  ];

  private static $conn = NULL;

  public function __construct($host, $user, $pass, $db, $prefix) {
    self::$conn = new PrefixPDO("mysql:host=$host;dbname=$db;charset=utf8",$user,$pass, [
      PDO::ATTR_CASE => PDO::CASE_LOWER,
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ], $prefix);
  }

  public static function pdo_data_type($var) {
    return self::$data_types[gettype($var)];
  }

  /**
   * @param $stmt
   * @return PDOStatement
   */
  public static function query($stmt)  {
    return self::$conn->query($stmt);
  }

  /**
   * @param $stmt
   * @return PDOStatement
   */
  public static function prepare($stmt)  {
    return self::$conn->prepare($stmt);
  }

  /**
   * @param $query
   * @return int
   */
   public static function exec($query) {
    return self::$conn->exec($query);
  }

  /**
   * @return string
   */
   public static function lastInsertId() {
    return self::$conn->lastInsertId();
  }

  /**
   * @param $query
   * @param array $args
   * @return PDOStatement
   * @throws Exception
   */
  public static function run($query, $args = [])  {
    try{
      if (!$args) {
        return self::query($query);
      }
      $stmt = self::prepare($query);

      foreach ($args as $key => $value) {
        $stmt->bindValue($key, $value, self::pdo_data_type($value));
      }
      $stmt->execute();
      return $stmt;
    } catch (PDOException $e) {
        throw new Exception($e->getMessage());
    }
  }

  /**
   * @param $query
   * @param array $args
   * @return mixed
   */
  public static function getRow($query, $args = [], $model = null)  {
    $result = self::run($query, $args);

    if($model) {
      $result->setFetchMode( PDO::FETCH_CLASS, $model);
    }

    return $result->fetch();
  }

  /**
   * @param $query
   * @param array $args
   * @return array
   */
  public static function getRows($query, $args = [], $model = null)  {
    $result = self::run($query, $args);

    if($model) {
      $result->setFetchMode( PDO::FETCH_CLASS, $model);
    }

    return $result->fetchAll();
  }

  /**
   * @param $query
   * @param array $args
   * @return mixed
   */
  public static function getValue($query, $args = [])  {
    $result = self::getRow($query, $args);
    if (!empty($result)) {
      $result = array_shift($result);
    }
    return $result;
  }

  /**
   * @param $query
   * @param array $args
   * @return array
   */
  public static function getColumn($query, $args = [])  {
    return self::run($query, $args)->fetchAll(PDO::FETCH_COLUMN);
  }
} ?>
