<?php
namespace Ububs\Core\Swoole\Server\Adapter;

use Ububs\Core\Http\Interaction\Request;
use Ububs\Core\Http\Interaction\Response;
use Ububs\Core\Http\Interaction\Route;
use Ububs\Core\Swoole\Server\ServerManager;

class Websocket extends ServerManager
{
    private static $server = null;

    public function init()
    {
        $configs = config('app.server');
        if (isset($configs['sock_type'][1])) {
            self::$server = new \swoole_websocket_server($configs['host'], $configs['port'], $configs['mode'], $configs['sock_type'][0] | $configs['sock_type'][1]);
        } elseif (isset($configs['sock_type'][0])) {
            self::$server = new \swoole_websocket_server($configs['host'], $configs['port'], $configs['mode'], $configs['sock_type'][0]);
        } else {
            self::$server = new \swoole_websocket_server($configs['host'], $configs['port'], $configs['mode']);
        }
        $sets = [
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
        ];
        if (!empty($configs['ssl_cert_file'])) {
            $sets['ssl_cert_file'] = $configs['ssl_cert_file'];
        }
        if (!empty($configs['ssl_key_file'])) {
            $sets['ssl_key_file'] = $configs['ssl_key_file'];
        }
        self::$server->set($sets);
    }

    public function getSwooleServer()
    {
        return self::$server;
    }

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
