<?php

namespace Ububs\Core\Swoole\Server\Adapter;

use Ububs\Core\Component\Db\Db;
use Ububs\Core\Http\Interaction\Request;
use Ububs\Core\Http\Interaction\Response;
use Ububs\Core\Http\Interaction\Route;
use Ububs\Core\Swoole\Factory;

class Http extends Factory
{
    private static $server;
    private static $client = null;

    private static $taskType = ['DB'];

    /**
     * swoole_http_server服务初始化
     * @return void
     */
    public function init(): void
    {
        if (!\extension_loaded('swoole')) {
            throw new \Exception("no swoole extension. get: https://github.com/swoole/swoole-src");
        }
        $config       = config('app.swoole_http_server');
        self::$server = new \Swoole_http_server($config['host'], $config['port']);
        self::$server->set(
            array(
                'worker_num'               => $config['worker_num'],
                'daemonize'                => $config['daemonize'],
                'max_request'              => $config['max_request'],
                'dispatch_mode'            => $config['dispatch_mode'],
                'debug_mode'               => $config['debug_mode'],
                'task_worker_num'          => $config['task_worker_num'],
                'heartbeat_check_interval' => $config['heartbeat_check_interval'],
                'heartbeat_idle_time'      => $config['heartbeat_idle_time'],
                'log_file'                 => $config['log_file'],
                'daemonize'                => $config['daemonize'],
            )
        );
        $this->setClient(config('app.swoole_callback_client'));
    }

    public function getServer()
    {
        return self::$server;
    }

    /**
     * 设置客户端回调对象
     * @param object $client 客户端类
     */
    public function setClient($client)
    {
        if (!class_exists($client)) {
            return null;
        }
        self::$client = new $client;
    }

    /**
     * 服务端开启动作回调
     * @param  object $serv server对象
     * @return void
     */
    public function onStart($serv)
    {
        if (self::$client !== null && method_exists(self::$client, 'onStart')) {
            self::$client->onStart($serv);
        }
    }

    /**
     * 服务端进程开启动作回调
     * @param  object $serv      server对象
     * @param  int    $worker_id 进程id
     * @return void
     */
    public function onWorkerStart($serv, $worker_id)
    {
        // 判定是否为Task Worker进程
        if ($worker_id >= self::$server->setting['worker_num']) { }
        // if ($worker_id == 0) {
        //     cli_set_process_title('php manager work');
        // }
        // 连接数据库

        if (self::$client !== null && method_exists(self::$client, 'onWorkerStart')) {
            self::$client->onWorkerStart($serv, $worker_id);
        }
    }

    public function onWorkerError(swoole_server $serv, int $worker_id, int $worker_pid, int $exit_code, int $signal)
    { }

    /**
     * request动作回调
     * @param  \swoole_http_request  $request  request对象
     * @param  \swoole_http_response $response response对象
     * @return void
     */
    public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {
        if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
            return $response->end();
        }
        $httpMethod = $request->server['request_method'];
        $pathInfo   = rawurldecode($request->server['path_info']);
        // 匹配路由
        $routeInfo = Route::getInstance()->getDispatcher()->dispatch($httpMethod, $pathInfo);
        $result    = '';
        // 解析路由
        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:

                $result = json_encode([
                    'status'  => 0,
                    'message' => '404 NOT FOUND!',
                ]);
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                $result         = json_encode([
                    'status'  => 0,
                    'message' => "405 Method Not Allowed!",
                ]);
                break;
            case \FastRoute\Dispatcher::FOUND:
                Request::init($request);
                Response::init($response);
                \ob_start();
                $result = Route::getInstance()->run($routeInfo);
                if (is_array($result)) {
                    $result = json_encode($result);
                }
                $result = \ob_get_contents() . $result;
                \ob_end_clean();
                break;
        }
        if (!Response::isEnd()) {
            $response->end($result);
        }
    }

    /**
     * task 动作回调
     * @return void
     */
    public function onTask($serv, $task_id, $from_id, $data)
    {
        $type = is_array($data) && isset($data['__TASK_TYPE__']) && in_array($data['__TASK_TYPE__'], self::$taskType) ? $data['__TASK_TYPE__'] : '';
        if ($type !== '') {
            $result = '';
            $data   = json_decode($data['data'], true);
            switch (strtoupper($type)) {
                case 'DB':
                    $result = Db::query(...$data);
                    break;

                default:
                    # code...
                    break;
            }
            $serv->finish($result);
        }
        return $data;
    }

    /**
     * finish 动作回调
     * @return void
     */
    public function onFinish($serv, $task_id, $data)
    {
        self::$client->onTaskFinish($serv, $task_id, $data);
    }

    private function registerExceptionHandler(): void
    {
        \set_exception_handler(__CLASS__ . '::exceptionHandler');
        \register_shutdown_function(__CLASS__ . '::shutdownHandler');
        \set_error_handler(__CLASS__ . '::errorHandler');
    }

    /**
     * @param $exception
     * @return mixed
     * @desc 默认的异常处理
     */
    final public static function exceptionHandler($exception)
    {
        $d = \json_encode([
            'status'  => ERROR_STATUS,
            'message' => $exception,
        ]);
        Log::info($d);
        return Response::end($d);
    }

    /**
     * @desc 默认的fatal处理
     */
    final public static function shutdownHandler()
    {
        $error     = \error_get_last();
        $d = \json_encode([
            'status'  => ERROR_STATUS,
            'message' => $error['message'],
            'file'    => $error['file'],
            'line'    => $error['line'],
        ]);
        Log::info($d);
        return Response::end($d);
    }

    /**
     * @desc 默认的fatal处理
     */
    final public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $d = \json_encode([
            'status'  => ERROR_STATUS,
            'message' => $errstr,
            'file'    => $errfile,
            'line'    => $errline,
        ]);
        Log::info($d);
        return Response::end($d);
    }
}
