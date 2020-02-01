<?php
ini_set('display_errors', '1');
ini_set('error_reporting', E_ALL);

require_once 'vendor/autoload.php';

use App\App;

if(count($argv) < 2) {
  die("Please specify the command\n");
}

$cmd = $argv[1];

$args = array_slice($argv, 1);

$cmd_executer = new App();

function app() {
  return $GLOBALS['app'];
}

try {
  $cmd_executer->run_command($cmd, $args);
} catch (\Exception $e) {
  die($e->getMessage()."\n");
}





?>
