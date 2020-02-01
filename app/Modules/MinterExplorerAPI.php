<?php
namespace App\Modules;

use GuzzleHttp\Client;

class MinterExplorerAPI
{
  private static $base_url = 'https://explorer-api.apps.minter.network/api/v1/';
  private static $client = NULL;

  public function __construct() {
    self::$client = new Client(['base_uri' => self::$base_url]);
  }

  public static function coins($symbol = '') {

    $params = [];

    if($symbol) {
      $params['query'] = ['symbol' => $symbol];
    }

    $response = self::$client->request('GET', 'coins', $params);
    $data = json_decode($response->getBody()->getContents());
    return $data->data;
  }
} ?>
