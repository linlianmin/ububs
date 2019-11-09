<?php

namespace Ububs\Core\Swoole\Server;

class ServerManager
{

    private static $instance;
    private static $serverInstance;
    private static $serverType;

    const SWOOLE_SERVER           = 'SWOOLE_SERVER';
    const SWOOLE_HTTP_SERVER      = 'SWOOLE_HTTP_SERVER';
    const SWOOLE_WEBSOCKET_SERVER = 'SWOOLE_WEBSOCKET_SERVER';

    public static function getInstance(): ServerManager
    {
        if (!isset(self::$instance)) {
            self::$instance = new ServerManager();
        }
        return self::$instance;
    }

    public function initServer()
    {
        if (isset(self::$serverInstance)) {
            return self::$serverInstance;
        }
        self::$serverType = strtoupper(config('app.server_type'));
        switch (self::$serverType) {
            case self::SWOOLE_HTTP_SERVER:
                self::$serverInstance = \Ububs\Core\Swoole\Server\Adapter\Http::getInstance();
                break;

            case self::SWOOLE_WEBSOCKET_SERVER:
                self::$serverInstance = \Ububs\Core\Swoole\Server\Adapter\Websocket::getInstance();
                break;
        }
        self::$serverInstance->init();
    }

    public function getServerInstance()
    {
        return self::$serverInstance;
    }

    public function getServer()
    {
        return $this->getServerInstance()->getServer();
    }

    public function getServerType()
    {
        return self::$serverType;
    }

    public function reload()
    { }
}
