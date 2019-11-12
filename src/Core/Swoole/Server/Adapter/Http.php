<?php

namespace Ububs\Core\Swoole\Server\Adapter;

use Ububs\Core\Http\Interaction\Request;
use Ububs\Core\Http\Interaction\Response;
use Ububs\Core\Http\Interaction\Route;
use Ububs\Core\Swoole\Server\ServerManager;

class Http extends ServerManager
{
    private static $server   = null;
    private static $taskType = ['DB'];

    /**
     * swoole_http_server服务初始化
     * @return void
     */
    public function init(): void
    {
        $configs      = config('app.server');
        self::$server = new \Swoole_http_server($configs['host'], $configs['port']);
        self::$server->set(
            array(
                'worker_num'               => $configs['worker_num'],
                'daemonize'                => $configs['daemonize'],
                'max_request'              => $configs['max_request'],
                'dispatch_mode'            => $configs['dispatch_mode'],
                'debug_mode'               => $configs['debug_mode'],
                'task_worker_num'          => $configs['task_worker_num'],
                'heartbeat_check_interval' => $configs['heartbeat_check_interval'],
                'heartbeat_idle_time'      => $configs['heartbeat_idle_time'],
                'log_file'                 => $configs['log_file'],
                'daemonize'                => $configs['daemonize'],
            )
        );
    }

    public function getSwooleServer()
    {
        return self::$server;
    }

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
        // 注册异常抓取
        $this->registerExceptionHandler();
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
}
