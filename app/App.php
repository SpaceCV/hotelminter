<?php
namespace App;

use App\Modules\DB;
use App\Modules\TelegramApi;
use App\Modules\Minter_API;
use App\Modules\MinterExplorerAPI;
use App\Modules\Menu;
use App\Modules\Router;
use App\Modules\Auth;
use App\Classes\Request;
use App\Models\User;
use App\Classes\Keyboard;
use App\Classes\Message;

class App {

  private $commands = [
    // 'test' => \App\Commands\TestCommand::class,
    'start' => \App\Commands\StartCommand::class,
    'stop' => \App\Commands\StopCommand::class,
    'migrate' => \App\Commands\MigrateCommand::class,
    'queue' => \App\Commands\QueueCommand::class,
    'test' => \App\Commands\TestCommand::class,
    'build_menu' => \App\Commands\BuildMenuCommand::class
  ];

  private $bot_commands = [
    '/start' => 'CommandController@start',
    '/back' => 'CommandController@back'
  ];

  public function __construct() {
    define('APP_DIR', dirname(__FILE__));
    $dotenv = \Dotenv\Dotenv::create(__DIR__);
    $dotenv->load();
    // new DB(DatabaseConfig::$host, DatabaseConfig::$user, DatabaseConfig::$pass, DatabaseConfig::$db, DatabaseConfig::$prefix);
    // $this->minter_api = new MinterAPI(Config::$node_url);
    // $this->minter_explorer = new MinterExplorerAPI();

    $GLOBALS['app'] = $this;
    new DB(getenv('DATABASE_HOST'), getenv('DATABASE_USER'), getenv('DATABASE_PASSWORD'), getenv('DATABASE_NAME'), getenv('DATABASE_PREFIX'));
    new TelegramApi(getenv('BOT_TOKEN'));
    new MinterExplorerAPI();
    new Minter_API(getenv('NODE_URL'));
    new Menu();
    new Router();
  }

  public function run() {
    echo "OK";
    $request = new Request();

    // var_dump($request);
    file_put_contents(APP_DIR.'/log.txt', json_encode($request, JSON_PRETTY_PRINT), FILE_APPEND);

    if($request->exists(['message'])) {
      $response = $this->handle_message($request->input('message'));
    } elseif ($request->exists(['callback_query'])) {
      $response = $this->handle_callback_query($request->input('callback_query'));
    } else {
      die('OK');
    }

    $user = Auth::user();

    if(!$user) {
      return;
    }

    if(is_array($response)) {
      if(count($response) > 0) {

        foreach ($response as $mes) {
          TelegramApi::sendMessage($user->chat_id, $mes);
        }


      }
    } else {
      TelegramApi::sendMessage($user->chat_id, $response);
    }


    // TelegramApi::invoke('sendMessage', [
    //   'chat_id' => $user->chat_id,
    //   'text' => $text,
    //   'reply_markup' => [
    //       'keyboard' => $keyboard,
    //       'one_time_keyboard' => false,
    //       'resize_keyboard' => true
    //     ],
    //     'parse_mode' => 'HTML'
    // ]);


  }

  private function handle_message($message) {

    $chat = $message['chat'];

    if(strpos($chat['id'], "-") !== false) {
      return new Message('');
    }

    if(!isset($chat['username'])) {
      $chat['username'] = $chat['first_name'].' '.$chat['last_name'];
    }

    $user = User::get_by_chat_id($chat['id']);

    if(!$user) {
      $user = User::create([
        'chat_id' => $chat['id'],
        'username' => $chat['username'],
        'first_name' => $chat['first_name'],
        'last_name' => $chat['last_name'],
        'step' => 0,
        'current_page' => 0,
        'current_sub_page' => 0,
        'time',
        'ban' => 0,
        'root' => 0,
        'tmp' => '',
      ]);
      // $user->save();
    }
    new Auth($user);

    $message_text = '';
    $photos = NULL;

    if (isset($message['text'])) {
      $message_text = $message['text'];
    } elseif (isset($message['caption'])) {
      $message_text = $message['caption'];
    }

    if (isset($message['photo'])) {
      $photos = $message['photo'];
    }


    $text = '';
    $handler = false;
    $prev_page_id = -1;

    if($message_text[0] == '/') {
      if(isset($this->bot_commands[$message_text])) {
        $handler = $this->bot_commands[$message_text];
      } else {
        $text = 'Команда не найдена!';
      }
    } else {
      $result = Router::route($message['text'], $user->current_page);

      if($result === false) {
        $result = Router::route(NULL, $user->current_page);
      }

      if(isset($result['page_id'])) {

        $prev_page_id = $user->current_page;

        if($user->current_page != $result['page_id']) {
          $user->current_page = $result['page_id'];
          $user->save();
        }
      }
      if(isset($result['handler'])) {
        $handler = $result['handler'];
      }
      $text = '';

      if(isset($result['menu'])) {
        $text = $result['title'];
        $keyboard = Keyboard::fromTemplate($result['menu']);
      }


      if(isset($result['message'])) {
        $text = $result['message'];
      }
    }

    if($handler) {
      if(is_string($handler)) {
        $s = explode('@', $handler);

        $controller = '\App\\Controllers\\'.$s[0];
        $controller = new $controller();

        if(!isset($keyboard)) {
          $keyboard = new Keyboard();
        }

        $response = $controller->{$s[1]}($message_text, $photos, $text, $keyboard);
      }
    }

    if(!isset($response)) {

      if($user->current_page != $prev_page_id) {
        $response = new Message($text, $keyboard);
      } else {
        $response = new Message('');
      }
    }

    return $response;

  }

  private function handle_callback_query($callback_query) {

    $message = $callback_query['message'];
    $chat = $message['chat'];

    if(strpos($chat['id'], "-") === false) {
      $user = User::get_by_chat_id($chat['id']);
      new Auth($user);
    }

    $data = json_decode($callback_query['data']);

    $controller = array_shift($data);
    $controller = '\App\\Controllers\\'.$controller.'Controller';
    $controller = new $controller();


    $response = $controller->callback_query($data, $message);


    return $response;
  }

  public function run_command($command, $args) {
    if(!isset($this->commands[$command])) {
      throw new \Exception('Command not found');
    }
    $executer = new $this->commands[$command]();
    $executer->execute($args);
  }

  public function notify($message) {
    $chat_id = getenv('ADMIN_CHAT_ID');

    if($message->hasPhoto()) {
      return TelegramApi::sendPhoto($chat_id, $message);
    } else {
      return TelegramApi::sendMessage($chat_id, $message);
    }
  }

}
