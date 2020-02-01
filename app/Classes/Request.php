<?php
namespace App\Classes;

class Request {

  public function __construct() {
    $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
    $url_data = parse_url($actual_link);

    $url_data['method'] = $_SERVER['REQUEST_METHOD'];

    $uri = $_SERVER['REQUEST_URI'];

    // Strip query string (?foo=bar) and decode URI
    if (false !== $pos = strpos($uri, '?')) {
        $uri = substr($uri, 0, $pos);
    }
    $uri = rawurldecode($uri);

    $url_data['uri'] = $uri;

    foreach ($url_data as $key => $value) {
      $this->{$key} = $value;
    }

    $this->update();

    // $data = [];
    //
    // $input_data = file_get_contents('php://input');

    // var_dump($input_data);
    // $exploded = explode('&', $input_data);
    //
    // foreach($exploded as $pair) {
    //   $item = explode('=', $pair);
    //   if(count($item) == 2) {
    //     $data[urldecode($item[0])] = urldecode($item[1]);
    //   }
    // }

    // $this->data = json_decode(urldecode($input_data), true);

  }

  public function input($field) {

    if(isset($this->data[$field])) {
        return $this->data[$field];
    }
    return NULL;

    // if(isset($_GET[$field])) {
    //   return $_GET[$field];
    // }
    // elseif (isset($this->data[$field])) {
    //   return $this->data[$field];
    // }
    // else {
    //   return null;
    // }
  }

  public function update() {

    $input_data = file_get_contents('php://input');
    $data = json_decode(urldecode($input_data), true);
    $data = $data ? $data : [];
    $data = array_merge($_GET, $data);
    $this->data = $data;
  }

  public function only($allowedKeys) {

    return array_filter(
        $this->data,
        function ($key) use ($allowedKeys) {
            return in_array($key, $allowedKeys);
        },
        ARRAY_FILTER_USE_KEY
    );
  }

  public function exists($keys) {
    return !array_diff_key(array_flip($keys), $this->data);
  }


} ?>
