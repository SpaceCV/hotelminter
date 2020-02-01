<!DOCTYPE html>
<html lang="ru" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title></title>
  </head>
  <body>
    <?php
      error_reporting(E_ALL);
      ini_set('display_errors', 1);
      require_once '../vendor/autoload.php';

      use App\App;

      function app() {
        return $GLOBALS['app'];
      }

      $app = new App();
      $GLOBALS['app'] = $app;

      $app->run();
    ?>
  </body>
</html>
