<?php

namespace Ububs\Core\Swoole\Server;

use Ububs\Core\Swoole\Factory;

class ServerManager extends Factory
{

    private static $serverInstance;
    private static $serverType;
    protected static $client = null;

    const SWOOLE_SERVER    = 'SWOOLE_SERVER';
    const HTTP_SERVER      = 'HTTP_SERVER';
    const WEBSOCKET_SERVER = 'WEBSOCKET_SERVER';

    public static function initServer()
    {
        if (!\extension_loaded('swoole')) {
            throw new \Exception("no swoole extension. get: https://github.com/swoole/swoole-src");
        }
        if (!empty(self::$serverInstance)) {
            return false;
        }
        self::$serverType = strtoupper(config('app.server_type'));
        $callback         = config('app.callback_client', '');
        switch (self::$serverType) {
            case self::HTTP_SERVER:
                self::$serverInstance = \Ububs\Core\Swoole\Server\Adapter\Http::getInstance();
                break;

            case self::WEBSOCKET_SERVER:
                self::$serverInstance = \Ububs\Core\Swoole\Server\Adapter\Websocket::getInstance();
                break;

            default:
                throw new Exception("Error init server");
        }
        self::setCallbackClient($callback);
        self::$serverInstance->init();
    }

    /**
     * 设置客户端回调对象
     * @param object $client 客户端类
     */
    public static function setCallbackClient(string $client)
    {
        if (!$client || !class_exists($client)) {
            throw new \Exception("Error init server, callback client must be set", 1);
        }
        self::$client = new $client;
    }

    public static function getServerInstance()
    {
        return self::$serverInstance;
    }

    public static function getCallbackClient()
    {
        return self::$client;
    }

    public static function getServer()
    {
        return self::getServerInstance()->getSwooleServer();
    }

    public static function getServerType()
    {
        return self::$serverType;
    }

    public static function start()
    {
        return self::getServer()->start();
    }

    public static function reload()
    {}

    public function registerExceptionHandler(): void
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
        $error = \error_get_last();
        $d     = \json_encode([
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
