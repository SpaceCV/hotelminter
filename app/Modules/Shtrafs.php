<?php
namespace App\Modules;

use GuzzleHttp\Client;

class Shtrafs
{
    private static $check_url = 'http://avto.shtraf.biz/checkpay.php';

    public static function get($uin)
    {
        $client = new Client();
        // Для вывода
        $pout = array();

        $httpoptp = array(
          "Cache-Control: no-cache",
          "X-Requested-With: XMLHttpRequest"
        );

        $post ='fld%5Bps%5D='.$number.'&fld%5Bflmon%5D=0&fld%5Btype%5D=10';

        $params = [
          'form_params' => [
            'fld' => [
              'ps' => $uin,
              'flmon' => 0,
              'type' => 10
            ]
          ],
          'headers' => [
            'Cache-Control' => 'no-cache',
            'X-Requested-With:' => 'XMLHttpRequest'
          ]
        ];

        $response = $client->requests('POST', self::$check_url, $params);
        $html = $response->getBody()->getContents();

        $data = [];

        // $html = gotourl('http://avto.shtraf.biz/checkpay.php', '', '', '', $post, '', '', $httpoptp);
        // file_put_contents('bb.txt', $html);
        if (strpos($html, '<span class="err">Начисления не найдены.') !== false) {
            return false;
        } elseif (strpos($html, '<span class="err">Некорректный формат номера Постановления/УИН') !== false) {
            return false;
        } else {
            if (preg_match('|Дата Постановления <b>(.+)</b>;|isU', $html, $ppnt)) {
                //$out .= 'Дата Постановления: '.$ppnt[1]."\n";
                $data['date'] = trim($ppnt[1]);
            }

            if (preg_match('|Место совершения административного правонарушения: (.+);|isU', $html, $ppnt)) {
                //$out .= 'Место совершения административного правонарушения: '.$ppnt[1] ."\n";
                $data['place'] = trim($ppnt[1]);
            }

            if (preg_match('|Статья КоАП:(.+);|isU', $html, $ppnt)) {
                //$out .= 'Какое нарушение совершил: '.$ppnt[1]."\n";
                $data['article'] = trim($ppnt[1]);
            }

            if (preg_match('|<b>Срок оплаты штрафа до (.+)</b>|isU', $html, $ppnt)) {
                //$out .= 'Срок оплаты штрафа: '.$ppnt[1]."\n";
                $data['due_date'] = trim($ppnt[1]);
            }

            if (preg_match('|<span id="uinstr" class="ok">Штрафов к оплате: <b>\d*</b>, на сумму: <b>(.+)</b> руб.|isU', $html, $ppnt)) {
                //$out .= 'На сумму: '.$ppnt[1]."\n";
                $data['total_sum'] = trim($ppnt[1]);
            }

            return $data;
        }

        return false;
    }
}
